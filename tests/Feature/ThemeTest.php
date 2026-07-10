<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;
use Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages;

it('resolves the configured preset and exposes its colours', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'emerald');

    $theme = ServerErrorPages::theme();

    expect($theme->preset)->toBe(ThemePreset::Emerald)
        ->and($theme->colorsLight['accent'])->toBe('#059669')
        ->and($theme->colorsDark)->toHaveKey('bg');
});

it('falls back to the default preset for an unknown value', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'nope');

    expect(ServerErrorPages::theme()->preset)->toBe(ThemePreset::Default);
});

it('merges per-token colour overrides on top of the preset', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'slate');
    config()->set('laranail.server-error-pages.theme.colors.light', ['accent' => '#ff0000']);

    $theme = ServerErrorPages::theme();

    expect($theme->colorsLight['accent'])->toBe('#ff0000')          // override wins
        ->and($theme->colorsLight['bg'])->toBe('#f8fafc');          // preset token kept
});

it('renders the preset onto the single layout with a theme body class', function (): void {
    config()->set('laranail.server-error-pages.theme.preset', 'crimson');

    $html = ServerErrorPages::htmlFor(404);

    expect($html)
        ->toContain('sep-theme-crimson')
        ->toContain('--sep-accent:#dc2626')
        ->toContain('sep-card')
        ->not->toContain('sep-variant')
        ->not->toContain('sep-hero-figure');
});
