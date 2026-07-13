# Skipping and passthrough

Let specific requests, routes, or status codes fall through to Laravel untouched.

> Scope: `codes.intercept` and `skipWhen()` govern **Path 2** — the api/inertia/spa/panel
> renderable. The server-HTML **web** context (Path 1) is pure view precedence: Laravel finds
> the package's `errors::{code}` view as a fallback. To pass a *web* code through, publish your
> own `resources/views/errors/{code}.blade.php` (it wins) or disable the package.

## Which codes are intercepted (Path 2)

```php
// config/error-pages.php
'codes' => [
    'intercept' => [401, 403, 404, 429, 500, 502, 503, 504], // drop 419 to pass API/Inertia 419 through
],
```

Any code not listed passes through to Laravel's default handling for the api/inertia/spa
contexts. Generic `4xx`/`5xx` web branding is automatic via Laravel's own
`errors::{status}` → `errors::{n}xx` resolution (the package ships both generic views).

## Veto at runtime (Path 2)

```php
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

ErrorPages::skipWhen(fn ($e, $request) => $request?->is('webhooks/*') === true)
    ->skipWhen(fn ($e, $request) => $e instanceof \App\Exceptions\PassThroughException);
```

Matching api/inertia/spa requests/exceptions are handled by Laravel as if the package were
not installed.

## Always passthrough (by design)

`ValidationException` (422) and `AuthenticationException` (login redirect / 401) are never
intercepted, so form feedback and auth challenges keep their framework behaviour. Turn the
whole package off with `error-pages.enabled = false`.

---
[← Docs index](../../README.md#documentation)
