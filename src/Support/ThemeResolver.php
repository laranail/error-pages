<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Illuminate\Contracts\Config\Repository as Config;
use Simtabi\Laranail\ErrorPages\Core\Enums\ThemePreset;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * Builds the core {@see ThemeSettings} from `config('error-pages.*')` (brand,
 * theme preset, dark mode, per-token colour overrides). An optional preset name
 * overrides the configured default (the DSL's `theme()`).
 */
final readonly class ThemeResolver
{
    private const string CONFIG = 'error-pages';

    public function __construct(
        private Config $config,
    ) {}

    public function resolve(?string $presetOverride = null): ThemeSettings
    {
        return new ThemeSettings(
            brandName: (string) $this->get('brand.name', 'Our site'),
            brandUrl: $this->safeUrl((string) $this->get('home_url', $this->get('brand.url', '/')), '/'),
            logo: $this->safeUrl($this->stringOrNull($this->get('brand.logo')), null),
            preset: ThemePreset::fromValue($presetOverride ?? (string) $this->get('theme.preset', 'default')),
            autoDark: (bool) $this->get('theme.auto_dark', true),
            overridesLight: $this->colors('light'),
            overridesDark: $this->colors('dark'),
        );
    }

    /**
     * Neutralise a dangerous URL scheme on brand/logo config. HTML-escaping already
     * prevents attribute breakout; this defends the (admin-config) case where a
     * `javascript:`/`data:`/`vbscript:` URI would still be a clickable script link.
     * Allows http(s), relative, anchor, and protocol-relative URLs.
     *
     * @template T of string|null
     *
     * @param  T  $fallback
     * @return string|T
     */
    private function safeUrl(?string $url, ?string $fallback): ?string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        // No scheme (relative/anchor/protocol-relative), or an explicit http(s) scheme.
        if (! preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) || preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return $fallback;
    }

    private function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get(self::CONFIG . '.' . $key, $default);
    }

    /**
     * @return array<string, string>
     */
    private function colors(string $scheme): array
    {
        /** @var array<string, mixed> $colors */
        $colors = (array) $this->get('theme.colors.' . $scheme, []);

        return array_map(strval(...), array_filter($colors, is_scalar(...)));
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
