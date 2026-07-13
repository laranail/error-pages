<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Tests;

use Illuminate\Foundation\Application;
use Inertia\ServiceProvider;
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
        $providers = [ErrorPagesServiceProvider::class];

        // Inertia is a dev-dep (to exercise the inertia stack); its context is
        // only active when a request carries the X-Inertia header, so loading it
        // here is inert for the other tests.
        if (class_exists(ServiceProvider::class)) {
            $providers[] = ServiceProvider::class;
        }

        return $providers;
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
