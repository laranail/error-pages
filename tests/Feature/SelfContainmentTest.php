<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Exceptions\NotSelfContainedException;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;

beforeEach(function (): void {
    $this->builder = app(StaticSiteBuilder::class);
});

it('flags external subresource loads', function (string $html): void {
    expect($this->builder->externalReferences($html))->not->toBe([]);
})->with([
    'external image' => ['<img src="https://cdn.example.com/logo.png">'],
    'protocol-relative image' => ['<img src="//cdn.example.com/logo.png">'],
    'external stylesheet' => ['<link rel="stylesheet" href="https://cdn.example.com/a.css">'],
    'external script' => ['<script src="https://cdn.example.com/a.js"></script>'],
    'external css url' => ['<style>body{background:url(https://cdn.example.com/bg.png)}</style>'],
]);

it('allows navigation links and inlined data URIs', function (string $html): void {
    expect($this->builder->externalReferences($html))->toBe([]);
})->with([
    'external nav link' => ['<a href="https://example.com/">Home</a>'],
    'root-relative link' => ['<a href="/">Home</a>'],
    'data uri image' => ['<img src="data:image/png;base64,iVBORw0KGgoAAAA//w==">'],
    'inlined style' => ['<style>.x{color:#fff}</style>'],
]);

it('rejects a non-self-contained page during build', function (): void {
    config()->set('laranail.server-error-pages.brand.logo', 'https://cdn.example.com/logo.png');
    config()->set('laranail.server-error-pages.output.path', sys_get_temp_dir() . '/sep-fail');

    expect(fn () => $this->builder->build(['404']))
        ->toThrow(NotSelfContainedException::class);
});
