<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Livewire;

use Livewire\Component;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * The full-page Livewire 4 error component (the `livewire` stack). It receives the
 * one payload array ({@see ErrorPages::payloadFor()})
 * and renders the shared DOM contract, so a Livewire app's error page matches every
 * other stack. Registered as `laranail-error-page` only when Livewire is installed.
 */
final class ErrorPage extends Component
{
    /** @var array<string, mixed> */
    public array $page = [];

    /**
     * @param array<string, mixed> $page
     */
    public function mount(array $page): void
    {
        $this->page = $page;
    }

    public function render(): View
    {
        return app(ViewFactory::class)->make('laranail-error-pages::livewire.error-page', ['page' => $this->page]);
    }
}
