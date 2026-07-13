{{--
    Blade error page — the CANONICAL design target (Path 1). This starter renders
    the shared DOM contract (.ep-shell / .ep-card / .ep-brand / .ep-status /
    .ep-title / .ep-message / .ep-actions); the maintainer's CSS-first templates
    replace it and the other stacks are ported from it. In scope: $page (an
    ErrorPage value object: ->key/->code/->title/->message/->retryable/->retryAfter/
    ->requestId) and $theme (ThemeSettings: ->preset/->brandName/->brandUrl/->logo/
    ->autoDark). Styles come from the shared stylesheet
    (@laranail/error-pages-ui/style.css, built from presets/shared/scss).
--}}
<!DOCTYPE html>
<html lang="{{ $theme->locale }}" dir="{{ $theme->dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="{{ $theme->autoDark ? 'light dark' : 'light' }}">
    @if($page->retryable && $page->retryAfter !== null)
        <meta http-equiv="refresh" content="{{ (int) $page->retryAfter }}">
    @endif
    <title>{{ $page->key }} &middot; {{ $page->title }}</title>
    {{-- Publish/point this at @laranail/error-pages-ui/style.css (or your build). --}}
    <link rel="stylesheet" href="{{ asset('vendor/error-pages/error-pages.css') }}">
</head>
<body class="ep-body ep-theme-{{ $theme->preset->value }}@if($theme->autoDark) ep-auto-dark @endif">
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
</body>
</html>
