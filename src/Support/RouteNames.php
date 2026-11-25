<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;

/**
 * The vendor-scoped names of the routes this package registers, and the
 * fallback that keeps the bare names they replaced resolving.
 *
 * Route names live in one flat, application-wide registry, so a bare
 * `error-pages.preview` collides with any sibling or application route of the
 * same name and the later registration silently wins. The scoped names below
 * are what the package registers; the bare ones still resolve through
 * `URL::resolveMissingNamedRoutesUsing()`, which Laravel consults only when a
 * name is NOT found, so an application's own route of that name is never
 * shadowed. The provider declares {@see self::DEPRECATED} through
 * `hasDeprecatedRouteNames()`, and package-tools' {@see BareRouteNameAliases}
 * installs that fallback at boot.
 */
final class RouteNames
{
    public const string PREFIX = 'laranail-error-pages.';

    public const string PROBLEM = self::PREFIX . 'problem';

    public const string ASSETS = self::PREFIX . 'assets';

    public const string PREVIEW_GALLERY = self::PREFIX . 'preview.gallery';

    public const string PREVIEW = self::PREFIX . 'preview';

    /**
     * Deprecated bare name => scoped replacement.
     *
     * Each bare name is a deprecated alias that still resolves (with an
     * `E_USER_DEPRECATED`); the earliest release that may stop resolving them
     * is the next minor after 0.1. The map itself is not deprecated.
     *
     * @var array<string, string>
     */
    public const array DEPRECATED = [
        'error-pages.problem'         => self::PROBLEM,
        'error-pages.assets'          => self::ASSETS,
        'error-pages.preview.gallery' => self::PREVIEW_GALLERY,
        'error-pages.preview'         => self::PREVIEW,
    ];

    /**
     * Make each deprecated bare name resolve to its scoped route, emitting an
     * `E_USER_DEPRECATED` that names the replacement.
     *
     * Delegates to package-tools' shared fallback, which chains any resolver
     * already installed, accepts only a string from it, and announces each
     * bare name once per process.
     *
     * @deprecated since 0.1, removable no earlier than the next minor after 0.1.
     *             The package declares its deprecated names with
     *             `hasDeprecatedRouteNames(map: RouteNames::DEPRECATED)` and
     *             package-tools installs the fallback at boot; call
     *             {@see BareRouteNameAliases::install()} to install one by hand.
     */
    public static function registerBareNameFallback(UrlGenerator $url, Router $router): void
    {
        trigger_error(
            sprintf(
                '%s::registerBareNameFallback() is deprecated and will be removed no earlier than the next minor after 0.1; '
                . 'declare hasDeprecatedRouteNames(map: %s::DEPRECATED) on the package, or call %s::install().',
                self::class,
                self::class,
                BareRouteNameAliases::class,
            ),
            E_USER_DEPRECATED,
        );

        BareRouteNameAliases::install(
            router: $router,
            url: $url,
            package: 'laranail/error-pages',
            map: self::DEPRECATED,
        );
    }
}
