# Managing content

Edit the titles and messages on every error page through Laravel translation files — no database, no admin panel.

## Where the files live

The package ships `resources/lang/en/errors.php`. Publish it to override or add locales:

```bash
php artisan vendor:publish --tag=laranail::server-error-pages-translations
```

That copies the file to `lang/vendor/server-error-pages/`, one directory per locale:

```text
lang/vendor/server-error-pages/
├── en/errors.php
└── fr/errors.php
```

Which locale the **static build** bakes in is set by `content.default_locale` (defaults to `APP_LOCALE`, then `en`). Dynamic pages honour the request locale.

## File shape

A plain Laravel translation array keyed by the status code as a string, plus the generic `4xx` / `5xx` fallbacks. Each entry has a `title` and a `message`:

```php
<?php

return [
    '404' => [
        'title' => 'Page not found',
        'message' => 'The page you are looking for could not be found.',
    ],
    '503' => [
        'title' => 'Back in a moment',
        'message' => 'We are deploying an update. This page will refresh itself shortly.',
    ],
    '5xx' => [
        'title' => 'Something went wrong',
        'message' => 'An unexpected error occurred on our side. Please try again shortly.',
    ],
];
```

Omit a code entirely to fall back to the built-in `HttpStatus` enum default — a partial file is fine, and any gap is filled by the package's own translations and then the enum.

## Resolution order

For each code and locale, content is resolved in this order:

1. **App override** — `lang/vendor/server-error-pages/{locale}/errors.php`.
2. **Package translations** — `resources/lang/{locale}/errors.php` (the shipped defaults).
3. **Built-in `HttpStatus` enum default**.

Because the enum default is always present, an unconfigured install still renders complete pages.

## Adding a locale

Create a directory for the locale and drop an `errors.php` in it:

```text
lang/vendor/server-error-pages/fr/errors.php
```

Set `content.default_locale` (or `APP_LOCALE`) to that locale so the static build uses it, then rebuild.

## Apply your changes

Translation edits reach the dynamic pages on the next request, but the static files must be rebuilt:

```bash
php artisan server-error-pages:build
```

Then deploy the changed translations and the regenerated `public/errors/*.html` — by git on a VPS, or FTP on shared hosting.

## Related

- [Configuration](../configuration.md) — the `content.default_locale` key.
- [Overriding an error view](overriding-error-views.md) — when you need to change markup, not just words.

---
[← Docs index](../../README.md#documentation)
