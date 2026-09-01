<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core;

use Simtabi\Laranail\ErrorPages\Core\Contracts\ContentRepository;
use Simtabi\Laranail\ErrorPages\Core\Enums\HttpStatus;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;

/**
 * Resolves a status key to an {@see ErrorPage} via one content chain: caller
 * overrides (through the {@see ContentRepository}) then built-in `HttpStatus`
 * enum defaults. Framework-agnostic — the Laravel bridge feeds it a translation
 * repository, plain-PHP consumers feed it an array repository. The output is
 * identical across every stack.
 */
final readonly class ErrorPageFactory
{
    public function __construct(
        private ContentRepository $content,
    ) {}

    /**
     * Build the page for a status key: a numeric code ("404") or a generic
     * fallback ("4xx" / "5xx").
     */
    public function makeByKey(string $key, ?string $locale = null): ErrorPage
    {
        return $key === '4xx' || $key === '5xx'
            ? $this->generic($key, $locale)
            : $this->make((int) $key, $locale);
    }

    /**
     * Build the page for an HTTP status code. A code with a `HttpStatus` case
     * uses the enum defaults; a code without one keeps its own number and falls
     * back to generic copy — honouring any per-code override first, then the
     * class-level (4xx/5xx) override.
     */
    public function make(int $code, ?string $locale = null): ErrorPage
    {
        $status = HttpStatus::tryFrom($code);

        return $this->build(
            displayKey: (string) $code,
            code: $code,
            locale: $locale,
            status: $status,
            classKey: $status === null ? HttpStatus::fallbackKeyFor($code) : null,
        );
    }

    /**
     * The generic catch-all page for a class of codes ("4xx" / "5xx").
     */
    public function generic(string $key, ?string $locale = null): ErrorPage
    {
        return $this->build(
            displayKey: $key,
            code: $key === '5xx' ? 500 : 400,
            locale: $locale,
            status: null,
            classKey: null,
        );
    }

    private function build(string $displayKey, int $code, ?string $locale, ?HttpStatus $status, ?string $classKey): ErrorPage
    {
        $override = $this->content->overridesFor($displayKey, $locale);

        // For a code without its own enum case, also consult the class-level
        // ("4xx"/"5xx") override before falling back to generic copy.
        if ($classKey !== null) {
            $classOverride = $this->content->overridesFor($classKey, $locale);
            $override['title'] ??= $classOverride['title'];
            $override['message'] ??= $classOverride['message'];
        }

        $isServer = $code >= 500;
        $retryable = $status?->isRetryable() ?? $isServer;

        return new ErrorPage(
            key: $displayKey,
            code: $code,
            title: $override['title'] ?? $status?->label() ?? ($isServer ? 'Something went wrong' : 'This page is unavailable'),
            message: $override['message'] ?? $status?->description() ?? ($isServer
                ? 'An unexpected error occurred on our side. Please try again shortly.'
                : 'The page could not be displayed. Please check the address and try again.'),
            retryable: $retryable,
            retryAfter: $retryable ? ($status?->retryAfter() ?? 15) : null,
        );
    }
}
