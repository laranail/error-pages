<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Simtabi\Laranail\ServerErrorPages\Contracts\StaticRenderer;
use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;
use Simtabi\Laranail\ServerErrorPages\Support\ErrorPageFactory;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ErrorPage;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * The package's public entry point (facade root). Resolves the content + theme
 * for a status code and renders the shared component — the ONE path used by
 * both the dynamic Blade view and the static build, so their output is
 * identical (both LINK the external stylesheet/script).
 */
final readonly class ServerErrorPagesManager
{
    private const string CONFIG = 'laranail.server-error-pages';

    public function __construct(
        private ErrorPageFactory $factory,
        private StaticRenderer $renderer,
        private Config $config,
    ) {}

    public function page(int $code, ?string $locale = null): ErrorPage
    {
        return $this->factory->make($code, $locale);
    }

    public function pageByKey(string $key, ?string $locale = null): ErrorPage
    {
        return $this->factory->makeByKey($key, $locale);
    }

    /**
     * Rendered HTML for an HTTP status code (links the external assets).
     */
    public function htmlFor(int $code, ?string $locale = null): string
    {
        return $this->renderer->render($this->page($code, $locale), $this->theme());
    }

    /**
     * Rendered HTML for a status key ('404' or the generic '4xx').
     */
    public function htmlForKey(string $key, ?string $locale = null): string
    {
        return $this->renderer->render($this->pageByKey($key, $locale), $this->theme());
    }

    /**
     * Every status key to generate (enabled codes + generic fallbacks).
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return $this->factory->keys();
    }

    /**
     * Presentation settings from config. The preset's colours live in the
     * compiled CSS; only per-token overrides are carried here (→ error-pages-theme.css). The
     * logo is used as-is in an <img src> (inlined only by the standalone export).
     */
    public function theme(): ThemeSettings
    {
        return new ThemeSettings(
            brandName: (string) $this->config->get(self::CONFIG . '.brand.name', 'Our site'),
            brandUrl: (string) $this->config->get(self::CONFIG . '.brand.url', '/'),
            logo: $this->stringOrNull($this->config->get(self::CONFIG . '.brand.logo')),
            preset: ThemePreset::fromValue((string) $this->config->get(self::CONFIG . '.theme.preset', 'default')),
            autoDark: (bool) $this->config->get(self::CONFIG . '.theme.auto_dark', true),
            overridesLight: $this->colorOverrides('light'),
            overridesDark: $this->colorOverrides('dark'),
        );
    }

    /**
     * Per-token colour overrides from config (empty unless set).
     *
     * @return array<string, string>
     */
    private function colorOverrides(string $scheme): array
    {
        /** @var array<string, mixed> $colors */
        $colors = (array) $this->config->get(self::CONFIG . '.theme.colors.' . $scheme, []);

        return array_map(strval(...), array_filter($colors, is_scalar(...)));
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
