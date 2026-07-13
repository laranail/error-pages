<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Enums;

use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;

/**
 * A named colour theme applied to the page as an `.ep-theme-{value}` class. The
 * colour tokens live in the shared stylesheet (presets/shared), so switching
 * presets needs no rebuild; per-token overrides are emitted inline by
 * {@see CssVariableMap}.
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
