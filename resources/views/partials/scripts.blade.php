@inject('sepAssets', 'Simtabi\Laranail\ServerErrorPages\Services\AssetInliner')
<script>window.__sep={retryAfter:{{ $page->retryAfter ?? 'null' }},retryable:{{ $page->retryable ? 'true' : 'false' }},urlBase:{!! json_encode(config('laranail.server-error-pages.output.url_base', '/')) !!}};</script>
<script>{!! $sepAssets->js() !!}</script>
