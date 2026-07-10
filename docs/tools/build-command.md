# `server-error-pages:build`

The `server-error-pages:build` command renders the self-contained static HTML pages and, unless disabled, the matching web-server config.

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

## What it does

1. Resolves the keys to build — the `--codes` list, or every enabled code plus the generic `4xx` / `5xx` fallbacks.
2. Renders each key through the shared component (the same one the dynamic views use).
3. Asserts the result is self-contained. If any page references an external stylesheet, script, `src`, or CSS `url()`, the build fails with `NotSelfContainedException` and writes nothing.
4. Minifies (when `output.minify` is on) and writes `{output.path}/{code}.html`.
5. Unless `--no-server` is passed, writes the Apache/Nginx config to its configured output paths and prints the include line to wire in.

## Output

```text
+------+---------+-----------------------------------+
| Code | Size    | File                              |
+------+---------+-----------------------------------+
| 404  | 4,812 B | /var/www/app/public/errors/404.html |
| 503  | 4,655 B | /var/www/app/public/errors/503.html |
| 5xx  | 4,690 B | /var/www/app/public/errors/5xx.html |
+------+---------+-----------------------------------+
Generated 3 static error page(s).
  apache → /var/www/app/public/.htaccess
  nginx → /var/www/app/storage/app/server-error-pages/errors.conf
Wire the generated config into your web server (see docs), then reload it.
```

## Exit codes

| Situation | Result |
|-----------|--------|
| Pages generated | success |
| A page is not self-contained | failure — the offending key and violations are printed |
| No pages matched `--codes` | failure with `No pages matched the requested codes.` |

> The self-containment check is a feature, not an obstacle: a page that would fetch a font or script from a CDN renders broken exactly when the app is down. Fix the offending reference (usually a remote logo URL — use a local file so it is inlined) and rebuild.

## Related

- [`server-error-pages:server-config`](server-config-command.md) — regenerate only the web-server config.
- [`server-error-pages:clear`](clear-command.md) — remove everything this command wrote.
- [Zero-downtime static pages](../recipes/zero-downtime-static-pages.md) — build in CI and deploy atomically.

---
[← Docs index](../../README.md#documentation)
