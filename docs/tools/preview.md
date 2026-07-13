# Preview

Design and review branded pages in development without triggering real errors — because in
dev, real errors show Ignition.

## Route

While `APP_DEBUG=true` (or `preview.enabled = true`), the package registers:

```
GET /_error-pages/{code}
```

`{code}` is a status code (`404`, `503`) or a generic key (`4xx`, `5xx`). Change the prefix
with `config('error-pages.preview.route')`.

```
http://localhost/_error-pages/503
http://localhost/_error-pages/4xx
```

## Command

```bash
php artisan laranail::laravel-error-pages.preview 500 --output=storage/preview-500.html
# alias:
php artisan laravel-error-pages:preview 500 -o storage/preview-500.html
```

Renders the page to an HTML file (default: `error-preview-{code}.html` in the CWD).

## Security

The preview surface is gated to `APP_DEBUG` by default (or set `preview.enabled`
explicitly). Keep it off in production — it can render arbitrary status pages.

---
[← Docs index](../../README.md#documentation)
