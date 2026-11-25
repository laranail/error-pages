<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\UrlGenerator;
use Simtabi\Laranail\ErrorPages\Support\RouteNames;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\NameRegistry;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;
use Simtabi\Laranail\ErrorPages\Providers\ErrorPagesServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;

/*
 * Guards the names this package registers by reading the LIVE registries of a
 * booted application, through package-tools' shared AssertsRegisteredNames,
 * not the provider source. Runs under ProblemDocsTestCase so every route the
 * package can register is on: problem docs, assets (default `route` mode) and
 * the preview gallery.
 */

uses(AssertsRegisteredNames::class);

/**
 * The scope the shared assertions judge names against.
 *
 * The base path is narrowed to one directory because the default (the package
 * root) contains this checkout's own vendor/ when the package is the root
 * project, which would claim every framework closure binding as the
 * package's. Code lives in src/, views and translations in resources/.
 */
function errorPagesScope(string $directory = 'src'): NamingScope
{
    return NamingScope::for(
        package: 'laranail/error-pages',
        ownerNamespace: 'Simtabi\\Laranail\\ErrorPages\\',
        basePath: dirname(__DIR__, 2) . '/' . $directory,
        // D2: the full-page Livewire component keeps its singular name, a
        // sanctioned vendor-scoped variant.
        prefixes: [NameRegistry::Livewire->value => ['laranail-error-pages', 'laranail-error-page']],
    );
}

/**
 * Capture E_USER_DEPRECATED messages raised while $callback runs.
 *
 * @return list<string>
 */
function errorPagesDeprecations(Closure $callback): array
{
    $deprecations = [];

    set_error_handler(static function (int $level, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $callback();
    } finally {
        restore_error_handler();
    }

    return $deprecations;
}

beforeEach(function (): void {
    BareRouteNameAliases::forgetWarnings();
});

it('inspects every route the package registers (non-vacuous)', function (): void {
    expect($this->assertRouteNamesScoped(errorPagesScope(), atLeast: 4))->toHaveCount(4);
});

it('registers only vendor-scoped route names', function (): void {
    expect($this->assertRouteNamesScoped(errorPagesScope(), atLeast: 4))->toEqualCanonicalizing([
        RouteNames::PROBLEM,
        RouteNames::ASSETS,
        RouteNames::PREVIEW_GALLERY,
        RouteNames::PREVIEW,
    ]);
});

it('registers no bare rate limiter', function (): void {
    expect($this->assertRateLimitersScoped(errorPagesScope(), atLeast: 0))->toBe([]);
});

it('registers only vendor-scoped commands, with no bare alias', function (): void {
    expect($this->assertCommandNamesScoped(errorPagesScope(), atLeast: 1))
        ->toContain('laranail::error-pages.preview');
});

it('registers no bare middleware alias', function (): void {
    expect($this->assertMiddlewareAliasesScoped(errorPagesScope(), atLeast: 0))->toBe([]);
});

it('registers views under the canonical slash namespace and the hyphen alias', function (): void {
    expect($this->assertViewNamespacesScoped(errorPagesScope('resources'), atLeast: 2))
        ->toContain('laranail/error-pages', 'laranail-error-pages');

    expect(view()->exists('laranail/error-pages::components.error'))->toBeTrue()
        ->and(view()->exists('laranail-error-pages::components.error'))->toBeTrue();
});

it('finds a view a host published under the hyphen namespace through the slash namespace', function (): void {
    expect(trim(view('laranail/error-pages::host-override')->render()))->toBe('host-override');
});

it('registers translations, Blade components, Livewire components and container aliases scoped', function (): void {
    $scope = errorPagesScope();

    expect($this->assertTranslationNamespacesScoped(errorPagesScope('resources'), atLeast: 2))
        ->toContain('laranail/error-pages', 'laranail-error-pages')
        ->and($this->assertBladeComponentsScoped($scope, atLeast: 1))->not->toBeEmpty()
        ->and($this->assertLivewireComponentsScoped($scope, atLeast: 1))->toContain('laranail-error-page')
        ->and($this->assertContainerAliasesScoped($scope, atLeast: 1))->toContain('laranail.error-pages');
});

it('still resolves every deprecated bare route name, with a deprecation naming the replacement', function (): void {
    $parameters = [
        'error-pages.problem' => ['code' => 404],
        'error-pages.assets'  => ['file' => 'error-pages.js'],
        'error-pages.preview' => ['code' => 500],
    ];

    $this->assertDeprecatedRouteNamesResolve(RouteNames::DEPRECATED, $parameters);

    BareRouteNameAliases::forgetWarnings();

    $deprecations = errorPagesDeprecations(static function () use ($parameters): void {
        foreach (RouteNames::DEPRECATED as $bare => $scoped) {
            expect(route($bare, $parameters[$bare] ?? []))->toBe(route($scoped, $parameters[$bare] ?? []));
        }
    });

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

    // Re-run the installation package-tools performs at boot, now that a
    // foreign resolver is in place.
    app()->getProvider(ErrorPagesServiceProvider::class)->package->bootPackageDeprecatedRouteNames(
        app('router'),
        app(UrlGenerator::class),
        static fn (): LoggerInterface => app(LoggerInterface::class),
    );

    expect(route('other-package.home'))->toBe('https://other.example/home');

    errorPagesDeprecations(static function (): void {
        expect(route('error-pages.preview.gallery'))->toBe(route(RouteNames::PREVIEW_GALLERY));
    });

    expect(fn (): string => route('nobody.owns.this'))->toThrow(RouteNotFoundException::class);
    expect(app('url'))->toBeInstanceOf(UrlGenerator::class);
});

it('keeps the deprecated RouteNames::registerBareNameFallback() working, with a deprecation', function (): void {
    URL::resolveMissingNamedRoutesUsing(static fn (): ?string => null);

    $deprecations = errorPagesDeprecations(static function (): void {
        RouteNames::registerBareNameFallback(app(UrlGenerator::class), app('router'));

        expect(route('error-pages.preview.gallery'))->toBe(route(RouteNames::PREVIEW_GALLERY));
    });

    expect(implode("\n", $deprecations))
        ->toContain('registerBareNameFallback() is deprecated')
        ->toContain('hasDeprecatedRouteNames')
        ->toContain('[error-pages.preview.gallery]');
});
