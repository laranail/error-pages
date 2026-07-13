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
- `RenderContext` value object centralises context/stack/status and renderer selection.
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
  type/mount helper (`presets/shared/payload.ts`), all rendering the one shared DOM contract,
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
