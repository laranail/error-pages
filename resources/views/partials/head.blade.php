@inject('sepAssets', 'Simtabi\Laranail\ServerErrorPages\Services\AssetInliner')
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="{{ $theme->autoDark ? 'light dark' : 'light' }}">
@if ($page->retryable && $page->retryAfter)
<meta http-equiv="refresh" content="{{ $page->retryAfter }}; url={{ config('laranail.server-error-pages.output.url_base', '/') }}">
@endif
<title>{{ $page->key }} · {{ $page->title }}</title>
<style>{!! \Simtabi\Laranail\ServerErrorPages\Support\CssVariableMap::inline($theme) !!}</style>
<style>{!! $sepAssets->css() !!}</style>
