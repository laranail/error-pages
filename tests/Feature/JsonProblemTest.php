<?php

declare(strict_types=1);

it('adds the RFC 7807 instance (request URI) to the JSON payload', function (): void {
    $response = $this->getJson('/problem-here');

    $response->assertStatus(404);
    expect($response->json('instance'))->toContain('/problem-here');
});

it('emits a per-status problem type when a base URI is configured', function (): void {
    config()->set('error-pages.problem_type_base', 'https://errors.example.com/');

    $response = $this->getJson('/problem-typed');

    $response->assertStatus(404)->assertJson(['type' => 'https://errors.example.com/404']);
});

it('reflects a client-supplied request id in the payload', function (): void {
    $response = $this->getJson('/rid-reflected', ['X-Request-Id' => 'abc-123']);

    expect($response->json('request_id'))->toBe('abc-123');
});

it('generates a request id when the client sends none', function (): void {
    $response = $this->getJson('/rid-generated');

    expect($response->json('request_id'))->toBeString()->not->toBeEmpty();
});

it('omits the request id when generation is disabled and no header is present', function (): void {
    config()->set('error-pages.request_id.generate', false);

    $response = $this->getJson('/rid-none');

    expect($response->json('request_id'))->toBeNull();
});

it('reads the request id from a configured custom header', function (): void {
    config()->set('error-pages.request_id.header', 'X-Correlation-ID');

    $response = $this->getJson('/rid-custom', ['X-Correlation-ID' => 'corr-9']);

    expect($response->json('request_id'))->toBe('corr-9');
});
