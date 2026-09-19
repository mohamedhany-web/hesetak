@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $brand = 'حصتك';
    $brandAr = 'حصتك';
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $mcActive = 'home';
@endphp

@include('partials.landing.mycourses.nav')
<main>
  {{-- Homepage shop IA: Hero → Promo mosaic → Trending → Best selling → Testimonials --}}
  @include('partials.landing.mycourses.hero-shop')
  @include('partials.landing.mycourses.promo-mosaic')
  @include('partials.landing.mycourses.trending')
  @include('partials.landing.mycourses.best-selling')
  @include('partials.landing.mycourses.testimonials')
</main>
@include('partials.landing.mycourses.footer')
