# Coexistence

How this package sits beside Ignition, Sentry, Flare, Bugsnag, Telescope, and your own
error handling — it complements, it does not compete.

## Reporting tools are untouched

Sentry, Flare, Bugsnag, and Ignition's reporting all run on Laravel's **report** pass. This
package only ever touches **rendering** — it never reports the original exception. So there
is no double-reporting, and your monitoring keeps working exactly as before. The only thing
it reports is a failure inside *its own* renderer (a distinct `ErrorPageRenderException`
carrying the cause), so a broken pretty-page degrades to Laravel's default instead of
escalating into a crash. Throttle or drop it like any exception via your handler's
`report`/`dontReport` configuration.

## Ignition keeps the dev debug page

| Situation | What renders |
|---|---|
| `abort(4xx/503/…)` in dev **or** prod | this package (branded) |
| Unhandled 500 in **prod** | this package (branded) |
| Unhandled 500 in **dev** | **Ignition** (untouched) |

This is mechanical, not an environment flag: HTTP exceptions flow through Laravel's
`errors::` view path (where we plug in), while a genuine unhandled 500 in dev goes down
Laravel's debug path to Ignition, which we never intercept. To preview branded pages in
dev, use the [preview route/command](tools/preview.md) — not by replacing Ignition.

## The app always wins where it can

- **Blade/web:** drop `resources/views/errors/{code}.blade.php` in your app — it takes
  precedence over ours. See [Overriding a view](recipes/overriding-error-views.md).
- **api/inertia/spa:** register your own `render()`/`respond()` callback in `bootstrap/app.php`,
  or veto ours with `ErrorPages::skipWhen(...)` / `codes.intercept` (these govern Path 2).

## Framework UX is preserved

`ValidationException` (422 form feedback) and `AuthenticationException` (login redirect /
401 JSON) always pass through to Laravel — the package never turns them into error pages.

---
[← Docs index](../README.md#documentation)
