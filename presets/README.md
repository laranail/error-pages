# presets/ — the single design home

Every stack's template + the shared design live here as **inert design assets**
(data — the PHP core never *executes* the Blade/Livewire/Inertia ones). The core
`src/` engine is what stays free of any framework; this folder is not subject to
that boundary.

```
presets/
├── shared/      SCSS/CSS stylesheet + theme tokens · alpine.js · SVG illustrations
├── plain-php/   agnostic, guaranteed fallback template (renders when Blade itself failed)
├── blade/       canonical CSS-first Blade templates (authored by the maintainer; Path-1 target)
├── livewire/    full-page Livewire component (reuses the shared Blade partial)
├── inertia/     shared Inertia page wrapper
├── vue/         Vue SFC ErrorPage component  ┐ built into the npm package
└── react/       React TSX ErrorPage component ┘ @laranail/error-pages-ui (shared/ + vue/ + react/)
```

Consumed three ways, no duplication:

- the **PHP core** renders `plain-php/` (+ `shared/critical.css`);
- the **Laravel bridge** points its `errors::{code}` view path at `blade/` and embeds `livewire/`;
- the **npm `@laranail/error-pages-ui`** package builds `shared/ + vue/ + react/`.

> The Blade/Vue/React/Livewire templates are populated from the canonical CSS-first
> Blade design; until then, `plain-php/` + `shared/critical.css` are the working
> placeholders and the guaranteed fallback.
