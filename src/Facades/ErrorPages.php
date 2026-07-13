<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Facades;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages stack(string $stack)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages theme(string $preset)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages context(Closure $resolver)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages nonce(Closure|string $nonce)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages skipWhen(callable $predicate)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages pipe(callable $stage)
 * @method static \Simtabi\Laranail\ErrorPages\ErrorPages fake()
 * @method static void assertRendered(int $code, ?string $stack = null, ?string $theme = null)
 * @method static void assertNothingRendered()
 * @method static string htmlFor(Throwable $e, ?Request $request = null)
 * @method static string renderForWeb(Throwable $e, ?Request $request = null)
 * @method static array<string, mixed> jsonFor(Throwable $e, ?Request $request = null)
 * @method static array<string, mixed> payloadFor(Throwable $e, ?Request $request = null)
 * @method static array<string, mixed> payloadForCode(int $code)
 * @method static array<string, mixed> payloadForKey(string $key)
 * @method static string htmlForCode(int $code)
 * @method static string htmlForKey(string $key)
 *
 * @see \Simtabi\Laranail\ErrorPages\ErrorPages
 */
final class ErrorPages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Simtabi\Laranail\ErrorPages\ErrorPages::class;
    }
}
