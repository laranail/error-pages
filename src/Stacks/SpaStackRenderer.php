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
 * The Vue/React SPA context renderer: serves the self-contained branded page and
 * embeds the error payload as JSON (`#error-page-data`) so the shipped
 * `@laranail/error-pages-ui` component can hydrate/take over on the client. Works
 * with no external request; the visual component is layered on top later.
 */
final readonly class SpaStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
    ) {}

    public function render(Throwable $e, Request $request, int $status): Response
    {
        $html = $this->pages->htmlFor($e, $request);

        $payload = json_encode($this->pages->payloadFor($e, $request), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        $script = '<script id="error-page-data" type="application/json">' . ($payload === false ? '{}' : $payload) . '</script>';

        $html = str_contains($html, '</body>')
            ? str_replace('</body>', $script . '</body>', $html)
            : $html . $script;

        return $this->responses->html($html, $status, $e);
    }
}
