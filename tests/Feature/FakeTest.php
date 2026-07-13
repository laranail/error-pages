<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

it('records a web render and asserts on code, stack and theme', function (): void {
    ErrorPages::fake();

    $this->get('/fake-web-missing')->assertStatus(404);

    ErrorPages::assertRendered(404);
    ErrorPages::assertRendered(404, null, 'default');
    ErrorPages::assertRendered(404, 'blade', 'default');
});

it('records an API render', function (): void {
    ErrorPages::fake();

    $this->getJson('/fake-api-missing')->assertStatus(404);

    ErrorPages::assertRendered(404);
});

it('asserts nothing rendered when no error occurs', function (): void {
    ErrorPages::fake();

    ErrorPages::assertNothingRendered();
});
