{{-- Thin bridge view (Path 1): renders the core HTML for this status.
      Superseded by the canonical CSS-first Blade design when it lands. --}}
{!! \Simtabi\Laranail\ErrorPages\Facades\ErrorPages::renderForWeb($exception, request()) !!}
