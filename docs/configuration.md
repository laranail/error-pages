# Configuration

Every key in `config/server-error-pages.php` and its matching `SERVER_ERROR_PAGES_*` env var.

All keys live under `config('laranail.server-error-pages.*')`. After changing any value, run `server-error-pages:build` to regenerate the static pages and server config — the dynamic Blade views pick up config changes on the next request, but the static files do not update until you rebuild.

## `brand`

Identity shown on every page.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `brand.name` | `SERVER_ERROR_PAGES_BRAND` | `APP_NAME`, else `Our site` | Displayed name. |
| `brand.url` | `SERVER_ERROR_PAGES_BRAND_URL` | `APP_URL`, else `/` | "Home" link target. |
| `brand.logo` | `SERVER_ERROR_PAGES_LOGO` | `null` | URL, absolute path, or path relative to the app base. A local file is inlined as a data-URI so static pages stay self-contained. |

## `codes`

Which HTTP status codes are rendered and generated.

| Key | Default | Notes |
|-----|---------|-------|
| `codes.enabled` | `[400, 401, 403, 404, 419, 429, 500, 502, 503, 504]` | Each gets a dedicated page. |
| `codes.fallbacks` | `true` | Also emit generic `4xx` / `5xx` pages and route the many other status codes to them in the Nginx config. |

## `content`

Where per-code titles and messages come from.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `content.source` | `SERVER_ERROR_PAGES_CONTENT` | `json` | `json` prefers the JSON files; `config` ignores them and uses only the `messages` array. |
| `content.json_path` | — | `resources/error-pages` | Resolved relative to the app base path. |
| `content.default_locale` | `SERVER_ERROR_PAGES_LOCALE` | `APP_LOCALE`, else `en` | Which `{locale}.json` file is read. |

Resolution precedence per code: **published JSON file** (`resources/error-pages/{locale}.json`) → **`messages.{code}`** in config → **built-in `HttpStatus` enum default**. Because the enum default is always present, an unconfigured install still renders complete pages.

## `messages`

Inline per-code overrides, keyed by code. Useful when you want to keep everything in one PHP file instead of JSON.

```php
'messages' => [
    503 => ['title' => 'Back shortly', 'message' => 'We are upgrading the site.'],
],
```

## `output`

Static-generation targets.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `output.disk` | `SERVER_ERROR_PAGES_DISK` | `null` | A filesystem disk name, or `null` to write to `path` on the local disk. |
| `output.path` | `SERVER_ERROR_PAGES_OUTPUT` | `public_path('errors')` | Where `{code}.html` files are written. |
| `output.url_path` | `SERVER_ERROR_PAGES_URL_PATH` | `/errors` | Root-relative URL where the pages are served; used in the Apache/Nginx config. Set to `/myapp/errors` for a subdirectory install. |
| `output.url_base` | `SERVER_ERROR_PAGES_URL_BASE` | `/` | Site root for the "home" and retry links on the pages themselves. |
| `output.filename` | — | `{code}.html` | Filename pattern. |
| `output.minify` | `SERVER_ERROR_PAGES_MINIFY` | `true` | Strip indentation and blank lines while keeping newlines so inlined `<style>`/`<script>` bodies stay valid. |

## `theme`

Presentation.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `theme.preset` | `SERVER_ERROR_PAGES_THEME` | `default` | Shipped colour theme: `default`, `slate`, `midnight`, `emerald`, or `crimson`. |
| `theme.colors.{light,dark}` | — | `[]` | Optional per-token overrides (`bg`, `surface`, `text`, `muted`, `accent`, `border`) merged on top of the preset. |
| `theme.auto_dark` | `SERVER_ERROR_PAGES_AUTO_DARK` | `true` | Follow the visitor's `prefers-color-scheme`. |
| `theme.colors.light` / `theme.colors.dark` | — | see below | Drive the `--sep-*` CSS custom properties. Re-branding needs no asset rebuild. |

Colour keys in each scheme: `bg`, `surface`, `text`, `muted`, `accent`, `border`.

```php
'colors' => [
    'light' => ['bg' => '#f8fafc', 'surface' => '#ffffff', 'text' => '#0f172a', 'muted' => '#64748b', 'accent' => '#4f46e5', 'border' => '#e2e8f0'],
    'dark'  => ['bg' => '#0b1120', 'surface' => '#111827', 'text' => '#f1f5f9', 'muted' => '#94a3b8', 'accent' => '#818cf8', 'border' => '#1f2937'],
],
```

## `assets`

Override the inlined bundle. Both default to the package's committed `resources/dist` files.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `assets.compiled_css` | `SERVER_ERROR_PAGES_CSS` | `null` | Path to the CSS inlined into every page. |
| `assets.static_js` | `SERVER_ERROR_PAGES_JS` | `null` | Path to the vanilla JS inlined into every page. |

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
| `server.profile` | `SERVER_ERROR_PAGES_PROFILE` | `vps` | `shared` (Apache `.htaccess`) or `vps` (Nginx vhost + FastCGI intercept). Selects the stub set. |
| `server.apache.enabled` | `SERVER_ERROR_PAGES_APACHE` | `true` | Emit the Apache config. |
| `server.apache.output` | `SERVER_ERROR_PAGES_APACHE_OUTPUT` | `public_path('.htaccess')` | Where the `.htaccess` block is merged. The managed block is written between sentinel markers, so Laravel's existing rewrite rules are preserved. |
| `server.nginx.enabled` | `SERVER_ERROR_PAGES_NGINX` | `true` | Emit the Nginx config. |
| `server.nginx.output` | `SERVER_ERROR_PAGES_NGINX_OUTPUT` | `storage_path('app/server-error-pages/errors.conf')` | Where the `error_page` snippet is written. |
| `server.nginx.fastcgi_intercept` | — | `true` | Reminds you that the PHP location needs `fastcgi_intercept_errors on;`. |

---
[← Docs index](../README.md#documentation)
