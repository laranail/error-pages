# presets/inertia

The Inertia stack renders `Inertia::render('ErrorPage', $payload)`; your app resolves the
`ErrorPage` component. Use the shipped Vue or React component as that page:

```ts
// resources/js/Pages/ErrorPage.vue  (or .tsx)
export { default } from '@laranail/error-pages-ui/vue'; // or /react
```

The props are the [payload contract](../shared/payload.ts) — the same shape the SPA stack
embeds as `#error-page-data`. Import the shared stylesheet once:

```ts
import '@laranail/error-pages-ui/style.css';
```

There is no separate Inertia component: the same `presets/vue` / `presets/react` component
serves both the Inertia and SPA stacks (identical DOM), so the page is pixel-consistent.
