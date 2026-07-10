# Configuration

Every key in `config/server-error-pages.php` and its matching `SERVER_ERROR_PAGES_*` env var.

All keys live under `config('laranail.server-error-pages.*')`. After changing any value, run `server-error-pages:build` to regenerate the static pages and server config — the dynamic Blade views pick up config changes on the next request, but the static files do not update until you rebuild. Changing the SCSS or JS additionally needs `npm run build` (see [Customizing components and themes](recipes/customizing-components-themes.md)).

## `brand`

Identity shown on every page.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `brand.name` | `SERVER_ERROR_PAGES_BRAND` | `APP_NAME`, else `Our site` | Displayed name. |
| `brand.url` | `SERVER_ERROR_PAGES_BRAND_URL` | `APP_URL`, else `/` | "Home" link target. |
| `brand.logo` | `SERVER_ERROR_PAGES_LOGO` | `null` | URL, absolute path, or path relative to the app base. Used as-is in an `<img src>`; a **local file is inlined as a data-URI only by the standalone export** so those pages stay self-contained. |

## `codes`

Which HTTP status codes are rendered and generated.

| Key | Default | Notes |
|-----|---------|-------|
| `codes.enabled` | `[400, 401, 402, 403, 404, 419, 429, 500, 502, 503, 504]` | Each gets a dedicated page. |
| `codes.fallbacks` | `true` | Also emit generic `4xx` / `5xx` pages and route the many other status codes to them in the server config. |

## `content`

Per-code titles and messages come from Laravel translations, keyed by status
(`resources/lang/{locale}/errors.php`, keys like `'404'` or the generic `'4xx'`).

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `content.default_locale` | `SERVER_ERROR_PAGES_LOCALE` | `APP_LOCALE`, else `en` | The locale baked into the static build. Dynamic pages honour the request locale. |

Resolution precedence per code: **app override** (`lang/vendor/server-error-pages/{locale}/errors.php`) → **package translations** (`resources/lang/{locale}/errors.php`) → **built-in `HttpStatus` enum default**. Because the enum default is always present, an unconfigured install still renders complete pages. See [Managing content](recipes/managing-content.md).

## `output`

Static-generation targets.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `output.disk` | `SERVER_ERROR_PAGES_DISK` | `null` | A filesystem disk name, or `null` to write to `path` on the local disk. |
| `output.path` | `SERVER_ERROR_PAGES_OUTPUT` | `public_path('errors')` | Where `{code}.html` files are written. |
| `output.url_path` | `SERVER_ERROR_PAGES_URL_PATH` | `/errors` | Root-relative URL where the pages are served; used in the Apache/Nginx config. Set to `/myapp/errors` for a subdirectory install. |
| `output.assets_url` | `SERVER_ERROR_PAGES_ASSETS_URL` | `/vendor/server-error-pages` | Root-relative URL the pages `<link>`/`<script>` the CSS/JS from. Kept **outside** the internal `/errors/` location so the web server serves it. |
| `output.assets_path` | `SERVER_ERROR_PAGES_ASSETS_PATH` | `public_path('vendor/server-error-pages')` | Filesystem path the linked bundle (and any generated `css/theme.css`) is copied to on each linked build. |
| `output.url_base` | `SERVER_ERROR_PAGES_URL_BASE` | `/` | Site root for the "home" and retry links on the pages themselves. |
| `output.filename` | — | `{code}.html` | Filename pattern. |
| `output.minify` | `SERVER_ERROR_PAGES_MINIFY` | `true` | Strip indentation and blank lines while keeping newlines, so any inlined `<style>`/`<script>` bodies stay valid. |

## `theme`

Presentation. The preset's colours live in the compiled CSS; only per-token overrides are carried into a generated `css/theme.css`.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `theme.preset` | `SERVER_ERROR_PAGES_THEME` | `default` | Shipped colour theme: `default`, `slate`, `midnight`, `emerald`, or `crimson`. Applied as a `sep-theme-{preset}` body class — switching needs **no rebuild** (all presets are in the one stylesheet). |
| `theme.auto_dark` | `SERVER_ERROR_PAGES_AUTO_DARK` | `true` | Toggle the `sep-auto-dark` body class so the preset's dark variant follows the visitor's `prefers-color-scheme`. |
| `theme.colors.{light,dark}` | — | `[]` | Optional per-token overrides merged on top of the preset. Any set token generates a linked `css/theme.css` at build time (no SCSS rebuild). |

Colour tokens in each scheme: `bg`, `surface`, `text`, `muted`, `accent`, `accent-2`, `border`.

```php
'theme' => [
    'preset' => 'slate',
    'auto_dark' => true,
    'colors' => [
        'light' => ['accent' => '#ea580c'],
        'dark'  => ['accent' => '#fb923c'],
    ],
],
```

## `assets`

Optional path overrides for the compiled CSS / JS the **standalone export** inlines. Both default to the package's committed `public/assets` bundle.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `assets.compiled_css` | `SERVER_ERROR_PAGES_CSS` | `null` | Path to the CSS inlined by `--standalone`. `null` uses `public/assets/css/error-pages.css`. |
| `assets.static_js` | `SERVER_ERROR_PAGES_JS` | `null` | Path to the JS inlined by `--standalone`. `null` uses `public/assets/js/error-pages.js`. |

These only affect the standalone export; the linked (default) build always serves the committed bundle.

## `security.headers`

Emitted into the generated Apache/Nginx config so they apply to the static pages, where the app cannot set headers. Set any value to `null` to skip it.

| Header | Default |
|--------|---------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `no-referrer` |
| `Content-Security-Policy` | `default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; base-uri 'none'; form-action 'self'` |

## `server`

Web-server config generation. Outputs are written to app/FTP-writable locations by default — never to `/etc` — and the commands print the include line for you to wire in.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `server.profile` | `SERVER_ERROR_PAGES_PROFILE` | `vps` | `shared` or `vps`. Selects the Apache stub set (stubs live under `stubs/{apache,nginx}/`). |
| `server.apache.enabled` | `SERVER_ERROR_PAGES_APACHE` | `true` | Emit the Apache config. |
| `server.apache.output` | `SERVER_ERROR_PAGES_APACHE_OUTPUT` | `public_path('.htaccess')` | Where the `.htaccess` block is merged. The managed block is written between sentinel markers, so Laravel's existing rewrite rules are preserved. |
| `server.nginx.enabled` | `SERVER_ERROR_PAGES_NGINX` | `true` | Emit the Nginx config. |
| `server.nginx.output` | `SERVER_ERROR_PAGES_NGINX_OUTPUT` | `storage_path('app/server-error-pages/errors.conf')` | Where the `error_page` snippet is written. |
| `server.nginx.fastcgi_intercept` | — | `true` | Reminds you that the PHP location needs `fastcgi_intercept_errors on;`. |

---
[← Docs index](../README.md#documentation)
