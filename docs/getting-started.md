# Getting started

Render, brand, and generate your error pages in a few minutes.

## The mental model

One Blade component is the single source for every page. Laravel renders it live while the app is up (dynamic path), and `server-error-pages:build` renders the exact same component to flat HTML files (static path) that your web server serves when PHP or the app is down. See [Architecture](architecture.md) for the full model.

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
SERVER_ERROR_PAGES_LOGO="resources/branding/acme-mark.svg"
```

A local `logo` file is inlined as a data-URI at build time so the static pages stay self-contained. Brand name and URL fall back to `APP_NAME` and `APP_URL` when unset.

## 3. Pick a look

```dotenv
SERVER_ERROR_PAGES_THEME=midnight     # default | slate | midnight | emerald | crimson
SERVER_ERROR_PAGES_AUTO_DARK=true
```

Colours are driven by CSS custom properties (`--sep-*`), so re-branding needs no asset rebuild — change the `theme.colors.light` / `theme.colors.dark` values and rebuild the static pages.

## 4. Edit the words

Open `resources/error-pages/en.json` and change any title or message:

```json
{
    "503": {
        "title": "Back in a moment",
        "message": "We are deploying an update. This page will refresh itself shortly."
    }
}
```

Delete a code entirely to fall back to the built-in default. See [Managing content in JSON](recipes/managing-content-json.md).

## 5. Rebuild the static pages

Any change to config, content, or theme only reaches the static files after a rebuild:

```bash
php artisan server-error-pages:build
```

This writes `public/errors/{code}.html` (plus generic `4xx.html` / `5xx.html`) and the Apache/Nginx config. The build fails loudly if any page references an external URL — that guarantee is what makes the pages survive the app being down.

## 6. Wire up the web server

- On a VPS with Nginx, include the generated snippet and turn on FastCGI intercept — see [VPS with git + Nginx](recipes/vps-git-nginx.md).
- On shared hosting, FTP-upload `public_html` including `errors/*.html` and the generated `.htaccess` — see [Shared hosting over FTP](recipes/shared-hosting-ftp.md).

## Where to go next

- [Configuration](configuration.md) — every key and env var.
- [`server-error-pages:build`](tools/build-command.md) — the command you run on every change.
- [Overriding an error view](recipes/overriding-error-views.md) — replace a page's markup entirely.

---
[← Docs index](../README.md#documentation)
