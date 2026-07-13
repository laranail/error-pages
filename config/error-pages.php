<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\Core\Enums\ThemePreset;

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false, the package renders nothing and Laravel's default error
    | handling (and Ignition, in dev) is untouched.
    |
    */

    'enabled' => env('ERROR_PAGES_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default stack
    |--------------------------------------------------------------------------
    |
    | How the web/inertia page is produced: blade | livewire | inertia-vue |
    | inertia-react | vue | react. The API context always renders RFC 7807 JSON.
    | Overridable per request via the ErrorPages DSL.
    |
    | `blade` is server-HTML (Path 1). `livewire` renders a full-page Livewire 4
    | component (Path 2, needs livewire/livewire ^4; degrades to core HTML without
    | it). The rest are client/SPA (Path 2).
    |
    */

    'stack' => env('ERROR_PAGES_STACK', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'name' => env('ERROR_PAGES_BRAND', env('APP_NAME', 'Our site')),
        'url' => env('ERROR_PAGES_BRAND_URL', env('APP_URL', '/')),
        'logo' => env('ERROR_PAGES_LOGO'),
    ],

    // Where the "home" / retry links point.
    'home_url' => env('ERROR_PAGES_HOME_URL', env('APP_URL', '/')),

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    |
    | Per-code titles/messages come from translations
    | (`error-pages::errors.{code}.{title|message}`); a missing key falls back
    | to the built-in HttpStatus enum default. Publish the lang files to override
    | or add locales.
    |
    */

    'content' => [
        'default_locale' => env('ERROR_PAGES_LOCALE', env('APP_LOCALE', 'en')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation id
    |--------------------------------------------------------------------------
    |
    | The reference shown to the user ("Reference: …") and returned as
    | `request_id` in the JSON payload. Read from the `header` when the proxy/app
    | sets one; otherwise a short id is generated per render when `generate` is on.
    |
    */

    'request_id' => [
        'header' => env('ERROR_PAGES_REQUEST_ID_HEADER', 'X-Request-Id'),
        'generate' => env('ERROR_PAGES_REQUEST_ID_GENERATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Problem type (RFC 7807)
    |--------------------------------------------------------------------------
    |
    | Optional base URI for the JSON `type` member. When set, the payload's
    | `type` becomes `{base}/{status}` (e.g. https://example.com/problems/404)
    | instead of the default `about:blank`. Leave empty to keep `about:blank`.
    |
    */

    'problem_type_base' => env('ERROR_PAGES_PROBLEM_TYPE_BASE', ''),

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | `preset` selects a shipped colour theme; `colors` are optional per-token
    | overrides emitted as inline CSS. `auto_dark` follows prefers-color-scheme.
    |
    */

    'theme' => [
        'preset' => env('ERROR_PAGES_THEME', ThemePreset::Default->value),
        'auto_dark' => env('ERROR_PAGES_AUTO_DARK', true),
        'colors' => [
            'light' => [
                // 'accent' => '#4f46e5',
            ],
            'dark' => [
                // 'accent' => '#818cf8',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes
    |--------------------------------------------------------------------------
    |
    | `intercept` lists the HTTP status codes the Path-2 renderable (api/inertia/
    | spa/panel) takes over; every other code passes through to Laravel. Generic
    | 4xx/5xx branding is automatic for the web context via Laravel's own
    | `errors::{status}` → `errors::{n}xx` resolution (the package ships both
    | `errors/4xx` and `errors/5xx` views).
    |
    | Scope note: `intercept` and `skipWhen()` govern Path 2 only. Path 1 (the
    | server-HTML web context) is pure view precedence — the app's own
    | `resources/views/errors/{code}.blade.php` overrides the package view; to
    | pass a web code through entirely, publish an app view or disable the package.
    |
    | 422 (validation) and auth redirects always pass through — enforced by
    | exception type, independent of this list. A web 419 (TokenMismatch/
    | PageExpired) is branded like any other code; apps that want a redirect-back
    | flow should handle that exception themselves.
    |
    */

    'codes' => [
        'intercept' => [401, 403, 404, 419, 429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | Critical CSS is ALWAYS inlined so a page is never unstyled. This setting
    | governs the optional progressive-enhancement JS bundle only:
    |
    |   route  (default) serves it from a package route — no publish, no Vite
    |          manifest. `version` busts the cache on upgrade.
    |   link   references a published URL (`asset('vendor/error-pages/…')`).
    |   inline embeds the script in the page (max resilience, no extra request).
    |   off    ships no enhancement JS (the page is fully functional without it).
    |
    */

    'assets' => [
        'mode' => env('ERROR_PAGES_ASSETS', 'route'),
        'route' => '/_error-pages/assets',
        'version' => env('ERROR_PAGES_ASSETS_VERSION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire stack
    |--------------------------------------------------------------------------
    |
    | `layout` renders the livewire stack inside YOUR own component layout — a
    | view with `{{ $slot }}` that loads `@livewireStyles`/`@livewireScripts` — so
    | the error sits in your app's chrome rather than the package's self-contained
    | page. null (default) uses the standalone full-page wrapper. To embed the
    | `laranail-error-page` component manually in any view, see the Livewire recipe.
    |
    */

    'livewire' => [
        'layout' => env('ERROR_PAGES_LIVEWIRE_LAYOUT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coexistence with Ignition
    |--------------------------------------------------------------------------
    |
    | Branded pages take over production-style responses only; genuine unhandled
    | 500s in dev show Ignition's debug page (mechanism-driven, no env branch).
    | `render_debug_pages` (inertia/spa only) forces branded output in dev too;
    | the API context is always branded (Ignition is HTML-only, so a JSON client
    | has no debug page to defer to). Use the preview route to design branded
    | pages in dev without real errors.
    |
    */

    'render_debug_pages' => env('ERROR_PAGES_RENDER_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Failure reporting
    |--------------------------------------------------------------------------
    |
    | If OUR renderer throws, we report a distinct ErrorPageRenderException (never
    | the original — the framework already reported it) and fall back to Laravel's
    | default. `throttle` (seconds) caps repeat reports of the same failure so a
    | persistently-broken pretty-page can't flood the log; 0 reports every time.
    |
    */

    'report' => [
        'throttle' => env('ERROR_PAGES_REPORT_THROTTLE', 0),
    ],

    'preview' => [
        // null => enabled only when app.debug is true.
        'enabled' => env('ERROR_PAGES_PREVIEW'),
        'route' => '/_error-pages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel adapters
    |--------------------------------------------------------------------------
    |
    | Opt-in flags for the Filament/Nova panel renderers.
    |
    | `filament` is auto-detected: a request under a Filament panel's own path
    | renders the panel-branded page (path-scoped, so it never hijacks a normal
    | route; set false to disable). Nova is Inertia-based, so route it via the
    | `inertia` stack or select it explicitly with `ErrorPages::context(fn () =>
    | \Laravel\Nova\Nova::whatever() ? 'nova' : null)`.
    |
    */

    'panels' => [
        'filament' => env('ERROR_PAGES_FILAMENT', true),
        'nova' => env('ERROR_PAGES_NOVA', true),
    ],

];
