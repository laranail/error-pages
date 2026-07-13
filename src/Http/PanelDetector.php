<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Http;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Throwable;

/**
 * Best-effort automatic panel-context detection, gated by the `panels.*` flags
 * and the panel package being installed. Detection is **path-scoped** — it only
 * matches requests under the panel's own path, so it can never hijack a normal
 * app route.
 *
 * Filament (server-HTML/Livewire) is auto-detected here. Nova is Inertia-based,
 * so it is left to the `inertia` stack / an explicit `ErrorPages::context()`
 * override rather than being force-routed to the HTML panel renderer.
 */
final readonly class PanelDetector
{
    public function __construct(
        private Config $config,
    ) {}

    public function detect(Request $request): ?string
    {
        if ((bool) $this->config->get('error-pages.panels.filament', true) && $this->onFilamentPanel($request)) {
            return 'filament';
        }

        return null;
    }

    private function onFilamentPanel(Request $request): bool
    {
        $facade = 'Filament\\Facades\\Filament';

        if (! class_exists($facade) || ! is_callable([$facade, 'getCurrentPanel'])) {
            return false;
        }

        try {
            $panel = call_user_func([$facade, 'getCurrentPanel']);
        } catch (Throwable) {
            return false;
        }

        if (! is_object($panel) || ! is_callable([$panel, 'getPath'])) {
            return false;
        }

        $path = call_user_func([$panel, 'getPath']);

        return is_string($path) && $this->requestWithin($request, $path);
    }

    private function requestWithin(Request $request, string $path): bool
    {
        $path = trim($path, '/');

        return $path !== '' && ($request->is($path) || $request->is($path . '/*'));
    }
}
