<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Stacks;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The Inertia context renderer: `Inertia::render('ErrorPage', payload)` so the
 * app's Inertia + Vue/React stack renders the shipped component with the correct
 * status. Guarded — returns null when Inertia is not installed so the handler
 * degrades. (Inertia treats some non-2xx visits specially; document the caveat.)
 */
final readonly class InertiaStackRenderer implements StackRenderer
{
    public function __construct(
        private ErrorPages $pages,
        private ErrorResponseFactory $responses,
    ) {}

    public function render(Throwable $e, Request $request, int $status): ?Response
    {
        if (! class_exists(Inertia::class)) {
            return null;
        }

        $response = Inertia::render('ErrorPage', $this->pages->payloadFor($e, $request))->toResponse($request);
        $response->setStatusCode($status);

        return $this->responses->harden($response, $status, $e);
    }
}
