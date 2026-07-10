# Zero-downtime static pages

Build the static error pages in CI so the branded 502/503/504 is already on disk before a deploy starts.

## Why build ahead of the deploy

The static pages exist for the moments the app cannot render: a mid-deploy window, a PHP-FPM restart, or a fatal boot error. If you only build them *after* switching to new code, there is a gap where an outage would show the web server's raw error. Building them as a release artifact — or early in the deploy — closes that gap.

## Build in CI

Run the build during your pipeline and treat the output as part of the release:

```yaml
# .github/workflows/deploy.yml (excerpt)
- name: Build static error pages
  run: php artisan server-error-pages:build

- name: Package release
  run: tar -czf release.tgz public/errors public/.htaccess storage/app/server-error-pages
```

The build fails if any page is not self-contained, so a broken release never ships.

## Serve them through the outage window

- **Nginx (VPS):** the `error_page` snippet plus `fastcgi_intercept_errors on;` means Nginx serves the flat files whenever PHP-FPM returns 502/503/504 — including while `php-fpm` is being reloaded during a deploy. Include the snippet once and it survives every release. See [VPS with git + Nginx](vps-git-nginx.md).
- **Apache (shared):** the `ErrorDocument` lines in `.htaccess` do the same for the app-down case. Add `ProxyErrorOverride On` when PHP-FPM is proxied so the static documents replace backend 5xx responses.

## Deploy an intentional maintenance page

To take the site down on purpose during a risky migration, return a 503 from your load balancer or app entrypoint and let the static `503.html` render. The 503 page is retryable: its `<meta http-equiv="refresh">` points at `output.url_base`, so visitors are bounced back to the site automatically once it returns.

## Keep the build fresh

Rebuild whenever content, config, or theme changes — otherwise the static files lag behind the dynamic ones:

```bash
php artisan server-error-pages:build
```

Wiring the build into CI makes this automatic on every release.

> Atomic deploys (symlink-swap releases) work cleanly here: build into the new release directory, point the web-server include at a stable path (or re-include per release), and the swap brings the fresh pages live with the code.

## Related

- [`server-error-pages:build`](../tools/build-command.md) · [VPS with git + Nginx](vps-git-nginx.md) · [Shared hosting over FTP](shared-hosting-ftp.md)
- [Architecture](../architecture.md) — why the static path exists.

---
[← Docs index](../../README.md#documentation)
