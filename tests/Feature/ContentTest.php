<?php

declare(strict_types=1);

it('resolves content in the configured default locale', function (): void {
    app('translator')->addLines(['errors.404.title' => 'Introuvable'], 'fr', 'error-pages');
    config()->set('error-pages.content.default_locale', 'fr');

    expect($this->get('/locale-missing')->getContent())->toContain('Introuvable');
});

it('falls back to the ambient locale when no default is configured', function (): void {
    app('translator')->addLines(['errors.404.title' => 'No encontrada'], 'es', 'error-pages');
    config()->set('error-pages.content.default_locale');
    app()->setLocale('es');

    expect($this->get('/ambient-locale-missing')->getContent())->toContain('No encontrada');
});

it('sets the html lang and ltr direction from the locale', function (): void {
    config()->set('error-pages.content.default_locale', 'fr');

    expect($this->get('/lang-missing')->getContent())
        ->toContain('lang="fr"')
        ->toContain('dir="ltr"');
});

it('sets rtl direction for a right-to-left locale', function (): void {
    config()->set('error-pages.content.default_locale', 'ar');

    expect($this->get('/rtl-missing')->getContent())
        ->toContain('lang="ar"')
        ->toContain('dir="rtl"');
});
