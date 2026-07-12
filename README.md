# laranail/server-error-pages

[![Packagist Version](https://img.shields.io/packagist/v/laranail/server-error-pages.svg?style=flat-square)](https://packagist.org/packages/laranail/server-error-pages)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/server-error-pages/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/server-error-pages/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/server-error-pages/static-analysis.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/laranail/server-error-pages/actions/workflows/static-analysis.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

> Branded HTTP error pages for Laravel that survive total app failure — dynamic Blade views, static HTML for when PHP is down, and generated Apache/Nginx config, all from one Vite/Tailwind component source, with a standalone single-file export for hosting anywhere.

Works with Laravel 13 on PHP 8.4+.

## Install

```bash
composer require laranail/server-error-pages
php artisan server-error-pages:install
```

The installer publishes the config, error views, translations, and asset bundle, then builds the static pages and server config. There is no database and no admin panel — every page is managed with a PHP config file and Laravel translation files, so it deploys by git (VPS) or FTP (shared hosting).

## Why

Every other Laravel error-page package only renders while the app is alive. When PHP-FPM crashes, a deploy is mid-flight, or the app fatals before booting, the web server serves its own raw error page and no Blade ever runs. This package generates matching **static** pages (with an external, web-server-served stylesheet/script) and the Apache/Nginx config to serve them — from the same component as the dynamic pages, so they look identical.

The assets are built by Vite + Tailwind 4 + SCSS into a committed bundle; edit the SCSS/JS and run `npm run build` to regenerate it. For hosts without a Laravel deploy, `server-error-pages:export` inlines everything into fully self-contained single-file pages.

## <a name="documentation"></a>Documentation

Full documentation is hosted at
**<https://opensource.simtabi.com/documentation/laranail/server-error-pages/>**.

### Guides

- [Installation](docs/installation.md) — requirements, publishing, first build.
- [Getting started](docs/getting-started.md) — render, customize, and generate pages in five minutes.
- [Configuration](docs/configuration.md) — every config key and env var.
- [Architecture](docs/architecture.md) — the one-source, three-outputs model and content resolution.
- [Release](docs/release.md) — versioning and publishing.

### Reference

- [`server-error-pages:build`](docs/tools/build-command.md) — generate static pages and server config.
- [`server-error-pages:export`](docs/tools/export-command.md) — export self-contained single-file pages.
- [`server-error-pages:server-config`](docs/tools/server-config-command.md) — print or write the web-server config.
- [`server-error-pages:install`](docs/tools/install-command.md) — one-step scaffold.
- [`server-error-pages:clear`](docs/tools/clear-command.md) — remove generated files.

### Recipes

- [VPS with git + Nginx](docs/recipes/vps-git-nginx.md)
- [Shared hosting over FTP](docs/recipes/shared-hosting-ftp.md)
- [Standalone export](docs/recipes/standalone-export.md)
- [Managing content](docs/recipes/managing-content.md)
- [Customizing components and themes](docs/recipes/customizing-components-themes.md)
- [Overriding an error view](docs/recipes/overriding-error-views.md)
- [Zero-downtime static pages](docs/recipes/zero-downtime-static-pages.md)

A runnable reference deployment (the two install pipelines for VPS and shared
hosting) lives in the separate
[`laranail/server-error-pages-demo`](https://github.com/laranail/server-error-pages-demo)
repository.

## License

MIT © [Simtabi LLC](https://opensource.simtabi.com). See [LICENSE](LICENSE).
