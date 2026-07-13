<?php

declare(strict_types=1);

it('serves the enhancement JS immutably from the asset route', function (): void {
    $response = $this->get('/_error-pages/assets/error-pages.js');

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('javascript')
        ->and($response->headers->get('cache-control'))->toContain('immutable')
        ->and($response->headers->get('etag'))->not->toBeNull()
        ->and((int) $response->headers->get('content-length'))->toBeGreaterThan(0);
});

it('serves the shared stylesheet from the asset route', function (): void {
    $response = $this->get('/_error-pages/assets/error-pages.css');

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('text/css');
});

it('404s an unknown asset file', function (): void {
    $this->get('/_error-pages/assets/secret.js')->assertStatus(404);
});

it('injects the enhancement script into a branded page in route mode', function (): void {
    $response = $this->get('/asset-missing');

    $response->assertStatus(404);
    expect($response->getContent())->toContain('/_error-pages/assets/error-pages.js');
});

it('inlines the enhancement script in inline mode', function (): void {
    config()->set('error-pages.assets.mode', 'inline');

    $response = $this->get('/asset-inline-missing');

    expect($response->getContent())->toContain('error-pages progressive enhancement')
        ->not->toContain('/_error-pages/assets/error-pages.js');
});

it('ships no enhancement script when assets are off', function (): void {
    config()->set('error-pages.assets.mode', 'off');

    $response = $this->get('/asset-off-missing');

    expect($response->getContent())->not->toContain('error-pages.js');
});
