<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Simtabi\Laranail\ErrorPages\Facades\ErrorPages;

it('builds a payload for a code and a generic key, for embedding', function (): void {
    $byCode = ErrorPages::payloadForCode(503);
    expect($byCode['status'])->toBe(503)
        ->and($byCode['title'])->toBe('Be right back')
        ->and($byCode)->toHaveKeys(['code', 'message', 'homeUrl', 'brand', 'theme']);

    expect(ErrorPages::payloadForKey('4xx')['code'])->toBe('4xx');
});

it('embeds the shared error fragment via a blade component', function (): void {
    $byCode = Blade::render('<x-error-pages::error :code="404" />');
    expect($byCode)
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->not->toContain('<!DOCTYPE'); // a fragment, not a full document

    expect(Blade::render('<x-error-pages::error key="5xx" />'))->toContain('>5xx<');
});

it('embeds the error component inline via the livewire tag', function (): void {
    $html = Blade::render(
        '<livewire:laranail-error-page :page="$page" />',
        ['page' => ErrorPages::payloadForCode(404)],
    );

    expect($html)
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->toContain('wire:'); // Livewire rendered the embedded component
});
