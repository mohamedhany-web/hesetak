@php
    $brandHref = $brandHref ?? route('home');
    $brandId = $brandId ?? null;
    $markPath = public_path('img/brand/hesetak-mark.png');
    $brandMarkUrl = asset('img/brand/hesetak-mark.png').'?v='.(is_file($markPath) ? filemtime($markPath) : time());
@endphp
<a href="{{ $brandHref }}" class="mc-brand"@if($brandId) id="{{ $brandId }}"@endif>
  <img
    src="{{ $brandMarkUrl }}"
    alt=""
    class="mc-brand__mark"
    width="44"
    height="44"
    decoding="async"
  >
  <span>{{ __('landing.nav.brand') }}</span>
</a>
