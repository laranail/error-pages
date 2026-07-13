<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\ErrorPages\ErrorPages;

it('renders a branded HTML page for a web 404 (Path 1)', function (): void {
    $response = $this->get('/definitely-missing');

    $response->assertStatus(404);
    expect($response->getContent())
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->toContain('ep-theme-default');
});

it('renders branded RFC 7807 JSON for an API 404 (Path 2)', function (): void {
    $response = $this->getJson('/definitely-missing');

    $response->assertStatus(404)->assertJson(['status' => 404, 'type' => 'about:blank']);
    expect($response->headers->get('content-type'))->toContain('application/problem+json');
});

it('shows a developer 4xx abort message but never leaks a 5xx message', function (): void {
    Route::get('/forbidden', fn () => abort(403, 'No entry here'));
    Route::get('/boom', fn () => throw new RuntimeException('SECRET internal detail'));

    expect($this->get('/forbidden')->getContent())->toContain('No entry here');

    $boom = $this->get('/boom');
    $boom->assertStatus(500);
    expect($boom->getContent())
        ->toContain('>500<')
        ->not->toContain('SECRET internal detail');
});

it('propagates Retry-After and no-store on a transient API error', function (): void {
    Route::get('/down', fn () => abort(503));

    $response = $this->getJson('/down');

    $response->assertStatus(503);
    expect($response->headers->get('Retry-After'))->not->toBeNull()
        ->and($response->headers->get('Cache-Control'))->toContain('no-store')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex');
});

it('passes validation through untouched (no branded JSON)', function (): void {
    Route::post('/validate', fn (Request $request) => $request->validate(['name' => 'required']));

    $response = $this->postJson('/validate', []);

    $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    expect($response->headers->get('content-type'))->not->toContain('problem+json');
});

it('does not intercept non-configured status codes (e.g. 402 API)', function (): void {
    Route::get('/pay', fn () => abort(402));

    // 402 is not in codes.intercept → passes through to Laravel's default JSON.
    $response = $this->getJson('/pay');

    $response->assertStatus(402);
    expect($response->headers->get('content-type'))->not->toContain('problem+json');
});

it('honours a consumer skipWhen veto', function (): void {
    app(ErrorPages::class)
        ->skipWhen(fn ($e, $request): bool => $request?->is('webhooks/*') === true);

    Route::get('webhooks/x', fn () => abort(404));

    $response = $this->getJson('/webhooks/x');

    $response->assertStatus(404);
    expect($response->headers->get('content-type'))->not->toContain('problem+json');
});

it('renders a page by code and by generic key (preview / design QA)', function (): void {
    $facade = Simtabi\Laranail\ErrorPages\Facades\ErrorPages::class;

    expect($facade::htmlForCode(503))->toContain('>503<')->toContain('Be right back')
        ->and($facade::htmlForKey('4xx'))->toContain('>4xx<');
});
