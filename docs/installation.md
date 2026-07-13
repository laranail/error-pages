# Installation

Install the package and publish the pieces you want to customise.

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

## Install

```bash
composer require laranail/error-pages
```

The service provider is auto-discovered. Out of the box the package renders branded
pages for production-style error responses (404, 403, 500, 502, 503, 504, 429, 401);
genuine unhandled 500s in local dev still show Ignition's debug page. Nothing else is
required.

## Publish

Everything is optional — publish only what you need to customise. Tags:

| Tag | Publishes | Use when |
|-----|-----------|----------|
| `laranail::error-pages-config` | `config/error-pages.php` | tune codes, stack, theme, brand, coexistence |
| `laranail::error-pages-translations` | `lang/vendor/error-pages/…` | override or translate the copy |
| `laranail::error-pages-views` | `resources/views/errors/*` | replace a page's Blade markup wholesale |

```bash
php artisan vendor:publish --tag=laranail::error-pages-config
```

## Verify

```bash
# health check
php artisan laranail::package-tools.doctor

# preview any page in dev (APP_DEBUG=true)
php artisan laranail::error-pages.preview 503 --output=storage/preview-503.html
```

Or hit the preview route while `APP_DEBUG=true`: `GET /_error-pages/503`.

## Optional integrations

- **Inertia** (Vue/React SPA stack): `composer require inertiajs/inertia-laravel`.
- **Filament / Nova**: the panel stacks activate when those packages are installed.

---
[← Docs index](../README.md#documentation)
