<?php

declare(strict_types=1);

namespace Simtabi\Laranail\LaravelErrorPages\Facades;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static \Simtabi\Laranail\LaravelErrorPages\ErrorPages stack(string $stack)
 * @method static \Simtabi\Laranail\LaravelErrorPages\ErrorPages theme(string $preset)
 * @method static \Simtabi\Laranail\LaravelErrorPages\ErrorPages context(Closure $resolver)
 * @method static \Simtabi\Laranail\LaravelErrorPages\ErrorPages skipWhen(callable $predicate)
 * @method static \Simtabi\Laranail\LaravelErrorPages\ErrorPages pipe(callable $stage)
 * @method static string htmlFor(Throwable $e, ?Request $request = null)
 * @method static array<string, mixed> jsonFor(Throwable $e, ?Request $request = null)
 * @method static string htmlForCode(int $code)
 *
 * @see \Simtabi\Laranail\LaravelErrorPages\ErrorPages
 */
final class ErrorPages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Simtabi\Laranail\LaravelErrorPages\ErrorPages::class;
    }
}
