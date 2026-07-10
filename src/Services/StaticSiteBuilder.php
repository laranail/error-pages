<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Simtabi\Laranail\ServerErrorPages\Concerns\InteractsWithOutputDisk;
use Simtabi\Laranail\ServerErrorPages\Exceptions\NotSelfContainedException;

/**
 * Generates the static HTML pages: render each enabled status key through the
 * shared component, assert it is fully self-contained, minify, and write it to
 * the output disk/path. This is the feature that makes error pages survive the
 * app being down.
 */
final readonly class StaticSiteBuilder
{
    use InteractsWithOutputDisk;

    public function __construct(
        private ServerErrorPagesManager $manager,
        private Config $configRepository,
        private Filesystem $filesystem,
        private AssetInliner $assets,
    ) {}

    /**
     * Build the given keys (or all configured keys). Returns a per-key report.
     *
     * @param  list<string>|null  $onlyKeys
     * @return array<string, array{path: string, bytes: int}>
     *
     * @throws NotSelfContainedException
     * @throws RuntimeException on a packaging/config error (missing CSS bundle, empty output path)
     */
    public function build(?array $onlyKeys = null): array
    {
        $this->guardBuildable();

        $keys = $onlyKeys ?? $this->manager->keys();
        $minify = (bool) $this->configRepository->get('laranail.server-error-pages.output.minify', true);

        $report = [];

        foreach ($keys as $key) {
            $html = $this->manager->htmlForKey($key);

            $violations = $this->externalReferences($html);
            if ($violations !== []) {
                throw new NotSelfContainedException($key, $violations);
            }

            if ($minify) {
                $html = $this->minify($html);
            }

            $path = $this->writePage($key, $html);
            $report[$key] = ['path' => $path, 'bytes' => strlen($html)];
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
     * Detect external subresource loads that would break a page when the
     * network/app is degraded. Escaped text content never matches (real
     * resource attributes require unescaped quotes, and `url()` is only scanned
     * inside <style> blocks), only genuine resource loads do. Navigation links
     * (<a href>) are intentionally allowed, so a full-URL `url_base` on the
     * "home" button never trips the check.
     *
     * @return list<string>
     */
    public function externalReferences(string $html): array
    {
        $external = '(?:https?:)?\/\/'; // http://, https://, or protocol-relative //

        $found = [];

        $markup = [
            'external stylesheet' => '/<link\b[^>]*\bhref\s*=\s*["\']' . $external . '/i',
            'external script' => '/<script\b[^>]*\bsrc\s*=\s*["\']' . $external . '/i',
            'external src/srcset' => '/\bsrc(?:set)?\s*=\s*["\']?\s*' . $external . '/i',
            'external svg use' => '/<(?:use|image)\b[^>]*\b(?:href|xlink:href)\s*=\s*["\']' . $external . '/i',
        ];
        foreach ($markup as $label => $pattern) {
            if (preg_match($pattern, $html) === 1) {
                $found[] = $label;
            }
        }

        // CSS url()/@import — only inside <style> blocks, so escaped page text
        // that merely contains "url(https://…)" cannot false-positive.
        if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styles) !== false) {
            foreach ($styles[1] as $css) {
                if (preg_match('/url\(\s*["\']?\s*' . $external . '/i', $css) === 1
                    || preg_match('/@import\s+(?:url\(\s*)?["\']?\s*' . $external . '/i', $css) === 1) {
                    $found[] = 'external css url()';
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Fail fast on packaging/config errors before writing anything.
     */
    private function guardBuildable(): void
    {
        if ($this->assets->css() === '') {
            throw new RuntimeException(
                'The compiled CSS bundle is empty or missing (resources/dist/error-pages.css). ' .
                'Reinstall the package or run `npm run build` to regenerate it.',
            );
        }

        $disk = $this->configRepository->get('laranail.server-error-pages.output.disk');
        $path = (string) $this->configRepository->get('laranail.server-error-pages.output.path', '');
        if ((! is_string($disk) || $disk === '') && trim($path) === '') {
            throw new RuntimeException('Output path is empty; set laranail.server-error-pages.output.path.');
        }
    }

    /**
     * Conservative HTML minify that NEVER touches inlined <script>/<style>
     * bodies: strip leading indentation and collapse blank lines in the markup
     * only, so a future multi-line JS template literal or CSS string can't be
     * corrupted.
     */
    private function minify(string $html): string
    {
        $protected = [];
        $html = (string) preg_replace_callback(
            '/<(script|style)\b[^>]*>.*?<\/\1>/is',
            static function (array $m) use (&$protected): string {
                $token = "\0SEP_PROTECTED_" . count($protected) . "\0";
                $protected[$token] = $m[0];

                return $token;
            },
            $html,
        );

        $html = (string) preg_replace('/^[ \t]+/m', '', $html);
        $html = (string) preg_replace("/\n{2,}/", "\n", $html);

        $html = strtr(trim($html), $protected);

        return $html . "\n";
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
