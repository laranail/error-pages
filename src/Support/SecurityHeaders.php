<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Support;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Formats the configured security headers for each web server. These live in
 * the emitted server config (not the HTML) because they must apply to the
 * static pages, which the application cannot set headers on.
 */
final class SecurityHeaders
{
    /**
     * @return array<string, string>
     */
    public static function fromConfig(Config $config): array
    {
        /** @var array<string, mixed> $headers */
        $headers = (array) $config->get('laranail.server-error-pages.security.headers', []);

        $out = [];
        foreach ($headers as $name => $value) {
            if (is_string($name) && is_string($value) && trim($value) !== '') {
                $out[$name] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function apache(array $headers, string $indent = '    '): string
    {
        if ($headers === []) {
            return '';
        }

        $lines = ['<IfModule mod_headers.c>'];
        foreach ($headers as $name => $value) {
            $lines[] = $indent . 'Header always set ' . $name . ' "' . self::escape($value) . '"';
        }
        $lines[] = '</IfModule>';

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function nginx(array $headers, string $indent = '    '): string
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $indent . 'add_header ' . $name . ' "' . self::escape($value) . '" always;';
        }

        return implode("\n", $lines);
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
