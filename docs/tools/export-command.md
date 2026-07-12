# `server-error-pages:export`

The `server-error-pages:export` command publishes fully self-contained, portable HTML error pages with the CSS, JS, and logo inlined — for hosting anywhere without deploying the Laravel app.

It is exactly `server-error-pages:build --standalone` under a dedicated name.

## Usage

```bash
php artisan server-error-pages:export
```

The fully namespaced name is `laranail::server-error-pages.export`; the alias `server-error-pages:export` is equivalent. It inherits every option from [`server-error-pages:build`](build-command.md) (`--codes`, `--no-server`) except that standalone mode is always on.

## What it does

1. Renders each configured page through the shared component.
2. Post-processes each page through `HtmlInliner`, replacing the linked stylesheet with an inline `<style>`, the linked script with an inline `<script>`, and a local `brand.logo` with a `data:` URI.
3. Asserts each page is **fully self-contained** — no external stylesheet, script, or `src`. If any survives, it fails with `NotSelfContainedException` and writes nothing.
4. Minifies (when `output.minify` is on, leaving inlined `<style>`/`<script>` bodies intact) and writes `{output.path}/{code}.html`.
5. Unless `--no-server` is passed, writes the Apache/Nginx config too.

The pages carry no reference to `assets_url` — each `{code}.html` is a single file with zero external requests.

## When to use it

Reach for the export when you want the branded pages on a host where you are **not** deploying Laravel:

- A static host, CDN, or object store that only serves flat files.
- Shared hosting where you upload just the error pages and `.htaccess` over FTP.
- A load balancer / edge maintenance page that must render with no backend at all.

Upload `public/errors/*.html` (and the generated `.htaccess`) — nothing else is needed. For the default Laravel deploy, use the linked [`server-error-pages:build`](build-command.md) instead: it is smaller and caches one shared stylesheet.

> A remote `http(s)` `brand.logo` cannot be inlined and will trip the self-containment check. Use a local file path so it is embedded as a data-URI.

## Related

- [`server-error-pages:build`](build-command.md) — the linked (default) build.
- [Standalone export](../recipes/standalone-export.md) — the full hosting-anywhere recipe.
- [Shared hosting over FTP](../recipes/shared-hosting-ftp.md) — uploading a standalone export.

---
[← Docs index](../../README.md#documentation)
