<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Support;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;

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
 * shadowed.
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
     * The URL generator holds exactly one missing-route resolver, so a second
     * package installing one would silently replace the first. Any resolver
     * already installed is captured and consulted for every name this package
     * does not own, so registering this one never breaks another package's
     * fallback.
     */
    public static function registerBareNameFallback(UrlGenerator $url, Router $router): void
    {
        $previous = Closure::bind(
            static fn (UrlGenerator $generator): mixed => $generator->missingNamedRouteResolver,
            null,
            UrlGenerator::class,
        )($url);

        $url->resolveMissingNamedRoutesUsing(
            static function (string $name, mixed $parameters, ?bool $absolute) use ($url, $router, $previous): ?string {
                $scoped = self::DEPRECATED[$name] ?? null;

                if ($scoped !== null && $router->getRoutes()->hasNamedRoute($scoped)) {
                    trigger_error(
                        sprintf(
                            'The route name [%s] is deprecated and will stop resolving in the next minor after 0.1; use [%s].',
                            $name,
                            $scoped,
                        ),
                        E_USER_DEPRECATED,
                    );

                    return $url->route($scoped, $parameters ?? [], $absolute ?? true);
                }

                if (is_callable($previous)) {
                    $resolved = $previous($name, $parameters, $absolute);

                    return is_string($resolved) ? $resolved : null;
                }

                return null;
            },
        );
    }
}
