<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\ErrorPages;

it('isolates per-request DSL mutations across Octane requests', function (): void {
    $pages = app(ErrorPages::class);

    // Boot-time config (must persist across requests on a persistent worker).
    $pages->stack('vue')->theme('midnight');

    // Request 1 start: snapshot the boot baseline.
    $pages->isolateOctaneRequest();

    // A (discouraged) per-request mutation — it must not leak to the next request.
    $pages->stack('react')->theme('crimson')->skipWhen(fn (): bool => true);
    expect($pages->stackName())->toBe('react')
        ->and($pages->themeSettings()->preset->value)->toBe('crimson')
        ->and($pages->shouldSkip(new RuntimeException, null))->toBeTrue();

    // Request 2 start: restore the baseline — per-request mutations are gone,
    // boot config remains.
    $pages->isolateOctaneRequest();
    expect($pages->stackName())->toBe('vue')
        ->and($pages->themeSettings()->preset->value)->toBe('midnight')
        ->and($pages->shouldSkip(new RuntimeException, null))->toBeFalse();
});

it('preserves boot-time pipe stages across the reset', function (): void {
    $pages = app(ErrorPages::class);
    $pages->pipe(fn ($page) => $page->withRequestId('BOOT'));

    $pages->isolateOctaneRequest(); // snapshot (boot pipe included)
    $pages->pipe(fn ($page) => $page->withRequestId('PER-REQUEST')); // transient
    $pages->isolateOctaneRequest(); // restore → only the boot pipe remains

    expect($pages->jsonFor(new RuntimeException)['request_id'])->toBe('BOOT');
});
