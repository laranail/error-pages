<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Enums;

/**
 * A named colour theme applied to the single error-page layout as a
 * `.sep-theme-{value}` class on <body>. The actual colour tokens live in the
 * SCSS (`resources/assets/scss/error-pages.scss`) and are compiled into the
 * shipped stylesheet — switching presets needs no rebuild. `config('theme.colors')`
 * can override individual tokens at build time via the generated `theme.css`.
 */
enum ThemePreset: string
{
    case Default = 'default';
    case Slate = 'slate';
    case Midnight = 'midnight';
    case Emerald = 'emerald';
    case Crimson = 'crimson';

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Default;
    }
}
