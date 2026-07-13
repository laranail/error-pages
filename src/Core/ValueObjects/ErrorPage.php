<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\ValueObjects;

use Simtabi\Laranail\ErrorPages\Core\ErrorPageFactory;

/**
 * A single resolved error page: the status key, its representative HTTP code,
 * the end-user display copy, retry hints, and an optional correlation id shown
 * for support. Immutable; hydrated once by {@see ErrorPageFactory}
 * and rendered identically by every stack.
 */
final readonly class ErrorPage
{
    public function __construct(
        /** Status key used for the view/key: e.g. "404" or the generic "4xx". */
        public string $key,
        /** Representative HTTP status code (e.g. 404, or 500 for the generic 5xx page). */
        public int $code,
        public string $title,
        public string $message,
        public bool $retryable = false,
        public ?int $retryAfter = null,
        /** Correlation / request id surfaced to the user for support ("Reference: …"). */
        public ?string $requestId = null,
    ) {}

    /**
     * A copy of this page with a correlation id attached.
     */
    public function withRequestId(?string $requestId): self
    {
        return new self(
            $this->key,
            $this->code,
            $this->title,
            $this->message,
            $this->retryable,
            $this->retryAfter,
            $requestId,
        );
    }
}
