<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Facades;

use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\ServerErrorPages\Services\ServerErrorPagesManager;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ErrorPage;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * @method static ErrorPage page(int $code, ?string $locale = null)
 * @method static ErrorPage pageByKey(string $key, ?string $locale = null)
 * @method static string htmlFor(int $code, ?string $locale = null)
 * @method static string htmlForKey(string $key, ?string $locale = null)
 * @method static list<string> keys()
 * @method static ThemeSettings theme()
 *
 * @see ServerErrorPagesManager
 */
final class ServerErrorPages extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServerErrorPagesManager::class;
    }
}
