<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Simtabi\Laranail\ErrorPages\Http\PanelDetector;

it('detects no panel when Filament is not installed', function (): void {
    expect(app(PanelDetector::class)->detect(Request::create('/admin/users')))->toBeNull();
});

it('returns null when the filament panel flag is disabled', function (): void {
    config()->set('error-pages.panels.filament', false);

    expect(app(PanelDetector::class)->detect(Request::create('/admin')))->toBeNull();
});

it('leaves normal web/api context resolution unchanged without a panel', function (): void {
    // Regression guard: adding panel detection must not alter the base flow.
    $this->get('/no-panel-missing')->assertStatus(404);
    $this->getJson('/no-panel-api-missing')->assertStatus(404)
        ->assertHeader('content-type', 'application/problem+json');
});
