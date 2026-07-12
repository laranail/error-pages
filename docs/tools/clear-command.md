# `server-error-pages:clear`

The `server-error-pages:clear` command removes the generated static pages and the emitted server-config files.

Use it to reset a build directory, or before switching output paths so stale files are not left behind.

## Usage

```bash
php artisan server-error-pages:clear
```

The fully namespaced name is `laranail::server-error-pages.clear`; the alias `server-error-pages:clear` is equivalent.

## What it removes

1. Every generated static page for the configured keys (enabled codes plus the generic `4xx` / `5xx`) under `output.path`.
2. The package's managed block in the Apache config at `server.apache.output` (default `public/.htaccess`). Only the block between the `# BEGIN laranail/server-error-pages` and `# END` markers is stripped — any other rules (including Laravel's front-controller rewrite) are left intact. If the file then holds nothing else, it is deleted.
3. The Nginx config at `server.nginx.output` (default `storage/app/server-error-pages/errors.conf`). Being a dedicated file, it is deleted once its managed block is the only content.

It prints each cleaned path and a final count. When there is nothing to remove it reports `Nothing to remove.` and exits successfully.

## Output

```text
  cleaned /var/www/app/public/errors/404.html
  cleaned /var/www/app/public/errors/503.html
  cleaned /var/www/app/public/.htaccess
  cleaned /var/www/app/storage/app/server-error-pages/errors.conf
Cleaned 4 file/location(s).
```

> `clear` is safe to run against your real `public/.htaccess`: it removes only the package's managed block, never Laravel's rewrite rules.

## Related

- [`server-error-pages:build`](build-command.md) — regenerate what `clear` removed.

---
[← Docs index](../../README.md#documentation)
