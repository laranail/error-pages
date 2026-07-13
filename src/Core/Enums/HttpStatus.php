<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Core\Enums;

/**
 * The HTTP status codes this engine renders, each carrying a built-in default
 * title (label), message (description), and severity (color) so an unconfigured
 * install still produces sensible pages — they are the last link in the
 * content-resolution chain (overrides -> enum default).
 *
 * A plain enum by design: the core has no framework dependency, so it cannot
 * lean on laranail/enumerator (which pulls in Illuminate). The bridge may expose
 * an enumerator-flavoured view on top of this.
 */
enum HttpStatus: int
{
    case BadRequest = 400;
    case Unauthorized = 401;
    case PaymentRequired = 402;
    case Forbidden = 403;
    case NotFound = 404;
    case PageExpired = 419;
    case TooManyRequests = 429;
    case InternalServerError = 500;
    case BadGateway = 502;
    case ServiceUnavailable = 503;
    case GatewayTimeout = 504;

    /**
     * The built-in default title for this status.
     */
    public function label(): string
    {
        return match ($this) {
            self::BadRequest => 'Bad request',
            self::Unauthorized => 'Unauthorized',
            self::PaymentRequired => 'Payment required',
            self::Forbidden => 'Forbidden',
            self::NotFound => 'Page not found',
            self::PageExpired => 'Page expired',
            self::TooManyRequests => 'Too many requests',
            self::InternalServerError => 'Something went wrong',
            self::BadGateway => 'Bad gateway',
            self::ServiceUnavailable => 'Be right back',
            self::GatewayTimeout => 'Gateway timeout',
        };
    }

    /**
     * The built-in default, end-user-safe message for this status.
     */
    public function description(): string
    {
        return match ($this) {
            self::BadRequest => 'The request could not be understood by the server.',
            self::Unauthorized => 'You need to sign in to view this page.',
            self::PaymentRequired => 'This resource requires a payment to access.',
            self::Forbidden => 'You do not have permission to view this page.',
            self::NotFound => 'The page you are looking for could not be found.',
            self::PageExpired => 'Your session has expired. Please refresh and try again.',
            self::TooManyRequests => 'You have made too many requests. Please slow down and try again shortly.',
            self::InternalServerError => 'An unexpected error occurred on our side. We have been notified.',
            self::BadGateway => 'The server received an invalid response upstream. Please try again shortly.',
            self::ServiceUnavailable => 'We are briefly offline for maintenance. Please try again in a few minutes.',
            self::GatewayTimeout => 'The server took too long to respond. Please try again shortly.',
        };
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
     * The generic fallback key for an arbitrary integer code ('4xx' or '5xx').
     */
    public static function fallbackKeyFor(int $code): string
    {
        return $code >= 500 ? '5xx' : '4xx';
    }
}
