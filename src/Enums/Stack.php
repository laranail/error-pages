<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Enums;

/**
 * How the web/inertia error page is produced. The API context is not a stack —
 * it always renders RFC 7807 JSON. `blade`/`livewire` are server-HTML (Path 1,
 * the `errors::{code}` view); the rest are client/SPA (Path 2, a renderable).
 */
enum Stack: string
{
    case Blade = 'blade';
    case Livewire = 'livewire';
    case InertiaVue = 'inertia-vue';
    case InertiaReact = 'inertia-react';
    case Vue = 'vue';
    case React = 'react';

    public static function fromValue(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Blade;
    }

    public function isServerHtml(): bool
    {
        return $this === self::Blade || $this === self::Livewire;
    }

    public function isInertia(): bool
    {
        return $this === self::InertiaVue || $this === self::InertiaReact;
    }

    public function isSpa(): bool
    {
        return $this === self::Vue || $this === self::React;
    }
}
