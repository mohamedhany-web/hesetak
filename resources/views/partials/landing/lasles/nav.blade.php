@php
    use App\Support\TadrisPublicNav;
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $img = $img ?? fn (string $file) => asset('img/lasles/'.$file);
    $langSwitch = $langSwitch ?? fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $navItems = TadrisPublicNav::primary();
    $short = fn (string $key, ?string $fallback = null) =>
        \Illuminate\Support\Facades\Lang::has('site.nav_short.'.$key)
            ? __('site.nav_short.'.$key)
            : ($fallback ?? __('site.nav.'.$key));

    // Essential desktop hubs only (one-word labels)
    $priority = ['home', 'about', 'teacher-development', 'institutional', 'contact'];
    $desktopItems = collect($navItems)->filter(fn ($i) => in_array($i['key'], $priority, true))->values();
@endphp
<header class="lasles-nav" id="lasles-nav">
  <div class="lasles-container lasles-nav__inner">
    <a href="{{ route('home') }}" class="lasles-brand">
      <img src="{{ $img('logo-mark.svg') }}" width="35" height="35" alt="">
      <span><b>حصتك</b></span>
    </a>

    <nav class="lasles-nav__links" aria-label="{{ $isRtl ? 'القائمة' : 'Main' }}">
      @foreach($desktopItems as $item)
        @if(!empty($item['children']))
          <div class="lasles-nav__dropdown {{ TadrisPublicNav::isActive($item['key']) ? 'is-active' : '' }}">
            <a href="{{ route($item['route']) }}" class="lasles-nav__parent {{ TadrisPublicNav::isActive($item['key']) ? 'is-active' : '' }}">
              {{ $short($item['key'], $item['label']) }}
              <span class="lasles-nav__caret" aria-hidden="true">▾</span>
            </a>
            <div class="lasles-nav__menu" role="menu">
              <a href="{{ route($item['route']) }}" role="menuitem">{{ $short('overview') }}</a>
              @foreach($item['children'] as $child)
                <a href="{{ route($child['route']) }}" role="menuitem">{{ $child['label'] }}</a>
              @endforeach
              @if($item['key'] === 'teacher-development')
                <a href="{{ route('public.site.assessment') }}" role="menuitem">{{ __('site.nav.assessment') }}</a>
                <a href="{{ route('public.site.consultations') }}" role="menuitem">{{ __('site.nav.consultations') }}</a>
                <a href="{{ route('public.site.workshops') }}" role="menuitem">{{ __('site.nav.workshops') }}</a>
                <a href="{{ route('public.site.resources') }}" role="menuitem">{{ __('site.nav.resources') }}</a>
              @endif
              @if($item['key'] === 'institutional')
                <a href="{{ route('public.site.consultations') }}" role="menuitem">{{ __('site.nav.consultations') }}</a>
                <a href="{{ route('public.site.certificates') }}" role="menuitem">{{ __('site.nav.certificates') }}</a>
              @endif
            </div>
          </div>
        @else
          <a href="{{ $item['key'] === 'home' ? route('home') : route($item['route']) }}"
             class="{{ TadrisPublicNav::isActive($item['key']) || ($item['key']==='home' && request()->routeIs('home')) ? 'is-active' : '' }}">
            {{ $short($item['key'], $item['label']) }}
          </a>
        @endif
      @endforeach
    </nav>

    <div class="lasles-nav__actions">
      <a href="{{ $langSwitch($isRtl ? 'en' : 'ar') }}" class="lasles-lang">{{ $isRtl ? 'EN' : 'عربي' }}</a>
      @auth
        <a href="{{ url('/dashboard') }}" class="lasles-nav__signin">{{ $short('account') }}</a>
      @else
        <a href="{{ route('login') }}" class="lasles-nav__signin">{{ $isRtl ? 'دخول' : 'Sign In' }}</a>
        <a href="{{ route('register') }}" class="lasles-btn-outline">{{ $isRtl ? 'حساب' : 'Sign Up' }}</a>
      @endauth
      <button type="button" class="lasles-burger" id="lasles-burger" aria-expanded="false" aria-controls="lasles-mobile" aria-label="{{ $isRtl ? 'القائمة' : 'Menu' }}">
        <span aria-hidden="true">☰</span>
      </button>
    </div>
  </div>

  <div class="lasles-container lasles-mobile" id="lasles-mobile" hidden>
    @foreach($desktopItems as $item)
      <a href="{{ $item['key'] === 'home' ? route('home') : route($item['route']) }}">{{ $short($item['key'], $item['label']) }}</a>
      @foreach($item['children'] ?? [] as $child)
        <a class="lasles-mobile__child" href="{{ route($child['route']) }}">{{ $child['label'] }}</a>
      @endforeach
    @endforeach
    <a href="{{ route('public.site.assessment') }}">{{ $short('assessment') }}</a>
    <a href="{{ route('public.site.consultations') }}">{{ $short('consultations') }}</a>
    <a href="{{ route('public.site.resources') }}">{{ $short('resources') }}</a>
    <a href="{{ route('public.site.account') }}">{{ $short('account') }}</a>
    @guest
      <a href="{{ route('login') }}">{{ $isRtl ? 'دخول' : 'Sign In' }}</a>
      <a href="{{ route('register') }}">{{ $isRtl ? 'حساب' : 'Sign Up' }}</a>
    @endguest
  </div>
</header>
