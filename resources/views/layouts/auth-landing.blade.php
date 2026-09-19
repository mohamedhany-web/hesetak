@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $brand = __('landing.nav.brand');
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ request()->boolean('figma') ? 'ltr' : ($isRtl ? 'rtl' : 'ltr') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') — {{ $brand }}</title>
  <meta name="robots" content="noindex, nofollow">
  <meta name="theme-color" content="#1E4E8C">
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  @stack('head')
  @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--auth" @yield('body_attrs')>
  <div class="mc-auth-shell">
    <header class="mc-auth-rail">
      <div class="mc-auth-rail__inner">
        @include('partials.landing.mycourses.brand')
        <div class="mc-auth-rail__meta">
          <div class="mc-lang" role="group" aria-label="{{ $isRtl ? 'اللغة' : 'Language' }}">
            <a href="{{ $langSwitch('ar') }}" class="{{ $isRtl ? 'is-on' : '' }}" hreflang="ar">عربي</a>
            <a href="{{ $langSwitch('en') }}" class="{{ ! $isRtl ? 'is-on' : '' }}" hreflang="en">EN</a>
          </div>
          <a href="{{ route('home') }}" class="mc-auth-rail__home">{{ __('auth.back_to_home') }}</a>
        </div>
      </div>
    </header>

    <main class="mc-auth-stage">
      <div class="mc-auth-stage__mark" aria-hidden="true">
        <img src="{{ public_img_url('brand/hesetak-mark.png') }}" alt="" width="280" height="280" decoding="async" onerror="this.onerror=null;this.src={{ \Illuminate\Support\Js::from(\App\Services\AdminPanelBranding::inlineFallbackDataUri()) }};">
      </div>
      <div class="mc-auth-sheet @yield('main_class')">
        @yield('content')
      </div>
      @hasSection('after_card')
        <div class="mc-auth-sheet-after">
          @yield('after_card')
        </div>
      @endif
      @hasSection('nav_action')
        <p class="mc-auth-stage__alt">@yield('nav_action')</p>
      @endif
    </main>
  </div>
  @stack('scripts')
</body>
</html>
