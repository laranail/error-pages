# The `ErrorPages` DSL

`Simtabi\Laranail\ErrorPages\Facades\ErrorPages` (alias `ErrorPages`) is the fluent entry
point — call it from a service provider's `boot()` to reshape rendering at runtime with no
package edits.

## Methods

| Method | Effect |
|--------|--------|
| `stack(string $stack)` | Override the default stack for this request lifecycle. |
| `theme(string $preset)` | Override the theme preset. |
| `context(Closure $resolver)` | Custom context detection — return a context string, or `null` to fall back to the default (Inertia → JSON `Accept` → `api/*` → web). |
| `skipWhen(callable $predicate)` | `fn ($e, $request): bool` — pass matching cases through to Laravel untouched. |
| `pipe(callable $stage)` | `fn (ErrorPage $page): ErrorPage` — enrich every page (support links, request id, solutions). |
| `extend(string $stack, Closure $factory)` | Register/override a `StackRenderer` (see [Stacks](stacks.md)). |

Rendering helpers (used by the views/preview, also callable directly): `htmlFor($e)`,
`jsonFor($e)`, `payloadFor($e)`, `htmlForCode(int)`, `htmlForKey(string)`.

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

## Notes

- The DSL is where **closures** live — never the published config file, so `config:cache`
  keeps working.
- The `ErrorPages` instance is a singleton; configure it once at boot.

---
[← Docs index](../../README.md#documentation)
