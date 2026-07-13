# presets/ — the single design home

Every stack's template + the shared design live here as **inert design assets**
(data — the PHP core never *executes* the Blade/Livewire/Inertia ones). The core
`src/` engine is what stays free of any framework; this folder is not subject to
that boundary.

```
presets/
├── shared/      shared stylesheet (critical.css) + progressive-enhancement JS (enhance.js)
├── plain-php/   agnostic, guaranteed fallback template (renders when Blade itself failed)
├── blade/       canonical CSS-first Blade templates (authored by the maintainer; Path-1 target)
├── livewire/    full-page Livewire component (reuses the shared Blade partial)
├── inertia/     shared Inertia page wrapper
├── vue/         Vue SFC ErrorPage component  ┐ built into the npm package
└── react/       React TSX ErrorPage component ┘ @laranail/error-pages-ui (shared/ + vue/ + react/)
```

Consumed, no duplication:

- the **PHP core** renders `plain-php/` (+ `shared/critical.css`), always inlining the critical CSS;
- the **Laravel bridge** serves `shared/enhance.js` from the asset route and (once populated) points
  its `errors::{code}` view path at `blade/`;
- the **npm `@laranail/error-pages-ui`** package will build `shared/ + vue/ + react/`.

> Status: `plain-php/template.php`, `shared/{critical.css,enhance.js,payload.ts}`, the `vue/` +
> `react/` components (Vitest-tested), and the `livewire/` view all ship today, all rendering the
> same DOM contract from the one payload. The real Livewire 4 component **class** lives at
> `src/Livewire/ErrorPage.php` (rendering `resources/views/livewire/*`). Their **final CSS/visual
> design** is derived from the canonical CSS-first Blade set (arriving next) — until then
> `shared/critical.css` styles them and `plain-php/` is the guaranteed server render. Only `blade/`
> remains a placeholder (it *is* the canonical design).
