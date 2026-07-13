<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered;
use Simtabi\Laranail\ErrorPages\Events\RenderingErrorPage;

it('dispatches lifecycle events for a web (Path 1) error page', function (): void {
    Event::fake([RenderingErrorPage::class, ErrorPageRendered::class]);

    $this->get('/web-evt-missing')->assertStatus(404);

    Event::assertDispatched(RenderingErrorPage::class, fn (RenderingErrorPage $e): bool => $e->context === 'web' && $e->status === 404);
    Event::assertDispatched(ErrorPageRendered::class, fn (ErrorPageRendered $e): bool => $e->context === 'web' && $e->status === 404);
});

it('dispatches lifecycle events for an API (Path 2) error page', function (): void {
    Event::fake([RenderingErrorPage::class, ErrorPageRendered::class]);

    $this->getJson('/api-evt-missing')->assertStatus(404);

    Event::assertDispatched(RenderingErrorPage::class, fn (RenderingErrorPage $e): bool => $e->context === 'api' && $e->status === 404);
    Event::assertDispatched(ErrorPageRendered::class, fn (ErrorPageRendered $e): bool => $e->context === 'api' && $e->status === 404);
});
