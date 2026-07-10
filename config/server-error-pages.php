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
    | filesystem path, or a path relative to the app base — a local file is
    | inlined as a data-URI at build time so static pages stay self-contained.
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
        'enabled' => [400, 401, 403, 404, 419, 429, 500, 502, 503, 504],
        'fallbacks' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content
    |--------------------------------------------------------------------------
    |
    | Where per-code titles/messages come from. Resolution precedence is:
    | JSON files -> config `messages` -> HttpStatus enum defaults.
    | Set `source` to 'json' to prefer JSON files, or 'config' to ignore them.
    | `json_path` is resolved relative to the app base path.
    |
    */

    'content' => [
        'source' => env('SERVER_ERROR_PAGES_CONTENT', 'json'),
        'json_path' => 'resources/error-pages',
        'default_locale' => env('SERVER_ERROR_PAGES_LOCALE', env('APP_LOCALE', 'en')),
    ],

    // Inline per-code overrides, e.g. 404 => ['title' => '…', 'message' => '…'].
    'messages' => [
        // 503 => ['title' => 'Back shortly', 'message' => 'We are upgrading the site.'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output (static generation)
    |--------------------------------------------------------------------------
    |
    | `disk` is a filesystem disk name, or null to write to `path` directly on
    | the local disk. `path` is where `errors/{code}.html` files are written.
    | `url_base` is the site root used for "home"/retry links on static pages.
    |
    */

    'output' => [
        'disk' => env('SERVER_ERROR_PAGES_DISK'),
        'path' => env('SERVER_ERROR_PAGES_OUTPUT', public_path('errors')),
        // Root-relative URL where the generated pages are served (used in the
        // Apache/Nginx config). Set to '/myapp/errors' for a subdirectory install.
        'url_path' => env('SERVER_ERROR_PAGES_URL_PATH', '/errors'),
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
    | Override paths to the compiled CSS / vanilla JS that get inlined into
    | every page. Null uses the package's committed `resources/dist` bundle.
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
