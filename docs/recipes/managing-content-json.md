# Managing content in JSON

Edit the titles and messages on every error page through per-locale JSON files — no database, no admin panel.

## Where the files live

The installer publishes them to `resources/error-pages/`, one file per locale:

```text
resources/error-pages/
├── en.json
└── fr.json
```

Which file is read is set by `content.default_locale` (defaults to `APP_LOCALE`, then `en`). The path is `content.json_path` (default `resources/error-pages`).

## File shape

Keys are the status code as a string, plus the generic `4xx` / `5xx` fallbacks. Each entry has a `title` and a `message`:

```json
{
    "404": {
        "title": "Page not found",
        "message": "The page you are looking for could not be found."
    },
    "503": {
        "title": "Back in a moment",
        "message": "We are deploying an update. This page will refresh itself shortly."
    },
    "5xx": {
        "title": "Something went wrong",
        "message": "An unexpected error occurred on our side. Please try again shortly."
    }
}
```

Delete a code entirely to fall back to the built-in `HttpStatus` enum default — a partial file is fine, and any gap is filled by config `messages` and then the enum.

## Resolution order

For each code and locale, content is resolved in this order:

1. This JSON file (skipped when `content.source` is `config`).
2. `config('laranail.server-error-pages.messages.{code}')`.
3. The built-in `HttpStatus` enum default.

## Apply your changes

JSON edits reach the dynamic pages on the next request, but the static files must be rebuilt:

```bash
php artisan server-error-pages:build
```

Then deploy the changed JSON and the regenerated `public/errors/*.html` — by git on a VPS, or FTP on shared hosting.

## Pinning to config instead

If you would rather keep everything in PHP, set the source to `config` and use the `messages` array:

```dotenv
SERVER_ERROR_PAGES_CONTENT=config
```

```php
'messages' => [
    503 => ['title' => 'Back shortly', 'message' => 'We are upgrading the site.'],
],
```

> A JSON syntax error makes that locale's file unreadable and the package silently falls through to config and enum defaults. Validate the file (`php -r "json_decode(file_get_contents('resources/error-pages/en.json'), null, 512, JSON_THROW_ON_ERROR);"`) before you deploy.

## Related

- [Configuration](../configuration.md) — the `content.*` and `messages` keys.
- [Overriding an error view](overriding-error-views.md) — when you need to change markup, not just words.

---
[← Docs index](../../README.md#documentation)
