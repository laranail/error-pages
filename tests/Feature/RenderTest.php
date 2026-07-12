<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages;

it('loads config under the laranail.server-error-pages key', function (): void {
    expect(config('laranail.server-error-pages.codes.enabled'))->toContain(404, 402);
});

it('renders a dynamic page that LINKS the external assets (no inline)', function (): void {
    $html = ServerErrorPages::htmlFor(404);

    expect($html)
        ->toContain('<!DOCTYPE html>')
        ->toContain('>404<')
        ->toContain('Page not found')                                  // from translations
        ->toContain('href="/vendor/server-error-pages/css/error-pages.css"')
        ->toContain('src="/vendor/server-error-pages/js/error-pages.js"')
        ->toContain('sep-theme-default')
        ->toContain('sep-badge')
        ->not->toContain('<style>')
        ->not->toContain('window.__sep');
});

it('resolves content through translations; an app override wins, missing falls to default', function (): void {
    $dir = lang_path('vendor/server-error-pages/en');
    File::ensureDirectoryExists($dir);
    File::put($dir . '/errors.php', "<?php\n\nreturn ['404' => ['title' => 'Not here']];\n");

    expect(ServerErrorPages::page(404)->title)->toBe('Not here')            // app override wins
        ->and(ServerErrorPages::page(500)->title)->toBe('Something went wrong'); // package/enum default

    File::deleteDirectory(lang_path('vendor/server-error-pages'));
});

it('keeps the number but uses generic copy for non-enum codes', function (): void {
    $client = ServerErrorPages::page(418);
    $server = ServerErrorPages::page(599);

    expect($client->key)->toBe('418')
        ->and($client->title)->toBe('This page is unavailable')
        ->and($server->key)->toBe('599')
        ->and($server->retryable)->toBeTrue();
});

it('adds 402 as a first-class branded page', function (): void {
    expect(ServerErrorPages::page(402)->title)->toBe('Payment required')
        ->and(ServerErrorPages::htmlFor(402))->toContain('Payment required');
});

it('builds linked static pages + copies the bundle + merges server config', function (): void {
    $dir = sys_get_temp_dir() . '/sep-' . bin2hex(random_bytes(4));
    $assets = sys_get_temp_dir() . '/sep-a-' . bin2hex(random_bytes(4));

    config()->set('laranail.server-error-pages.output.disk');
    config()->set('laranail.server-error-pages.output.path', $dir);
    config()->set('laranail.server-error-pages.output.assets_path', $assets);
    config()->set('laranail.server-error-pages.server.apache.output', $dir . '/.htaccess');
    config()->set('laranail.server-error-pages.server.nginx.output', $dir . '/errors.conf');

    $this->artisan('server-error-pages:build')->assertSuccessful();

    expect(file_exists($dir . '/404.html'))->toBeTrue()
        ->and(file_exists($dir . '/5xx.html'))->toBeTrue()
        ->and(file_exists($assets . '/css/error-pages.css'))->toBeTrue()
        ->and(file_exists($assets . '/js/error-pages.js'))->toBeTrue()
        ->and(file_exists($dir . '/.htaccess'))->toBeTrue();

    expect(file_get_contents($dir . '/404.html'))
        ->toContain('>404<')
        ->toContain('href="/vendor/server-error-pages/css/error-pages.css"');
    expect(file_get_contents($dir . '/.htaccess'))->toContain('ErrorDocument 404 /errors/404.html');

    File::deleteDirectory($dir);
    File::deleteDirectory($assets);
});

it('is the same component for dynamic and static output', function (): void {
    expect(ServerErrorPages::htmlFor(503))
        ->toContain('Be right back')
        ->toContain('http-equiv="refresh"');
});
