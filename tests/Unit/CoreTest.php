<?php

declare(strict_types=1);

use Simtabi\Laranail\ErrorPages\Core\Content\ArrayContentRepository;
use Simtabi\Laranail\ErrorPages\Core\Contracts\Renderer;
use Simtabi\Laranail\ErrorPages\Core\Enums\HttpStatus;
use Simtabi\Laranail\ErrorPages\Core\Enums\ThemePreset;
use Simtabi\Laranail\ErrorPages\Core\ErrorPages;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ErrorPage;
use Simtabi\Laranail\ErrorPages\Core\ValueObjects\ThemeSettings;

function theme(ThemePreset $preset = ThemePreset::Default): ThemeSettings
{
    return new ThemeSettings('Acme', 'https://acme.test', null, $preset, true);
}

it('exposes built-in copy and traits for each status', function (): void {
    expect(HttpStatus::NotFound->label())->toBe('Page not found')
        ->and(HttpStatus::NotFound->description())->toContain('could not be found')
        ->and(HttpStatus::ServiceUnavailable->isRetryable())->toBeTrue()
        ->and(HttpStatus::ServiceUnavailable->retryAfter())->toBe(15)
        ->and(HttpStatus::fallbackKeyFor(507))->toBe('5xx');
});

it('renders a self-contained HTML page with no framework', function (): void {
    $html = ErrorPages::make()->render(404, theme());

    expect($html)->toBeString()
        ->toContain('<!DOCTYPE html>')
        ->toContain('class="ep-status"')
        ->toContain('>404<')
        ->toContain('Page not found')
        ->toContain('<style>')
        ->toContain('ep-theme-default');
});

it('escapes content in the HTML page', function (): void {
    $content = new ArrayContentRepository(['404' => ['title' => '<script>x</script>']]);
    $html = ErrorPages::make($content)->render(404, theme());

    expect($html)->toContain('&lt;script&gt;')->not->toContain('<script>x</script>');
});

it('renders RFC 7807 JSON', function (): void {
    $json = ErrorPages::make()->render(503, theme(), 'json');

    expect($json)->toBeArray()
        ->toHaveKey('status', 503)
        ->toHaveKey('title', 'Be right back')
        ->toHaveKey('type', 'about:blank')
        ->toHaveKey('retry_after', 15);
});

it('honours content overrides then falls back to enum copy', function (): void {
    $content = new ArrayContentRepository(['404' => ['title' => 'Nope', 'message' => 'Gone']]);

    expect(ErrorPages::make($content)->page(404)->title)->toBe('Nope')
        ->and(ErrorPages::make($content)->page(500)->title)->toBe('Something went wrong');
});

it('resolves generic 4xx/5xx and unknown codes', function (): void {
    expect(ErrorPages::make()->pageByKey('5xx')->title)->toBe('Something went wrong')
        ->and(ErrorPages::make()->page(460)->key)->toBe('460');
});

it('supports a custom renderer via extend()', function (): void {
    $out = ErrorPages::make()
        ->extend('txt', fn (): Renderer => new class implements Renderer
        {
            public function render(ErrorPage $page, ThemeSettings $theme): string
            {
                return "ERR {$page->code}";
            }
        })
        ->render(404, theme(), 'txt');

    expect($out)->toBe('ERR 404');
});

it('enriches the page through the pipeline', function (): void {
    $page = ErrorPages::make()->pipe(fn (ErrorPage $p): ErrorPage => $p->withRequestId('abc123'))->page(404);

    expect($page->requestId)->toBe('abc123');
});
