<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;

it('renders the SPA shell with embedded payload for a web SPA stack', function (): void {
    config()->set('error-pages.stack', 'vue');

    $response = $this->get('/spa-missing');

    $response->assertStatus(404);
    expect($response->getContent())
        ->toContain('class="ep-status"')
        ->toContain('id="error-page-data"')
        ->toContain('"status":404');
});

it('renders an Inertia response for an Inertia request', function (): void {
    $response = $this->get('/inertia-missing', ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);

    $response->assertStatus(404)->assertHeader('X-Inertia', 'true');
    expect($response->json('component'))->toBe('ErrorPage')
        ->and($response->json('props.status'))->toBe(404);
});

it('dispatches to a consumer-registered stack via the DSL', function (): void {
    app(ErrorPages::class)
        ->extend('smoke', fn ($app): StackRenderer => new class implements StackRenderer
        {
            public function render(Throwable $e, Request $request, int $status): Response
            {
                return new Response('SMOKE ' . $status, $status);
            }
        })
        ->context(fn ($request): string => 'smoke');

    $response = $this->get('/smoke-missing');

    $response->assertStatus(404);
    expect($response->getContent())->toContain('SMOKE 404');
});

it('still brands API JSON in dev — does not defer to Ignition (which is HTML-only)', function (): void {
    config()->set('app.debug', true);

    $response = $this->getJson('/dev-api-missing');

    $response->assertStatus(404)->assertJson(['status' => 404]);
    expect($response->headers->get('content-type'))->toContain('problem+json');
});

it('renders a panel-tagged page for the filament/nova context', function (): void {
    app(ErrorPages::class)->context(fn ($request): string => 'filament');

    $response = $this->get('/panel-missing');

    $response->assertStatus(404);
    expect($response->getContent())->toContain('data-panel="filament"')->toContain('class="ep-status"');
});
