<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\ErrorPages\Providers\ErrorPagesServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ErrorPagesServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Production-style by default: branded pages take over, no Ignition.
        $app['config']->set('app.debug', false);

        // Testbench ships its own resources/views/errors/* which would shadow
        // ours at view-path index 0 (that models an app WITH custom error views).
        // Point the "app" view path at a clean dir so we exercise OUR fallback,
        // as a real app (no errors/ views) would.
        $app['config']->set('view.paths', [__DIR__ . '/fixtures/views']);
    }
}
