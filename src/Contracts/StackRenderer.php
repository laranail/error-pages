<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders a branded error page for one front-end stack/context (json, inertia,
 * spa, or a consumer-registered driver). Returns a Response, or null when the
 * stack cannot render (e.g. its packages are not installed) so the handler can
 * degrade down the fallback ladder instead of throwing.
 */
interface StackRenderer
{
    public function render(Throwable $e, Request $request, int $status): ?Response;
}
