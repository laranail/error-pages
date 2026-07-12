<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\ValueObjects;

/**
 * A single resolved error page: the status key, its representative HTTP code,
 * and the display copy. Immutable; hydrated once by the error-page factory and
 * shared by the dynamic (Blade) and static (build) renders.
 */
final readonly class ErrorPage
{
    public function __construct(
        /** Status key used for the filename/view: e.g. "404" or the generic "4xx". */
        public string $key,
        /** Representative HTTP status code (e.g. 404, or 400 for the generic 4xx page). */
        public int $code,
        public string $title,
        public string $message,
        public bool $retryable = false,
        public ?int $retryAfter = null,
    ) {}

    /**
     * True for the generic catch-all pages ("4xx" / "5xx").
     */
    public function isGeneric(): bool
    {
        return $this->key === '4xx' || $this->key === '5xx';
    }
}
