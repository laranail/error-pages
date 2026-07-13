<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Exceptions;
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\ErrorPages;
use Simtabi\Laranail\ErrorPages\Exceptions\ErrorPageRenderException;

/**
 * Register a stack renderer that always throws, so the handler's failure path runs.
 */
function registerThrowingStack(): void
{
    app(ErrorPages::class)
        ->extend('boom', fn ($app): StackRenderer => new class implements StackRenderer
        {
            public function render(Throwable $e, Request $request, int $status): Response
            {
                throw new RuntimeException('renderer exploded');
            }
        })
        ->context(fn (Request $request): string => 'boom');
}

it('reports the renderer failure once and degrades (no throttle)', function (): void {
    Exceptions::fake();
    registerThrowingStack();

    $this->get('/boom-1')->assertStatus(404); // degraded to core HTML

    Exceptions::assertReported(ErrorPageRenderException::class);
});

it('throttles repeat reports of the same renderer failure', function (): void {
    config()->set('error-pages.report.throttle', 300);
    Exceptions::fake();
    registerThrowingStack();

    $this->get('/boom-2');
    $this->get('/boom-3');
    $this->get('/boom-4');

    Exceptions::assertReportedCount(1);
});
