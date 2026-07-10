# Customizing components and themes

Re-brand the pages with a theme preset, colour overrides, and a logo — and, when you need to, override the component markup itself. There is one solid centered layout; the theme is the customization axis.

## Theme preset

Five colour themes ship. Pick one — it sets every `--sep-*` token for light and dark:

| Preset | Accent |
|--------|--------|
| `default` | Indigo (the default). |
| `slate` | Neutral slate. |
| `midnight` | Deep indigo, dark-leaning. |
| `emerald` | Green. |
| `crimson` | Red. |

```dotenv
SERVER_ERROR_PAGES_THEME=midnight
```

## Colour overrides

Colours are runtime CSS custom properties (`--sep-*`), so a re-brand needs no asset rebuild — just a config change and a static rebuild. Set only the tokens you want to change under `theme.colors`; they merge on top of the preset:

```php
'theme' => [
    'preset' => 'slate',
    'colors' => [
        'light' => ['accent' => '#ea580c'],
        'dark'  => ['accent' => '#fb923c'],
    ],
],
```

The tokens are `bg`, `surface`, `text`, `muted`, `accent`, and `border`. `auto_dark` (default `true`) follows the visitor's `prefers-color-scheme`.

## Logo

Point `brand.logo` at a local file; it is inlined as a data-URI at build time so the static pages stay self-contained:

```dotenv
SERVER_ERROR_PAGES_LOGO="resources/branding/acme-mark.svg"
```

> Do not use a remote `http(s)` logo URL for static pages. The build's self-containment check rejects external `src`/`url()` references and fails, because such a logo would not load when the app or network is down. A local path is inlined and always works.

## Overriding the component markup

The pages are built from one anonymous component, `<x-server-error-pages::layout>`, composing `status`, `message`, `actions`, and `brand` sub-components. To change the markup, publish the component views and edit them:

```bash
php artisan vendor:publish --tag=server-error-pages::content   # content JSON
# publish the package views to resources/views/vendor/server-error-pages/ to edit components
php artisan vendor:publish --provider="Simtabi\Laranail\ServerErrorPages\Providers\ServerErrorPagesServiceProvider"
```

Keep the CSS and JS inline — anything that fetches an external resource will fail the build's self-containment assertion. To replace a single page's markup wholesale rather than the shared component, see [Overriding an error view](overriding-error-views.md).

## Rebuild after any change

```bash
php artisan server-error-pages:build
```

Config, content, and theme changes only reach the static files on rebuild.

## Related

- [Configuration](../configuration.md) — the `theme.*`, `brand.*`, and `assets.*` keys.
- [Architecture](../architecture.md) — how the one component feeds every output.

---
[← Docs index](../../README.md#documentation)
