# Inertia and SPA error pages

Render errors as Inertia pages or hydrate them in a Vue/React SPA.

## Inertia

Install Inertia and select an inertia stack:

```bash
composer require inertiajs/inertia-laravel
```

```dotenv
ERROR_PAGES_STACK=inertia-vue   # or inertia-react
```

An Inertia request (`X-Inertia` header) — and a plain web page load under an `inertia-*`
stack — is rendered as `Inertia::render('ErrorPage', $payload)` with the correct status.
Point your `ErrorPage` page at the shipped component (props are the
[payload](../tools/stacks.md#the-payload)):

```ts
// resources/js/Pages/ErrorPage.vue  (or .tsx)
export { default } from '@laranail/error-pages-ui/vue'; // or /react
```

```ts
import '@laranail/error-pages-ui/style.css'; // shared page styles
```

The Vue/React components render the same DOM contract as the server page, so it stays
pixel-consistent. Their final visual design lands with the canonical templates.

> Inertia treats some non-2xx visits specially on the client; verify your setup handles a
> 404/500 error page as expected.

## Pure SPA (Vue/React, no Inertia)

```dotenv
ERROR_PAGES_STACK=vue   # or react
```

A web error returns a self-contained branded page with the payload embedded as
`<script id="error-page-data" type="application/json">`. Hydrate it on the client with the
shipped mount helper — until then the server-rendered page is already styled and correct:

```ts
import { mountErrorPage } from '@laranail/error-pages-ui/vue'; // or /react
mountErrorPage('#app');
```

---
[← Docs index](../../README.md#documentation)
