<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

/**
 * Maps a request to a render context. The built-in detection returns `web`,
 * `api`, `inertia`, or an auto-detected panel (`filament`); a consumer override
 * (taking precedence) may return any custom context string that a registered
 * stack driver handles. Detection order: override → Filament panel → Inertia
 * header → explicit JSON → `api/*` (content-negotiated when enabled) → web.
 */
final class ContextResolver
{
    private ?Closure $override = null;

    public function __construct(
        private readonly PanelDetector $panels,
        private readonly Config $config,
    ) {}

    public function using(?Closure $override): void
    {
        $this->override = $override;
    }

    public function resolve(Request $request): string
    {
        if ($this->override instanceof Closure) {
            $context = ($this->override)($request);
            if (is_string($context) && $context !== '') {
                return $context;
            }
        }

        $panel = $this->panels->detect($request);
        if ($panel !== null) {
            return $panel;
        }

        if ($request->hasHeader('X-Inertia')) {
            return 'inertia';
        }

        // An explicit JSON client always gets problem+json.
        if ($request->expectsJson()) {
            return 'api';
        }

        if ($request->is('api/*')) {
            // Content negotiation: a browser hitting an API URL gets the branded
            // page instead of raw JSON, when enabled. Require an EXPLICIT
            // `text/html` in the Accept header — a JSON client is already handled
            // by `expectsJson()` above, and a `*/*` default (curl/Guzzle) must
            // stay JSON, so `prefers()` (which resolves `*/*` to text/html) is not
            // safe here.
            if ((bool) $this->config->get('error-pages.content_negotiation', false)
                && str_contains(strtolower((string) $request->header('Accept', '')), 'text/html')) {
                return 'web';
            }

            return 'api';
        }

        return 'web';
    }
}
