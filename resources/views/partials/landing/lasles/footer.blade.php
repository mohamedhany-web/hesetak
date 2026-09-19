@php
    use App\Support\TadrisPublicNav;
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $brand = 'حصتك';
    $brandAr = 'حصتك';
    $img = $img ?? fn (string $file) => asset('img/lasles/'.$file);
    $items = TadrisPublicNav::primary();
@endphp
<footer class="lasles-footer">
  <div class="lasles-container lasles-footer__grid">
    <div class="lasles-footer__about">
      <a href="{{ route('home') }}" class="lasles-brand">
        <img src="{{ $img('logo-mark.svg') }}" width="35" height="35" alt="">
        <span><b>حصتك</b></span>
      </a>
      <p>
        @if($isRtl)
          حصتك منصة للتدريب والتطوير التعليمي — تمكين المعلمين ودعم المؤسسات التعليمية.
        @else
          حصتك is an educational training and development platform for teachers and institutions.
        @endif
      </p>
      <p class="lasles-copy">©{{ date('Y') }} {{ $isRtl ? $brandAr : $brand }}</p>
    </div>
    <div class="lasles-footer__cols">
      <div>
        <h4>{{ __('site.nav.teacher-development') }}</h4>
        <a href="{{ route('public.site.teacher-development') }}">{{ __('site.nav.teacher-development') }}</a>
        <a href="{{ route('public.site.teacher-paths') }}">{{ __('site.nav.teacher-paths') }}</a>
        <a href="{{ route('public.site.workshops') }}">{{ __('site.nav.workshops') }}</a>
        <a href="{{ route('public.site.resources') }}">{{ __('site.nav.resources') }}</a>
      </div>
      <div>
        <h4>{{ __('site.nav.institutional') }}</h4>
        <a href="{{ route('public.site.institutional') }}">{{ __('site.nav.institutional') }}</a>
        <a href="{{ route('public.site.consultations') }}">{{ __('site.nav.consultations') }}</a>
        <a href="{{ route('public.site.assessment') }}">{{ __('site.nav.assessment') }}</a>
        <a href="{{ route('public.site.certificates') }}">{{ __('site.nav.certificates') }}</a>
      </div>
      <div>
        <h4>{{ $isRtl ? 'المنصة' : 'Platform' }}</h4>
        <a href="{{ route('public.about') }}">{{ __('site.nav.about') }}</a>
        <a href="{{ route('public.contact') }}">{{ __('site.nav.contact') }}</a>
        <a href="{{ route('public.site.account') }}">{{ __('site.nav.account') }}</a>
        <a href="{{ route('login') }}">{{ $isRtl ? 'تسجيل الدخول' : 'Sign in' }}</a>
        <a href="{{ route('public.privacy') }}">{{ $isRtl ? 'الخصوصية' : 'Privacy' }}</a>
        <a href="{{ route('public.terms') }}">{{ $isRtl ? 'الشروط' : 'Terms' }}</a>
      </div>
    </div>
  </div>
</footer>
