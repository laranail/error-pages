<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Enums\Stack;
use Simtabi\Laranail\ErrorPages\Http\ErrorPageHandler;
use Simtabi\Laranail\ErrorPages\Http\RenderContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('routes a web request under an Inertia stack to the inertia renderer', function (): void {
    $ctx = RenderContext::make(new RuntimeException, Request::create('/x'), 'web', Stack::InertiaVue);

    expect($ctx->rendererKey())->toBe('inertia');
});

it('routes a web request under an SPA stack to the spa renderer', function (): void {
    $ctx = RenderContext::make(new RuntimeException, Request::create('/x'), 'web', Stack::Vue);

    expect($ctx->rendererKey())->toBe('spa');
});

it('maps api to json and inertia to inertia regardless of the configured stack', function (): void {
    expect(RenderContext::make(new RuntimeException, Request::create('/x'), 'api', Stack::Blade)->rendererKey())->toBe('json')
        ->and(RenderContext::make(new RuntimeException, Request::create('/x'), 'inertia', Stack::Blade)->rendererKey())->toBe('inertia');
});

it('maps a custom context to a same-named driver key', function (): void {
    expect(RenderContext::make(new RuntimeException, Request::create('/x'), 'filament', Stack::Blade)->rendererKey())->toBe('filament');
});

it('derives status from an HttpException, else 500', function (): void {
    expect(RenderContext::make(new NotFoundHttpException, Request::create('/x'), 'web', Stack::Blade)->status)->toBe(404)
        ->and(RenderContext::make(new RuntimeException, Request::create('/x'), 'web', Stack::Blade)->status)->toBe(500);
});

it('registers the package view path exactly once, even across workers (Octane-safe)', function (): void {
    $config = app('config');

    $package = static fn (array $paths): int => count(array_filter(
        $paths,
        static fn (string $p): bool => str_ends_with($p, 'error-pages/resources/views'),
    ));

    // The provider already booted once.
    expect($package((array) $config->get('view.paths')))->toBe(1);

    // A second worker re-registering into the same shared config must not duplicate.
    $handler = new ErrorPageHandler(app(), $config);
    $handler->register();
    $handler->register();

    expect($package((array) $config->get('view.paths')))->toBe(1);
});
