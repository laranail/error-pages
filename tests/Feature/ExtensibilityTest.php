<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;

it('lets a consumer fully reshape rendering: custom driver + context + skipWhen', function (): void {
    app(ErrorPages::class)
        ->extend('audit', fn ($app): StackRenderer => new class implements StackRenderer
        {
            public function render(Throwable $e, Request $request, int $status): Response
            {
                return new Response('AUDIT ' . $status, $status, ['X-Audit' => '1']);
            }
        })
        ->context(fn (Request $request): ?string => $request->is('audit/*') ? 'audit' : null)
        ->skipWhen(fn ($e, $request): bool => $request?->is('audit/skip') === true);

    Route::get('audit/x', fn () => abort(404));
    Route::get('audit/skip', fn () => abort(404));

    // The custom driver takes over its own context.
    $handled = $this->get('/audit/x');
    $handled->assertStatus(404)->assertHeader('X-Audit', '1');
    expect($handled->getContent())->toContain('AUDIT 404');

    // skipWhen vetoes → the custom driver never runs (falls through to Laravel/Path 1).
    expect($this->get('/audit/skip')->getContent())->not->toContain('AUDIT');
});

it('enriches every page through a consumer pipe stage', function (): void {
    app(ErrorPages::class)->pipe(fn ($page) => $page->withRequestId('PIPED-REF'));

    $response = $this->getJson('/piped-missing');

    expect($response->json('request_id'))->toBe('PIPED-REF');
});
