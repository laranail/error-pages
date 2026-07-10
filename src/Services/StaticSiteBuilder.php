<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Simtabi\Laranail\ServerErrorPages\Concerns\InteractsWithOutputDisk;
use Simtabi\Laranail\ServerErrorPages\Exceptions\NotSelfContainedException;
use Simtabi\Laranail\ServerErrorPages\Support\CssVariableMap;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Generates the static error pages. Default mode LINKS the external CSS/JS
 * (copied next to a servable location) — DRY and cacheable, and still resilient
 * because the web server serves those files when PHP is down. The `--standalone`
 * mode inlines everything into portable single files for upload to any host.
 */
final readonly class StaticSiteBuilder
{
    use InteractsWithOutputDisk;

    public function __construct(
        private ServerErrorPagesManager $manager,
        private Config $configRepository,
        private Filesystem $filesystem,
        private HtmlInliner $inliner,
    ) {}

    /**
     * Build all configured keys (or a subset). In standalone mode each page is
     * inlined and validated as self-contained; otherwise the linked bundle is
     * copied next to the pages.
     *
     * @param  list<string>|null  $onlyKeys
     * @return array<string, array{path: string, bytes: int}>
     *
     * @throws NotSelfContainedException|RuntimeException
     */
    public function build(bool $standalone = false, ?array $onlyKeys = null): array
    {
        $this->guardBuildable();

        $theme = $this->manager->theme();
        $locale = $this->stringOrNull($this->configRepository->get('laranail.server-error-pages.content.default_locale'));
        $keys = $onlyKeys ?? $this->manager->keys();
        $minify = (bool) $this->configRepository->get('laranail.server-error-pages.output.minify', true);

        $report = [];

        foreach ($keys as $key) {
            $html = $this->manager->htmlForKey($key, $locale);

            if ($standalone) {
                $html = $this->inliner->inline($html, $theme);
                $violations = $this->inliner->externalReferences($html);
                if ($violations !== []) {
                    throw new NotSelfContainedException($key, $violations);
                }
            }

            if ($minify) {
                $html = $this->minify($html);
            }

            $path = $this->writePage($key, $html);
            $report[$key] = ['path' => $path, 'bytes' => strlen($html)];
        }

        if (! $standalone) {
            $this->publishLinkedAssets($theme);
        }

        return $report;
    }

    /**
     * Remove every generated static page. Returns the paths removed.
     *
     * @return list<string>
     */
    public function clear(): array
    {
        return $this->deletePages($this->manager->keys());
    }

    /**
     * Copy the linked bundle next to a servable location so the pages' assets
     * are always present, and write the theme override file when configured.
     */
    private function publishLinkedAssets(ThemeSettings $theme): void
    {
        $dest = (string) $this->configRepository->get('laranail.server-error-pages.output.assets_path', '');
        if ($dest === '') {
            return;
        }

        $src = dirname(__DIR__, 2) . '/public/assets';
        if ($this->filesystem->isDirectory($src)) {
            $this->filesystem->ensureDirectoryExists($dest);
            $this->filesystem->copyDirectory($src, $dest);
        }

        $themeCss = CssVariableMap::themeCss($theme);
        $themePath = $dest . '/css/error-pages-theme.css';
        if ($themeCss !== '') {
            $this->filesystem->ensureDirectoryExists($dest . '/css');
            $this->filesystem->put($themePath, $themeCss);
        } elseif ($this->filesystem->exists($themePath)) {
            $this->filesystem->delete($themePath);
        }
    }

    private function guardBuildable(): void
    {
        $bundle = dirname(__DIR__, 2) . '/public/assets/css/error-pages.css';
        if (! $this->filesystem->exists($bundle)) {
            throw new RuntimeException(
                'The compiled bundle public/assets/css/error-pages.css is missing. Run `npm install && npm run build`.',
            );
        }

        $disk = $this->configRepository->get('laranail.server-error-pages.output.disk');
        $path = (string) $this->configRepository->get('laranail.server-error-pages.output.path', '');
        if ((! is_string($disk) || $disk === '') && trim($path) === '') {
            throw new RuntimeException('Output path is empty; set laranail.server-error-pages.output.path.');
        }
    }

    /**
     * Conservative HTML minify (markup only — inlined <script>/<style> bodies
     * are left intact so a standalone export stays valid).
     */
    private function minify(string $html): string
    {
        $protected = [];
        $html = (string) preg_replace_callback(
            '/<(script|style)\b[^>]*>.*?<\/\1>/is',
            static function (array $m) use (&$protected): string {
                $token = "\0SEP_" . count($protected) . "\0";
                $protected[$token] = $m[0];

                return $token;
            },
            $html,
        );

        $html = (string) preg_replace('/^[ \t]+/m', '', $html);
        $html = (string) preg_replace("/\n{2,}/", "\n", $html);

        return strtr(trim($html), $protected) . "\n";
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function config(): Config
    {
        return $this->configRepository;
    }

    protected function files(): Filesystem
    {
        return $this->filesystem;
    }
}
