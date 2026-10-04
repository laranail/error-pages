<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\ErrorPages\Support\RouteNames;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Simtabi\Laranail\ErrorPages\Providers\ErrorPagesServiceProvider;

/*
 * Guards the names this package registers by reading the LIVE registries of a
 * booted application (router, rate limiter, console kernel, middleware map),
 * not the provider source. Runs under ProblemDocsTestCase so every route the
 * package can register is on: problem docs, assets (default `route` mode) and
 * the preview gallery.
 */

/**
 * Routes whose action is a class in this package.
 *
 * @return list<Route>
 */
function errorPagesOwnedRoutes(): array
{
    return array_values(array_filter(
        app('router')->getRoutes()->getRoutes(),
        static function (Route $route): bool {
            $action = $route->getAction('uses');
            $class = is_string($action) ? explode('@', $action)[0] : '';

            return str_starts_with($class, 'Simtabi\\Laranail\\ErrorPages\\');
        },
    ));
}

it('inspects every route the package registers (non-vacuous)', function (): void {
    expect(errorPagesOwnedRoutes())->toHaveCount(4);
});

it('registers only vendor-scoped route names', function (): void {
    $names = array_map(static fn (Route $route): ?string => $route->getName(), errorPagesOwnedRoutes());

    expect($names)->each->toStartWith('laranail-error-pages.');

    expect($names)->toEqualCanonicalizing([
        RouteNames::PROBLEM,
        RouteNames::ASSETS,
        RouteNames::PREVIEW_GALLERY,
        RouteNames::PREVIEW,
    ]);
});

it('registers no bare rate limiter', function (): void {
    $limiters = (fn (): array => $this->limiters)->call(app(RateLimiter::class));

    foreach (array_keys($limiters) as $name) {
        expect($name)->toStartWith('laranail-error-pages.');
    }

    expect(true)->toBeTrue();
});

it('registers only vendor-scoped commands, with no bare alias', function (): void {
    $owned = array_filter(
        Artisan::all(),
        static fn (object $command): bool => str_starts_with($command::class, 'Simtabi\\Laranail\\ErrorPages\\'),
    );

    expect($owned)->not->toBeEmpty();

    foreach ($owned as $command) {
        expect($command->getName())->toStartWith('laranail::error-pages.');

        foreach ($command->getAliases() as $alias) {
            expect($alias)->toStartWith('laranail::error-pages.');
        }
    }
});

it('registers no bare middleware alias', function (): void {
    $owned = array_filter(
        app('router')->getMiddleware(),
        static fn (string $class): bool => str_starts_with($class, 'Simtabi\\Laranail\\ErrorPages\\'),
    );

    foreach (array_keys($owned) as $alias) {
        expect($alias)->toStartWith('laranail-error-pages');
    }

    expect(true)->toBeTrue();
});

it('still resolves every deprecated bare route name, with a deprecation naming the replacement', function (): void {
    $deprecations = [];

    set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        expect(route('error-pages.problem', ['code' => 404]))->toBe(route(RouteNames::PROBLEM, ['code' => 404]))
            ->and(route('error-pages.assets', ['file' => 'error-pages.js']))->toBe(route(RouteNames::ASSETS, ['file' => 'error-pages.js']))
            ->and(route('error-pages.preview.gallery'))->toBe(route(RouteNames::PREVIEW_GALLERY))
            ->and(route('error-pages.preview', ['code' => 500]))->toBe(route(RouteNames::PREVIEW, ['code' => 500]));
    } finally {
        restore_error_handler();
    }

    expect($deprecations)->toHaveCount(4);

    foreach (RouteNames::DEPRECATED as $bare => $scoped) {
        expect(implode("\n", $deprecations))->toContain("[{$bare}]")->toContain("[{$scoped}]");
    }
});

it('does not shadow an application route of a bare name', function (): void {
    app('router')->get('/mine', static fn (): string => 'mine')->name('error-pages.preview');
    app('router')->getRoutes()->refreshNameLookups();

    expect(route('error-pages.preview'))->toEndWith('/mine');
});

it('chains to a missing-route resolver installed before it', function (): void {
    URL::resolveMissingNamedRoutesUsing(
        static fn (string $name): ?string => $name === 'other-package.home' ? 'https://other.example/home' : null,
    );

    $provider = new ErrorPagesServiceProvider(app());
    new ReflectionMethod($provider, 'registerBareRouteNames')->invoke($provider);

    expect(route('other-package.home'))->toBe('https://other.example/home');

    set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);

    try {
        expect(route('error-pages.preview.gallery'))->toBe(route(RouteNames::PREVIEW_GALLERY));
    } finally {
        restore_error_handler();
    }

    expect(fn (): string => route('nobody.owns.this'))->toThrow(RouteNotFoundException::class);
    expect(app('url'))->toBeInstanceOf(UrlGenerator::class);
});
