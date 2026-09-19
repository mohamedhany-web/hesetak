<section class="lasles-network" id="network">
  <div class="lasles-container">
    <h2 class="lasles-section-title">
      {{ $isRtl ? 'شبكة واسعة من المعلمين والممارسات' : 'Huge Global Network of Fast VPN' }}
    </h2>
    <p class="lasles-section-lead">
      @if($isRtl)
        شاهد كيف تصل {{ $brandAr }} لمعلمين في سياقات مختلفة لتسهيل تطوير الممارسة المهنية.
      @else
        See <strong>{{ $brand }}</strong> everywhere to make it easier for you when you move locations.
      @endif
    </p>
    <div class="lasles-network__map">
      <img src="{{ $img('map-global.svg') }}" width="900" height="450" alt="" loading="lazy" decoding="async">
    </div>
    <div class="lasles-sponsors" aria-label="{{ $isRtl ? 'شركاء' : 'Sponsors' }}">
      <img src="{{ $img('sponsor-1.png') }}" alt="" loading="lazy">
      <img src="{{ $img('sponsor-2.png') }}" alt="" loading="lazy">
      <img src="{{ $img('sponsor-3.png') }}" alt="" loading="lazy">
      <img src="{{ $img('sponsor-4.png') }}" alt="" loading="lazy">
      <img src="{{ $img('sponsor-5.png') }}" alt="" loading="lazy">
    </div>
  </div>
</section>
