# Getting started

Render, brand, and generate your error pages in a few minutes.

## The mental model

One Blade component is the single source for every page. Laravel renders it live while the app is up (dynamic path), and `server-error-pages:build` renders the exact same component to flat HTML files (static path) that your web server serves when PHP or the app is down. Both **link** an external stylesheet and script that the web server serves too, so the fallback survives an outage. See [Architecture](architecture.md) for the full model.

## 1. Install

```bash
composer require laranail/server-error-pages
php artisan server-error-pages:install
```

At this point unconfigured defaults already render — the built-in `HttpStatus` enum carries a sensible title and message for every code, so nothing else is required to get branded pages.

## 2. Set your brand

Edit `config/server-error-pages.php` or your `.env`:

```dotenv
SERVER_ERROR_PAGES_BRAND="Acme Store"
SERVER_ERROR_PAGES_BRAND_URL="https://acme.test"
SERVER_ERROR_PAGES_LOGO="/vendor/server-error-pages/img/acme-mark.svg"
```

The logo is used as-is in an `<img src>`. Brand name and URL fall back to `APP_NAME` and `APP_URL` when unset.

## 3. Pick a look

```dotenv
SERVER_ERROR_PAGES_THEME=midnight     # default | slate | midnight | emerald | crimson
SERVER_ERROR_PAGES_AUTO_DARK=true
```

The preset is applied as a `sep-theme-{preset}` body class — every preset is compiled into the one stylesheet, so switching needs no asset rebuild. For per-token tweaks, set `theme.colors.light` / `theme.colors.dark` (they generate a linked `css/theme.css` at build time). See [Customizing components and themes](recipes/customizing-components-themes.md).

## 4. Edit the words

Content is Laravel translations. Publish and edit them:

```php
// lang/vendor/server-error-pages/en/errors.php
return [
    '503' => [
        'title' => 'Back in a moment',
        'message' => 'We are deploying an update. This page will refresh itself shortly.',
    ],
];
```

Omit a code entirely to fall back to the built-in default. See [Managing content](recipes/managing-content.md).

## 5. Rebuild the static pages

Any change to config, content, or theme only reaches the static files after a rebuild:

```bash
php artisan server-error-pages:build
```

This writes `public/errors/{code}.html` (plus generic `4xx.html` / `5xx.html`), copies the linked bundle next to them, and writes the Apache/Nginx config.

> Changing the SCSS or JS source is different — that needs `npm run build` to regenerate the committed `public/assets/` bundle first.

## 6. Wire up the web server

- On a VPS with Nginx, include the generated snippet and turn on FastCGI intercept — see [VPS with git + Nginx](recipes/vps-git-nginx.md).
- On shared hosting, FTP-upload `public_html` including `errors/*.html`, the linked assets, and the generated `.htaccess` — see [Shared hosting over FTP](recipes/shared-hosting-ftp.md).

## Deploying without Laravel

If you just want the pages on an arbitrary host with no Laravel deploy, export fully self-contained single files:

```bash
php artisan server-error-pages:export
```

Upload just `public/errors/*.html` + `.htaccess` — no separate assets. See [Standalone export](recipes/standalone-export.md).

## Where to go next

- [Configuration](configuration.md) — every key and env var.
- [`server-error-pages:build`](tools/build-command.md) — the command you run on every change.
- [Overriding an error view](recipes/overriding-error-views.md) — replace a page's markup entirely.

---
[← Docs index](../README.md#documentation)
