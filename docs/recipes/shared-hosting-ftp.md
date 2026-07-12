# Shared hosting over FTP

Ship branded static error pages to cPanel-style shared hosting that has Apache and often no SSH.

## The approach

Shared hosts usually run Apache and rarely give you a shell, so you build locally (or in CI) and FTP the result up. Because the host has no reliable way to serve a separate assets directory for you, the cleanest path is the **standalone export** — self-contained single-file pages you upload alongside the `.htaccess`.

## Steps

1. Set the Apache profile locally, in `.env` or `config/server-error-pages.php`:

   ```dotenv
   SERVER_ERROR_PAGES_PROFILE=shared
   ```

2. Export self-contained pages and the `.htaccess` on your machine or in CI:

   ```bash
   php artisan server-error-pages:export
   ```

   With the defaults this produces `public/errors/*.html` (each with CSS/JS/logo inlined — zero external requests) and `public/.htaccess` containing the `ErrorDocument` lines and security headers.

3. FTP-upload your `public_html` (or the host's document root), including:
   - `errors/*.html`
   - the generated `.htaccess`

   The generated block is delimited by `# BEGIN laranail/server-error-pages` / `# END laranail/server-error-pages` markers and is merged into your existing `.htaccess` automatically — Laravel's rewrite rules are preserved. If you keep a separate `.htaccess`, point `server.apache.output` at another file and merge the marked block by hand.

4. Load a missing URL in a browser to confirm the branded 404 renders.

## Subdirectory installs

If the app lives under a subpath (for example `example.com/shop`), set the URL path so the `ErrorDocument` targets resolve:

```dotenv
SERVER_ERROR_PAGES_URL_PATH=/shop/errors
```

Then re-export before uploading.

> The standalone export is what makes shared hosting reliable: you never depend on the host serving a separate assets folder, and each page renders even if only that one `.html` reaches the browser. Run `server-error-pages:export` in CI or on your workstation and treat `public/errors/` and `.htaccess` as build artifacts — never expect the host to run artisan.

## Related

- [Standalone export](standalone-export.md) · [`server-error-pages:export`](../tools/export-command.md)
- [Managing content](managing-content.md)
- [Configuration](../configuration.md) — `output.url_path` and the `server.apache.*` keys.

---
[← Docs index](../../README.md#documentation)
