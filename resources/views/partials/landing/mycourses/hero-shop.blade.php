@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $heroWebp = public_path('img/mycourses/hero-tutor-student.webp');
    $heroFile = is_file($heroWebp) ? 'mycourses/hero-tutor-student.webp' : 'mycourses/hero-tutor-student.png';
    $heroImage = public_img_url($heroFile);
@endphp
<section class="dp-hero" aria-labelledby="dp-hero-title">
  <figure class="dp-hero__media">
    <img
      src="{{ $heroImage }}"
      width="1600"
      height="900"
      alt="{{ app()->getLocale() === 'ar' ? 'ولي أمر يتابع درسًا أونلاين مع ابنه' : 'A parent following an online lesson with their child' }}"
      loading="eager"
      fetchpriority="high"
      decoding="async"
    >
  </figure>
  <div class="dp-hero__shade" aria-hidden="true"></div>
  <div class="mc-container dp-shell dp-hero__inner">
    <div class="dp-hero__copy">
      <p class="mc-eyebrow">{{ __('landing.mc.hero.eyebrow') }}</p>
      <h1 class="dp-hero__title" id="dp-hero-title">{!! __('landing.mc.hero.title_html') !!}</h1>
      <p class="dp-hero__lead">{{ __('landing.mc.hero.lead') }}</p>
      <div class="dp-hero__search-wrap">
        <label class="dp-hero__search-label" for="dp-hero-search">{{ $isRtl ? 'ابحث عن احتياجك' : 'Search what you need' }}</label>
        <form class="mc-search dp-hero__search" action="{{ route('public.search') }}" method="get" role="search">
          <input
            id="dp-hero-search"
            type="search"
            name="q"
            placeholder="{{ $isRtl ? 'مادة، مرحلة، معلم، كورس…' : 'Subject, stage, teacher, course…' }}"
            aria-label="{{ $isRtl ? 'بحث' : 'Search' }}"
            autocomplete="off"
          >
          <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'بحث' : 'Search' }}</button>
        </form>
        <p class="dp-hero__path">
          <span class="dp-hero__path-label">{{ $isRtl ? 'المسار:' : 'Path:' }}</span>
          <a href="{{ route('public.request') }}" class="dp-hero__path-step">{{ $isRtl ? 'المنهج' : 'Curriculum' }}</a>
          <span class="dp-hero__path-sep" aria-hidden="true">←</span>
          <a href="{{ route('public.pricing') }}" class="dp-hero__path-step">{{ $isRtl ? 'المرحلة' : 'Stage' }}</a>
          <span class="dp-hero__path-sep" aria-hidden="true">←</span>
          <a href="{{ route('public.pricing') }}" class="dp-hero__path-step">{{ $isRtl ? 'المادة' : 'Subject' }}</a>
          <span class="dp-hero__path-sep" aria-hidden="true">←</span>
          <a href="{{ route('public.pricing') }}" class="dp-hero__path-step">{{ $isRtl ? 'عدد الحصص' : 'Lessons' }}</a>
        </p>
      </div>
      <div class="dp-hero__actions">
        <a href="{{ route('public.request') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.hero.cta_request') }}</a>
        <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $isRtl ? 'تصفح الباقات' : 'Browse packages' }}</a>
        @auth
          <a href="{{ route('dashboard') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('landing.nav.dashboard') }}</a>
        @else
          <a href="{{ route('register') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('landing.nav.register') }}</a>
        @endauth
      </div>
    </div>
  </div>
</section>
