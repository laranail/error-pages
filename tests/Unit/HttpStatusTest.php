<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Enums\HttpStatus;

it('carries built-in default titles and messages via attributes', function (): void {
    expect(HttpStatus::NotFound->label())->toBe('Page not found')
        ->and(HttpStatus::NotFound->description())->toBe('The page you are looking for could not be found.')
        ->and(HttpStatus::ServiceUnavailable->label())->toBe('Be right back');
});

it('classifies client and server errors', function (): void {
    expect(HttpStatus::NotFound->isClientError())->toBeTrue()
        ->and(HttpStatus::NotFound->isServerError())->toBeFalse()
        ->and(HttpStatus::InternalServerError->isServerError())->toBeTrue()
        ->and(HttpStatus::InternalServerError->isClientError())->toBeFalse();
});

it('identifies retryable codes with a retry window', function (): void {
    expect(HttpStatus::ServiceUnavailable->isRetryable())->toBeTrue()
        ->and(HttpStatus::ServiceUnavailable->retryAfter())->toBe(15)
        ->and(HttpStatus::NotFound->isRetryable())->toBeFalse()
        ->and(HttpStatus::NotFound->retryAfter())->toBeNull();
});

it('maps codes to the generic fallback key', function (): void {
    expect(HttpStatus::Forbidden->fallbackKey())->toBe('4xx')
        ->and(HttpStatus::BadGateway->fallbackKey())->toBe('5xx')
        ->and(HttpStatus::fallbackKeyFor(418))->toBe('4xx')
        ->and(HttpStatus::fallbackKeyFor(599))->toBe('5xx');
});

it('exposes every configured status code', function (): void {
    $values = array_map(fn (HttpStatus $s): int => $s->value, HttpStatus::cases());

    expect($values)->toContain(400, 401, 403, 404, 419, 429, 500, 502, 503, 504);
});
