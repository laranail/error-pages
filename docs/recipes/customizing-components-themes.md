# Customizing components and themes

Re-brand the pages with a theme preset, colour overrides, and a logo — and, when you need to, edit the SCSS component source itself. There is one solid centered layout; the theme is the customization axis.

## Theme preset

Five colour presets are compiled into the one stylesheet. Pick one — it sets every `--sep-*` token for light and dark via a `sep-theme-{preset}` body class, so switching needs **no rebuild**:

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

`auto_dark` (default `true`) adds the `sep-auto-dark` body class so the preset's dark variant follows the visitor's `prefers-color-scheme`.

## Colour overrides

To nudge individual tokens without touching SCSS, set them under `theme.colors`. Any override generates a small **linked `css/error-pages-theme.css`** at build time (copied next to the pages), so it still needs no asset rebuild — just a static rebuild:

```php
'theme' => [
    'preset' => 'slate',
    'colors' => [
        'light' => ['accent' => '#ea580c'],
        'dark'  => ['accent' => '#fb923c'],
    ],
],
```

The tokens are `bg`, `surface`, `text`, `muted`, `accent`, `accent-2`, and `border`.

## Logo

Point `brand.logo` at a file; it renders as an ordinary `<img src>`:

```dotenv
SERVER_ERROR_PAGES_LOGO="/vendor/server-error-pages/img/acme-mark.svg"
```

For the linked build, host the logo somewhere the web server serves it (for example under `assets_url`). For the [standalone export](standalone-export.md), use a **local file path** — it is inlined as a data-URI so the single-file page has no external request.

## Editing the SCSS component source

The pages are built from one anonymous component, `<x-server-error-pages::layout>`, composing `brand`, `status`, `message`, and `actions` sub-components, styled by `resources/assets/scss/error-pages.scss` (Tailwind 4 + SCSS) and enhanced by `resources/assets/scripts/error-pages.js`. This source is built by Vite into the committed `public/assets/` bundle.

To change the styling or markup beyond presets/overrides:

1. Edit the SCSS (or the Blade component views / JS).
2. Rebuild the bundle:

   ```bash
   npm install
   npm run build
   ```

   This regenerates `public/assets/css/error-pages.css` and `public/assets/js/error-pages.js`.
3. Rebuild the static pages:

   ```bash
   php artisan server-error-pages:build
   ```

### Build scripts

The bundle is built by Vite; the committed output is always **minified**. The scripts:

| Script | What it does |
|--------|--------------|
| `npm run build` | Minified production bundle → `public/assets/`. **This is the shipped bundle** — run it before committing. |
| `npm run build:pretty` | Un-minified, Prettier-formatted bundle for inspecting the compiled CSS/JS. Overwrites `public/assets/`, so re-run `npm run build` before committing. |
| `npm run dev` | `vite build --watch` — rebuilds the minified bundle on every source change. |
| `npm run format` | Prettier-format the SCSS/JS **source** under `resources/assets/`. |
| `npm run format:check` | Check source formatting without writing (CI-friendly). |

To replace a single page's markup wholesale instead of the shared component, see [Overriding an error view](overriding-error-views.md).

## What needs a rebuild

| Change | `npm run build`? | `server-error-pages:build`? |
|--------|:----------------:|:---------------------------:|
| `theme.preset` | no | yes |
| `theme.colors` overrides | no | yes |
| `brand.*`, content, config | no | yes |
| SCSS / JS / component markup | yes | yes |

## Related

- [Configuration](../configuration.md) — the `theme.*`, `brand.*`, and `assets.*` keys.
- [Architecture](../architecture.md) — the Vite/Tailwind/SCSS pipeline and how the one component feeds every output.

---
[← Docs index](../../README.md#documentation)
