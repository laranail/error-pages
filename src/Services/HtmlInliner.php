<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Simtabi\Laranail\ServerErrorPages\Support\CssVariableMap;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Export-time post-processor: turns a rendered (LINKED) error page into a fully
 * self-contained single file by replacing the linked stylesheet/script with
 * inline `<style>`/`<script>` and a local brand logo with a data-URI. Used only
 * by the standalone export (`build --standalone` / `:export`); the default build
 * leaves the assets linked.
 */
final readonly class HtmlInliner
{
    private const string CONFIG = 'laranail.server-error-pages.assets';

    public function __construct(private Config $config) {}

    public function inline(string $html, ThemeSettings $theme): string
    {
        $css = $this->readAsset('compiled_css', 'css/error-pages.css');
        if ($css !== null) {
            $html = $this->replaceLink($html, 'error-pages.css', '<style>' . $css . '</style>');
        }

        if ($theme->hasOverrides()) {
            $html = $this->replaceLink($html, 'theme.css', '<style>' . CssVariableMap::themeCss($theme) . '</style>');
        }

        $js = $this->readAsset('static_js', 'js/error-pages.js');
        if ($js !== null) {
            $html = $this->replaceScript($html, 'error-pages.js', '<script>' . $js . '</script>');
        }

        return $this->inlineLogo($html, $theme->logo);
    }

    /**
     * External subresource references that survive inlining (used to validate a
     * standalone export). Navigation links (<a href>) are allowed.
     *
     * @return list<string>
     */
    public function externalReferences(string $html): array
    {
        $found = [];
        $patterns = [
            'stylesheet link' => '/<link\b[^>]*\bhref\s*=\s*["\'][^"\']*\.css["\']/i',
            'external script' => '/<script\b[^>]*\bsrc\s*=/i',
            'external image' => '/\bsrc\s*=\s*["\'](?:https?:)?\/\//i',
        ];
        foreach ($patterns as $label => $pattern) {
            if (preg_match($pattern, $html) === 1) {
                $found[] = $label;
            }
        }

        return $found;
    }

    private function replaceLink(string $html, string $filename, string $replacement): string
    {
        $pattern = '#<link\b[^>]*\bhref\s*=\s*"[^"]*' . preg_quote($filename, '#') . '"[^>]*>#i';

        return (string) preg_replace_callback($pattern, static fn (): string => $replacement, $html);
    }

    private function replaceScript(string $html, string $filename, string $replacement): string
    {
        $pattern = '#<script\b[^>]*\bsrc\s*=\s*"[^"]*' . preg_quote($filename, '#') . '"[^>]*>\s*</script>#i';

        return (string) preg_replace_callback($pattern, static fn (): string => $replacement, $html);
    }

    private function inlineLogo(string $html, ?string $logo): string
    {
        if ($logo === null || $logo === '' || str_starts_with($logo, 'data:')) {
            return $html;
        }

        $dataUri = $this->logoDataUri($logo);
        if ($dataUri === null) {
            return $html;
        }

        return (string) preg_replace_callback(
            '#(<img\b[^>]*\bsrc\s*=\s*")' . preg_quote($logo, '#') . '("[^>]*>)#i',
            static fn (array $m): string => $m[1] . $dataUri . $m[2],
            $html,
        );
    }

    private function logoDataUri(string $logo): ?string
    {
        $candidates = [$logo];
        if (function_exists('base_path')) {
            $candidates[] = base_path(ltrim($logo, '/'));
        }
        if (function_exists('public_path')) {
            $candidates[] = public_path(ltrim($logo, '/'));
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                $data = @file_get_contents($path);
                if ($data !== false) {
                    return 'data:' . $this->mimeFor($path) . ';base64,' . base64_encode($data);
                }
            }
        }

        return null;
    }

    private function readAsset(string $configKey, string $distRelative): ?string
    {
        $override = $this->config->get(self::CONFIG . '.' . $configKey);
        $candidates = [];
        if (is_string($override) && $override !== '') {
            $candidates[] = $override;
        }
        $candidates[] = dirname(__DIR__, 2) . '/public/assets/' . $distRelative;

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return (string) file_get_contents($path);
            }
        }

        return null;
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
