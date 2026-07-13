<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\ErrorPages;

it('threads a CSP nonce into the inline style and enhancement script', function (): void {
    app(ErrorPages::class)->nonce('r4nd0mN0nce');

    $html = $this->get('/nonce-missing')->getContent();

    expect($html)
        ->toContain('<style nonce="r4nd0mN0nce">')
        ->toContain('nonce="r4nd0mN0nce" defer'); // enhancement <script>
});

it('accepts a per-request nonce resolver closure', function (): void {
    app(ErrorPages::class)->nonce(fn (): string => 'closure-nonce');

    expect($this->get('/nonce-closure-missing')->getContent())
        ->toContain('<style nonce="closure-nonce">');
});

it('emits no nonce attribute when none is configured', function (): void {
    expect($this->get('/no-nonce-missing')->getContent())
        ->toContain('<style>')
        ->not->toContain('nonce=');
});

it('nonces the SPA payload script', function (): void {
    config()->set('error-pages.stack', 'vue');
    app(ErrorPages::class)->nonce('spa-nonce');

    expect($this->get('/nonce-spa-missing')->getContent())
        ->toContain('id="error-page-data" type="application/json" nonce="spa-nonce"');
});
