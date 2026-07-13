<?php

declare(strict_types=1);

it('renders the preview gallery index with every code and theme', function (): void {
    $response = $this->get('/_error-pages');

    $response->assertStatus(200);
    expect($response->getContent())
        ->toContain('preview gallery')
        ->toContain('/_error-pages/404?theme=midnight')
        ->toContain('/_error-pages/503?theme=crimson')
        ->toContain('>4xx<');
});

it('previews a single code and honours a ?theme override', function (): void {
    $response = $this->get('/_error-pages/500?theme=midnight');

    $response->assertStatus(200);
    expect($response->getContent())
        ->toContain('>500<')
        ->toContain('ep-theme-midnight');
});

it('previews a generic key', function (): void {
    expect($this->get('/_error-pages/5xx')->getContent())->toContain('>5xx<');
});
