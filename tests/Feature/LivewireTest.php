<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Livewire\ErrorPage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('renders the Livewire ErrorPage component with the shared DOM', function (): void {
    Livewire::test(ErrorPage::class, ['page' => app(ErrorPages::class)->payloadFor(
        new NotFoundHttpException,
    )])
        ->assertSee('404')
        ->assertSeeHtml('class="ep-status"')
        ->assertSeeHtml('class="ep-title"');
});

it('renders a full-page Livewire error for the livewire stack', function (): void {
    config()->set('error-pages.stack', 'livewire');

    $response = $this->get('/livewire-missing');

    $response->assertStatus(404);
    expect($response->getContent())
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->toContain('wire:'); // Livewire rendered the component (wire: attributes present)
});

it('renders the livewire stack inside a configured app layout', function (): void {
    config()->set('error-pages.stack', 'livewire');
    config()->set('error-pages.livewire.layout', 'error-layout');

    $response = $this->get('/livewire-layout-missing');

    $response->assertStatus(404);
    expect($response->getContent())
        ->toContain('id="app-chrome"')   // the app layout chrome wraps it
        ->toContain('class="ep-status"') // the embedded component
        ->toContain('>404<');
});

it('auto-refreshes a retryable livewire page (parity with blade/spa)', function (): void {
    config()->set('error-pages.stack', 'livewire');
    Route::get('/lw-down', fn () => abort(503));

    $response = $this->get('/lw-down');

    $response->assertStatus(503);
    expect($response->getContent())->toContain('http-equiv="refresh"');
});

it('exposes the livewire views under a publishable, overridable namespace', function (): void {
    // Registered via package-tools hasViews('error-pages'); consumers publish +
    // customise them with `vendor:publish --tag=laranail::error-pages-views`.
    expect(view()->exists('error-pages::livewire.error-page'))->toBeTrue()
        ->and(view()->exists('error-pages::livewire.page'))->toBeTrue();
});
