@php($assets = rtrim(config('laranail.server-error-pages.output.assets_url', '/vendor/server-error-pages'), '/'))
<script src="{{ $assets }}/js/error-pages.js" defer></script>
