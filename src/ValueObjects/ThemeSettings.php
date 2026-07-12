<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\ValueObjects;

use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;

/**
 * Presentation settings shared by every error page: brand identity, the chosen
 * theme preset, dark-mode behaviour, and any per-token colour OVERRIDES from
 * config. The preset's full colour set lives in the compiled CSS; overrides (if
 * any) are emitted to a linked `error-pages-theme.css` at build time. Immutable.
 */
final readonly class ThemeSettings
{
    /**
     * @param  array<string, string>  $overridesLight
     * @param  array<string, string>  $overridesDark
     */
    public function __construct(
        public string $brandName,
        public string $brandUrl,
        public ?string $logo,
        public ThemePreset $preset,
        public bool $autoDark,
        public array $overridesLight = [],
        public array $overridesDark = [],
    ) {}

    /**
     * True when config sets at least one per-token colour override (→ a
     * `error-pages-theme.css` is generated and linked).
     */
    public function hasOverrides(): bool
    {
        return $this->overridesLight !== [] || $this->overridesDark !== [];
    }
}
