# Testing against error pages

Assert that your app produced a branded error page, without asserting on HTML.

## Fake and assert

`ErrorPages::fake()` records every page rendered during the test; rendering still happens.

```php
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

it('brands a not-found', function () {
    ErrorPages::fake();

    $this->get('/missing')->assertNotFound();

    ErrorPages::assertRendered(404);
});
```

Narrow by stack and/or theme:

```php
ErrorPages::assertRendered(503, stack: 'blade', theme: 'crimson');
```

Assert nothing was rendered (e.g. a passthrough / skip):

```php
ErrorPages::fake();

$this->postJson('/form', [])->assertStatus(422); // validation passes through

ErrorPages::assertNothingRendered();
```

## Preview during design

For visual review (not assertions), use the [preview gallery](../tools/preview.md) — all codes
× themes at `GET /_error-pages`.

---
[← Docs index](../../README.md#documentation)
