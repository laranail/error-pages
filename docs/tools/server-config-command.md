# `server-error-pages:server-config`

The `server-error-pages:server-config` command prints — or writes — the Apache `.htaccess` / Nginx `error_page` config that points the web server at the static pages, without regenerating the pages themselves.

Use it to preview the config before wiring it in, or to re-emit it after changing `server.*`, `codes.*`, `output.url_path`, or `security.headers`.

## Usage

```bash
php artisan server-error-pages:server-config
```

The fully namespaced name is `laranail::server-error-pages.server-config`; the alias `server-error-pages:server-config` is equivalent.

## Options

| Option | Description |
|--------|-------------|
| `--write` | Write each config to its configured output path instead of printing it. |

## Behaviour

- Without `--write`, the command prints each enabled config (Apache and/or Nginx) to the terminal, prefixed with the path it *would* be written to. Nothing is saved.
- With `--write`, each enabled config is written to its `server.{apache,nginx}.output` path and the command reports where.

Which configs are emitted depends on `server.apache.enabled` and `server.nginx.enabled`. The `server.profile` (`shared` or `vps`) selects the Apache stub set.

## Output paths

| Config | Default output |
|--------|----------------|
| Apache | `public_path('.htaccess')` |
| Nginx | `storage_path('app/server-error-pages/errors.conf')` |

These are app/FTP-writable locations by default — the command never writes to `/etc`. You include the generated file into your `server { }` block (Nginx) or the file already sits in your document root (Apache).

## What the config contains

- An `ErrorDocument` (Apache) or `error_page` (Nginx) line for every enabled code, pointing at `{output.url_path}/{code}.html`.
- When `codes.fallbacks` is on, the Nginx output additionally routes the long tail of other 4xx/5xx status codes to the generic `4xx.html` / `5xx.html`.
- The `security.headers` from config, applied to the static pages where the app cannot set them.
- An `internal` guard on the errors location (Nginx) so the raw files are not directly browsable.

> `server-error-pages:build` already emits this config on every run unless you pass `--no-server`. Reach for `server-error-pages:server-config` when you want the config alone — for example to review a diff or re-emit after tweaking headers.

## Related

- [`server-error-pages:build`](build-command.md) — build pages and config together.
- [VPS with git + Nginx](../recipes/vps-git-nginx.md) and [Shared hosting over FTP](../recipes/shared-hosting-ftp.md).

---
[← Docs index](../../README.md#documentation)
