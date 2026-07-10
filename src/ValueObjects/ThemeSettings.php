<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\ValueObjects;

use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;

/**
 * Presentation settings shared by every error page: brand identity, the chosen
 * theme preset, dark-mode behaviour, and the resolved light/dark colour maps
 * that feed the `--sep-*` CSS custom properties. Immutable.
 */
final readonly class ThemeSettings
{
    /**
     * @param  array<string, string>  $colorsLight
     * @param  array<string, string>  $colorsDark
     */
    public function __construct(
        public string $brandName,
        public string $brandUrl,
        public ?string $logo,
        public ThemePreset $preset,
        public bool $autoDark,
        public array $colorsLight,
        public array $colorsDark,
    ) {}
}
