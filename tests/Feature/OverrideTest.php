<?php

declare(strict_types=1);

use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

afterEach(function (): void {
    File::delete(resource_path('views/errors/404.blade.php'));
    File::delete(resource_path('views/errors/503.blade.php'));
});

it('publishes the error stubs into the app under a dedicated tag', function (): void {
    expect(array_keys(ServiceProvider::$publishGroups))
        ->toContain('laranail::server-error-pages-errors');

    $this->artisan('vendor:publish', ['--tag' => 'laranail::server-error-pages-errors', '--force' => true])
        ->assertSuccessful();

    expect(file_exists(resource_path('views/errors/404.blade.php')))->toBeTrue();
});

it('resolves errors::{code} to a published stub and lets an app custom view win', function (): void {
    $appErrors = resource_path('views/errors');
    File::ensureDirectoryExists($appErrors);

    // A published stub renders our branded page (via the facade).
    File::put($appErrors . '/503.blade.php', file_get_contents(
        dirname(__DIR__, 2) . '/resources/views/errors/503.blade.php',
    ));
    // An app fully-custom view wins outright.
    File::put($appErrors . '/404.blade.php', 'APP-OVERRIDE-404');

    (new RegisterErrorViewPaths)();

    expect(view()->exists('errors::503'))->toBeTrue()
        ->and(view('errors::503')->render())->toContain('Be right back')
        ->and(trim(view('errors::404')->render()))->toBe('APP-OVERRIDE-404');
});
