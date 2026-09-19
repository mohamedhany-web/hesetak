@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __('public.services_page_title') }} — {{ $brand }}</title>
  <meta name="description" content="{{ __('public.services_subtitle') }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.services.index') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog', 'subpages']])
  <style>
    .gl-svci {
      background:
        radial-gradient(ellipse 70% 45% at 100% 0%, rgba(11,61,145,.08), transparent 55%),
        radial-gradient(ellipse 50% 35% at 0% 20%, rgba(245,184,0,.06), transparent 50%),
        var(--bg, #F4F7FC);
      padding-top: 72px;
    }
    @media (max-width: 991px) {
      .gl-svci { padding-top: 64px; }
    }
    .gl-svci-hero {
      padding: clamp(28px, 5vw, 48px) 0 clamp(28px, 4vw, 40px);
      text-align: center;
    }
    .gl-svci-hero__eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 12px; border-radius: 999px; margin-bottom: .75rem;
      background: #E8EEF8; color: #0B3D91; border: 1px solid #C5D4F0;
      font-size: .7rem; font-weight: 800;
    }
    .gl-svci-hero h1 {
      margin: 0 0 .5rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: clamp(1.55rem, 3.8vw, 2.35rem); font-weight: 900;
      color: #0B1220; line-height: 1.25;
    }
    .gl-svci-hero h1 .hl { color: #0B3D91; }
    .gl-svci-hero > p {
      margin: 0 auto; max-width: 36rem;
      font-size: .88rem; line-height: 1.7; color: #5B6577; font-weight: 600;
    }
    .gl-svci-metrics {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: .65rem;
      max-width: 640px; margin: 1.25rem auto 0;
    }
    @media (max-width: 575px) {
      .gl-svci-metrics { grid-template-columns: 1fr; }
    }
    .gl-svci-metric {
      background: #fff; border: 1.5px solid #D7DDE6; border-radius: 14px;
      padding: .85rem .7rem; text-align: center;
      box-shadow: 0 8px 22px -18px rgba(11,61,145,.28);
    }
    .gl-svci-metric strong {
      display: block; font-size: 1.35rem; font-weight: 900; color: #0B3D91; line-height: 1.2;
    }
    .gl-svci-metric span {
      display: block; margin-top: .25rem; font-size: .7rem; font-weight: 700; color: #5B6577;
    }
    .gl-svci-grid-wrap {
      padding: 0 0 clamp(40px, 6vw, 64px);
    }
    .gl-svci-grid {
      display: grid; gap: .85rem;
      grid-template-columns: 1fr;
    }
    @media (min-width: 640px) {
      .gl-svci-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 992px) {
      .gl-svci-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .gl-svci-card {
      display: flex; flex-direction: column;
      background: #fff; border: 1.5px solid #D7DDE6; border-radius: 16px;
      overflow: hidden; text-decoration: none !important; color: inherit;
      box-shadow: 0 10px 28px -20px rgba(11,61,145,.28);
      transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .gl-svci-card:hover {
      border-color: rgba(11,61,145,.35);
      box-shadow: 0 16px 36px -20px rgba(11,61,145,.4);
      transform: translateY(-3px);
    }
    .gl-svci-card__media {
      height: 148px; background: linear-gradient(145deg, #051F4D, #0B3D91);
      display: grid; place-items: center; color: rgba(255,255,255,.35); font-size: 2rem;
      overflow: hidden;
    }
    .gl-svci-card__media img {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transition: transform .45s ease;
    }
    .gl-svci-card:hover .gl-svci-card__media img { transform: scale(1.05); }
    .gl-svci-card__body { padding: 1rem 1.05rem 1.1rem; flex: 1; display: flex; flex-direction: column; }
    .gl-svci-card__body h2 {
      margin: 0 0 .4rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: .98rem; font-weight: 900; color: #0B1220; line-height: 1.35;
    }
    .gl-svci-card__body p {
      margin: 0 0 .85rem; flex: 1;
      font-size: .76rem; line-height: 1.6; color: #5B6577; font-weight: 600;
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .gl-svci-card__more {
      font-size: .74rem; font-weight: 800; color: #0B3D91;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .gl-svci-empty {
      text-align: center; padding: 3rem 1.25rem;
      background: #fff; border: 1.5px dashed #D7DDE6; border-radius: 18px;
    }
    .gl-svci-empty i {
      width: 56px; height: 56px; border-radius: 16px; margin: 0 auto .85rem;
      display: grid; place-items: center; background: #E8EEF8; color: #0B3D91; font-size: 1.35rem;
    }
    .gl-svci-empty h3 {
      margin: 0 0 .35rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: 1.15rem; font-weight: 900; color: #0B1220;
    }
    .gl-svci-empty p { margin: 0 0 1rem; font-size: .84rem; color: #5B6577; font-weight: 600; }
    .gl-svci-final {
      margin: 0 0 clamp(40px, 6vw, 64px);
      border-radius: 18px; padding: clamp(1.25rem, 3vw, 1.75rem);
      background: linear-gradient(135deg, #051F4D 0%, #0B3D91 55%, #072A66 100%);
      color: #fff; text-align: center;
      box-shadow: 0 16px 40px -24px rgba(11,61,145,.5);
    }
    .gl-svci-final h2 {
      margin: 0 0 .4rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: clamp(1.1rem, 2.4vw, 1.4rem); font-weight: 900;
    }
    .gl-svci-final > p {
      margin: 0 auto 1rem; max-width: 34rem;
      font-size: .84rem; line-height: 1.65; color: rgba(255,255,255,.78); font-weight: 600;
    }
    .gl-svci-final__actions {
      display: flex; flex-wrap: wrap; justify-content: center; gap: .55rem;
    }
    .gl-svci-final__actions .sana-btn {
      padding: .7rem 1.15rem; font-size: .84rem; min-height: 0;
    }
  </style>
</head>
<body class="sana-home sana-courses-page gl-svci-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => '', 'navSolid' => true, 'navHero' => false])

<main class="gl-svci">
  <section class="gl-svci-hero">
    <div class="sana-container sana-reveal">
      <span class="gl-svci-hero__eyebrow"><i class="fas fa-concierge-bell"></i> {{ __('public.services_page_title') }}</span>
      <h1>{{ __('public.services_heading') }} <span class="hl">{{ $brand }}</span></h1>
      <p>{{ __('public.services_subtitle') }}</p>
      <div class="gl-svci-metrics">
        <article class="gl-svci-metric">
          <strong>{{ number_format($services->count()) }}</strong>
          <span>{{ __('public.services_count_label') }}</span>
        </article>
        <article class="gl-svci-metric">
          <strong><i class="fas fa-check-circle" style="font-size:1.15rem"></i></strong>
          <span>{{ __('public.services_quality_hint') }}</span>
        </article>
        <article class="gl-svci-metric">
          <strong><i class="fas fa-headset" style="font-size:1.15rem"></i></strong>
          <span>{{ __('public.services_support_hint') }}</span>
        </article>
      </div>
    </div>
  </section>

  <section class="gl-svci-grid-wrap">
    <div class="sana-container">
      @if($services->count() > 0)
        <div class="gl-svci-grid sana-reveal">
          @foreach($services as $service)
            <a href="{{ route('public.services.show', $service) }}" class="gl-svci-card">
              <div class="gl-svci-card__media">
                @if($service->publicImageUrl())
                  <img src="{{ $service->publicImageUrl() }}" alt="" loading="lazy" decoding="async">
                @else
                  <i class="fas fa-layer-group" aria-hidden="true"></i>
                @endif
              </div>
              <div class="gl-svci-card__body">
                <h2>{{ $service->name }}</h2>
                <p>{{ \Illuminate\Support\Str::limit(strip_tags($service->summary ?: $service->body), 140) }}</p>
                <span class="gl-svci-card__more">
                  {{ __('public.services_read_more') }}
                  <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} text-[10px]"></i>
                </span>
              </div>
            </a>
          @endforeach
        </div>
      @else
        <div class="gl-svci-empty sana-reveal">
          <i class="fas fa-concierge-bell"></i>
          <h3>{{ __('public.services_empty_title') }}</h3>
          <p>{{ __('public.services_empty_desc') }}</p>
          <a href="{{ route('home') }}" class="sana-btn sana-btn--purple">{{ __('public.home') }}</a>
        </div>
      @endif
    </div>
  </section>

  <div class="sana-container">
    <section class="gl-svci-final sana-reveal">
      <h2>{{ __('public.services_cta_title') }}</h2>
      <p>{{ __('public.services_cta_text') }}</p>
      <div class="gl-svci-final__actions">
        <a href="{{ route('public.contact') }}" class="sana-btn sana-btn--yellow">
          <i class="fas fa-paper-plane"></i> {{ __('public.contact_us') }}
        </a>
        <a href="{{ $waUrl }}" class="sana-btn sana-btn--wa" target="_blank" rel="noopener">
          <i class="fab fa-whatsapp"></i> {{ $isRtl ? 'واتساب' : 'WhatsApp' }}
        </a>
        <a href="{{ route('public.courses') }}" class="sana-btn sana-btn--white-outline">
          {{ __('public.browse_courses') }}
        </a>
      </div>
    </section>
  </div>
</main>

@include('partials.landing.footer')
</body>
</html>
