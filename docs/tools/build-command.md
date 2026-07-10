# `server-error-pages:build`

The `server-error-pages:build` command renders the static HTML pages (linked assets by default) and, unless disabled, the matching web-server config.

This is the command you run after every change to config, content, theme, or the component — nothing reaches the static files until you rebuild.

## Usage

```bash
php artisan server-error-pages:build
```

The fully namespaced name is `laranail::server-error-pages.build`; the alias `server-error-pages:build` is equivalent.

## Options

| Option | Description |
|--------|-------------|
| `--codes=404,503,5xx` | Comma-separated status keys to build. Accepts numeric codes and the generic `4xx` / `5xx` keys. Default: every configured key. |
| `--no-server` | Build only the HTML pages; skip generating the Apache/Nginx config. |
| `--standalone` | Inline CSS/JS/logo into fully self-contained, portable single files (no separate assets). See [`server-error-pages:export`](export-command.md). |

## What it does

1. Asserts the compiled bundle `public/assets/css/error-pages.css` exists — if not, it fails and tells you to run `npm install && npm run build`.
2. Resolves the keys to build — the `--codes` list, or every enabled code plus the generic `4xx` / `5xx` fallbacks.
3. Renders each key through the shared component (the same one the dynamic views use), linking the external stylesheet/script.
4. In `--standalone` mode, inlines the assets and asserts each page is self-contained, failing with `NotSelfContainedException` if any external reference survives.
5. Minifies (when `output.minify` is on) and writes `{output.path}/{code}.html`.
6. In the default (linked) mode, copies the committed bundle to `output.assets_path` and writes a `css/theme.css` there when `theme.colors` overrides are set.
7. Unless `--no-server` is passed, writes the Apache/Nginx config to its configured output paths and prints the include line to wire in.

## Output

```text
+------+---------+-------------------------------------+
| Code | Size    | File                                |
+------+---------+-------------------------------------+
| 404  | 2,140 B | /var/www/app/public/errors/404.html |
| 503  | 2,050 B | /var/www/app/public/errors/503.html |
| 5xx  | 2,080 B | /var/www/app/public/errors/5xx.html |
+------+---------+-------------------------------------+
Generated 3 static error page(s).
  apache → /var/www/app/public/.htaccess
  nginx → /var/www/app/storage/app/server-error-pages/errors.conf
Wire the generated config into your web server (see docs), then reload it.
```

## Exit codes

| Situation | Result |
|-----------|--------|
| Pages generated | success |
| The compiled bundle is missing | failure — run `npm run build` |
| A `--standalone` page is not self-contained | failure — the offending key and violations are printed |
| No pages matched `--codes` | failure with `No pages matched the requested codes.` |

> The linked build stays resilient because the web server serves the CSS/JS from `assets_url` just as it serves the HTML when PHP is down. The self-containment check applies only to `--standalone`, where the whole point is a single file with zero external requests.

## Related

- [`server-error-pages:export`](export-command.md) — the same as `--standalone`, as a named command.
- [`server-error-pages:server-config`](server-config-command.md) — regenerate only the web-server config.
- [`server-error-pages:clear`](clear-command.md) — remove everything this command wrote.
- [Zero-downtime static pages](../recipes/zero-downtime-static-pages.md) — build in CI and deploy atomically.

---
[← Docs index](../../README.md#documentation)
