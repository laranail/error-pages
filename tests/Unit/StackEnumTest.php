<?php

declare(strict_types=1);

use Simtabi\Laranail\Enumerator\Contracts\Enumerator;
use Simtabi\Laranail\ErrorPages\Enums\Stack;

it('is a laranail/enumerator enum with attribute-driven metadata', function (): void {
    expect(Stack::Blade)->toBeInstanceOf(Enumerator::class)
        ->and(Stack::Blade->label())->toBe('Blade')
        ->and(Stack::InertiaVue->label())->toBe('Inertia + Vue')
        ->and(Stack::Vue->description())->toBeString()->not->toBeEmpty();
});

it('keeps the bridge routing predicates', function (): void {
    expect(Stack::fromValue(null))->toBe(Stack::Blade)
        ->and(Stack::fromValue('nope'))->toBe(Stack::Blade)
        ->and(Stack::Blade->isServerHtml())->toBeTrue()
        ->and(Stack::Livewire->isServerHtml())->toBeTrue()
        ->and(Stack::InertiaReact->isInertia())->toBeTrue()
        ->and(Stack::React->isSpa())->toBeTrue();
});
