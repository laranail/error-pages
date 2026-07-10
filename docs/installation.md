# Installation

Requirements, install, and first build for `laranail/server-error-pages`.

## Requirements

| Requirement | Constraint |
|-------------|-----------|
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel (`illuminate/*`) | `^13.0` |
| `laranail/package-tools` | `^4.0` |
| `laranail/enumerator` | `^0.4` |
| `laranail/console` | `^1.1` |
| `laranail/toolkit` | `^0.3` |

There is no database, no cache table, and no admin UI. All content is managed with a PHP config file and per-locale JSON files that you edit and redeploy (git on a VPS, FTP on shared hosting).

## Install the package

```bash
composer require laranail/server-error-pages
```

The service provider (`Simtabi\Laranail\ServerErrorPages\Providers\ServerErrorPagesServiceProvider`) is auto-discovered — no manual registration.

## Run the installer

```bash
php artisan server-error-pages:install
```

The installer runs three steps in order:

1. Publishes `config/server-error-pages.php`.
2. Publishes the editable content JSON to `resources/error-pages/` (tag `server-error-pages::content`).
3. Runs `server-error-pages:build` to generate the static HTML pages and the Apache/Nginx config.

After it finishes you have branded dynamic error views working immediately, static fallback pages under `public/errors/`, and a generated `.htaccess` / `errors.conf` ready to wire into your web server.

> The install command's alias is `server-error-pages:install`; its fully namespaced name is `laranail::server-error-pages.install`. Either form works.

## What gets published

| Artifact | Destination | Publish tag |
|----------|-------------|-------------|
| Config | `config/server-error-pages.php` | (published by the installer's config step) |
| Content JSON | `resources/error-pages/{locale}.json` | `server-error-pages::content` |
| Compiled assets (optional) | `public/vendor/server-error-pages/` | `server-error-pages::assets` |

You rarely need the `assets` tag: the CSS and JS are inlined into every page at render time, so publishing the raw bundle is only useful if you want to serve it as a normal static asset elsewhere.

## Verify

```bash
php artisan about
```

Look for the "Server Error Pages" section, which reports the content source, output path, active theme, enabled codes, and server profile. To confirm a dynamic page renders, visit any unknown URL in your app and you should see the branded 404.

---
[← Docs index](../README.md#documentation)
