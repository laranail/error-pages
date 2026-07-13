# laranail/error-pages

[![Packagist Version](https://img.shields.io/packagist/v/laranail/error-pages.svg?style=flat-square)](https://packagist.org/packages/laranail/error-pages)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/error-pages/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/error-pages/actions)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/error-pages/ci.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/error-pages/actions)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

> Beautiful, branded **production** error and exception pages for Laravel that **complement** Ignition — it renders the end-user page, Ignition keeps the dev debug page, your reporting tools keep the report pipeline.

Adapts to the caller's context and front-end stack — Blade (web), RFC 7807 JSON (API), Inertia/Vue/React (SPA), and Filament/Nova panels — all Alpine-enhanced and fully configurable. Runs on Laravel `^13.0`, PHP `^8.4.1 || ^8.5`. The framework-agnostic rendering engine lives in an illuminate-free `Core\` namespace within the package.

## Install

```bash
composer require laranail/error-pages
```

It self-registers and takes over production-style error responses out of the box (dev 500s still show Ignition). Publish the config to customise:

```bash
php artisan vendor:publish --tag=laranail::error-pages-config
```

## <a name="documentation"></a>Documentation

Hosted at **<https://opensource.simtabi.com/documentation/laranail/error-pages/>**.

### Guides

- [Installation](docs/installation.md) — install, publish, verify.
- [Getting started](docs/getting-started.md) — the mental model and first customisations.
- [Configuration](docs/configuration.md) — every config key.
- [Architecture](docs/architecture.md) — the two hook paths and the framework-agnostic core.
- [Coexistence](docs/coexistence.md) — how it sits beside Ignition, Sentry, Flare, Bugsnag.

### Reference

- [Stacks](docs/tools/stacks.md) — the front-end stacks and custom drivers.
- [The `ErrorPages` DSL](docs/tools/dsl.md) — the fluent runtime API.
- [Preview](docs/tools/preview.md) — the preview route and command.

### Recipes

- [Customising brand and theme](docs/recipes/customizing-brand-theme.md)
- [Managing content](docs/recipes/managing-content.md)
- [Overriding an error view](docs/recipes/overriding-error-views.md)
- [API error responses](docs/recipes/api-json.md)
- [Inertia and SPA error pages](docs/recipes/inertia-spa.md)
- [Skipping and passthrough](docs/recipes/skip-and-veto.md)

### Project

- [Release](docs/release.md)

## License

MIT © [Simtabi LLC](https://opensource.simtabi.com). See [LICENSE](LICENSE).
