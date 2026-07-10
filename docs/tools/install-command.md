# `server-error-pages:install`

The `server-error-pages:install` command publishes the config, error views, translations, and asset bundle, then builds the static pages and server config — the one-step scaffold you run once after requiring the package.

## Usage

```bash
php artisan server-error-pages:install
```

The fully namespaced name is `laranail::server-error-pages.install`; the alias `server-error-pages:install` is equivalent.

## Steps

The installer runs these in order:

1. **Publish config** — writes `config/server-error-pages.php`.
2. **Publish error views** — copies the `errors/{code}.blade.php` stubs to `resources/views/errors/`.
3. **Publish content translations** — copies `errors.php` to `lang/vendor/server-error-pages/{locale}/`.
4. **Publish assets** — copies the compiled CSS/JS bundle to `public/vendor/server-error-pages/`.
5. **Build static pages and server config** — runs `server-error-pages:build`.

After it completes you have a published config, conventional Laravel error views, editable translation files, the linked asset bundle, static pages under `public/errors/`, and the generated Apache/Nginx config.

## Publish tags

If you need to re-publish an individual artifact later, use `vendor:publish` directly:

| Tag | Copies |
|-----|--------|
| `laranail::server-error-pages-config` | Config → `config/server-error-pages.php` |
| `laranail::server-error-pages-errors` | Error views → `resources/views/errors/` |
| `laranail::server-error-pages-translations` | Content translations → `lang/vendor/server-error-pages/` |
| `laranail::server-error-pages-assets` | Compiled CSS/JS bundle → `public/vendor/server-error-pages/` |

```bash
php artisan vendor:publish --tag=laranail::server-error-pages-translations
```

The linked build also copies the bundle to `output.assets_path` on every run, so you rarely need to re-publish the `-assets` tag by hand.

## Related

- [Installation](../installation.md) — requirements and verification.
- [`server-error-pages:build`](build-command.md) — the step you re-run on every content or config change.

---
[← Docs index](../../README.md#documentation)
