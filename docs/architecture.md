# Architecture

How the runtime renderer hooks Laravel, stays out of Ignition's way, and keeps a
framework-agnostic core.

## Two complementary hook paths

The package never registers one blanket exception renderer (that would run before
Ignition and has no priority guarantee). It splits by context:

- **Path 1 — server-HTML (`blade`, `livewire`).** The provider pushes the package's
  `resources/views` onto `config('view.paths')`, so Laravel's own
  `renderHttpException()` → `errors::{code}` resolution finds our thin views as a
  **fallback**. Precedence, for free: your app's `resources/views/errors/{code}` wins →
  ours → the framework default. No renderable runs, so it cannot double-report or preempt
  the debug page.
- **Path 2 — client/SPA (`inertia`, `vue`, `react`) and API JSON.** One gated
  `renderable` callback (registered idempotently on the handler) that **defers** (returns
  `null`) for validation/auth exceptions, the server-HTML web context, non-intercepted
  codes, consumer `skipWhen` vetoes, and — for genuine dev 500s in HTML-ish contexts — to
  Ignition.

The result, with **no environment branching in our code**: `abort(4xx/503)` is branded in
dev and prod; an unhandled 500 is branded in prod but shows Ignition in dev (Laravel's own
`isHttpException && app.debug` split does this mechanically).

## Two axes: stack × theme

A `StackManager` (an Illuminate `Manager`) resolves a `StackRenderer` by key — `json`,
`inertia`, `spa`, `filament`, `nova`, or any consumer-registered driver via
`ErrorPages::extend()`. The handler maps the resolved *context* to a renderer key; the
configured *stack* decides how the web/inertia page is produced. Themes are colour presets
applied over the same markup. See [Stacks](tools/stacks.md).

## The Core engine (framework-agnostic)

The rendering engine lives in an **illuminate-free `Simtabi\Laranail\ErrorPages\Core`
sub-namespace** (`src/Core/`), guarded by an architecture test. It owns: the `HttpStatus`
enum (built-in copy + severity), the `ErrorPage`/`ThemeSettings` value objects, the
`ErrorPageFactory` content chain (overrides → enum default), the `HtmlRenderer`
(self-contained page with critical CSS inlined) and `JsonRenderer` (RFC 7807), and the
`CssVariableMap`. The Laravel layer (facade/DSL, provider, handler, renderers) sits at the
`ErrorPages` root and delegates to it.

## Failure-safety

Building the page is classified **degradable**. The Path-2 render is wrapped: if our
renderer throws, we report a **new** `ErrorPageRenderException` (never the original
exception — the framework already reported it, so there is no double-report) and return
`null` to fall back to Laravel's default. The `HtmlRenderer` is the guaranteed last rung —
it needs no view engine or asset pipeline.

## Security

For 4xx `HttpException`s a developer-intended `abort(403, 'message')` is shown; for **5xx**
the message is **never** used (it may carry internals) — always the generic copy. Every
response also carries `X-Robots-Tag: noindex`, `Cache-Control: no-store`, and a
propagated/derived `Retry-After` for transient codes.

## Why not a separate composer package?

An earlier design split an agnostic core from a Laravel bridge (the spatie/ignition model).
Since laranail is a Laravel-specific ecosystem, that was collapsed into one package — the
agnostic engine is preserved as the `Core\` namespace (still boundary-tested) rather than a
second repo.

---
[← Docs index](../README.md#documentation)
