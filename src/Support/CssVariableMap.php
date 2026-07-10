<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Support;

use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Renders the optional `theme.css` file that carries per-token colour overrides
 * from config. It targets the chosen preset's `.sep-theme-{preset}` class so,
 * linked AFTER the main stylesheet, it wins by source order. Values are
 * sanitised so nothing from config can break out of the declaration. Returns
 * an empty string when there are no overrides.
 */
final class CssVariableMap
{
    public static function themeCss(ThemeSettings $theme): string
    {
        if (! $theme->hasOverrides()) {
            return '';
        }

        $class = '.sep-theme-' . $theme->preset->value;
        $css = '';

        $light = self::block($theme->overridesLight);
        if ($light !== '') {
            $css .= $class . '{' . $light . '}';
        }

        $dark = self::block($theme->overridesDark);
        if ($dark !== '') {
            $css .= '@media (prefers-color-scheme:dark){.sep-auto-dark' . $class . '{' . $dark . '}}';
        }

        return $css;
    }

    /**
     * @param  array<string, string>  $colors
     */
    private static function block(array $colors): string
    {
        $out = '';

        foreach ($colors as $name => $value) {
            $token = preg_replace('/[^a-z0-9-]/i', '', (string) $name);
            if ($token === '') {
                continue;
            }
            if ($token === null) {
                continue;
            }

            $out .= '--sep-' . strtolower($token) . ':' . self::safeColor((string) $value) . ';';
        }

        return $out;
    }

    /**
     * Allow only safe CSS colour tokens (hex, rgb/rgba, hsl/hsla, or a bare
     * keyword). Anything else collapses to a neutral fallback.
     */
    private static function safeColor(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(?:rgb|rgba|hsl|hsla)\([0-9.,%\s\/]+\)$/i', $value) === 1) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z]+$/', $value) === 1) {
            return $value;
        }

        return '#334155';
    }
}
