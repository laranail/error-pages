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
Create the `ErrorPage` component in your app (props are the
[payload](../tools/stacks.md#the-payload)). The shipped Vue/React components
(`@laranail/error-pages-ui`) land with the visual template set as a starting point.

> Inertia treats some non-2xx visits specially on the client; verify your setup handles a
> 404/500 error page as expected.

## Pure SPA (Vue/React, no Inertia)

```dotenv
ERROR_PAGES_STACK=vue   # or react
```

A web error returns a self-contained branded page with the payload embedded as
`<script id="error-page-data" type="application/json">`. Mount the shipped component onto it
to take over on the client — until then the server-rendered page is already styled and
correct.

---
[← Docs index](../../README.md#documentation)
