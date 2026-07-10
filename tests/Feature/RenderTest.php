<?php

declare(strict_types=1);

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages;
use Simtabi\Laranail\ServerErrorPages\Services\ServerErrorPagesManager;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;

it('loads config under the laranail.server-error-pages key', function (): void {
    expect(config('laranail.server-error-pages.codes.enabled'))->toContain(404);
});

it('renders a self-contained dynamic error page', function (): void {
    $html = ServerErrorPages::htmlFor(404);

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('>404<')
        ->toContain('Page not found')
        ->toContain('<style>')
        ->toContain('sep-card');

    expect(app(StaticSiteBuilder::class)->externalReferences($html))->toBe([]);
});

it('resolves errors::{code} to the package view once error paths are registered', function (): void {
    (new RegisterErrorViewPaths)();

    expect(view()->exists('errors::404'))->toBeTrue()
        ->and(view()->exists('errors::4xx'))->toBeTrue()
        ->and(view()->exists('errors::5xx'))->toBeTrue();
});

it('applies a config override then falls back to the enum default', function (): void {
    config()->set('laranail.server-error-pages.content.source', 'config');
    config()->set('laranail.server-error-pages.messages', ['404' => ['title' => 'Nope']]);

    expect(ServerErrorPages::page(404)->title)->toBe('Nope')
        ->and(ServerErrorPages::page(500)->title)->toBe('Something went wrong');
});

it('keeps the number but uses generic copy for non-enum codes', function (): void {
    $client = ServerErrorPages::page(418);
    $server = ServerErrorPages::page(599);

    expect($client->key)->toBe('418')
        ->and($client->code)->toBe(418)
        ->and($client->title)->toBe('This page is unavailable')
        ->and($server->key)->toBe('599')
        ->and($server->title)->toBe('Something went wrong')
        ->and($server->retryable)->toBeTrue();

    // The generic catch-all pages are still reachable by key.
    expect(ServerErrorPages::pageByKey('4xx')->key)->toBe('4xx')
        ->and(ServerErrorPages::pageByKey('5xx')->key)->toBe('5xx');
});

it('honors a per-code override for a non-enum code', function (): void {
    config()->set('laranail.server-error-pages.content.source', 'config');
    config()->set('laranail.server-error-pages.messages', ['418' => ['title' => "I'm a teapot"]]);

    expect(ServerErrorPages::page(418)->title)->toBe("I'm a teapot");
});

it('builds static pages and server config to the configured paths', function (): void {
    $dir = sys_get_temp_dir() . '/sep-' . bin2hex(random_bytes(4));

    config()->set('laranail.server-error-pages.output.disk');
    config()->set('laranail.server-error-pages.output.path', $dir);
    config()->set('laranail.server-error-pages.server.apache.output', $dir . '/.htaccess');
    config()->set('laranail.server-error-pages.server.nginx.output', $dir . '/errors.conf');

    $this->artisan('server-error-pages:build')->assertSuccessful();

    expect(file_exists($dir . '/404.html'))->toBeTrue()
        ->and(file_exists($dir . '/5xx.html'))->toBeTrue()
        ->and(file_exists($dir . '/.htaccess'))->toBeTrue()
        ->and(file_exists($dir . '/errors.conf'))->toBeTrue();

    expect(file_get_contents($dir . '/404.html'))->toContain('>404<');
    expect(file_get_contents($dir . '/.htaccess'))->toContain('ErrorDocument 404 /errors/404.html');
    expect(file_get_contents($dir . '/errors.conf'))->toContain('error_page 404 /errors/404.html');

    array_map(unlink(...), glob($dir . '/*') ?: []);
    @rmdir($dir);
});

it('is the same component for dynamic and static output', function (): void {
    $manager = app(ServerErrorPagesManager::class);

    expect($manager->htmlFor(503))
        ->toContain('Be right back')
        ->toContain('http-equiv="refresh"');
});
