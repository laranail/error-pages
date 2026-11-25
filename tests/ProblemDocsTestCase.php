<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Tests;

use Illuminate\Foundation\Application;
use Override;

/**
 * The problem-docs route is registered at boot (gated on `problem.docs.enabled`),
 * so its tests need the flag set before the app boots.
 */
class ProblemDocsTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('error-pages.problem.docs.enabled', true);
        $app['config']->set('app.url', 'https://api.example');
    }
}
