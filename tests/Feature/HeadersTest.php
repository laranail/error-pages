<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('merges the exception own headers onto the branded response', function (): void {
    Route::get('/needs-auth', fn () => abort(401, 'Sign in', ['WWW-Authenticate' => 'Bearer realm="api"']));

    $response = $this->getJson('/needs-auth');

    $response->assertStatus(401);
    expect($response->headers->get('WWW-Authenticate'))->toBe('Bearer realm="api"')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('honours an exception-provided Retry-After over the default', function (): void {
    Route::get('/slow', fn () => abort(503, '', ['Retry-After' => '120']));

    $response = $this->getJson('/slow');

    $response->assertStatus(503);
    expect($response->headers->get('Retry-After'))->toBe('120');
});
