# Stacks

The front-end stacks the package renders into, and how to add your own. A stack decides how
the web/inertia page is produced; the API context always emits RFC 7807 JSON.

## Built-in stacks

| Stack | Context | Renders via |
|-------|---------|-------------|
| `blade` (default) | web | Laravel `errors::{code}` view (Path 1) |
| `livewire` | web | full-page **Livewire 4** `ErrorPage` component (Path 2) |
| `inertia-vue` / `inertia-react` | web / inertia | `Inertia::render('ErrorPage', payload)` (Path 2) |
| `vue` / `react` | web (SPA) | self-contained page + embedded `#error-page-data` payload (Path 2) |
| _(fixed)_ api | api | RFC 7807 `application/problem+json` (Path 2) |
| `filament` | panel | branded HTML panel page (Path 2) |
| `nova` | panel | Inertia response (Nova is an Inertia SPA) (Path 2) |

Select the default with `config('error-pages.stack')` or `ERROR_PAGES_STACK`; override per
request with `ErrorPages::stack(...)`.

Renderer selection is by **context first**, then stack: an `X-Inertia` request always uses
the Inertia renderer; a plain (non-Inertia) web page load under an `inertia-*` stack also
renders an Inertia response (so the client app takes over); a `livewire` stack renders the
full-page Livewire component; and a `vue`/`react` stack renders the self-contained SPA shell.

> The `livewire` stack needs **`livewire/livewire ^4`** installed (`composer require
> livewire/livewire`); without it the stack degrades to the core HTML page. Livewire bundles
> its own Alpine, so the package's enhancement JS is not loaded on the Livewire page.

## The payload

The Inertia and SPA components receive one payload (`ErrorPages::payloadFor()`):

```json
{
  "status": 503, "code": "503", "title": "Be right back",
  "message": "…", "retryable": true, "retryAfter": 15, "requestId": "9f2c1a7b3d4e5f60",
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

**Filament is auto-detected** — a request under a Filament panel's own path resolves to the
`filament` context (path-scoped, so it never hijacks a normal route; toggle with
`panels.filament`) and renders the branded HTML panel page.

**Nova is auto-detected** for Inertia requests under `config('nova.path')` (toggle with
`panels.nova`) and renders an **Inertia** response — returning HTML to Nova's `X-Inertia`
requests would break its client. A plain full-page load under Nova stays `web` (branded HTML).
Full panel theming and the Filament plugin land with the panel visual set.

---
[← Docs index](../../README.md#documentation)
