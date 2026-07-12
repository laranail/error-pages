<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\ServerErrorPages\Providers\ServerErrorPagesServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ServerErrorPagesServiceProvider::class];
    }
}
