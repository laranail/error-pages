{{--
    The Livewire ErrorPage component view — the shared DOM contract
    (.ep-shell / .ep-card / .ep-brand / .ep-status / .ep-title / .ep-message /
    .ep-actions), driven by the one payload array. Single root element (Livewire).
    Livewire bundles its own Alpine, so the package enhancement JS is not loaded
    here. Final visual CSS lands with the canonical templates.
--}}
<div class="ep-body ep-theme-{{ $page['theme']['preset'] }}@if($page['theme']['autoDark']) ep-auto-dark @endif">
    <main class="ep-shell" role="main">
        <section class="ep-card">
            <div class="ep-brand">
                @if(! empty($page['brand']['logo']))
                    <img class="ep-logo" src="{{ $page['brand']['logo'] }}" alt="{{ $page['brand']['name'] }}">
                @else
                    <span class="ep-brand-name">{{ $page['brand']['name'] }}</span>
                @endif
            </div>
            <p class="ep-status" aria-hidden="true">{{ $page['code'] }}</p>
            <h1 class="ep-title">{{ $page['title'] }}</h1>
            <p class="ep-message">{{ $page['message'] }}</p>
            <div class="ep-actions">
                <a class="ep-btn ep-btn-primary" href="{{ $page['homeUrl'] }}">Back to home</a>
            </div>
            @if(! empty($page['requestId']))
                <p class="ep-ref">Reference: <code>{{ $page['requestId'] }}</code></p>
            @endif
        </section>
    </main>
</div>
