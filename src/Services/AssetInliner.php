<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * Reads the committed, prebuilt CSS/JS bundles and returns them as strings to
 * be inlined into every page — so a rendered error page has zero external
 * requests and survives the app being down. Resolution order: a config-set
 * override path, then the published `public/vendor/...` copy, then the
 * package's shipped `resources/dist`. Cached in-process.
 */
final class AssetInliner
{
    private const string CONFIG = 'laranail.server-error-pages.assets';

    /** @var array<string, string> */
    private array $cache = [];

    public function __construct(private readonly Config $config) {}

    public function css(): string
    {
        return $this->read('css', 'compiled_css', 'error-pages.css');
    }

    public function js(): string
    {
        return $this->read('js', 'static_js', 'static/error-pages.js');
    }

    private function read(string $cacheKey, string $configKey, string $distRelative): string
    {
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        foreach ($this->candidates($configKey, $distRelative) as $path) {
            if ($path !== null && $path !== '' && is_file($path)) {
                return $this->cache[$cacheKey] = (string) file_get_contents($path);
            }
        }

        return $this->cache[$cacheKey] = '';
    }

    /**
     * @return list<string|null>
     */
    private function candidates(string $configKey, string $distRelative): array
    {
        $packageRoot = dirname(__DIR__, 2);

        return [
            $this->stringOrNull($this->config->get(self::CONFIG . '.' . $configKey)),
            function_exists('public_path') ? public_path('vendor/server-error-pages/' . $distRelative) : null,
            $packageRoot . '/resources/dist/' . $distRelative,
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
