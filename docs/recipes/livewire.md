# Livewire error pages

Render the error page as a full-page **Livewire 4** component, so a Livewire app's error
screen matches the rest of its stack.

## Enable it

Install Livewire (v4+) and select the stack:

```bash
composer require livewire/livewire
```

```dotenv
ERROR_PAGES_STACK=livewire
```

A branded error now renders the `laranail-error-page` component in a full page (the critical
CSS is inlined; Livewire loads its own Alpine, so the package enhancement JS is not added).
If Livewire is **not** installed the stack degrades to the guaranteed core HTML page.

## How it renders

The `livewire` stack is Path 2: the handler resolves the `livewire` driver, which renders the
`error-pages::livewire.page` wrapper embedding `<livewire:laranail-error-page :page="$payload" />`.
The component receives the [payload](../tools/stacks.md#the-payload) and renders the shared DOM
contract (`.ep-shell` / `.ep-card` / `.ep-status` / `.ep-title` / …).

## Embed the component in your own view

Not every app uses full-page Livewire. The `laranail-error-page` component is registered, so
you can drop it into any Blade view inside your own layout — no stack change needed. The
component takes the payload array:

```blade
{{-- resources/views/errors/500.blade.php (wins over the package view) --}}
@extends('layouts.app')

@section('content')
    <livewire:laranail-error-page :page="\Simtabi\Laranail\ErrorPages\Facades\ErrorPages::payloadFor($exception, request())" />
@endsection
```

Outside an error view (a demo page, a maintenance screen) build the payload from a code or key:

```blade
<livewire:laranail-error-page :page="ErrorPages::payloadForCode(404)" />
<livewire:laranail-error-page :page="ErrorPages::payloadForKey('5xx')" />
```

Your layout must load `@livewireStyles` / `@livewireScripts` (a Livewire app's layout already
does). Include `@laranail/error-pages-ui/style.css` (or your own CSS) for the `ep-*` classes.

## Use your app's layout for the stack

To keep the automatic `livewire` stack but render it inside your app's chrome instead of the
package's self-contained page, point `livewire.layout` at a **component layout** (a view with
`{{ $slot }}` that loads Livewire's assets):

```dotenv
ERROR_PAGES_LIVEWIRE_LAYOUT=components.layouts.app
```

Leave it unset for the standalone full-page wrapper.

## Customise the markup

Publish the views and edit your copy — it overrides the package's:

```bash
php artisan vendor:publish --tag=laranail::error-pages-views
```

This writes `resources/views/vendor/error-pages/livewire/{error-page,page}.blade.php`. Keep the
single root element and the `ep-*` classes so the theme CSS still applies.

## Replace the component

To supply your own component, register it under the same name from a service provider's `boot()`
(last registration wins):

```php
use Livewire\Livewire;

Livewire::component('laranail-error-page', \App\Livewire\MyErrorPage::class);
```

Your component's `mount(array $page)` receives the payload array.

---
[← Docs index](../../README.md#documentation)
