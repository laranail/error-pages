<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\Enums\Stack;
use Simtabi\Laranail\ErrorPages\Http\RenderContext;
use Simtabi\Laranail\ErrorPages\Rendering\StackManager;

/**
 * Asserts that every renderer key this package can produce is one `StackManager` can actually build.
 *
 * `StackManager extends Illuminate\Support\Manager`, which resolves by **interpolating the key into
 * a method name** — `driver('livewire')` becomes `createLivewireDriver()`. A key with no method is an
 * `InvalidArgumentException`, and it surfaces at the worst possible moment: inside the error handler,
 * while already rendering someone else's exception.
 *
 * Two of the keys are not written anywhere as strings a search would find:
 *
 *  - `RenderContext::rendererKey()` ends in `default => $this->context`, so the **context itself** is
 *    interpolated into the method name.
 *  - `PanelDetector` builds those contexts out of the **key names under `error-pages.panels`** —
 *    `panels.filament` produces the context `filament`, which produces `createFilamentDriver()`.
 *
 * So adding `'panels' => ['horizon' => true]` plus a detector arm is a three-line config change that
 * silently requires a seventh driver, and nothing objects until an exception is thrown inside Horizon.
 *
 * The fixtures below are read from the shipped config and the `Stack` enum rather than hand-written,
 * so a case added to either is covered here the day it lands. No I/O: the assertion is that a driver
 * is *buildable*, not that anything renders.
 */
function shippedErrorPagesConfig(): array
{
    return require __DIR__ . '/../../config/error-pages.php';
}

/** Every context this package can produce on its own: the panel keys, plus the resolver's three. */
function builtInRenderContexts(): array
{
    return [...array_keys(shippedErrorPagesConfig()['panels'] ?? []), 'inertia', 'api', 'web'];
}

function createMethodFor(string $key): string
{
    // Manager studlies the name: 'inertia-vue' would become createInertiaVueDriver.
    return 'create' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))) . 'Driver';
}

it('offers panels in its config, so this guard is not vacuous', function (): void {
    // A discovery-driven test that finds nothing passes everything below it trivially.
    expect(shippedErrorPagesConfig()['panels'] ?? [])->not->toBeEmpty();
});

it('can build a driver for every renderer key it can produce', function (): void {
    $manager   = new ReflectionClass(StackManager::class);
    $offenders = [];

    // The full cartesian product: the `web` context picks its key from the stack, so a stack case
    // added without a matching renderer is exactly as broken as a missing panel driver.
    foreach (builtInRenderContexts() as $context) {
        foreach (Stack::cases() as $stack) {
            $key    = RenderContext::make(new RuntimeException('probe'), request(), $context, $stack)->rendererKey();
            $method = createMethodFor($key);

            if (! $manager->hasMethod($method)) {
                // Keyed by driver, so a panel context (whose key ignores the stack) reports one row
                // rather than one per stack case.
                $offenders[$key] ??= "driver('{$key}') => {$method}()  -- reached by context [{$context}], stack [{$stack->value}]";
            }
        }
    }

    expect(array_values($offenders))->toBe([], sprintf(
        "these renderer keys resolve to a driver StackManager cannot build:\n  %s",
        implode("\n  ", $offenders),
    ));
});

it('reads the stack config at the key it is registered under', function (): void {
    // The handler reads `error-pages.stack` and falls back to 'blade'. If the package registered
    // that config anywhere else, the fallback wins permanently and every consumer silently gets the
    // server-HTML path -- which looks like working software, because blade renders fine.
    expect(config()->has('error-pages.stack'))
        ->toBeTrue('the handler reads [error-pages.stack]; nothing is registered there');

    expect(config('error-pages.panels'))
        ->toBeArray('PanelDetector reads [error-pages.panels]; nothing is registered there');
});

it('fails loudly, not silently, on a key it cannot build', function (): void {
    // Pinning the failure mode: an exception, not a null that degrades to Laravel's default page and
    // reads as the branded pages simply not being installed.
    expect(fn () => app(StackManager::class)->renderer('not-a-real-stack'))
        ->toThrow(InvalidArgumentException::class);
});
