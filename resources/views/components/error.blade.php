{{-- Embeddable error fragment (the shared DOM contract, no document chrome) for
     <x-error-pages::error />. In scope: $page (the payload array). Include the
     shared stylesheet (or your own) for the ep-* classes. --}}
<div {{ $attributes->class(['ep-body', 'ep-theme-' . $page['theme']['preset'], 'ep-auto-dark' => $page['theme']['autoDark']]) }}>
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
