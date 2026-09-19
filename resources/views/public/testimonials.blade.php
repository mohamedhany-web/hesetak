@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $count = $testimonials->count();
    $featuredCount = $testimonials->where('is_featured', true)->count();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __('public.testimonials_page_title') }} — {{ $brand }}</title>
  <meta name="description" content="{{ __('public.home_testimonials_sub') }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.testimonials') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog', 'subpages']])
  <style>
    .gl-tm {
      background:
        radial-gradient(ellipse 70% 45% at 100% 0%, rgba(11,61,145,.08), transparent 55%),
        radial-gradient(ellipse 50% 35% at 0% 20%, rgba(245,184,0,.06), transparent 50%),
        var(--bg, #F4F7FC);
      padding-top: 72px;
    }
    @media (max-width: 991px) {
      .gl-tm { padding-top: 64px; }
    }
    .gl-tm-hero {
      padding: clamp(28px, 5vw, 48px) 0 clamp(28px, 4vw, 40px);
      text-align: center;
    }
    .gl-tm-hero__eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 12px; border-radius: 999px; margin-bottom: .75rem;
      background: #E8EEF8; color: #0B3D91; border: 1px solid #C5D4F0;
      font-size: .7rem; font-weight: 800;
    }
    .gl-tm-hero h1 {
      margin: 0 0 .5rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: clamp(1.55rem, 3.8vw, 2.35rem); font-weight: 900;
      color: #0B1220; line-height: 1.25;
    }
    .gl-tm-hero h1 .hl { color: #0B3D91; }
    .gl-tm-hero > p {
      margin: 0 auto; max-width: 36rem;
      font-size: .88rem; line-height: 1.7; color: #5B6577; font-weight: 600;
    }
    .gl-tm-metrics {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: .65rem;
      max-width: 640px; margin: 1.25rem auto 0;
    }
    @media (max-width: 575px) {
      .gl-tm-metrics { grid-template-columns: 1fr; }
    }
    .gl-tm-metric {
      background: #fff; border: 1.5px solid #D7DDE6; border-radius: 14px;
      padding: .85rem .7rem; text-align: center;
      box-shadow: 0 8px 22px -18px rgba(11,61,145,.28);
    }
    .gl-tm-metric strong {
      display: block; font-size: 1.35rem; font-weight: 900; color: #0B3D91; line-height: 1.2;
    }
    .gl-tm-metric span {
      display: block; margin-top: .25rem; font-size: .7rem; font-weight: 700; color: #5B6577;
    }
    .gl-tm-grid-wrap { padding: 0 0 clamp(40px, 6vw, 64px); }
    .gl-tm-grid {
      display: grid; gap: .85rem;
      grid-template-columns: 1fr;
    }
    @media (min-width: 640px) {
      .gl-tm-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 992px) {
      .gl-tm-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .gl-tm-card {
      display: flex; flex-direction: column;
      background: #fff; border: 1.5px solid #D7DDE6; border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 28px -20px rgba(11,61,145,.28);
      transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .gl-tm-card:hover {
      border-color: rgba(11,61,145,.3);
      box-shadow: 0 16px 36px -20px rgba(11,61,145,.38);
      transform: translateY(-2px);
    }
    .gl-tm-card--featured {
      background: linear-gradient(155deg, #051F4D 0%, #0B3D91 60%, #072A66 100%);
      border-color: #0B3D91;
      color: #fff;
    }
    .gl-tm-card__media {
      aspect-ratio: 4 / 3; max-height: 15rem;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; background: #F4F7FC;
    }
    .gl-tm-card--featured .gl-tm-card__media { background: rgba(255,255,255,.1); }
    .gl-tm-card__media img {
      max-height: 100%; max-width: 100%; width: auto; height: auto;
      object-fit: contain; object-center: center; display: block;
    }
    .gl-tm-card__body {
      padding: 1.05rem 1.1rem 1.15rem;
      flex: 1; display: flex; flex-direction: column;
    }
    .gl-tm-card__quote {
      margin: 0 0 .35rem; color: #0B3D91; font-size: 1.1rem; opacity: .55;
    }
    .gl-tm-card--featured .gl-tm-card__quote { color: #F5B800; opacity: .85; }
    .gl-tm-card__text {
      margin: 0; flex: 1;
      font-size: .82rem; line-height: 1.75; font-weight: 600; color: #3D4656;
    }
    .gl-tm-card--featured .gl-tm-card__text { color: rgba(255,255,255,.92); }
    .gl-tm-card__author {
      margin: 1rem 0 0; padding-top: .85rem;
      border-top: 1px solid #E8EEF8;
      display: flex; align-items: center; gap: .65rem;
    }
    .gl-tm-card--featured .gl-tm-card__author { border-top-color: rgba(255,255,255,.18); }
    .gl-tm-card__avatar {
      width: 40px; height: 40px; border-radius: 12px; flex-shrink: 0;
      display: grid; place-items: center;
      background: #E8EEF8; color: #0B3D91;
      font-family: Cairo, Tajawal, sans-serif;
      font-size: .9rem; font-weight: 900;
    }
    .gl-tm-card--featured .gl-tm-card__avatar {
      background: rgba(245,184,0,.2); color: #F5B800;
    }
    .gl-tm-card__author strong {
      display: block; font-size: .84rem; font-weight: 900; color: #0B1220; line-height: 1.3;
    }
    .gl-tm-card--featured .gl-tm-card__author strong { color: #F5B800; }
    .gl-tm-card__author span {
      display: block; margin-top: 2px; font-size: .7rem; font-weight: 700; color: #5B6577;
    }
    .gl-tm-card--featured .gl-tm-card__author span { color: rgba(255,255,255,.72); }
    .gl-tm-empty {
      text-align: center; padding: 3rem 1.25rem;
      background: #fff; border: 1.5px dashed #D7DDE6; border-radius: 18px;
    }
    .gl-tm-empty i {
      width: 56px; height: 56px; border-radius: 16px; margin: 0 auto .85rem;
      display: grid; place-items: center; background: #E8EEF8; color: #0B3D91; font-size: 1.35rem;
    }
    .gl-tm-empty h3 {
      margin: 0 0 .35rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: 1.15rem; font-weight: 900; color: #0B1220;
    }
    .gl-tm-empty p { margin: 0 0 1rem; font-size: .84rem; color: #5B6577; font-weight: 600; }
    .gl-tm-final {
      margin: 0 0 clamp(40px, 6vw, 64px);
      border-radius: 18px; padding: clamp(1.25rem, 3vw, 1.75rem);
      background: linear-gradient(135deg, #051F4D 0%, #0B3D91 55%, #072A66 100%);
      color: #fff; text-align: center;
      box-shadow: 0 16px 40px -24px rgba(11,61,145,.5);
    }
    .gl-tm-final h2 {
      margin: 0 0 .4rem; font-family: Cairo, Tajawal, sans-serif;
      font-size: clamp(1.1rem, 2.4vw, 1.4rem); font-weight: 900;
    }
    .gl-tm-final > p {
      margin: 0 auto 1rem; max-width: 34rem;
      font-size: .84rem; line-height: 1.65; color: rgba(255,255,255,.78); font-weight: 600;
    }
    .gl-tm-final__actions {
      display: flex; flex-wrap: wrap; justify-content: center; gap: .55rem;
    }
    .gl-tm-final__actions .sana-btn {
      padding: .7rem 1.15rem; font-size: .84rem; min-height: 0;
    }
  </style>
</head>
<body class="sana-home sana-courses-page gl-tm-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => '', 'navSolid' => true, 'navHero' => false])

<main class="gl-tm">
  <section class="gl-tm-hero">
    <div class="sana-container sana-reveal">
      <span class="gl-tm-hero__eyebrow"><i class="fas fa-quote-right"></i> {{ __('public.testimonials_page_title') }}</span>
      <h1>{{ __('public.home_testimonials_heading') }} <span class="hl">{{ $brand }}</span></h1>
      <p>{{ __('public.home_testimonials_sub') }}</p>
      <div class="gl-tm-metrics">
        <article class="gl-tm-metric">
          <strong>{{ number_format($count) }}</strong>
          <span>{{ $isRtl ? 'رأي منشور' : 'Published voices' }}</span>
        </article>
        <article class="gl-tm-metric">
          <strong>{{ number_format($featuredCount) }}</strong>
          <span>{{ $isRtl ? 'آراء مميزة' : 'Featured' }}</span>
        </article>
        <article class="gl-tm-metric">
          <strong><i class="fas fa-star" style="font-size:1.1rem;color:#F5B800"></i></strong>
          <span>{{ $isRtl ? 'تجارب حقيقية' : 'Real experiences' }}</span>
        </article>
      </div>
    </div>
  </section>

  <section class="gl-tm-grid-wrap">
    <div class="sana-container">
      @if($testimonials->isEmpty())
        <div class="gl-tm-empty sana-reveal">
          <i class="fas fa-quote-right"></i>
          <h3>{{ __('public.testimonials_page_title') }}</h3>
          <p>{{ __('public.home_testimonials_empty') }}</p>
          <a href="{{ route('home') }}" class="sana-btn sana-btn--purple">{{ __('public.home') }}</a>
        </div>
      @else
        <div class="gl-tm-grid sana-reveal">
          @foreach($testimonials as $t)
            @php
              $featured = (bool) $t->is_featured;
              $initial = mb_substr($t->author_name ?: ($isRtl ? 'ر' : 'T'), 0, 1, 'UTF-8');
              $bodyText = strip_tags((string) ($t->body ?? ''));
            @endphp
            <article class="gl-tm-card {{ $featured ? 'gl-tm-card--featured' : '' }}">
              @if($t->isImageType() && $t->publicImageUrl())
                <div class="gl-tm-card__media">
                  <img src="{{ $t->publicImageUrl() }}" alt="" loading="lazy" decoding="async">
                </div>
              @endif
              <div class="gl-tm-card__body">
                @if(! $t->isImageType())
                  <div class="gl-tm-card__quote" aria-hidden="true"><i class="fas fa-quote-right"></i></div>
                @endif
                @if($bodyText !== '')
                  <p class="gl-tm-card__text">
                    @if($t->isImageType())
                      {{ \Illuminate\Support\Str::limit($bodyText, 160) }}
                    @else
                      «{{ \Illuminate\Support\Str::limit($bodyText, 260) }}»
                    @endif
                  </p>
                @endif
                @if($t->author_name || $t->role_label)
                  <div class="gl-tm-card__author">
                    <span class="gl-tm-card__avatar">{{ $initial }}</span>
                    <div class="min-w-0">
                      @if($t->author_name)
                        <strong>{{ $t->author_name }}</strong>
                      @endif
                      @if($t->role_label)
                        <span>{{ $t->role_label }}</span>
                      @endif
                    </div>
                  </div>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  <div class="sana-container">
    <section class="gl-tm-final sana-reveal">
      <h2>{{ $isRtl ? 'جاهز تبدأ رحلتك معنا؟' : 'Ready to start your journey?' }}</h2>
      <p>{{ $isRtl ? 'احجز تقييم مستوى مجاني أو تواصل معنا عبر واتساب — نرشدك للمسار المناسب.' : 'Book a free level assessment or message us on WhatsApp — we’ll guide you to the right path.' }}</p>
      <div class="gl-tm-final__actions">
        <a href="{{ route('home') }}?open_trial=1" class="sana-btn sana-btn--yellow">
          <i class="fas fa-clipboard-check"></i> {{ __('landing.academy.free_trial_cta') }}
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
