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
            brandUrl: (string) $this->get('home_url', $this->get('brand.url', '/')),
            logo: $this->stringOrNull($this->get('brand.logo')),
            preset: ThemePreset::fromValue($presetOverride ?? (string) $this->get('theme.preset', 'default')),
            autoDark: (bool) $this->get('theme.auto_dark', true),
            overridesLight: $this->colors('light'),
            overridesDark: $this->colors('dark'),
        );
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
