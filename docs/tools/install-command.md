# `server-error-pages:install`

The `server-error-pages:install` command publishes the config and editable content, then builds the static pages and server config — the one-step scaffold you run once after requiring the package.

## Usage

```bash
php artisan server-error-pages:install
```

The fully namespaced name is `laranail::server-error-pages.install`; the alias `server-error-pages:install` is equivalent.

## Steps

The installer runs these in order:

1. **Publish config** — writes `config/server-error-pages.php`.
2. **Publish editable error content** — runs `vendor:publish --tag=server-error-pages::content`, copying the JSON content into `resources/error-pages/`.
3. **Build static pages and server config** — runs `server-error-pages:build`.

After it completes you have a published config, editable `resources/error-pages/{locale}.json` files, static pages under `public/errors/`, and the generated Apache/Nginx config.

## Publish tags

If you need to re-publish an individual artifact later, use `vendor:publish` directly:

| Tag | Copies |
|-----|--------|
| `server-error-pages::content` | Content JSON → `resources/error-pages/` |
| `server-error-pages::assets` | Compiled CSS/JS bundle → `public/vendor/server-error-pages/` |

```bash
php artisan vendor:publish --tag=server-error-pages::content
```

The `assets` tag is optional — the CSS and JS are inlined into every page at render time, so you only need the published bundle if you want to serve it as a regular static asset elsewhere.

## Related

- [Installation](../installation.md) — requirements and verification.
- [`server-error-pages:build`](build-command.md) — the step you re-run on every content or config change.

---
[← Docs index](../../README.md#documentation)
