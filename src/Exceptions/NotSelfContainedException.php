<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Exceptions;

use RuntimeException;

/**
 * Thrown when a generated static page would load an external resource (CDN,
 * remote stylesheet/script/image). Static pages must be fully self-contained
 * so they render even when the app/network is degraded.
 */
final class NotSelfContainedException extends RuntimeException
{
    /**
     * @param  list<string>  $violations
     */
    public function __construct(
        public readonly string $key,
        public readonly array $violations,
    ) {
        parent::__construct(sprintf(
            'Error page "%s" is not self-contained; external references found: %s',
            $key,
            implode('; ', $violations),
        ));
    }
}
