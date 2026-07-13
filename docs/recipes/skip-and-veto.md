# Skipping and passthrough

Let specific requests, routes, or status codes fall through to Laravel untouched.

## Which codes are intercepted

```php
// config/error-pages.php
'codes' => [
    'intercept' => [401, 403, 404, 429, 500, 502, 503, 504], // drop 419 to pass it through
    'fallbacks' => true,
],
```

Any code not listed passes through to Laravel's default handling.

## Veto at runtime

```php
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

ErrorPages::skipWhen(fn ($e, $request) => $request?->is('webhooks/*') === true)
    ->skipWhen(fn ($e, $request) => $e instanceof \App\Exceptions\PassThroughException);
```

Matching requests/exceptions are handled by Laravel as if the package were not installed.

## Always passthrough (by design)

`ValidationException` (422) and `AuthenticationException` (login redirect / 401) are never
intercepted, so form feedback and auth challenges keep their framework behaviour. Turn the
whole package off with `error-pages.enabled = false`.

---
[← Docs index](../../README.md#documentation)
