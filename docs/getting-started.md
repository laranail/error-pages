# Getting started

Beautiful production error pages, working in minutes — and how the pieces fit together.

## The mental model

This package renders the **end-user** error page at request time. It is not a debugger:
it **complements** Ignition (which keeps the dev debug page) and your reporting tools
(Sentry, Flare, Bugsnag keep the report pipeline — the package never touches it). See
[Coexistence](coexistence.md).

Rendering is organised on two independent axes:

- **Stack** — *how* a page is produced for your front end: `blade` (default), `livewire`,
  `inertia-vue`, `inertia-react`, `vue`, `react`. The API context always emits RFC 7807 JSON.
- **Theme** — *how* it looks: `default | slate | midnight | emerald | crimson`, plus
  per-token colour overrides.

It hooks Laravel two complementary ways ([Architecture](architecture.md)): server-HTML
pages ride Laravel's native `errors::{code}` resolution; API/Inertia/SPA responses ride a
single, gated renderable. Your own `resources/views/errors/*` always win.

## 1. It already works

With `APP_DEBUG=false`, hit a missing route — you get a branded 404. `abort(503)` gives a
branded, `Retry-After`-carrying 503. An `Accept: application/json` request gets RFC 7807
JSON. No setup.

## 2. Brand it

```dotenv
ERROR_PAGES_BRAND="Acme Inc"
ERROR_PAGES_LOGO="/images/logo.svg"
ERROR_PAGES_THEME=midnight
```

Or publish `config/error-pages.php` and edit `brand`, `theme`, `home_url`.

## 3. Pick a stack

```dotenv
ERROR_PAGES_STACK=inertia-react
```

See [Stacks](tools/stacks.md) for each stack and how to add your own.

## 4. Design in dev

`APP_DEBUG=true` shows Ignition for real bugs, so preview branded pages explicitly:

```bash
php artisan laranail::error-pages.preview 500
```

or visit `GET /_error-pages/500`.

## 5. Reshape it from your provider

```php
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

ErrorPages::theme('crimson')
    ->skipWhen(fn ($e, $request) => $request?->is('webhooks/*'))
    ->pipe(fn ($page) => $page->withRequestId(request()->header('X-Request-Id')));
```

Full surface: [The `ErrorPages` DSL](tools/dsl.md).

---
[← Docs index](../README.md#documentation)
