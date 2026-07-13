<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Stacks;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Throwable;

/**
 * The `filament` / `nova` panel context renderer. It tags the page with the
 * panel so admin panels get a branded error inside their chrome.
 *
 * Plumbing only for now: it renders the shared page with a `data-panel` marker.
 * Panel-matched theming (Filament::getCurrentPanel() colours / Nova branding),
 * the Filament `Plugin` + Nova tool, and automatic panel detection are layered on
 * with the visual design — they require those packages to be installed.
 */
final readonly class PanelStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
        private string $panel,
    ) {}

    public function render(Throwable $e, Request $request, int $status): Response
    {
        $html = str_replace(
            '<body class="ep-body',
            '<body data-panel="' . $this->panel . '" class="ep-body',
            $this->pages->htmlFor($e, $request),
        );

        return $this->responses->html($html, $status, $e);
    }
}
