# Overriding an error view

Replace the markup of a single error page entirely, using Laravel's standard error-view override.

## How resolution works

The install command publishes the package's error-view stubs into your app at `resources/views/errors/{code}.blade.php` — Laravel's conventional error views. Because they are the app's own views, `renderHttpException()` resolves them directly, and editing (or replacing) one simply wins. There is no view-path trickery to reason about.

## Override one page

Edit the published view (or create it if you skipped the publish step):

```blade
{{-- resources/views/errors/404.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>404 · Lost</title></head>
<body>
    <h1>Nothing here</h1>
    <p>That page has moved on.</p>
</body>
</html>
```

Laravel now renders your view for a 404. No config change is needed.

## Keep the package styling, change only the content

If you want the branded layout but different words, do not override the view — edit the translations instead. See [Managing content](managing-content.md). That keeps the dynamic and static pages identical.

## Reuse the package renderer inside your own view

The default published stub is a one-liner that calls the facade — you can keep that call while wrapping it:

```blade
{{-- resources/views/errors/503.blade.php --}}
{!! \Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages::htmlFor(503) !!}
```

The facade also exposes `htmlForKey('5xx')`, `page($code)`, `keys()`, and `theme()` if you need the underlying data.

> A hand-written app override affects **only** the dynamic Blade path. The static file at `public/errors/{code}.html` is still generated from the package component by `server-error-pages:build` — it does not read your app's `resources/views/errors/`. If you need the app-down page to match a custom override, edit the shared component's SCSS/Blade source (see [Customizing components and themes](customizing-components-themes.md)) so both paths use it.

## Related

- [Architecture](../architecture.md) — the dynamic vs static render paths.
- [Customizing components and themes](customizing-components-themes.md) — change the shared component.

---
[← Docs index](../../README.md#documentation)
