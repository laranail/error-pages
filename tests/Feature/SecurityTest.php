<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('never leaks a framework-rewritten 4xx message (ModelNotFound) to the user', function (): void {
    // Laravel rewrites ModelNotFoundException into a NotFoundHttpException whose
    // message names the model + ids and sets it as `previous`.
    Route::get('/bound', function (): never {
        throw new NotFoundHttpException(
            'No query results for model [App\\Models\\User] 999999',
            new ModelNotFoundException,
        );
    });

    $response = $this->get('/bound');

    $response->assertStatus(404);
    expect($response->getContent())
        ->not->toContain('App\\Models\\User')
        ->not->toContain('999999')
        ->toContain('could not be found'); // generic copy instead
});

it('leaks nothing in the API/JSON payload for a framework-rewritten 4xx', function (): void {
    Route::get('/bound-api', function (): never {
        throw new NotFoundHttpException(
            'No query results for model [App\\Models\\Order] 42',
            new ModelNotFoundException,
        );
    });

    $response = $this->getJson('/bound-api');

    $response->assertStatus(404);
    expect($response->json('detail'))->not->toContain('App\\Models\\Order');
});

it('builds the asset URL from app.url, never the request Host header', function (): void {
    config()->set('app.url', 'https://real.example');

    $response = $this->get('/spoof-missing', ['Host' => 'attacker.example']);

    expect($response->getContent())
        ->toContain('https://real.example/_error-pages/assets/error-pages.js')
        ->not->toContain('attacker.example');
});

it('sanitises and clamps an attacker-controlled request id', function (): void {
    $response = $this->getJson('/rid-evil', ['X-Request-Id' => '<script>alert(1)</script>' . str_repeat('A', 500)]);

    $id = $response->json('request_id');
    expect($id)->toBeString()
        ->and($id)->not->toContain('<')
        ->and($id)->not->toContain('>')
        ->and(strlen((string) $id))->toBeLessThanOrEqual(128);
});

it('sets X-Content-Type-Options nosniff on error responses', function (): void {
    $response = $this->getJson('/nosniff-missing');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('neutralises a dangerous brand URL scheme', function (): void {
    config()->set('error-pages.home_url', 'javascript:alert(document.cookie)');

    $response = $this->get('/js-scheme-missing');

    expect($response->getContent())
        ->not->toContain('javascript:')
        ->toContain('href="/"');
});
