<?php

declare(strict_types=1);

it('routes a Nova (Inertia) request to an Inertia response, not HTML', function (): void {
    // Simulate Nova being installed + serving at its configured path.
    config()->set('nova.path', 'nova');

    $response = $this->get('/nova/dashboards/main', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => '',
    ]);

    $response->assertStatus(404)->assertHeader('X-Inertia', 'true');
    expect($response->json('component'))->toBe('ErrorPage');
});

it('does not claim the nova context for a plain full-page load', function (): void {
    config()->set('nova.path', 'nova');

    // No X-Inertia header → not an Inertia request → stays web (branded HTML).
    $response = $this->get('/nova/dashboards/main');

    $response->assertStatus(404);
    expect($response->getContent())->toContain('class="ep-status"'); // HTML, not Inertia
});

it('ignores nova detection when panels.nova is disabled', function (): void {
    config()->set('nova.path', 'nova');
    config()->set('error-pages.panels.nova', false);

    // With detection off, an X-Inertia request under the nova path is a normal
    // inertia context (still Inertia, but not via nova detection) — assert it
    // does not error and returns Inertia.
    $this->get('/nova/x', ['X-Inertia' => 'true', 'X-Inertia-Version' => ''])
        ->assertStatus(404)
        ->assertHeader('X-Inertia', 'true');
});
