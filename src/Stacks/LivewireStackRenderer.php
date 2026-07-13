<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Stacks;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Livewire\Livewire;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Throwable;

/**
 * The `livewire` stack: render a full-page Livewire ErrorPage component (Livewire
 * 4+). Returns null when Livewire is not installed so the handler degrades down
 * the fallback ladder to the core HTML page. The critical CSS is inlined; Livewire
 * loads its own Alpine, so the package enhancement JS is not added here.
 */
final readonly class LivewireStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
        private ViewFactory $views,
    ) {}

    public function render(Throwable $e, Request $request, int $status): ?Response
    {
        if (! class_exists(Livewire::class)) {
            return null;
        }

        $theme = $this->pages->themeSettings();
        $cssPath = dirname(__DIR__, 2) . '/presets/shared/critical.css';

        $html = $this->views->make('error-pages::livewire.page', [
            'page' => $this->pages->payloadFor($e, $request),
            'criticalCss' => is_file($cssPath) ? (string) file_get_contents($cssPath) : '',
            'themeCss' => CssVariableMap::themeCss($theme),
        ])->render();

        return $this->responses->html($html, $status, $e);
    }
}
