# API error responses

Return RFC 7807 `application/problem+json` for API clients automatically.

Any request that expects JSON (`Accept: application/json`, `api/*`, or XHR) is detected as
the **api** context and rendered as a structured payload — no configuration:

```json
{
  "type": "about:blank",
  "title": "Page not found",
  "status": 404,
  "detail": "The page you are looking for could not be found.",
  "code": "404",
  "request_id": "9f2c1a7b3d4e5f60",
  "instance": "/orders/42"
}
```

The response carries `Content-Type: application/problem+json`, plus `Retry-After` for
transient codes (429/502/503/504) and `Cache-Control: no-store`. `instance` is the request
URI; `request_id` is read from the configured header (`request_id.header`, default
`X-Request-Id`) or generated when absent. Set `problem_type_base` to emit a per-status
`type` URI (`{base}/{status}`) instead of `about:blank`. Internal exception details are
never leaked (5xx uses generic copy).

Customise detection with the [DSL](../tools/dsl.md) `context()`, or replace the JSON
entirely with `ErrorPages::extend('json', ...)`.

## Problem-type documentation pages

RFC 7807/9457's `type` is meant to link to a human-readable page describing the problem. Turn
it on to serve those pages and point `type` at them:

```dotenv
ERROR_PAGES_PROBLEM_DOCS=true
```

Now `GET /errors/problems/{code}` (prefix configurable via `problem.docs.route`) returns a
branded, `noindex` page for that status — with **what this means**, **common causes**, and
**how to fix** — and the JSON `type` becomes `{app.url}/errors/problems/{status}`, so an API
consumer can open the `type` link and read it. The copy comes from the `problems` translations
(per-code, with `4xx`/`5xx` fallbacks); publish + edit them:

```bash
php artisan vendor:publish --tag=laranail::error-pages-translations
# → lang/vendor/error-pages/en/problems.php
```

## Field-level validation errors (RFC 9457)

By default a `ValidationException` passes through to Laravel's own 422 (`{message, errors}`),
preserving form UX. Opt in to a problem+json with an `errors[]` array for API clients:

```dotenv
ERROR_PAGES_PROBLEM_VALIDATION=true
```

```json
{
  "type": "...", "title": "Validation failed", "status": 422,
  "detail": "The given data failed validation.",
  "errors": [
    { "pointer": "/email", "field": "email", "detail": "The email field is required." },
    { "pointer": "/age",   "field": "age",   "detail": "The age must be an integer." }
  ]
}
```

## Content negotiation

By default an `api/*` path always returns JSON. Enable content negotiation so a browser
(`Accept: text/html`) opening an API URL gets the branded **page** instead of raw JSON, while
explicit JSON clients (`Accept: application/json`) still get problem+json:

```dotenv
ERROR_PAGES_CONTENT_NEGOTIATION=true
```

---
[← Docs index](../../README.md#documentation)
