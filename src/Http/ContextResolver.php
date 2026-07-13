<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Closure;
use Illuminate\Http\Request;

/**
 * Maps a request to a render context. The built-in detection returns `web`,
 * `api`, or `inertia`; a consumer override (taking precedence) may return any
 * custom context string (e.g. `filament`, `nova`) that a registered stack driver
 * handles. Detection order: override → Inertia header → explicit JSON/`api/*` → web.
 */
final class ContextResolver
{
    private ?Closure $override = null;

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

        if ($request->hasHeader('X-Inertia')) {
            return 'inertia';
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return 'api';
        }

        return 'web';
    }
}
