{{-- The public problem-type documentation page (the RFC 7807/9457 `type` target):
     the branded card + a "what this means / common causes / how to fix" panel.
     In scope: $page (payload array), $doc (meaning/causes/resolution),
     $criticalCss, $themeCss. --}}
<!DOCTYPE html>
<html lang="{{ $page['theme']['locale'] }}" dir="{{ $page['theme']['dir'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="{{ $page['theme']['autoDark'] ? 'light dark' : 'light' }}">
<title>{{ $page['code'] }} &middot; {{ $page['title'] }}</title>
<style>{!! $criticalCss !!}{!! $themeCss !!}</style>
</head>
<body class="ep-body ep-theme-{{ $page['theme']['preset'] }}@if($page['theme']['autoDark']) ep-auto-dark @endif">
<main class="ep-shell ep-shell-doc" role="main">
    <section class="ep-card ep-card-doc">
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

        @if($doc['meaning'] !== '' || $doc['causes'] !== [] || $doc['resolution'] !== [])
            <div class="ep-doc">
                @if($doc['meaning'] !== '')
                    <h2 class="ep-doc-heading">What this means</h2>
                    <p class="ep-doc-text">{{ $doc['meaning'] }}</p>
                @endif
                @if($doc['causes'] !== [])
                    <h2 class="ep-doc-heading">Common causes</h2>
                    <ul class="ep-doc-list">
                        @foreach($doc['causes'] as $cause)<li>{{ $cause }}</li>@endforeach
                    </ul>
                @endif
                @if($doc['resolution'] !== [])
                    <h2 class="ep-doc-heading">How to fix</h2>
                    <ul class="ep-doc-list">
                        @foreach($doc['resolution'] as $step)<li>{{ $step }}</li>@endforeach
                    </ul>
                @endif
            </div>
        @endif

        <div class="ep-actions">
            <a class="ep-btn ep-btn-primary" href="{{ $page['homeUrl'] }}">Back to home</a>
        </div>
    </section>
</main>
</body>
</html>
