<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Color;
use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * The HTTP status codes this package renders. The `#[Label]` / `#[Description]`
 * attributes carry the built-in default title/message, so an unconfigured
 * install still produces sensible pages — they are the last link in the
 * content-resolution chain (JSON -> config -> enum default).
 *
 * `label()` / `description()` / `color()` / `options()` come from
 * {@see HasEnumeratorBehavior}.
 */
enum HttpStatus: int implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Bad request'), Description('The request could not be understood by the server.'), Color('warning')]
    case BadRequest = 400;

    #[Label('Unauthorized'), Description('You need to sign in to view this page.'), Color('warning')]
    case Unauthorized = 401;

    #[Label('Payment required'), Description('This resource requires a payment to access.'), Color('warning')]
    case PaymentRequired = 402;

    #[Label('Forbidden'), Description('You do not have permission to view this page.'), Color('warning')]
    case Forbidden = 403;

    #[Label('Page not found'), Description('The page you are looking for could not be found.'), Color('warning')]
    case NotFound = 404;

    #[Label('Page expired'), Description('Your session has expired. Please refresh and try again.'), Color('warning')]
    case PageExpired = 419;

    #[Label('Too many requests'), Description('You have made too many requests. Please slow down and try again shortly.'), Color('warning')]
    case TooManyRequests = 429;

    #[Label('Something went wrong'), Description('An unexpected error occurred on our side. We have been notified.'), Color('danger')]
    case InternalServerError = 500;

    #[Label('Bad gateway'), Description('The server received an invalid response upstream. Please try again shortly.'), Color('danger')]
    case BadGateway = 502;

    #[Label('Be right back'), Description('We are briefly offline for maintenance. Please try again in a few minutes.'), Color('info')]
    case ServiceUnavailable = 503;

    #[Label('Gateway timeout'), Description('The server took too long to respond. Please try again shortly.'), Color('danger')]
    case GatewayTimeout = 504;

    public function isClientError(): bool
    {
        return $this->value >= 400 && $this->value < 500;
    }

    public function isServerError(): bool
    {
        return $this->value >= 500;
    }

    /**
     * Codes worth auto-refreshing / offering a retry — transient by nature.
     */
    public function isRetryable(): bool
    {
        return in_array($this->value, [429, 502, 503, 504], true);
    }

    /**
     * A sensible default Retry-After / refresh window (seconds) for retryable
     * codes, else null.
     */
    public function retryAfter(): ?int
    {
        return $this->isRetryable() ? 15 : null;
    }

    /**
     * The generic fallback page key for this code ('4xx' or '5xx').
     */
    public function fallbackKey(): string
    {
        return $this->isServerError() ? '5xx' : '4xx';
    }

    /**
     * The generic fallback key for an arbitrary integer code.
     */
    public static function fallbackKeyFor(int $code): string
    {
        return $code >= 500 ? '5xx' : '4xx';
    }
}
