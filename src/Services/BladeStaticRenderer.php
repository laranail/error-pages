<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ServerErrorPages\Services;

use Illuminate\Support\Facades\Blade;
use Simtabi\Laranail\ServerErrorPages\Contracts\StaticRenderer;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ErrorPage;
use Simtabi\Laranail\ServerErrorPages\ValueObjects\ThemeSettings;

/**
 * Renders the shared `<x-server-error-pages::layout>` anonymous component to a
 * string via {@see Blade::render()}. Deliberately does NOT resolve the
 * `errors::` view namespace (which Laravel only registers mid-exception), so
 * this works identically in an HTTP request and in a console build command.
 */
final class BladeStaticRenderer implements StaticRenderer
{
    public function render(ErrorPage $page, ThemeSettings $theme): string
    {
        return trim(Blade::render(
            '<x-server-error-pages::layout :page="$page" :theme="$theme" />',
            ['page' => $page, 'theme' => $theme],
        )) . "\n";
    }
}
