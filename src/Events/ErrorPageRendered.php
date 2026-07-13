<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Events;

use Throwable;

/**
 * Dispatched after a branded error page has been rendered for the response.
 * Use it for telemetry — "which error pages are being served, and how".
 */
final readonly class ErrorPageRendered
{
    public function __construct(
        public Throwable $exception,
        public string $context,
        public int $status,
    ) {}
}
