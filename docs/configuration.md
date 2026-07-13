# Configuration

Every knob in `config/error-pages.php`, published with
`vendor:publish --tag=laranail::error-pages-config`.

## Keys

| Key | Type | Default | Purpose |
|-----|------|---------|---------|
| `enabled` | bool | `true` | Master switch. When false the package renders nothing (Laravel/Ignition untouched). |
| `stack` | string | `blade` | Default stack: `blade`, `livewire`, `inertia-vue`, `inertia-react`, `vue`, `react`. |
| `brand.name` | string | `APP_NAME` | Brand shown on the page. |
| `brand.url` | string | `APP_URL` | Brand link. |
| `brand.logo` | ?string | `null` | Logo URL (rendered as `<img>`). |
| `home_url` | string | `APP_URL` | Where "home"/retry links point. |
| `content.default_locale` | string | `APP_LOCALE` | Locale for the copy. |
| `theme.preset` | string | `default` | `default \| slate \| midnight \| emerald \| crimson`. |
| `theme.auto_dark` | bool | `true` | Follow `prefers-color-scheme`. |
| `theme.colors.{light,dark}` | array | `[]` | Per-token colour overrides (see [Customising](recipes/customizing-brand-theme.md)). |
| `codes.intercept` | int[] | `401,403,404,419,429,500,502,503,504` | Status codes the package takes over; others pass through. |
| `codes.fallbacks` | bool | `true` | Also brand the generic `4xx`/`5xx` pages. |
| `assets.mode` | string | `route` | `route` (package-served), `link` (published URL), or `inline`. |
| `render_debug_pages` | bool | `false` | Force branded output in dev for API/Inertia (never web — Ignition owns dev 500s). |
| `preview.enabled` | ?bool | `null` | Preview route; `null` = on only when `APP_DEBUG`. |
| `preview.route` | string | `/_error-pages` | Preview route prefix. |
| `panels.filament` / `panels.nova` | bool | `true` | Enable the panel stacks when those packages are installed. |

## Env vars

Every scalar has an `ERROR_PAGES_*` env override: `ERROR_PAGES_ENABLED`,
`ERROR_PAGES_STACK`, `ERROR_PAGES_BRAND`, `ERROR_PAGES_BRAND_URL`, `ERROR_PAGES_LOGO`,
`ERROR_PAGES_HOME_URL`, `ERROR_PAGES_LOCALE`, `ERROR_PAGES_THEME`, `ERROR_PAGES_AUTO_DARK`,
`ERROR_PAGES_ASSETS`, `ERROR_PAGES_RENDER_DEBUG`, `ERROR_PAGES_PREVIEW`,
`ERROR_PAGES_FILAMENT`, `ERROR_PAGES_NOVA`.

## Callbacks live in code, not config

Anything closure-shaped — a context resolver, a `skipWhen` predicate, a tenant/brand
resolver, a CSP nonce — is registered through the [DSL](tools/dsl.md) in a service
provider, **never** in the published config file, so `php artisan config:cache` keeps
working.

## What is enforced by exception type

Independent of `codes.intercept`: `ValidationException` (422) and `AuthenticationException`
(login redirect / 401 JSON) always pass through to the framework, so form feedback and
auth challenges are never replaced.

---
[← Docs index](../README.md#documentation)
