<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Stacks;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Throwable;

/**
 * The `livewire` stack: render the Livewire ErrorPage component (Livewire 4+). By
 * default it is a self-contained full page; set `error-pages.livewire.layout` to
 * embed the component inside YOUR own component layout instead (for apps that
 * don't use full-page Livewire). Returns null when Livewire is not installed so
 * the handler degrades to the core HTML page. Livewire loads its own Alpine, so
 * the package enhancement JS is not added here.
 */
final readonly class LivewireStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
        private ViewFactory $views,
        private Config $config,
    ) {}

    public function render(Throwable $e, Request $request, int $status): ?Response
    {
        if (! class_exists(Livewire::class)) {
            return null;
        }

        $payload = $this->pages->payloadFor($e, $request);
        $layout = $this->config->get('error-pages.livewire.layout');

        $html = is_string($layout) && $layout !== ''
            ? $this->renderInLayout($layout, $payload)
            : $this->renderStandalone($payload);

        return $this->responses->html($html, $status, $e);
    }

    /**
     * Embed the component in the consumer's component layout (which supplies the
     * chrome + @livewireStyles/@livewireScripts).
     *
     * @param  array<string, mixed>  $payload
     */
    private function renderInLayout(string $layout, array $payload): string
    {
        $slot = $this->views->make('error-pages::livewire.slot', ['page' => $payload])->render();

        return $this->views->make($layout, ['slot' => new HtmlString($slot)])->render();
    }

    /**
     * The package's self-contained full page (critical CSS inlined).
     *
     * @param  array<string, mixed>  $payload
     */
    private function renderStandalone(array $payload): string
    {
        $cssPath = dirname(__DIR__, 2) . '/presets/shared/critical.css';

        return $this->views->make('error-pages::livewire.page', [
            'page' => $payload,
            'criticalCss' => is_file($cssPath) ? (string) file_get_contents($cssPath) : '',
            'themeCss' => CssVariableMap::themeCss($this->pages->themeSettings()),
        ])->render();
    }
}
