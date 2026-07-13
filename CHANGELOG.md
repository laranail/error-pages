# Changelog

All notable changes to `laranail/error-pages` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **Rebuilt as a runtime exception/error-page renderer** and renamed
  `laranail/server-error-pages` → `laranail/error-pages` (namespace
  `Simtabi\Laranail\ErrorPages`; framework-agnostic engine under `…\ErrorPages\Core`).
  Complements Ignition/Sentry/Flare — it renders the production page, never touches the
  report pipeline, and leaves the dev debug page to Ignition. See [`UPGRADE.md`](UPGRADE.md).

### Added

- Two-path coexistence hook (native `errors::` view injection + one gated renderable).
- Stacks: `blade`, `livewire`, `inertia-vue/react`, `vue`, `react`, `filament`/`nova`, and
  RFC 7807 JSON for API; a `StackManager` with an `ErrorPages::extend()` driver seam.
- Fluent `ErrorPages` facade/DSL (`stack`/`theme`/`context`/`skipWhen`/`pipe`/`extend`),
  theme presets + per-token overrides, 4xx-message/5xx-security policy, `Retry-After`/
  `no-store`/`noindex` headers, correlation id, translation-backed content, preview
  route + command.
- Lifecycle events `RenderingErrorPage` / `ErrorPageRendered` (both paths).
- Stack-unavailable fallback ladder (a renderer returning `null` degrades to the guaranteed
  core HTML page, except API which stays JSON).
- Progressive-enhancement asset route (`assets.mode` = `route`/`link`/`inline`/`off`) serving
  an immutably-cached, ETag-validated `error-pages.js` + shared CSS from `presets/shared`.
- RFC 7807 `instance` (request URI) and an optional per-status `type` via `problem_type_base`.
- Configurable correlation id: `request_id.header` (default `X-Request-Id`) with an optional
  generated fallback (`request_id.generate`).
