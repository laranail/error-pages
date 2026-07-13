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

> Status: `plain-php/template.php`, `shared/critical.css`, and `shared/enhance.js` ship today. The
> `blade/`, `livewire/`, `inertia/`, `vue/`, and `react/` directories are placeholders — they are
> populated from the canonical CSS-first Blade design, from which the other stacks are ported. Until
> then, `plain-php/` + `shared/critical.css` are the working, guaranteed render for every stack.
