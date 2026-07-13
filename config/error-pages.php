<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\Enums\ThemePreset;

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
    | `intercept` lists the HTTP status codes this package takes over; every
    | other code passes through to Laravel untouched. `fallbacks` also brands the
    | generic 4xx/5xx pages for intercepted codes without a dedicated view.
    |
    | Note: 419 renders only for API/Inertia (web passes through so the app's
    | redirect-back flow wins); 422 (validation) and auth redirects always pass
    | through — this is enforced by exception type, independent of this list.
    |
    */

    'codes' => [
        'intercept' => [401, 403, 404, 419, 429, 500, 502, 503, 504],
        'fallbacks' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | `route` (default) serves the committed bundle from a package route — no
    | publish step, no Vite manifest dependency. `link` uses a published URL;
    | `inline` embeds everything (max resilience). Critical CSS is always inlined
    | so a page is never unstyled.
    |
    */

    'assets' => [
        'mode' => env('ERROR_PAGES_ASSETS', 'route'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coexistence with Ignition
    |--------------------------------------------------------------------------
    |
    | Branded pages take over production-style responses only; genuine unhandled
    | 500s in dev show Ignition's debug page (mechanism-driven, no env branch).
    | `render_debug_pages` (api/inertia only) forces branded output in dev too.
    | Use the preview route to design branded pages in dev without real errors.
    |
    */

    'render_debug_pages' => env('ERROR_PAGES_RENDER_DEBUG', false),

    'preview' => [
        // null => enabled only when app.debug is true.
        'enabled' => env('ERROR_PAGES_PREVIEW'),
        'route' => '/_error-pages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel adapters
    |--------------------------------------------------------------------------
    */

    'panels' => [
        'filament' => env('ERROR_PAGES_FILAMENT', true),
        'nova' => env('ERROR_PAGES_NOVA', true),
    ],

];
