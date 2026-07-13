<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Rendering;

use Illuminate\Support\Manager;
use RuntimeException;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Http\ErrorResponseFactory;
use Simtabi\Laranail\ErrorPages\Stacks\InertiaStackRenderer;
use Simtabi\Laranail\ErrorPages\Stacks\JsonStackRenderer;
use Simtabi\Laranail\ErrorPages\Stacks\LivewireStackRenderer;
use Simtabi\Laranail\ErrorPages\Stacks\PanelStackRenderer;
use Simtabi\Laranail\ErrorPages\Stacks\SpaStackRenderer;

/**
 * The driver seam for Path-2 rendering: resolve a {@see StackRenderer} by key
 * (`json` | `inertia` | `spa`, plus any consumer-registered driver). Consumers
 * add stacks with `ErrorPages::extend('livewire', fn ($app) => …)`, which
 * delegates here — the idiomatic Laravel `Manager::extend()`.
 */
final class StackManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return 'json';
    }

    /**
     * Resolve a stack renderer by key (validates the driver is a StackRenderer).
     */
    public function renderer(string $key): StackRenderer
    {
        $renderer = $this->driver($key);

        if (! $renderer instanceof StackRenderer) {
            throw new RuntimeException("Error-pages stack driver [{$key}] must return a " . StackRenderer::class . '.');
        }

        return $renderer;
    }

    protected function createJsonDriver(): StackRenderer
    {
        return $this->container->make(JsonStackRenderer::class);
    }

    protected function createInertiaDriver(): StackRenderer
    {
        return $this->container->make(InertiaStackRenderer::class);
    }

    protected function createSpaDriver(): StackRenderer
    {
        return $this->container->make(SpaStackRenderer::class);
    }

    protected function createLivewireDriver(): StackRenderer
    {
        return $this->container->make(LivewireStackRenderer::class);
    }

    protected function createFilamentDriver(): StackRenderer
    {
        // Filament is server-HTML (Livewire) — render the branded HTML panel page.
        return $this->panelRenderer('filament');
    }

    protected function createNovaDriver(): StackRenderer
    {
        // Nova is Inertia-based: returning HTML to its X-Inertia requests breaks
        // the client ("must receive a valid Inertia response"). Render via Inertia
        // — the same path Nova's own exception handler uses.
        return $this->container->make(InertiaStackRenderer::class);
    }

    private function panelRenderer(string $panel): PanelStackRenderer
    {
        return new PanelStackRenderer(
            $this->container->make(ErrorPages::class),
            $this->container->make(ErrorResponseFactory::class),
            $panel,
        );
    }
}
