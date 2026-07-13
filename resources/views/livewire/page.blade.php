{{-- Full-page wrapper for the Livewire error stack: inlines the critical CSS and
     loads Livewire's own styles/scripts (which bundle Alpine). --}}
<!DOCTYPE html>
<html lang="{{ $page['theme']['locale'] }}" dir="{{ $page['theme']['dir'] }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="{{ $page['theme']['autoDark'] ? 'light dark' : 'light' }}">
@if($page['retryable'] && $page['retryAfter'] !== null)
    <meta http-equiv="refresh" content="{{ (int) $page['retryAfter'] }}">
@endif
<title>{{ $page['code'] }} &middot; {{ $page['title'] }}</title>
<style>{!! $criticalCss !!}{!! $themeCss !!}</style>
@livewireStyles
</head>
<body>
<livewire:laranail-error-page :page="$page" />
@livewireScripts
</body>
</html>
