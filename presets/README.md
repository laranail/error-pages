# presets/ — the single design home

Every stack's template + the shared design live here as **inert design assets**
(data — the PHP core never *executes* the Blade/Livewire/Inertia ones). The core
`src/` engine is what stays free of any framework; this folder is not subject to
that boundary. Each folder is organised by asset kind.

```
presets/
├── shared/            the cross-stack design + assets
│   ├── scss/          SOURCE styles — _tokens.scss (theme map) + _base.scss + critical.scss
│   ├── css/           BUILT stylesheet — critical.css (generated from scss/, committed)
│   ├── js/            enhance.js (progressive-enhancement bundle)
│   └── ts/            payload.ts (the shared payload contract) + fixtures.ts (test data)
├── plain-php/views/   template.php — agnostic, guaranteed fallback (renders when Blade failed)
├── blade/             canonical CSS-first Blade design (Path-1 target)
│   ├── views/         error-page.blade.php
│   └── scss/          blade-specific style overrides
├── livewire/          the Livewire design assets (real component class is src/Livewire/ErrorPage.php)
│   ├── views/         error-page.blade.php
│   └── scss/          livewire-specific style overrides
├── inertia/           README — the shared Vue/React component serves Inertia too
├── vue/               @laranail/error-pages-ui/vue
│   ├── components/    ErrorPage.vue (+ test)
│   └── index.ts       entry + mount helper
└── react/             @laranail/error-pages-ui/react
    ├── components/    ErrorPage.tsx (+ test)
    └── index.tsx      entry + mount helper
```

## Styles: SCSS → CSS

The stylesheet is authored in **SCSS** (`shared/scss/`) and built to **CSS**
(`shared/css/critical.css`) — the built CSS is committed so the PHP renderer and consumers
never need a build step. `_tokens.scss` holds one `$themes` map that generates every
`.ep-theme-{preset}` class (light + dark), so adding a preset is one map entry.

```bash
cd presets && npm install && npm run build:css   # shared/scss/critical.scss → shared/css/critical.css
npm test                                          # Vitest DOM-parity tests for the Vue/React components
```

Consumed, no duplication:

- the **PHP core** renders `plain-php/views/template.php` (+ `shared/css/critical.css`), always inlining the critical CSS;
- the **Laravel bridge** serves `shared/css/critical.css` + `shared/js/enhance.js` from the asset route;
- the **npm `@laranail/error-pages-ui`** package ships `shared/ + vue/ + react/`.

> Status: everything above ships and renders the same DOM contract from the one payload; the
> Vue/React components are Vitest-tested and the SCSS builds clean. The **final visual CSS** is
> refined by the maintainer's canonical CSS-first Blade design when it lands — until then
> `shared/css/critical.css` styles every stack and `plain-php/` is the guaranteed server render.
