# Standalone export

Ship branded error pages to any host — a static host, CDN, object store, or edge maintenance page — with no Laravel deploy, as fully self-contained single files.

## When you need this

The default [`server-error-pages:build`](../tools/build-command.md) links an external stylesheet and script (`assets_url`), which the web server serves alongside the pages on a normal Laravel deploy. But when there is no Laravel — a bare static host, a CDN origin, a load balancer's maintenance page — you want each `{code}.html` to be one file with **zero external requests**. That is the standalone export.

## Export

```bash
php artisan server-error-pages:export
```

Or, equivalently, `php artisan server-error-pages:build --standalone`. It renders each page, inlines the CSS, JS, and local logo, and asserts the result is fully self-contained — failing loudly if any external reference survives. Output lands in `public/errors/` (per `output.path`).

## Upload

Copy just the pages (and, on Apache, the generated `.htaccess`) to the host's document root:

```text
public/errors/
├── 400.html
├── 402.html
├── 404.html
├── 4xx.html
├── 500.html
├── 5xx.html
└── … (every enabled code)
```

Each file embeds its own `<style>`, `<script>`, and logo, so it renders correctly even offline. Point the host's error handling at them — an `.htaccess` `ErrorDocument`, a bucket's website-error-document setting, or a CDN error-page rule.

## Use a local logo

A remote `http(s)` `brand.logo` cannot be inlined and will trip the self-containment check. Use a local file path so it is embedded as a data-URI:

```dotenv
SERVER_ERROR_PAGES_LOGO="resources/branding/acme-mark.svg"
```

> The self-containment assertion is a feature: a page that would fetch a font, script, or image from the network renders broken exactly when there is no app to serve a fallback. Fix the offending reference and re-export.

## Keeping the linked build for Laravel

Only reach for the export when you are hosting the pages **outside** a Laravel app. On a normal deploy, the linked build is smaller and lets the browser cache one shared stylesheet across every error page — see [Zero-downtime static pages](zero-downtime-static-pages.md).

## Related

- [`server-error-pages:export`](../tools/export-command.md) — the command reference.
- [Shared hosting over FTP](shared-hosting-ftp.md) — uploading a standalone export by FTP.

---
[← Docs index](../../README.md#documentation)
