<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;

it('produces valid minified output', function (): void {
    $dir = sys_get_temp_dir() . '/sep-min-' . bin2hex(random_bytes(3));
    config()->set('laranail.server-error-pages.output.disk');
    config()->set('laranail.server-error-pages.output.path', $dir);
    config()->set('laranail.server-error-pages.output.minify', true);
    config()->set('laranail.server-error-pages.server.apache.enabled', false);
    config()->set('laranail.server-error-pages.server.nginx.enabled', false);

    app(StaticSiteBuilder::class)->build(['503']);
    $html = file_get_contents($dir . '/503.html');

    // structure intact
    expect($html)->toContain('<!DOCTYPE html>')
        ->toContain('</html>')
        ->toContain('(function ()')      // JS IIFE survived
        ->toContain('addEventListener')
        ->toContain('http-equiv="refresh"')
        ->toContain('Be right back');
    // no external refs
    expect(app(StaticSiteBuilder::class)->externalReferences($html))->toBe([]);

    array_map(unlink(...), glob($dir . '/*') ?: []);
    @rmdir($dir);
});
