# The `ErrorPages` DSL

`Simtabi\Laranail\ErrorPages\Facades\ErrorPages` (alias `ErrorPages`) is the fluent entry
point — call it from a service provider's `boot()` to reshape rendering at runtime with no
package edits.

## Methods

| Method | Effect |
|--------|--------|
| `stack(string $stack)` | Override the default stack for this request lifecycle. |
| `theme(string $preset)` | Override the theme preset. |
| `context(Closure $resolver)` | Custom context detection — return a context string, or `null` to fall back to the default (Filament panel → Inertia → JSON `Accept` → `api/*` → web). |
| `nonce(Closure\|string $nonce)` | A CSP nonce (value or per-request resolver) put on the inline `<style>` and the enhancement `<script>` — for strict-CSP apps. |
| `skipWhen(callable $predicate)` | `fn ($e, $request): bool` — pass matching cases through to Laravel untouched. |
| `pipe(callable $stage)` | `fn (ErrorPage $page): ErrorPage` — enrich every page (support links, request id, solutions). |
| `extend(string $stack, Closure $factory)` | Register/override a `StackRenderer` (see [Stacks](stacks.md)). |

Rendering helpers (used by the views/preview/embeds, also callable directly): `htmlFor($e)`,
`renderForWeb($e)`, `jsonFor($e)`, `payloadFor($e)`, `htmlForCode(int)`, `htmlForKey(string)`,
`payloadForCode(int)`, `payloadForKey(string)`.

## Example

```php
use Illuminate\Support\Facades\Filament;
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

public function boot(): void
{
    ErrorPages::stack('inertia-react')
        ->theme('midnight')
        ->context(fn ($request) => Filament::getCurrentPanel() ? 'filament' : null)
        ->skipWhen(fn ($e, $request) => $request?->is('webhooks/*') === true)
        ->pipe(fn ($page) => $page->withRequestId(request()->header('X-Request-Id')));
}
```

## Events

Two events fire around every branded render — observation hooks for telemetry:

- `Simtabi\Laranail\ErrorPages\Events\RenderingErrorPage` — before rendering.
- `Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered` — after a page is rendered.

Both carry `$exception`, `$context` (`web` | `api` | `inertia` | a custom context), and
`$status`. To *change* a page use `pipe()` (enrich) or `skipWhen()` (veto), not a listener.

```php
use Simtabi\Laranail\ErrorPages\Events\ErrorPageRendered;

Event::listen(ErrorPageRendered::class, function (ErrorPageRendered $e) {
    Metrics::increment("error_pages.{$e->context}.{$e->status}");
});
```

## Testing helpers

For consumer tests, `ErrorPages::fake()` records every rendered page; assert on them after:

```php
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

ErrorPages::fake();

$this->get('/missing')->assertNotFound();

ErrorPages::assertRendered(404);
ErrorPages::assertRendered(404, stack: 'blade', theme: 'midnight'); // narrow by stack/theme
ErrorPages::assertNothingRendered();                                // when no error occurred
```

## Notes

- The DSL is where **closures** live — never the published config file, so `config:cache`
  keeps working (`nonce`, `context`, `skipWhen`, `pipe` are all DSL-only for this reason).
- The `ErrorPages` instance is a singleton; configure it once at boot. Under **Octane** the
  boot config persists across requests, and the DSL is reset to that boot baseline at the
  start of each request — so an accidental per-request `stack()`/`skipWhen()`/`pipe()` can't
  leak into the next request.

---
[← Docs index](../../README.md#documentation)
