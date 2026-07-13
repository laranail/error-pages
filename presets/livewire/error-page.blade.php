{{--
    The Livewire error-page view — a faithful port of the shared DOM contract
    (.ep-shell / .ep-card / .ep-brand / .ep-status / .ep-title / .ep-message /
    .ep-actions). This is the view a full-page Livewire ErrorPage component will
    render; it is an inert design asset until Livewire declares Laravel 13 support
    and the canonical templates land. In scope: $page (ErrorPage), $theme
    (ThemeSettings). Livewire bundles its own Alpine, so the package enhancement
    JS is intentionally NOT loaded here (no double-Alpine).
--}}
<div class="ep-body ep-theme-{{ $theme->preset->value }} @if($theme->autoDark) ep-auto-dark @endif">
    <main class="ep-shell" role="main">
        <section class="ep-card">
            <div class="ep-brand">
                @if($theme->logo)
                    <img class="ep-logo" src="{{ $theme->logo }}" alt="{{ $theme->brandName }}">
                @else
                    <span class="ep-brand-name">{{ $theme->brandName }}</span>
                @endif
            </div>
            <p class="ep-status" aria-hidden="true">{{ $page->key }}</p>
            <h1 class="ep-title">{{ $page->title }}</h1>
            <p class="ep-message">{{ $page->message }}</p>
            <div class="ep-actions">
                <a class="ep-btn ep-btn-primary" href="{{ $theme->brandUrl }}">Back to home</a>
            </div>
            @if($page->requestId)
                <p class="ep-ref">Reference: <code>{{ $page->requestId }}</code></p>
            @endif
        </section>
    </main>
</div>
