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
      <form class="mc-search dp-hero__search" action="{{ route('public.search') }}" method="get" role="search" style="margin:1rem 0 1.25rem;max-width:32rem">
        <label for="dp-hero-search" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0">{{ $isRtl ? 'ابحث عن احتياجك' : 'Search what you need' }}</label>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <input id="dp-hero-search" type="search" name="q" placeholder="{{ $isRtl ? 'ابحث: مادة، مرحلة، معلم، كورس…' : 'Search: subject, stage, teacher, course…' }}" aria-label="{{ $isRtl ? 'بحث' : 'Search' }}" style="flex:1;min-width:12rem;border-radius:999px;border:0;padding:.85rem 1.1rem;font:inherit">
          <button type="submit" class="mc-btn mc-btn--md mc-btn--secondary">{{ $isRtl ? 'بحث' : 'Search' }}</button>
        </div>
      </form>
      <p class="dp-hero__path" style="margin:0 0 1rem;opacity:.92;font-size:.95rem">
        {{ $isRtl ? 'المسار الواضح: اختر المنهج ← المرحلة ← المادة ← عدد الحصص' : 'Clear path: curriculum → stage → subject → lesson pack' }}
      </p>
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
