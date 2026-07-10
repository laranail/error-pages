# Overriding an error view

Replace the markup of a single error page entirely, using Laravel's standard error-view override.

## How resolution works

The package registers its own `resources/error-views` directory on `config('view.paths')`, so `errors::{code}` resolves to a package stub without any publish step. Because the app's own view path is registered ahead of the package's, an app-published `resources/views/errors/{code}.blade.php` always wins. That is the supported way to fully override one page.

## Override one page

Create the view Laravel already looks for:

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

Laravel now renders your view for a 404 and never touches the package stub. No config change is needed.

## Keep the package styling, change only the content

If you want the branded layout but different words, do not override the view — edit the content instead. See [Managing content in JSON](managing-content-json.md) or the config `messages` array. That keeps the dynamic and static pages identical.

## Reuse the package renderer inside your own view

You can still call the package from a custom stub — for example to wrap it:

```blade
{{-- resources/views/errors/503.blade.php --}}
{!! \Simtabi\Laranail\ServerErrorPages\Facades\ServerErrorPages::htmlFor(503) !!}
```

The facade also exposes `htmlForKey('5xx')`, `page($code)`, `keys()`, and `theme()` if you need the underlying data.

> A hand-written app override affects **only** the dynamic Blade path. The static file at `public/errors/{code}.html` is still generated from the package component by `server-error-pages:build` — it does not read your app's `resources/views/errors/`. If you need the app-down page to match a custom override, edit the shared component (see [Customizing components and themes](customizing-components-themes.md)) so both paths use it.

## Related

- [Architecture](../architecture.md) — the dynamic vs static render paths.
- [Customizing components and themes](customizing-components-themes.md) — change the shared component.

---
[← Docs index](../../README.md#documentation)
