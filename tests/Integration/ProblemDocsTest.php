<?php

declare(strict_types=1);

it('serves a public problem-type doc page (200, noindex)', function (): void {
    $response = $this->get('/errors/problems/404');

    $response->assertStatus(200)->assertHeader('X-Robots-Tag', 'noindex');
    expect($response->getContent())
        ->toContain('class="ep-status"')
        ->toContain('>404<');
});

it('serves a generic problem-type page for a class key', function (): void {
    expect($this->get('/errors/problems/5xx')->getContent())->toContain('>5xx<');
});

it('points the JSON type at the served problem-docs page', function (): void {
    $response = $this->getJson('/docs-linked-missing');

    expect($response->json('type'))->toBe('https://api.example/errors/problems/404');
});
