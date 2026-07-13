<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Http\Response;
use Simtabi\Laranail\ErrorPages\ErrorPages;

/**
 * Serves the human-readable problem-type page that an RFC 7807/9457 `type` URI
 * dereferences to (`GET {problem.docs.route}/{code}`). Unlike the dev-only
 * preview route this is a PUBLIC, production page (so API clients/developers can
 * open the `type` link), returning 200 with `noindex` — it documents the error,
 * it is not itself an error response.
 */
final readonly class ProblemController
{
    public function __construct(
        private ErrorPages $pages,
    ) {}

    public function show(string $code): Response
    {
        $html = ctype_digit($code)
            ? $this->pages->htmlForCode((int) $code)
            : $this->pages->htmlForKey($code);

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
