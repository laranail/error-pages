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

---
[← Docs index](../../README.md#documentation)
