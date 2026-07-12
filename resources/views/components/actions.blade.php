@props(['page', 'theme'])
@php($home = config('laranail.server-error-pages.output.url_base', '/'))
<div class="sep-actions">
<a class="sep-btn sep-btn-primary" href="{{ $home }}">Back to home</a>
@if ($page->retryable)
<button type="button" class="sep-btn sep-btn-ghost" data-sep-action="retry">Try again</button>
@endif
<button type="button" class="sep-btn sep-btn-ghost" data-sep-action="copy" data-sep-code="{{ $page->code }}" data-sep-title="{{ $page->title }}">Copy error details</button>
</div>
@if ($page->retryable && $page->retryAfter)
<p class="sep-retry" data-sep-countdown="{{ $page->retryAfter }}" hidden>Retrying automatically in <span data-sep-seconds>{{ $page->retryAfter }}</span>s…</p>
@endif
