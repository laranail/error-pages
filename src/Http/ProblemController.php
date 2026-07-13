<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use Simtabi\Laranail\ErrorPages\Core\Theme\CssVariableMap;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Support\ProblemDocs;

/**
 * Serves the human-readable problem-type page that an RFC 7807/9457 `type` URI
 * dereferences to (`GET {problem.docs.route}/{code}`). Unlike the dev-only
 * preview route this is a PUBLIC, production page (so API clients/developers can
 * open the `type` link), returning 200 with `noindex` — it documents the error
 * (what it means, common causes, how to fix), it is not itself an error response.
 */
final readonly class ProblemController
{
    public function __construct(
        private ErrorPages $pages,
        private ProblemDocs $docs,
        private ViewFactory $views,
    ) {}

    public function show(string $code): Response
    {
        $payload = ctype_digit($code)
            ? $this->pages->payloadForCode((int) $code)
            : $this->pages->payloadForKey($code);

        $cssPath = dirname(__DIR__, 2) . '/presets/shared/css/critical.css';

        $html = $this->views->make('error-pages::problems.show', [
            'page' => $payload,
            'doc' => $this->docs->for($code),
            'criticalCss' => is_file($cssPath) ? (string) file_get_contents($cssPath) : '',
            'themeCss' => CssVariableMap::themeCss($this->pages->themeSettings()),
        ])->render();

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
