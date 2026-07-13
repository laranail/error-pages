<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Closure;
use Illuminate\Http\Request;

/**
 * Maps a request to a render context. The built-in detection returns `web`,
 * `api`, `inertia`, or an auto-detected panel (`filament`); a consumer override
 * (taking precedence) may return any custom context string that a registered
 * stack driver handles. Detection order: override → Filament panel → Inertia
 * header → explicit JSON/`api/*` → web.
 */
final class ContextResolver
{
    private ?Closure $override = null;

    public function __construct(
        private readonly PanelDetector $panels,
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

        if ($request->expectsJson() || $request->is('api/*')) {
            return 'api';
        }

        return 'web';
    }
}
