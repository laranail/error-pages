<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

it('registers all commands under both names', function (): void {
    $all = array_keys(app(Kernel::class)->all());
    $expected = [
        'server-error-pages:build', 'laranail::server-error-pages.build',
        'server-error-pages:export', 'laranail::server-error-pages.export',
        'server-error-pages:server-config', 'laranail::server-error-pages.server-config',
        'server-error-pages:clear', 'laranail::server-error-pages.clear',
        'server-error-pages:install',
    ];
    foreach ($expected as $name) {
        expect($all)->toContain($name);
    }
});

it('runs server-config preview and clear without error', function (): void {
    $this->artisan('server-error-pages:server-config')->assertSuccessful();
    $this->artisan('server-error-pages:clear')->assertSuccessful();
});
