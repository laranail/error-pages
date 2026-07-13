# Stacks

The six front-end stacks the package renders into, and how to add your own. A stack decides
how the web/inertia page is produced; the API context always emits RFC 7807 JSON.

## Built-in stacks

| Stack | Context | Renders via |
|-------|---------|-------------|
| `blade` (default) | web | Laravel `errors::{code}` view (Path 1) |
| `livewire` | web | `errors::{code}` view embedding a Livewire component (Path 1) |
| `inertia-vue` / `inertia-react` | inertia | `Inertia::render('ErrorPage', payload)` (Path 2) |
| `vue` / `react` | web (SPA) | self-contained page + embedded `#error-page-data` payload (Path 2) |
| _(fixed)_ api | api | RFC 7807 `application/problem+json` (Path 2) |
| `filament` / `nova` | panel | panel-tagged page (Path 2) |

Select the default with `config('error-pages.stack')` or `ERROR_PAGES_STACK`; override per
request with `ErrorPages::stack(...)`.

## The payload

The Inertia and SPA components receive one payload (`ErrorPages::payloadFor()`):

```json
{
  "status": 503, "code": "503", "title": "Be right back",
  "message": "…", "retryable": true, "retryAfter": 15, "requestId": null,
  "homeUrl": "/", "brand": { "name": "Acme", "url": "/", "logo": null },
  "theme": { "preset": "midnight", "autoDark": true }
}
```

For SPA it is embedded as `<script id="error-page-data" type="application/json">` for the
client to hydrate; for Inertia it is the page props.

## Custom stacks

Register a driver from your provider — it composes with everything else:

```php
use Simtabi\Laranail\ErrorPages\Contracts\StackRenderer;
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

ErrorPages::extend('svelte', fn ($app): StackRenderer => new SvelteErrorRenderer(...))
    ->context(fn ($request) => $request->hasHeader('X-Svelte') ? 'svelte' : null);
```

A `StackRenderer` returns a `Response`, or `null` to degrade down the fallback ladder (e.g.
the Inertia renderer returns null when Inertia is not installed).

## Panel stacks

`filament` and `nova` render a panel-tagged page. Full panel theming, the Filament plugin,
the Nova tool, and automatic panel detection layer on when those packages are installed.

---
[← Docs index](../../README.md#documentation)
