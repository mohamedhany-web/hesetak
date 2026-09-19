@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $langSwitch = $langSwitch ?? fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $mcActive = $mcActive ?? ($laslesNavActive ?? '');
    $navInstructors = $mcActive === 'instructors' || request()->routeIs('public.instructors*');
    $navCurricula = $mcActive === 'curricula' || request()->routeIs('public.curricula*');
    $navCourses = $mcActive === 'courses' || request()->routeIs('public.courses*');
    $navPricing = $mcActive === 'pricing' || request()->routeIs('public.pricing');
    $navTeachers = $mcActive === 'for-teachers' || request()->routeIs('public.for-teachers');
@endphp
@include('partials.landing.mycourses.topbar')
<header class="mc-nav" id="mc-nav">
  <div class="mc-container mc-nav__inner">
    @include('partials.landing.mycourses.brand')

    <nav class="mc-nav__links" aria-label="{{ $isRtl ? 'القائمة' : 'Main' }}">
      <a href="{{ route('public.instructors.index') }}" class="{{ $navInstructors ? 'is-active' : '' }}">{{ __('landing.mc.nav.instructors') }}</a>
      <a href="{{ route('public.curricula') }}" class="{{ $navCurricula ? 'is-active' : '' }}">{{ __('landing.mc.nav.curricula') }}</a>
      <a href="{{ route('public.courses') }}" class="{{ $navCourses ? 'is-active' : '' }}">{{ __('landing.mc.nav.courses') }}</a>
      <a href="{{ route('public.pricing') }}" class="{{ $navPricing ? 'is-active' : '' }}">{{ __('landing.mc.nav.pricing') }}</a>
      <a href="{{ route('public.for-teachers') }}" class="{{ $navTeachers ? 'is-active' : '' }}">{{ __('landing.mc.nav.for_teachers') }}</a>
    </nav>

    <div class="mc-nav__actions">
      @auth
        @include('partials.landing.mycourses.user-menu')
      @else
        <a href="{{ route('login') }}" class="mc-btn mc-btn--md mc-btn--ghost mc-nav__cta-desktop">{{ __('landing.nav.login') }}</a>
        <a href="{{ route('register') }}" class="mc-btn mc-btn--md mc-btn--primary mc-nav__cta-desktop">{{ __('landing.nav.register') }}</a>
      @endauth
      <div class="mc-lang" role="group" aria-label="{{ $isRtl ? 'اللغة' : 'Language' }}">
        <a href="{{ $langSwitch('ar') }}" class="{{ $isRtl ? 'is-on' : '' }}" hreflang="ar">عربي</a>
        <a href="{{ $langSwitch('en') }}" class="{{ ! $isRtl ? 'is-on' : '' }}" hreflang="en">EN</a>
      </div>
      <button type="button" class="mc-nav__toggle" id="mc-nav-toggle" aria-expanded="false" aria-controls="mc-nav-drawer" aria-label="{{ __('landing.nav.mobile_menu') }}">
        <span class="mc-nav__burger" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
    </div>
  </div>
</header>

<div class="mc-drawer" id="mc-nav-drawer" hidden aria-hidden="true">
  <button type="button" class="mc-drawer__backdrop" data-mc-drawer-close tabindex="-1" aria-label="{{ $isRtl ? 'إغلاق القائمة' : 'Close menu' }}"></button>
  <aside class="mc-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="mc-drawer-title">
    <div class="mc-drawer__head">
      @include('partials.landing.mycourses.brand', ['brandId' => 'mc-drawer-title'])
      <button type="button" class="mc-drawer__close" data-mc-drawer-close aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}">
        <span aria-hidden="true">×</span>
      </button>
    </div>

    <nav class="mc-drawer__nav" aria-label="{{ $isRtl ? 'قائمة الموبايل' : 'Mobile menu' }}">
      <a href="{{ route('public.instructors.index') }}" class="{{ $navInstructors ? 'is-active' : '' }}">{{ __('landing.mc.nav.instructors') }}</a>
      <a href="{{ route('public.curricula') }}" class="{{ $navCurricula ? 'is-active' : '' }}">{{ __('landing.mc.nav.curricula') }}</a>
      <a href="{{ route('public.courses') }}" class="{{ $navCourses ? 'is-active' : '' }}">{{ __('landing.mc.nav.courses') }}</a>
      <a href="{{ route('public.pricing') }}" class="{{ $navPricing ? 'is-active' : '' }}">{{ __('landing.mc.nav.pricing') }}</a>
      <a href="{{ route('public.for-teachers') }}" class="{{ $navTeachers ? 'is-active' : '' }}">{{ __('landing.mc.nav.for_teachers') }}</a>
      <a href="{{ route('public.for-students') }}">{{ __('landing.mc.nav.for_students') }}</a>
      <a href="{{ route('public.how') }}">{{ __('landing.mc.nav.how') }}</a>
      <a href="{{ route('public.faq') }}">{{ __('landing.mc.nav.faq') }}</a>
      <a href="{{ route('public.contact') }}">{{ __('landing.mc.nav.contact') }}</a>
    </nav>

    <div class="mc-drawer__foot">
      <div class="mc-drawer__actions">
        @auth
          @php
            $drawerUser = auth()->user();
            $drawerAvatar = $drawerUser?->profile_image_url;
            $drawerName = trim((string) ($drawerUser?->name ?? ''));
            $drawerInitials = collect(preg_split('/\s+/u', $drawerName) ?: [])
                ->filter()
                ->take(2)
                ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('') ?: 'ح';
          @endphp
          <div class="mc-drawer__user">
            <span class="mc-user-menu__avatar mc-user-menu__avatar--lg" aria-hidden="true">
              @if($drawerAvatar)
                <img src="{{ $drawerAvatar }}" alt="" width="48" height="48" decoding="async">
              @else
                <span class="mc-user-menu__initials">{{ $drawerInitials }}</span>
              @endif
            </span>
            <div>
              <p class="mc-drawer__user-name">{{ $drawerName !== '' ? $drawerName : __('landing.nav.brand') }}</p>
              @if(!empty($drawerUser->email))
                <p class="mc-drawer__user-email" dir="ltr">{{ $drawerUser->email }}</p>
              @endif
            </div>
          </div>
          <a href="{{ route('dashboard') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ __('landing.nav.dashboard') }}</a>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="mc-btn mc-btn--md mc-btn--soft" style="width:100%;justify-content:center">{{ __('landing.nav.logout') }}</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ __('landing.nav.login') }}</a>
          <a href="{{ route('register') }}" class="mc-btn mc-btn--md mc-btn--secondary">{{ __('landing.nav.register') }}</a>
        @endauth
      </div>
    </div>
  </aside>
</div>
