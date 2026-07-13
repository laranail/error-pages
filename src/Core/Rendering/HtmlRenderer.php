<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Rendering;

use Simtabi\Laranail\ErrorPages\Core\Contracts\Renderer;
use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

/**
 * The canonical, dependency-free HTML renderer: it renders the plain-PHP page
 * template (`presets/plain-php/template.php`) with the shared critical CSS
 * inlined, so a page is fully self-contained and renders even when a framework's
 * view engine or asset pipeline is the thing that failed. This is the guaranteed
 * fallback at the bottom of the bridge's stack ladder, and the renderer plain-PHP
 * / PSR-15 consumers use directly.
 */
final readonly class HtmlRenderer implements Renderer
{
    private string $templatePath;

    private string $cssPath;

    public function __construct(?string $templatePath = null, ?string $cssPath = null)
    {
        $presets = dirname(__DIR__, 3) . '/presets';
        $this->templatePath = $templatePath ?? $presets . '/plain-php/template.php';
        $this->cssPath = $cssPath ?? $presets . '/shared/critical.css';
    }

    public function render(ErrorPage $page, ThemeSettings $theme): string
    {
        $criticalCss = is_file($this->cssPath) ? (string) file_get_contents($this->cssPath) : '';

        return $this->capture($this->templatePath, [
            'page' => $page,
            'theme' => $theme,
            'criticalCss' => $criticalCss,
            'themeOverrideCss' => CssVariableMap::themeCss($theme),
        ]);
    }

    /**
     * Render the template with its context extracted into local scope. Passing the
     * values through `extract()` keeps every one visibly "used" so dead-code
     * tooling can't strip the data the required template depends on.
     *
     * @param  array<string, mixed>  $context  provides $page, $theme, $criticalCss, $themeOverrideCss
     */
    private function capture(string $template, array $context): string
    {
        $context['e'] = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        extract($context, EXTR_OVERWRITE);

        ob_start();
        require $template;
        $html = ob_get_clean();

        return $html === false ? '' : $html;
    }
}
