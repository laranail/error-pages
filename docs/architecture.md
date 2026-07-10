# Architecture

One component source, two build modes, three coordinated outputs — and why the design is shaped that way.

## The one-source model

Everything derives from a single anonymous Blade component, `<x-server-error-pages::layout>`. It composes `brand`, `status`, `message`, and `actions` sub-components into one polished centered layout, and links an external stylesheet and a small vanilla JS. The theme is a colour preset selected by a `sep-theme-{preset}` class on `<body>`; there is one layout and no second template — the look is customised by picking a preset (or overriding individual colour tokens), which is realistic for pages that are rarely seen.

That one component is rendered along three paths:

| Output | Trigger | How | Serves when |
|--------|---------|-----|-------------|
| Dynamic Blade view | Laravel `renderHttpException()` | `errors/{code}.blade.php` calls the facade | The app is up |
| Static HTML file | `server-error-pages:build` | The same component rendered to `{code}.html` | PHP / the app is down |
| Web-server config | `server-error-pages:build` / `:server-config` | Apache `.htaccess` / Nginx `error_page` pointing at the static files | The web server needs to pick a page |

Because all three come from the same component (`ServerErrorPagesManager::htmlForKey()`), the live page and the static fallback are visually identical.

## External, linked assets

The CSS and JS are **external files, linked** — not inlined. The page head emits `<link href="{assets_url}/css/error-pages.css">` and the body ends with `<script src="{assets_url}/js/error-pages.js" defer>`. `assets_url` defaults to `/vendor/server-error-pages`, deliberately kept **outside** the internal `/errors/` location so the web server serves it as an ordinary static asset.

This is still resilient in an outage. When PHP-FPM is down, the web server (Nginx/Apache) still serves the flat `{code}.html` **and** the static CSS/JS next to it — no application code runs to deliver any of them. Linking (rather than inlining) keeps every page small, lets the browser cache one shared stylesheet across all error pages, and keeps the markup DRY.

## Two build modes

`server-error-pages:build` has two modes:

- **Linked (default).** Renders each page with `<link>`/`<script>` tags and copies the committed bundle (`public/assets/`) to `output.assets_path` (default `public_path('vendor/server-error-pages')`) so the linked files are always present next to the pages. This is what a Laravel deploy uses.
- **Standalone (`--standalone`, or `server-error-pages:export`).** Post-processes each rendered page through `HtmlInliner`, replacing the linked stylesheet/script with inline `<style>`/`<script>` and a local logo with a data-URI, then **asserts the page is fully self-contained** (no external stylesheet, script, or `src`) — failing the build with `NotSelfContainedException` otherwise. The result is single-file pages with zero external requests, for users who want to upload `public/errors/*.html` + `.htaccess` to any host without deploying Laravel.

## Dynamic path

The install command publishes the package's error-view stubs into the app at `resource_path('views/errors')` (`errors/{code}.blade.php`). Each stub is a one-liner that calls the facade:

```php
{!! \Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages::htmlFor(404) !!}
```

These are Laravel's conventional error views, so `renderHttpException()` resolves them with no extra wiring. Because they are published into the app, editing `resources/views/errors/{code}.blade.php` (or replacing it wholesale) simply wins — it is the app's own view.

## Static path

`server-error-pages:build` renders the *same* component (via the manager's `htmlForKey()`, independent of the `errors::` namespace, which only exists mid-exception) and writes each result to `{output.path}/{code}.html`. The static build covers exactly the scenarios the dynamic path cannot: PHP-FPM crashed, a deploy is mid-flight, or the app fatals before it boots. In all of those, no Blade ever runs, so a prebuilt flat file plus its linked assets are the only things that can be served.

## Content resolution

Titles and messages are real Laravel translations, keyed by status code, shared by both the dynamic and static render so their output stays identical:

1. **App override** — `lang/vendor/server-error-pages/{locale}/errors.php`, keys like `'404'` or generic `'4xx'`.
2. **Package translations** — `resources/lang/en/errors.php` (the shipped defaults).
3. **Built-in `HttpStatus` enum default** — the last link, which is why an unconfigured install still renders complete pages.

A code outside the enum falls back to the generic `4xx` / `5xx` page. `content.default_locale` is the locale baked into the static build; dynamic pages honour the request locale.

## Assets and theming pipeline

The source lives under `resources/assets/{scss,scripts}` and is built by **Vite + Tailwind 4 + SCSS** into the committed `public/assets/{css,js}/` bundle (`css/error-pages.css`, `js/error-pages.js`). The JS entry `import`s the SCSS, so one build emits both files; the config uses stable, unhashed names the pages can link. Consumers never build this — the bundle is committed and shipped — but a maintainer regenerates it with `npm run build` after changing the SCSS or JS.

Theming has three tiers, cheapest first:

- **Preset** — `theme.preset` swaps the `sep-theme-{preset}` body class. All five presets are compiled into the one stylesheet, so switching needs **no rebuild**.
- **Per-token overrides** — `theme.colors.{light,dark}` generate a small linked `css/error-pages-theme.css` (via `CssVariableMap`) at build time that overrides individual `--sep-*` custom properties. No SCSS rebuild.
- **Deep custom** — edit the SCSS and run `npm run build`.

`theme.auto_dark` toggles the `sep-auto-dark` body class; combined with the OS `prefers-color-scheme`, the presets' dark variants apply automatically.

## Server config generation

`ServerConfigEmitter` fills the stubs under `stubs/{apache,nginx}/` with `ErrorDocument` (Apache) or `error_page` (Nginx) lines for each enabled code, plus the security headers from config. When `codes.fallbacks` is on, the output also routes the long tail of other 4xx/5xx codes to the generic pages. The snippet is written as a **managed block** between `# BEGIN laranail/server-error-pages` / `# END` sentinels and merged into the target file, so existing content — notably Laravel's own `public/.htaccess` front-controller rules — is preserved. Outputs go to app/FTP-writable locations by default (`public/.htaccess`, `storage/app/server-error-pages/errors.conf`) — never to `/etc` — and the command prints the include line for you to wire in.

## Why this design?

- **Why generate static files at all?** Every other Laravel error-page approach only renders while the app is alive. The static files exist precisely for the moments Blade cannot run, which are the moments a maintenance or outage page matters most.
- **Why link assets instead of inlining them?** The web server serves the linked CSS/JS from `assets_url` just as reliably as it serves the HTML when PHP is down, so resilience is preserved — while one cached stylesheet across all pages keeps the markup small and DRY. The standalone export exists for the narrow case (arbitrary hosting, no Laravel deploy) where a truly single-file page is worth the size.
- **Why one component instead of separate templates?** A single source guarantees the live and static pages never drift apart visually, and content changes propagate to both through the same translation chain.
- **Why translations, no database?** The pages must render when the database and app are unreachable. Translation files deploy by git or FTP and have no runtime dependency, while giving first-class multi-locale support.

---
[← Docs index](../README.md#documentation)
