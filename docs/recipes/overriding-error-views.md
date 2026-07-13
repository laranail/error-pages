# Overriding an error view

Replace a page's Blade markup wholesale — your view always wins over the package's.

Because the package registers its views as a **fallback** view path, any
`resources/views/errors/{code}.blade.php` in your app takes precedence (this is standard
Laravel, no package APIs involved).

```bash
# start from the package's thin views (optional)
php artisan vendor:publish --tag=laranail::error-pages-views
```

```blade
{{-- resources/views/errors/404.blade.php --}}
<x-app-layout>
    <h1>404 — not here</h1>
    <p>{{ $exception->getMessage() }}</p>
</x-app-layout>
```

The `$exception` (a Symfony `HttpException`) is available in the view. To keep the package's
design but tweak copy or theme instead of replacing markup, prefer
[content](managing-content.md) or [theme](customizing-brand-theme.md) overrides.

## Embed the branded fragment in your own view

To keep your own layout but drop the branded error card into it, use the Blade component — it
renders the shared `ep-*` markup (a fragment, no `<html>`/`<head>`):

```blade
{{-- resources/views/errors/500.blade.php --}}
<x-app-layout>
    <x-error-pages::error :page="\Simtabi\Laranail\ErrorPages\Facades\ErrorPages::payloadFor($exception, request())" />
</x-app-layout>
```

Outside an error view, resolve the content from a code or key:

```blade
<x-error-pages::error :code="404" />
<x-error-pages::error key="5xx" class="my-8" />
```

Include `@laranail/error-pages-ui/style.css` (or your own CSS) for the `ep-*` classes. This is
the Blade parity of the [Livewire embed](livewire.md#embed-the-component-in-your-own-view).

For non-Blade contexts (API/Inertia/SPA), override by registering your own driver via
`ErrorPages::extend()` — see [Stacks](../tools/stacks.md).

---
[← Docs index](../../README.md#documentation)
