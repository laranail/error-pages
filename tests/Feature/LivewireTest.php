<?php

declare(strict_types=1);

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
