# Architecture

One component source, three coordinated outputs — and why the design is shaped that way.

## The one-source, three-outputs model

Everything derives from a single anonymous Blade component, `<x-server-error-pages::layout>`. It composes `status`, `message`, `actions`, and `brand` sub-components into one solid centered layout. It always inlines the CSS and a small vanilla JS (no Alpine, no CDN) and emits the chosen theme preset's colours as `:root { --sep-* }` custom properties. There is one layout and no second template — the look is customised by picking a theme preset (or overriding individual colour tokens), which is more realistic for pages that are rarely seen.

That one component is rendered along three paths:

| Output | Trigger | How | Serves when |
|--------|---------|-----|-------------|
| Dynamic Blade view | Laravel `renderHttpException()` | `errors::{code}` resolves to a package stub | The app is up |
| Static HTML file | `server-error-pages:build` | The same component rendered to `{code}.html` | PHP / the app is down |
| Web-server config | `server-error-pages:build` / `:server-config` | Apache `.htaccess` / Nginx `error_page` pointing at the static files | The web server needs to pick a page |

Because all three come from the same component, the live page and the static fallback are visually identical.

## Dynamic path

The provider's `packageBooted()` pushes the package's `resources/error-views` directory onto `config('view.paths')`. Laravel's `RegisterErrorViewPaths` re-reads that config on every `renderHttpException()` and maps each entry to `{path}/errors`, so `errors::{code}` resolves to the package's `errors/{code}.blade.php` stub — with no publish step. An app-published `resources/views/errors/{code}.blade.php` still wins, because the app's own view path is registered ahead of the package's.

Each stub is a one-liner that calls the facade:

```php
{!! \Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages::htmlFor(404) !!}
```

## Static path

`server-error-pages:build` renders the *same* component (via the manager's `htmlForKey()`, not the `errors::` namespace, which only exists mid-exception) and writes the result to `{output.path}/{code}.html`. Before writing, it asserts each page is self-contained: the build scans for external stylesheets, scripts, `src` attributes, and CSS `url()` loads, and fails the whole build if any page would reach out to the network. Navigation links (`<a href>`) are intentionally allowed, so a full-URL "home" button never trips the check.

The static build covers exactly the scenarios the dynamic path cannot: PHP-FPM crashed, a deploy is mid-flight, or the app fatals before it boots. In all of those, no Blade ever runs, so a prebuilt flat file is the only thing that can be served.

## Content resolution

Titles and messages resolve through one chain, shared by both the dynamic and static render so their output stays byte-identical:

1. Published JSON file — `resources/error-pages/{locale}.json`, keys like `"404"` or generic `"4xx"` (skipped when `content.source` is `config`).
2. `config('laranail.server-error-pages.messages.{code}')`.
3. Built-in `HttpStatus` enum `#[Label]` / `#[Description]` defaults.

The enum default is the last link, which is why an unconfigured install still renders complete pages. A code outside the enum falls back to the generic `4xx` / `5xx` page.

## Assets and theming

The CSS and JS are hand-authored, dependency-free, and prebuilt into `resources/dist/`, which is committed to the repository. A Tailwind v4 source and a `package.json` exist for maintainers, but consumers never run a build step — the shipped bundle is inlined directly. Colours are runtime CSS variables, so a re-brand is a config change plus a rebuild, never a recompile.

## Server config generation

`ServerConfigEmitter` fills the stubs under `resources/server/` with `ErrorDocument` (Apache) or `error_page` (Nginx) lines for each enabled code, plus the security headers from config. When `codes.fallbacks` is on, the Nginx output also routes the long tail of other 4xx/5xx codes to the generic pages. Outputs are written to app/FTP-writable locations by default (`public/.htaccess`, `storage/app/server-error-pages/errors.conf`) — never to `/etc` — and the command prints the include line for you to wire in.

## Why this design?

- **Why generate static files at all?** Every other Laravel error-page approach only renders while the app is alive. The static files exist precisely for the moments Blade cannot run, which are the moments a maintenance or outage page matters most.
- **Why one component instead of separate templates?** A single source guarantees the live and static pages never drift apart visually, and content changes propagate to both through the same resolution chain.
- **Why inline everything and reject external references?** A page that loads a stylesheet or font from a CDN is not self-contained; if the network or app is degraded it renders broken. The build's self-containment assertion turns that risk into a hard failure at generation time rather than a silent one during an outage.
- **Why file-managed content, no database?** The pages must render when the database and app are unreachable. Config plus JSON files deploy by git or FTP and have no runtime dependencies.

---
[← Docs index](../README.md#documentation)
