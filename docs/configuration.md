# Configuration

Every knob in `config/error-pages.php`, published with
`vendor:publish --tag=laranail::error-pages-config`.

## Keys

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `enabled` | bool | `true` | Master switch. When false the package renders nothing (Laravel/Ignition untouched). |
| `stack` | string | `blade` | Default stack: `blade`, `inertia-vue`, `inertia-react`, `vue`, `react` (`livewire` is a server-HTML alias of `blade` for now). |
| `brand.name` | string | `APP_NAME` | Brand shown on the page. |
| `brand.url` | string | `APP_URL` | Brand link. |
| `brand.logo` | ?string | `null` | Logo URL (rendered as `<img>`). |
| `home_url` | string | `APP_URL` | Where "home"/retry links point. |
| `content.default_locale` | string | `APP_LOCALE` | Locale for the copy (falls back to the ambient app locale when empty). |
| `request_id.header` | string | `X-Request-Id` | Request header read for the support reference / JSON `request_id`. |
| `request_id.generate` | bool | `true` | Generate a reference when the header is absent. |
| `problem_type_base` | string | `''` | When set, the JSON `type` becomes `{base}/{status}` (else `about:blank`). |
| `theme.preset` | string | `default` | `default \| slate \| midnight \| emerald \| crimson`. |
| `theme.auto_dark` | bool | `true` | Follow `prefers-color-scheme`. |
| `theme.colors.{light,dark}` | array | `[]` | Per-token colour overrides (see [Customising](recipes/customizing-brand-theme.md)). |
| `codes.intercept` | int[] | `401,403,404,419,429,500,502,503,504` | Status codes the **Path-2** renderable (api/inertia/spa/panel) takes over; others pass through. Web 4xx/5xx branding is automatic via Laravel's `errors::{n}xx` resolution. |
| `assets.mode` | string | `route` | Enhancement JS delivery: `route` (package-served), `link` (published URL), `inline`, or `off`. Critical CSS is always inlined. |
| `assets.route` | string | `/_error-pages/assets` | Prefix the enhancement bundle is served from in `route` mode. |
| `assets.version` | ?string | `null` | Cache-bust token; derived from the bundle file when null. |
| `render_debug_pages` | bool | `false` | Force branded output in dev for inertia/spa (API is always branded; web 500s stay with Ignition). |
| `preview.enabled` | ?bool | `null` | Preview route; `null` = on only when `APP_DEBUG`. |
| `preview.route` | string | `/_error-pages` | Preview route prefix. |
| `panels.filament` / `panels.nova` | bool | `true` | Enable the panel drivers (selected via a `context()` override for now; auto-detection ships with the panel set). |

## Env vars

Every scalar has an `ERROR_PAGES_*` env override: `ERROR_PAGES_ENABLED`,
`ERROR_PAGES_STACK`, `ERROR_PAGES_BRAND`, `ERROR_PAGES_BRAND_URL`, `ERROR_PAGES_LOGO`,
`ERROR_PAGES_HOME_URL`, `ERROR_PAGES_LOCALE`, `ERROR_PAGES_REQUEST_ID_HEADER`,
`ERROR_PAGES_REQUEST_ID_GENERATE`, `ERROR_PAGES_PROBLEM_TYPE_BASE`, `ERROR_PAGES_THEME`,
`ERROR_PAGES_AUTO_DARK`, `ERROR_PAGES_ASSETS`, `ERROR_PAGES_ASSETS_VERSION`,
`ERROR_PAGES_RENDER_DEBUG`, `ERROR_PAGES_PREVIEW`, `ERROR_PAGES_FILAMENT`, `ERROR_PAGES_NOVA`.

## Callbacks live in code, not config

Anything closure-shaped — a context resolver, a `skipWhen` predicate, a tenant/brand
resolver, a `pipe` enrichment stage — is registered through the [DSL](tools/dsl.md) in a
service provider, **never** in the published config file, so `php artisan config:cache`
keeps working.

## What is enforced by exception type

Independent of `codes.intercept`: `ValidationException` (422) and `AuthenticationException`
(login redirect / 401 JSON) always pass through to the framework, so form feedback and
auth challenges are never replaced.

---
[← Docs index](../README.md#documentation)
