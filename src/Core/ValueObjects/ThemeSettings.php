<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\ValueObjects;

use Simtabi\Laranail\ErrorPages\Core\Enums\ThemePreset;
use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;

/**
 * Presentation settings shared by every error page: brand identity, the chosen
 * theme preset, dark-mode behaviour, and any per-token colour OVERRIDES. The
 * preset's full colour set lives in the shared stylesheet; overrides (if any)
 * are emitted inline by {@see CssVariableMap}.
 * Immutable.
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
     * True when at least one per-token colour override is set (→ inline
     * `<style>` overrides are emitted for the chosen preset).
     */
    public function hasOverrides(): bool
    {
        return $this->overridesLight !== [] || $this->overridesDark !== [];
    }
}