- `content.default_locale` is now threaded into content resolution.
- Component-embed support: `payloadForCode(int)` / `payloadForKey(string)` accessors and an
  `error-pages.livewire.layout` option so the `laranail-error-page` component can be embedded in
  your own view/layout (for apps that don't use full-page Livewire), not only rendered standalone.
- An embeddable Blade component `<x-error-pages::error :code="404" />` (or `:key`/`:page`) that
  renders the shared `ep-*` fragment inside any view — the Blade parity for the Livewire embed.
- `RenderContext` value object centralises context/stack/status and renderer selection.
- Octane per-request DSL isolation: the `ErrorPages` singleton snapshots its boot-time DSL
  config on the first request and resets to that baseline each request (via Octane's
  `RequestReceived`), so accidental per-request DSL mutations can't leak across requests.
- The bridge `Stack` enum adopts the org-standard `laranail/enumerator` (attribute-driven
  `label()`/`description()`, surfaced in the `about` section). The `Core\` enums stay plain
  to keep the engine framework-agnostic (arch-boundary test).
- CSP nonce via `ErrorPages::nonce(Closure|string)` — threaded into the inline `<style>`,
  the enhancement `<script>`, and the SPA payload script.
- `report.throttle` (seconds) caps repeat reports of the same renderer failure (fails open).
- Preview gallery at the preview route (`GET /_error-pages`) over every code × theme, and a
  `?theme=` override on the per-code preview.
- Consumer test helpers: `ErrorPages::fake()` + `assertRendered($code, stack:, theme:)` /
  `assertNothingRendered()`.
- Filament panel **auto-detection** (path-scoped, `panels.filament`-gated) — a request under a
  Filament panel renders the panel context without a manual `context()`.
- Vue (`presets/vue`) + React (`presets/react`) `ErrorPage` components and a shared payload
  type/mount helper (`presets/shared/ts/payload.ts`), all rendering the one shared DOM contract,
  wired into `@laranail/error-pages-ui` and **unit-tested with Vitest** (DOM-parity) in a new
  `assets` CI workflow.
- The `livewire` stack now renders a real full-page **Livewire 4** `ErrorPage` component
  (`src/Livewire/ErrorPage.php`, registered only when `livewire/livewire ^4` is installed;
  degrades to the core HTML page otherwise). `Stack::Livewire` moved from a `blade` alias to a
  Path-2 stack. The component + wrapper views are **publishable/overridable**
  (`vendor:publish --tag=laranail::error-pages-views`); registration uses package-tools'
  `hasViews()` / `hasLivewireComponent()` (late-binding-safe, guarded).
- Nova is now auto-detected (Inertia request under `nova.path`, `panels.nova`-gated) and the
  `nova` driver renders an **Inertia** response instead of HTML (Nova is an Inertia SPA).

### Changed

- **Reorganised `presets/` by asset kind.** `shared/` now splits into `scss/` (source),
  `css/` (built `critical.css`), `js/` (`enhance.js`), and `ts/` (`payload.ts`/`fixtures.ts`);
  each stack folder has `views/`/`components/`/`scss/` sub-folders. The stylesheet is authored
  in **SCSS** (one `$themes` map generating every `.ep-theme-*` class) and built to CSS via
  `npm run build:css` (committed; the `assets` CI job builds it and checks it is up to date).
  The `blade/` preset is populated with a starter design. (PHP asset paths updated accordingly.)
  A **Prettier** format gate (the JS/TS/Vue/SCSS analog of Pint) now runs in the `assets` CI.
- Renderer selection now honours the configured stack for a plain web page load: an
  `inertia-*` stack renders an Inertia response (not the generic SPA shell).
- `render_debug_pages` documented as inertia/spa-only (the API context is always branded).
- Retryable pages reload the current URL rather than redirecting to the brand home
  (removes a maintenance/rate-limit refresh-loop risk).

### Fixed

- Scoped `codes.intercept` / `skipWhen()` to Path 2 in the docs and config (Path 1 web is
  view precedence); corrected the `419`, `codes.fallbacks`, `render_debug_pages`, panel, and
  Livewire descriptions to match behaviour.
- Preview command name `laranail::error-pages.preview` (was the stale `laravel-error-pages`
  slug); translation publish tag/path references.

### Removed

- The static-HTML generator + Apache/Nginx config emitter, the `build`/`export`/
  `server-config`/`clear` commands, and the `output.*`/`server.*`/`security.headers` config.
- The unused `codes.fallbacks` flag (generic 4xx/5xx branding is automatic via Laravel's
  native `errors::{n}xx` resolution).
- Dead code: unused predicates `HttpStatus::{color,isClientError,isServerError,fallbackKey}`
  (the static `fallbackKeyFor` is kept), `ErrorPage::{isGeneric,isServerError}`, and
  `Stack::isSpa` (SPA is reached via the renderer-key default branch).

### Fixed

- The progressive-enhancement **retry countdown now works** — `enhance.js` creates its own
  countdown element from the meta-refresh (no template previously emitted `[data-ep-countdown]`,
  so it was a no-op), and the copy-reference button + retry line are now styled (`.ep-copy`/
  `.ep-retry`).
- The full-page **Livewire** stack now emits the retryable `<meta http-equiv="refresh">` for
  transient codes — parity with the blade and SPA stacks.
- **Path 1 is now failure-safe**: if the branded web render throws (a bad `pipe()` stage,
  translation, or theme), `renderForWeb()` degrades to a static shell, reports only the
  wrapped `ErrorPageRenderException`, and still fires `ErrorPageRendered` — matching Path 2.
- A 4xx message equal to the framework's default reason phrase (e.g. `abort(404, 'Not Found')`)
  no longer overrides the nicer localized copy.
- a11y: all five themes (light + dark) now meet **WCAG 2.1 AA** contrast. Fixed low-contrast
  `muted` text on midnight/emerald, and added an `--ep-on-accent` token so primary-button text
  is legible on every accent. A `check:contrast` gate (in the `assets` CI) prevents regressions.
- i18n/a11y: the rendered page's `<html lang>` and `dir` now reflect the resolved locale
  instead of a hard-coded `en`/`ltr` — right-to-left locales (`ar`, `he`, `fa`, …) render
  `dir="rtl"`. Threaded via `ThemeSettings` (Core stays framework-agnostic; the bridge
  resolves it).
- `.editorconfig` now covers `ts`/`tsx`/`vue`/`scss` at 2-space (the org "2-space JS" rule);
  they were falling under the 4-space default.

### Security

- Never surface a framework-rewritten 4xx message (e.g. `ModelNotFoundException` →
  `NotFoundHttpException` naming the model + ids): a 4xx `getMessage()` is shown only when
  the developer set it directly (the exception has no `previous`).
- Build the enhancement asset URL from the trusted `app.url`, never the request `Host`/
  `X-Forwarded-Host` header (removes a cache-poisoning / script-source reflection).
- Sanitise and clamp the reflected `X-Request-Id` (safe charset, ≤128 chars).
- Validate the scheme on the configured brand/logo URL: the brand `<a href>` allows only
  http(s)/relative; the logo `<img src>` additionally allows inline `data:` images;
  `javascript:`/`vbscript:` are neutralised.
- Add `X-Content-Type-Options: nosniff` to every error response.

## [0.1.0] - 2026-07-11

Initial public release (static-HTML generator — superseded by the Unreleased redesign).
