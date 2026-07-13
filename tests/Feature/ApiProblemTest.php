<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('points the JSON type at a configured problem_type_base', function (): void {
    config()->set('error-pages.problem_type_base', 'https://errors.example.com/');

    expect($this->getJson('/pt-missing')->json('type'))->toBe('https://errors.example.com/404');
});

it('renders RFC 9457 problem+json with a field-level errors[] when problem.validation is on', function (): void {
    config()->set('error-pages.problem.validation', true);
    Route::post('/register', fn (Request $r) => $r->validate(['email' => 'required', 'age' => 'integer']));

    $response = $this->postJson('/register', ['age' => 'abc']);

    $response->assertStatus(422);
    expect($response->headers->get('content-type'))->toContain('application/problem+json')
        ->and($response->json('title'))->toBe('Validation failed')
        ->and($response->json('status'))->toBe(422)
        ->and($response->json('errors'))->toBeArray();

    $fields = array_column($response->json('errors'), 'field');
    expect($fields)->toContain('email')->toContain('age')
        ->and($response->json('errors.0'))->toHaveKeys(['pointer', 'field', 'detail']);
});

it('passes validation through to Laravel by default (no problem.validation)', function (): void {
    Route::post('/reg-default', fn (Request $r) => $r->validate(['email' => 'required']));

    $response = $this->postJson('/reg-default', []);

    $response->assertStatus(422)->assertJsonStructure(['message', 'errors']);
    expect($response->headers->get('content-type'))->not->toContain('problem+json');
});

it('content-negotiates an api/* path to the HTML page for a browser', function (): void {
    config()->set('error-pages.content_negotiation', true);

    $response = $this->get('/api/widgets/999', ['Accept' => 'text/html,application/xhtml+xml']);

    $response->assertStatus(404);
    expect($response->getContent())->toContain('class="ep-status"'); // branded page, not JSON
});

it('still returns problem+json for an explicit JSON client on an api path', function (): void {
    config()->set('error-pages.content_negotiation', true);

    $response = $this->getJson('/api/widgets/998');

    $response->assertStatus(404);
    expect($response->headers->get('content-type'))->toContain('application/problem+json');
});
