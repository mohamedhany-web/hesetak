@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $g = 'landing.groups_page';
    $brand = config('app.name', 'حصتك');
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $currency = __('landing.currency');
    $fallbackImg = 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80';
    $groups = $groups ?? $courses ?? collect();
    $count = (int) ($groupCount ?? $groups->total());
    $countLabel = $count === 1
        ? __($g.'.courses_count_one')
        : __($g.'.courses_count', ['count' => $count]);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __($g.'.catalog_group_title') }} — {{ $brand }}</title>
  <meta name="description" content="{{ __($g.'.catalog_group_desc') }}">
  <meta name="theme-color" content="#0B3D91">
  <link rel="canonical" href="{{ route('public.groups.courses') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog']])
  @include('partials.landing.groups-catalog-styles')
</head>
<body class="sana-home sana-courses-page gl-gc-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => 'groups', 'navSolid' => true, 'navHero' => false])

<main class="sana-cat-page">
  <section class="sana-cat-hero">
    <div class="sana-container sana-cat-hero__inner sana-reveal">
      <div class="sana-cat-hero__breadcrumb">
        <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
        <span>/</span>
        <a href="{{ route('public.groups') }}">{{ __($g.'.title') }}</a>
        <span>/</span>
        <span>{{ __($g.'.catalog_group_title') }}</span>
      </div>
      <h1 class="sana-cat-hero__title">
        {{ __($g.'.catalog_group_title') }}
        <span class="hl">{{ $isRtl ? 'مع المدرّس' : 'with tutor' }}</span>
      </h1>
      <p class="sana-cat-hero__desc">{{ __($g.'.catalog_group_desc') }}</p>
      <p class="sana-cat-hero__stats">
        <span class="sana-cat-hero__stat"><i class="fas fa-users"></i> {{ $countLabel }}</span>
        <span class="sana-cat-hero__stat"><i class="fas fa-calendar"></i> {{ $isRtl ? 'حسب جدول المدرب' : 'Tutor schedule' }}</span>
      </p>
    </div>
  </section>

  <div class="sana-container gl-gc-body">
    @if ($groups->isNotEmpty())
      <div class="gl-gc-grid sana-reveal">
        @foreach ($groups as $item)
          @php $thumb = $item->imageUrl() ?: $fallbackImg; @endphp
          <a href="{{ route('public.groups.show', $item->slug) }}" class="gl-gc-card">
            <div class="gl-gc-card__media">
              <img src="{{ $thumb }}" alt="{{ $item->title }}" loading="lazy" width="600" height="375">
              <span class="gl-gc-card__badge"><i class="fas fa-users"></i> {{ $isRtl ? 'جماعي' : 'Group' }}</span>
            </div>
            <div class="gl-gc-card__body">
              <h2>{{ $item->title }}</h2>
              <p class="gl-gc-card__meta">
                <i class="fas fa-chalkboard-user"></i>
                {{ $item->instructor->name ?? ($isRtl ? 'معلّم على المنصة' : 'Platform tutor') }}
              </p>
              <p class="gl-gc-card__meta"><i class="fas fa-clock"></i> {{ $item->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }} · {{ $isRtl ? 'سعة' : 'seats' }} {{ $item->capacity }}</p>
              <div class="gl-gc-card__foot">
                <span class="gl-gc-card__price">{{ $item->formattedPrice() }}</span>
                <span class="gl-gc-card__cta">{{ __($g.'.details_cta') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}"></i></span>
              </div>
            </div>
          </a>
        @endforeach
      </div>
      @if ($groups->hasPages())
        <div class="gl-gc-pager">{{ $groups->withQueryString()->links() }}</div>
      @endif
    @else
      <div class="gl-gc-empty sana-reveal">
        <p>{{ __($g.'.empty_group') }}</p>
        <a href="{{ route('public.groups') }}" class="sana-btn sana-btn--yellow">{{ __($g.'.title') }}</a>
      </div>
    @endif

    <div class="gl-gc-band sana-reveal">
      <div class="gl-gc-band__inner">
        <div>
          <h2>{{ __($g.'.cta_title') }}</h2>
          <p>{{ __($g.'.catalog_group_cta_sub') }}</p>
        </div>
        <div class="gl-gc-band__actions">
          <a href="{{ route('home') }}?open_trial=1" class="sana-btn sana-btn--yellow"><i class="fas fa-clipboard-check"></i> {{ __($g.'.cta_trial') }}</a>
          <a href="{{ route('public.instructors.index') }}" class="sana-btn sana-btn--wa"><i class="fas fa-user"></i> {{ $isRtl ? 'المعلمون والحصص الخاصة' : 'Teachers & private lessons' }}</a>
          <a href="{{ $waUrl }}" class="sana-btn" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.28)" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        </div>
      </div>
    </div>
  </div>
</main>

@include('partials.landing.footer')
</body>
</html>
