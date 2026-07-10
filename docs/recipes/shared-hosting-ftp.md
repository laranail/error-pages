# Shared hosting over FTP

Ship branded static error pages to cPanel-style shared hosting that has Apache and often no SSH.

## The approach

Shared hosts usually run Apache and rarely give you a shell, so you build locally (or in CI) and FTP the result up. The host's PHP still serves the dynamic Blade pages while the app is alive; the static `.html` files and `.htaccess` cover the moments PHP is unavailable.

## Steps

1. Set the Apache profile locally, in `.env` or `config/server-error-pages.php`:

   ```dotenv
   SERVER_ERROR_PAGES_PROFILE=shared
   ```

2. Build the pages and the `.htaccess` on your machine or in CI:

   ```bash
   php artisan server-error-pages:build
   ```

   With the defaults this produces `public/errors/*.html` and `public/.htaccess` containing the `ErrorDocument` lines and security headers.

3. FTP-upload your `public_html` (or the host's document root), including:
   - `errors/*.html`
   - the generated `.htaccess`

   If you already maintain an `.htaccess`, point the output at a separate file instead and merge by hand, or append the generated block — it is delimited by `# --- laranail/server-error-pages` markers.

4. Load a missing URL in a browser to confirm the branded 404 renders.

## Subdirectory installs

If the app lives under a subpath (for example `example.com/shop`), set the URL path so the `ErrorDocument` targets resolve:

```dotenv
SERVER_ERROR_PAGES_URL_PATH=/shop/errors
```

Then rebuild before uploading.

> Many shared hosts have no CLI at all. Run `server-error-pages:build` in CI or on your workstation and treat `public/errors/` and `.htaccess` as build artifacts you deploy — never expect the host to run artisan.

## Related

- [`server-error-pages:build`](../tools/build-command.md) · [Managing content in JSON](managing-content-json.md)
- [Configuration](../configuration.md) — `output.url_path` and the `server.apache.*` keys.

---
[← Docs index](../../README.md#documentation)
