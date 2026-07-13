<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Wraps a failure inside our OWN renderer (never the original app exception, which
 * the framework already reported). Reporting this — not the original — is what
 * keeps the failure-safe degrade path from double-reporting against Sentry/Flare/
 * Bugsnag/Ignition. The cause is preserved for the descriptive report.
 */
final class ErrorPageRenderException extends RuntimeException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct(
            'Failed to render a branded error page: ' . $previous->getMessage(),
            previous: $previous,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'cause' => $this->getPrevious()?->getMessage(),
            'cause_type' => $this->getPrevious() instanceof Throwable ? $this->getPrevious()::class : null,
            'decision' => 'degraded-to-laravel-default',
        ];
    }
}
