<?php

declare(strict_types=1);

use Simtabi\Laranail\ServerErrorPages\Enums\ThemePreset;

return [

    /*
    |--------------------------------------------------------------------------
    | Brand
    |--------------------------------------------------------------------------
    |
    | Identity shown on every error page. `logo` may be a URL, an absolute
    | filesystem path, or a path relative to the app base — used as-is in an
    | <img src>; a local file is inlined as a data-URI only by the standalone
    | export so those single-file pages stay self-contained.
    |
    */

    'brand' => [
        'name' => env('SERVER_ERROR_PAGES_BRAND', env('APP_NAME', 'Our site')),
        'url' => env('SERVER_ERROR_PAGES_BRAND_URL', env('APP_URL', '/')),
        'logo' => env('SERVER_ERROR_PAGES_LOGO'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Codes
    |--------------------------------------------------------------------------
    |
    | The HTTP status codes to render/generate. `fallbacks` also emits generic
    | 4xx/5xx pages for any code without a dedicated page.
    |
    */

    'codes' => [
        'enabled' => [400, 401, 402, 403, 404, 419, 429, 500, 502, 503, 504],
        'fallbacks' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    |
    | Per-code titles/messages come from Laravel translations
    | (`server-error-pages::errors.{code}.{title|message}`). Publish the lang
    | files to override or add locales; a missing key falls back to the built-in
    | HttpStatus enum default. `default_locale` is the locale baked into the
    | static build (dynamic pages honour the request locale).
    |
    */

    'content' => [
        'default_locale' => env('SERVER_ERROR_PAGES_LOCALE', env('APP_LOCALE', 'en')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output (static generation)
    |--------------------------------------------------------------------------
    |
    | `disk` is a filesystem disk name, or null to write to `path` directly.
    | `path` is where `errors/{code}.html` files are written. `assets_url` /
    | `assets_path` are where the linked CSS/JS live (served by the web server,
    | kept OUTSIDE the internal `/errors/` location). `url_base` is the site root
    | for the "home"/retry links.
    |
    */

    'output' => [
        'disk' => env('SERVER_ERROR_PAGES_DISK'),
        'path' => env('SERVER_ERROR_PAGES_OUTPUT', public_path('errors')),
        // Root-relative URL where the generated pages are served (Apache/Nginx
        // config). Set to '/myapp/errors' for a subdirectory install.
        'url_path' => env('SERVER_ERROR_PAGES_URL_PATH', '/errors'),
        // Where the linked stylesheet/script are served from + written to.
        'assets_url' => env('SERVER_ERROR_PAGES_ASSETS_URL', '/vendor/server-error-pages'),
        'assets_path' => env('SERVER_ERROR_PAGES_ASSETS_PATH', public_path('vendor/server-error-pages')),
        // Site root used for the "home"/retry links on the pages themselves.
        'url_base' => env('SERVER_ERROR_PAGES_URL_BASE', '/'),
        'filename' => '{code}.html',
        'minify' => env('SERVER_ERROR_PAGES_MINIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | `preset` selects a shipped colour theme (default | slate | midnight |
    | emerald | crimson) applied to the single layout. `colors` are optional
    | per-token overrides merged on top of the preset — set a token to re-brand
    | with no asset rebuild (they drive the `--sep-*` CSS custom properties).
    | `auto_dark` follows the visitor's `prefers-color-scheme`.
    |
    */

    'theme' => [
        'preset' => env('SERVER_ERROR_PAGES_THEME', ThemePreset::Default->value),
        'auto_dark' => env('SERVER_ERROR_PAGES_AUTO_DARK', true),
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
    | Assets
    |--------------------------------------------------------------------------
    |
    | Override paths to the compiled CSS / JS the STANDALONE export inlines.
    | Null uses the package's committed `public/assets` bundle. (The default
    | linked build always serves the committed bundle — these only affect the
    | `--standalone` / `:export` output.)
    |
    */

    'assets' => [
        'compiled_css' => env('SERVER_ERROR_PAGES_CSS'),
        'static_js' => env('SERVER_ERROR_PAGES_JS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security headers
    |--------------------------------------------------------------------------
    |
    | Emitted into the generated Apache/Nginx config so they apply to the static
    | pages (where the app cannot set them). Set a value to null to skip it.
    |
    */

    'security' => [
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; base-uri 'none'; form-action 'self'",
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server config generation
    |--------------------------------------------------------------------------
    |
    | `profile` picks the stub set (shared hosting vs VPS). Outputs are written
    | to app/FTP-writable locations by default — never to /etc — and the command
    | prints the include line for the operator to wire into the site block.
    |
    */

    'server' => [
        'profile' => env('SERVER_ERROR_PAGES_PROFILE', 'vps'),
        'apache' => [
            'enabled' => env('SERVER_ERROR_PAGES_APACHE', true),
            'output' => env('SERVER_ERROR_PAGES_APACHE_OUTPUT', public_path('.htaccess')),
        ],
        'nginx' => [
            'enabled' => env('SERVER_ERROR_PAGES_NGINX', true),
            'output' => env('SERVER_ERROR_PAGES_NGINX_OUTPUT', storage_path('app/server-error-pages/errors.conf')),
            'fastcgi_intercept' => true,
        ],
    ],

];
