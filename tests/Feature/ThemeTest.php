<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;
use Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages;
use Simtabi\Laranail\ServerErrorPages\Services\StaticSiteBuilder;
use Simtabi\Laranail\ServerErrorPages\Support\CssVariableMap;

it('applies the configured preset as a body class (no rebuild to switch)', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'midnight');

    $html = ServerErrorPages::htmlFor(404);

    expect(ServerErrorPages::theme()->preset)->toBe(ThemePreset::Midnight)
        ->and($html)->toContain('sep-theme-midnight')
        ->and($html)->not->toContain('sep-theme-default');
});

it('falls back to the default preset for an unknown value', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'nope');

    expect(ServerErrorPages::theme()->preset)->toBe(ThemePreset::Default);
});

it('generates a linked theme.css only when per-token overrides are set', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'slate');
    config()->set('laranail.server-error-pages.theme.colors.light', ['accent' => '#ff0000']);

    $theme = ServerErrorPages::theme();
    expect($theme->hasOverrides())->toBeTrue();

    $css = CssVariableMap::themeCss($theme);
    expect($css)->toContain('.sep-theme-slate{')->toContain('--sep-accent:#ff0000');

    // the page links theme.css when overrides exist
    expect(ServerErrorPages::htmlFor(404))->toContain('href="/vendor/server-error-pages/css/theme.css"');
});

it('writes theme.css to assets_path during a linked build', function (): void {
    $dir = sys_get_temp_dir() . '/sep-t-' . bin2hex(random_bytes(4));
    $assets = sys_get_temp_dir() . '/sep-ta-' . bin2hex(random_bytes(4));
    config()->set('laranail.server-error-pages.output.disk');
    config()->set('laranail.server-error-pages.output.path', $dir);
    config()->set('laranail.server-error-pages.output.assets_path', $assets);
    config()->set('laranail.server-error-pages.server.apache.enabled', false);
    config()->set('laranail.server-error-pages.server.nginx.enabled', false);
    config()->set('laranail.server-error-pages.theme.colors.dark', ['accent' => '#00ff00']);

    app(StaticSiteBuilder::class)->build(false, ['404']);

    expect(file_exists($assets . '/css/theme.css'))->toBeTrue();
    expect(file_get_contents($assets . '/css/theme.css'))->toContain('--sep-accent:#00ff00');

    File::deleteDirectory($dir);
    File::deleteDirectory($assets);
});
