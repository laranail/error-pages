@props(['theme'])
<div class="sep-brand">
@if ($theme->logo)
<img class="sep-logo" src="{{ $theme->logo }}" alt="{{ $theme->brandName }}">
@else
<span class="sep-brand-name">{{ $theme->brandName }}</span>
@endif
</div>
