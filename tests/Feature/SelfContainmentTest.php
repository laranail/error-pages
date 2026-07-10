<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Exceptions\NotSelfContainedException;
use Simtabi\Laranail\ServerErrorPages\Services\HtmlInliner;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/sep-std-' . bin2hex(random_bytes(4));
    config()->set('laranail.server-error-pages.output.disk');
    config()->set('laranail.server-error-pages.output.path', $this->dir);
    config()->set('laranail.server-error-pages.server.apache.enabled', false);
    config()->set('laranail.server-error-pages.server.nginx.enabled', false);
});

afterEach(function (): void {
    array_map(unlink(...), glob($this->dir . '/*') ?: []);
    @rmdir($this->dir);
});

it('exports fully self-contained inlined pages (no external subresource)', function (): void {
    app(StaticSiteBuilder::class)->build(standalone: true, onlyKeys: ['404', '503']);

    $html = file_get_contents($this->dir . '/404.html');
    expect($html)
        ->toContain('<style>')
        ->toContain('<script>')
        ->not->toContain('<link')
        ->not->toContain('src="/vendor');
    expect(app(HtmlInliner::class)->externalReferences($html))->toBe([]);
});

it('rejects a remote logo that cannot be inlined', function (): void {
    config()->set('laranail.server-error-pages.brand.logo', 'https://cdn.example.com/logo.png');

    expect(fn () => app(StaticSiteBuilder::class)->build(standalone: true, onlyKeys: ['404']))
        ->toThrow(NotSelfContainedException::class);
});

it('flags external subresources but allows navigation links + data URIs', function (): void {
    $inliner = app(HtmlInliner::class);

    expect($inliner->externalReferences('<img src="https://cdn.example.com/x.png">'))->not->toBe([]);
    expect($inliner->externalReferences('<link rel="stylesheet" href="/x/error-pages.css">'))->not->toBe([]);
    expect($inliner->externalReferences('<a href="https://example.com/">Home</a><style>.x{}</style>'))->toBe([]);
    expect($inliner->externalReferences('<img src="data:image/png;base64,AA//BB">'))->toBe([]);
});
