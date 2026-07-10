<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Support;

use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Turns a {@see ThemeSettings} colour map into the inline `:root { --sep-* }`
 * custom properties that theme every page. Because colours are variables,
 * re-branding needs no asset rebuild. Values are sanitised so nothing from
 * config can break out of the declaration.
 */
final class CssVariableMap
{
    /**
     * Full inline stylesheet body: light defaults, an optional dark media
     * query, and explicit `[data-theme]` overrides for a manual toggle.
     */
    public static function inline(ThemeSettings $theme): string
    {
        $light = self::block($theme->colorsLight);
        $dark = self::block($theme->colorsDark);

        $css = ':root{' . $light . '}';

        if ($theme->autoDark) {
            $css .= '@media (prefers-color-scheme:dark){:root{' . $dark . '}}';
        }

        return $css . (':root[data-theme="dark"]{' . $dark . '}:root[data-theme="light"]{' . $light . '}');
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

        if (preg_match('/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
            return $value;
        }

        if (preg_match('/^(?:rgb|rgba|hsl|hsla)\([0-9.,%\s\/]+\)$/i', $value)) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z]+$/', $value)) {
            return $value;
        }

        return '#334155';
    }
}
