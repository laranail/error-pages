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
 * both the dynamic Blade stub and the static build, so their output is
 * identical.
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
     * Full self-contained HTML for an HTTP status code.
     */
    public function htmlFor(int $code, ?string $locale = null): string
    {
        return $this->renderer->render($this->page($code, $locale), $this->theme());
    }

    /**
     * Full self-contained HTML for a status key ('404' or the generic '4xx').
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
     * Presentation settings from config; a local brand logo is inlined as a
     * data-URI so pages stay self-contained.
     */
    public function theme(): ThemeSettings
    {
        $preset = ThemePreset::fromValue((string) $this->config->get(self::CONFIG . '.theme.preset', 'default'));

        return new ThemeSettings(
            brandName: (string) $this->config->get(self::CONFIG . '.brand.name', 'Our site'),
            brandUrl: (string) $this->config->get(self::CONFIG . '.brand.url', '/'),
            logo: $this->resolveLogo($this->config->get(self::CONFIG . '.brand.logo')),
            preset: $preset,
            autoDark: (bool) $this->config->get(self::CONFIG . '.theme.auto_dark', true),
            colorsLight: array_merge($preset->light(), $this->colorOverrides('light')),
            colorsDark: array_merge($preset->dark(), $this->colorOverrides('dark')),
        );
    }

    /**
     * Per-token colour overrides from config, merged on top of the preset.
     *
     * @return array<string, string>
     */
    private function colorOverrides(string $scheme): array
    {
        /** @var array<string, mixed> $colors */
        $colors = (array) $this->config->get(self::CONFIG . '.theme.colors.' . $scheme, []);

        return array_map(strval(...), array_filter($colors, is_scalar(...)));
    }

    private function resolveLogo(mixed $logo): ?string
    {
        if (! is_string($logo) || $logo === '') {
            return null;
        }

        if (str_starts_with($logo, 'data:')) {
            return $logo;
        }

        // Inline a local file (absolute, base_path- or public_path-relative) so
        // the page stays self-contained.
        foreach ($this->logoCandidates($logo) as $candidate) {
            if (is_file($candidate)) {
                $data = @file_get_contents($candidate);
                if ($data !== false) {
                    return 'data:' . $this->mimeFor($candidate) . ';base64,' . base64_encode($data);
                }
            }
        }

        // A remote URL is returned as-is so the build's self-containment check
        // rejects it (forcing a local, inlinable file — the correct choice for
        // pages that must render when the app/network is down).
        if (preg_match('#^(?:https?:)?//#i', $logo) === 1) {
            return $logo;
        }

        // An unresolvable relative/root-relative path can't be guaranteed
        // available when the app is down, so drop it rather than emit a request.
        return null;
    }

    /**
     * @return list<string>
     */
    private function logoCandidates(string $logo): array
    {
        $candidates = [$logo];

        if (function_exists('base_path')) {
            $candidates[] = base_path($logo);
        }

        if (function_exists('public_path')) {
            $candidates[] = public_path(ltrim($logo, '/'));
        }

        return $candidates;
    }

    private function mimeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };
    }
}
