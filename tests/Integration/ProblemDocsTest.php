<?php

declare(strict_types=1);

it('serves a rich problem-type doc page (200, noindex) with meaning/causes/fixes', function (): void {
    $response = $this->get('/errors/problems/404');

    $response->assertStatus(200)->assertHeader('X-Robots-Tag', 'noindex');
    expect($response->getContent())
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->toContain('What this means')
        ->toContain('does not exist at this address') // the 404-specific meaning
        ->toContain('Common causes')
        ->toContain('How to fix');
});

it('falls back to the class (4xx/5xx) doc content for a code without its own entry', function (): void {
    // 451 has no per-code entry → uses the 4xx class content.
    $response = $this->get('/errors/problems/451');

    $response->assertStatus(200);
    expect($response->getContent())
        ->toContain('>451<')
        ->toContain('not a fault on the server'); // the 4xx-class meaning
});

it('serves validation-specific (not 4xx-generic) doc content for 422', function (): void {
    $response = $this->get('/errors/problems/422');

    $response->assertStatus(200);
    expect($response->getContent())
        ->toContain('>422<')
        ->toContain('Validation failed') // the 422 title, not the 4xx-generic one
        ->toContain('failed validation') // the 422-specific meaning
        ->not->toContain('mistyped'); // must NOT fall back to the 4xx-class copy
});

it('serves a generic problem-type page for a class key', function (): void {
    expect($this->get('/errors/problems/5xx')->getContent())
        ->toContain('>5xx<')
        ->toContain('went wrong on our side'); // the 5xx-class meaning
});

it('points the JSON type at the served problem-docs page', function (): void {
    $response = $this->getJson('/docs-linked-missing');

    expect($response->json('type'))->toBe('https://api.example/errors/problems/404');
});
