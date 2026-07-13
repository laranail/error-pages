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

### Removed

- The static-HTML generator + Apache/Nginx config emitter, the `build`/`export`/
  `server-config`/`clear` commands, and the `output.*`/`server.*`/`security.headers` config.

## [0.1.0] - 2026-07-11

Initial public release (static-HTML generator — superseded by the Unreleased redesign).
