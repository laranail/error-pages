# Managing content

Override or translate the title/message for any status via Laravel translations.

Publish the language files and edit them; a missing key falls back to the built-in
`HttpStatus` copy.

```bash
php artisan vendor:publish --tag=laranail::error-pages-translations
```

```php
// lang/vendor/error-pages/en/errors.php
return [
    '404' => ['title' => 'Lost in space', 'message' => 'That page drifted away.'],
    '5xx' => ['message' => 'Our engineers are on it.'],
];
```

Add locales by creating `lang/vendor/error-pages/{locale}/errors.php`. Keys are status codes
(`404`) or the generic `4xx`/`5xx`. The current request locale is used at render time.

For richer, per-exception content (a custom exception → its own copy/actions), enrich the
page through the [DSL](../tools/dsl.md) `pipe()`.

---
[← Docs index](../../README.md#documentation)
