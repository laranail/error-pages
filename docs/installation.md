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
| Node.js + npm | Only to rebuild assets (`npm run build`); a committed bundle ships, so consumers rarely need it. |

There is no database, no cache table, and no admin UI. Content is Laravel translation files you edit and redeploy (git on a VPS, FTP on shared hosting).

## Install the package

```bash
composer require laranail/server-error-pages
```

The service provider (`Simtabi\Laranail\ServerErrorPages\Providers\ServerErrorPagesServiceProvider`) is auto-discovered — no manual registration.

## Run the installer

```bash
php artisan server-error-pages:install
```

The installer runs, in order:

1. Publishes `config/server-error-pages.php`.
2. Publishes the error-view stubs to `resources/views/errors/` (Laravel's conventional error views).
3. Publishes the content translations to `lang/vendor/server-error-pages/`.
4. Publishes the compiled asset bundle to `public/vendor/server-error-pages/`.
5. Runs `server-error-pages:build` to generate the static HTML pages and the Apache/Nginx config.

After it finishes you have branded dynamic error views working immediately, static fallback pages under `public/errors/`, the linked CSS/JS under `public/vendor/server-error-pages/`, and a generated `.htaccess` / `errors.conf` ready to wire into your web server.

> The install command's alias is `server-error-pages:install`; its fully namespaced name is `laranail::server-error-pages.install`. Either form works.

## What gets published

| Artifact | Destination | Publish tag |
|----------|-------------|-------------|
| Config | `config/server-error-pages.php` | `laranail::server-error-pages-config` |
| Error views | `resources/views/errors/{code}.blade.php` | `laranail::server-error-pages-errors` |
| Content translations | `lang/vendor/server-error-pages/{locale}/errors.php` | `laranail::server-error-pages-translations` |
| Compiled asset bundle | `public/vendor/server-error-pages/{css,js}/` | `laranail::server-error-pages-assets` |

Publish any one individually with `vendor:publish --tag=<tag>`. The linked build also copies the bundle to `output.assets_path` on every run, so the assets are present even if you skip the `-assets` tag.

## Rebuilding the assets (maintainers)

The shipped `public/assets/` bundle is committed, built by Vite + Tailwind 4 + SCSS from `resources/assets/{scss,scripts}`. You only rebuild it after editing that source:

```bash
npm install
npm run build
```

This regenerates `public/assets/css/error-pages.css` and `public/assets/js/error-pages.js`. `server-error-pages:build` refuses to run if that bundle is missing.

## Verify

```bash
php artisan about
```

Look for the "Server Error Pages" section, which reports the output path, assets URL, active theme, enabled codes, and server profile. To confirm a dynamic page renders, visit any unknown URL in your app and you should see the branded 404.

---
[← Docs index](../README.md#documentation)
