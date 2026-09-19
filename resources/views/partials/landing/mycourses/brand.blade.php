@php
    $brandHref = $brandHref ?? route('home');
    $brandId = $brandId ?? null;
    $brandMarkUrl = public_img_url('brand/hesetak-mark.png');
    $brandFallback = \App\Services\AdminPanelBranding::inlineFallbackDataUri();
@endphp
<a href="{{ $brandHref }}" class="mc-brand"@if($brandId) id="{{ $brandId }}"@endif>
  <img
    src="{{ $brandMarkUrl }}"
    alt=""
    class="mc-brand__mark"
    width="44"
    height="44"
    decoding="async"
    onerror="this.onerror=null;this.src={{ \Illuminate\Support\Js::from($brandFallback) }};"
  >
  <span>{{ __('landing.nav.brand') }}</span>
</a>
