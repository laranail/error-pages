<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Events;

use Throwable;

/**
 * Dispatched just before a branded error page is rendered (any context/stack).
 * An observation/telemetry hook — to influence the page, use the DSL's `pipe()`
 * (enrich), `skipWhen()` (veto), or a stack driver instead.
 */
final readonly class RenderingErrorPage
{
    public function __construct(
        public Throwable $exception,
        public string $context,
        public int $status,
    ) {}
}
