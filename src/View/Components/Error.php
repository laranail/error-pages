<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\View\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Simtabi\Laranail\ErrorPages\ErrorPages;

/**
 * Embeddable Blade error fragment: `<x-error-pages::error :code="404" />` renders
 * the shared `ep-*` DOM (no document chrome) inside any view/layout — the Blade
 * parity for the Livewire embed. Resolve the content from a `:code`, a `:key`
 * ("4xx"/"5xx"), or a ready `:page` payload array.
 */
final class Error extends Component
{
    /** @var array<string, mixed> */
    public array $page;

    /**
     * @param  array<string, mixed>|null  $page
     */
    public function __construct(?int $code = null, ?string $key = null, ?array $page = null)
    {
        $pages = app(ErrorPages::class);

        $this->page = $page ?? match (true) {
            $code !== null => $pages->payloadForCode($code),
            $key !== null => $pages->payloadForKey($key),
            default => $pages->payloadForCode(500),
        };
    }

    public function render(): View
    {
        return app(ViewFactory::class)->make('error-pages::components.error', ['page' => $this->page]);
    }
}
