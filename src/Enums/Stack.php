<?php

declare(strict_types=1);

namespace Simtabi\Laranail\ErrorPages\Enums;

use Simtabi\Laranail\Enumerator\Attributes\Description;
use Simtabi\Laranail\Enumerator\Attributes\Label;
use Simtabi\Laranail\Enumerator\Concerns\HasEnumeratorBehavior;
use Simtabi\Laranail\Enumerator\Contracts\Enumerator;

/**
 * How the web/inertia error page is produced. The API context is not a stack —
 * it always renders RFC 7807 JSON. `blade`/`livewire` are server-HTML (Path 1,
 * the `errors::{code}` view); the rest are client/SPA (Path 2, a renderable).
 *
 * Uses the org-standard `laranail/enumerator` for attribute-driven metadata
 * (`label()`, `description()`); the bridge-specific routing predicates below
 * decide which coexistence path and renderer a stack maps to.
 */
enum Stack: string implements Enumerator
{
    use HasEnumeratorBehavior;

    #[Label('Blade'), Description('Server-rendered Blade views (Path 1).')]
    case Blade = 'blade';

    #[Label('Livewire'), Description('Server-HTML alias of Blade until the Livewire component ships.')]
    case Livewire = 'livewire';

    #[Label('Inertia + Vue'), Description('Inertia page rendered by a Vue ErrorPage component (Path 2).')]
    case InertiaVue = 'inertia-vue';

    #[Label('Inertia + React'), Description('Inertia page rendered by a React ErrorPage component (Path 2).')]
    case InertiaReact = 'inertia-react';

    #[Label('Vue SPA'), Description('Self-contained page + embedded payload for a Vue SPA (Path 2).')]
    case Vue = 'vue';

    #[Label('React SPA'), Description('Self-contained page + embedded payload for a React SPA (Path 2).')]
    case React = 'react';

    /**
     * Resolve a (possibly null/invalid) config value to a case, defaulting to
     * the canonical server-HTML `blade` stack.
     */
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
