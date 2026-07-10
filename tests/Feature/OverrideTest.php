<?php

declare(strict_types=1);
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Support\Facades\File;

it('lets an app-published errors view win over the package view', function (): void {
    $appErrors = resource_path('views/errors');
    File::ensureDirectoryExists($appErrors);
    File::put($appErrors . '/404.blade.php', 'APP-OVERRIDE-404');

    (new RegisterErrorViewPaths)();

    expect(view()->exists('errors::404'))->toBeTrue();
    expect(trim(view('errors::404')->render()))->toBe('APP-OVERRIDE-404');

    // package view still fills a code the app did NOT override
    expect(view('errors::503')->render())->toContain('Be right back');

    File::delete($appErrors . '/404.blade.php');
});
