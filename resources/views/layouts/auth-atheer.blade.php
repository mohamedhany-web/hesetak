@php
    $isRtl = app()->getLocale() === 'ar';
    $langSwitch = fn (string $lang) => request()->fullUrlWithQuery(array_merge(request()->query(), ['lang' => $lang]));
    $authBg = $authBackgroundUrl ?? \App\Providers\AppServiceProvider::authBackgroundUrl();
    $authBgFallback = asset(\App\Providers\AppServiceProvider::AUTH_BACKGROUND_PUBLIC_RELATIVE);
    $authBgCss = str_replace(["\\", "'", '"'], ['/', '%27', '%22'], $authBg);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') — {{ config('app.name') }}</title>
  <meta name="theme-color" content="#0B3D91">
  <meta name="robots" content="noindex, nofollow">
  @include('partials.favicon-links')
  <link rel="preload" as="image" href="{{ $authBg }}" fetchpriority="high">
  @include('partials.atheer-head')
  <script>
    (function () {
      var base = (typeof tailwind !== 'undefined' && tailwind.config) ? tailwind.config : {};
      var extend = (base.theme && base.theme.extend) ? base.theme.extend : {};
      var colors = Object.assign({}, extend.colors || {}, {
        accent: '#0B3D91',
        'accent-soft': '#E8EEF8',
        metal: '#F5B800',
        canvas: '#F4F7FC',
        'canvas-muted': '#E8EEF8',
      });
      tailwind.config = Object.assign({}, base, {
        theme: Object.assign({}, base.theme || {}, {
          extend: Object.assign({}, extend, { colors: colors }),
        }),
      });
    })();
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak]{display:none!important}
    .auth-brand-photo{
      background-image:url('{{ $authBgCss }}');
      background-size:cover;
      background-position:center;
    }
    /* حصتك brand tokens — auth shell only */
    body.auth-glottical{
      --atheer-accent:#0B3D91;
      --atheer-metal:#F5B800;
      --atheer-canvas:#F4F7FC;
      background:#F4F7FC;
      overflow-x:clip;
    }
    body.auth-glottical .bg-accent{background-color:#0B3D91!important}
    body.auth-glottical .bg-accent-soft{background-color:#E8EEF8!important}
    body.auth-glottical .text-accent{color:#0B3D91!important}
    body.auth-glottical .text-metal{color:#F5B800!important}
    body.auth-glottical .bg-metal\/15{background-color:rgba(245,184,0,.15)!important}
    body.auth-glottical .auth-brand-panel{
      background:
        radial-gradient(ellipse 80% 60% at 10% 0%, rgba(11,61,145,.58), transparent 55%),
        radial-gradient(ellipse 70% 50% at 100% 100%, rgba(245,184,0,.22), transparent 50%),
        #0b1220;
    }
    body.auth-glottical .auth-input:focus,
    body.auth-glottical .auth-phone:focus-within{
      border-color:#0B3D91;
      box-shadow:0 0 0 3px rgba(11,61,145,.16);
    }
    body.auth-glottical .auth-input[type="checkbox"],
    body.auth-glottical input[type="checkbox"].text-accent{
      accent-color:#0B3D91;
    }
    /* Responsive polish */
    body.auth-glottical .auth-shell{
      min-height:100dvh;
      display:block;
    }
    body.auth-glottical .auth-brand-panel{
      display:none;
    }
    body.auth-glottical .auth-form-col{
      min-height:100dvh;
      display:flex;
      flex-direction:column;
    }
    body.auth-glottical .auth-mobile-strip{
      display:block;
    }
    body.auth-glottical .auth-desktop-header{
      display:none;
    }
    body.auth-glottical .auth-form-main{
      padding-bottom:max(1.5rem, env(safe-area-inset-bottom));
      display:flex;
      flex:1 1 auto;
      flex-direction:column;
      justify-content:center;
    }
    body.auth-glottical .auth-mobile-footer{
      display:block;
    }
    @media (min-width:1024px){
      body.auth-glottical .auth-shell{
        display:grid;
        grid-template-columns:1fr 1fr;
      }
      body.auth-glottical .auth-brand-panel{
        display:flex;
        flex-direction:column;
        justify-content:space-between;
      }
      body.auth-glottical .auth-mobile-strip,
      body.auth-glottical .auth-mobile-footer{
        display:none!important;
      }
      body.auth-glottical .auth-desktop-header{
        display:flex;
        align-items:center;
        justify-content:space-between;
      }
    }
    /* منع القفزة عند التحميل — إلغاء أنيميشن الدخول */
    body.auth-glottical .fade-up,
    body.auth-glottical .fade-up-delay-1,
    body.auth-glottical .fade-up-delay-2,
    body.auth-glottical .fade-up-delay-3,
    body.auth-glottical .page-enter{
      animation:none!important;
      opacity:1!important;
      transform:none!important;
    }
    @media (max-width:1023px){
      body.auth-glottical .auth-mobile-strip{
        padding-top:max(0.75rem, env(safe-area-inset-top));
      }
      body.auth-glottical .auth-form-main{
        justify-content:flex-start;
        padding-top:1.25rem;
      }
      body.auth-glottical .auth-form-col{
        min-height:auto;
      }
    }
    @media (max-width:380px){
      body.auth-glottical .auth-form-card h1{
        font-size:1.5rem;
      }
    }
    @media (prefers-reduced-motion: reduce){
      body.auth-glottical *{
        animation:none!important;
        transition:none!important;
      }
    }
  </style>
  @stack('head')
</head>
<body class="auth-glottical font-sans antialiased text-ink" @yield('body_attrs')>
  <div class="auth-shell min-h-screen lg:grid lg:grid-cols-2">
    {{-- Brand / atmosphere panel --}}
    <aside class="auth-brand-panel relative hidden overflow-hidden text-white lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-14">
      <div class="auth-brand-photo pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>
      <img
        src="{{ $authBg }}"
        alt=""
        width="1200"
        height="1600"
        decoding="async"
        fetchpriority="high"
        class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-0"
        data-auth-bg
        onerror="this.onerror=null;this.src='{{ $authBgFallback }}';var p=this.previousElementSibling;if(p){p.style.backgroundImage='url(\'{{ $authBgFallback }}\')';}"
      >
      <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink via-ink/80 to-ink/45"></div>
      <div class="pointer-events-none absolute -{{ $isRtl ? 'left' : 'right' }}-24 top-1/3 size-72 rounded-full bg-accent/20 blur-3xl"></div>
      <div class="pointer-events-none absolute -{{ $isRtl ? 'right' : 'left' }}-16 bottom-0 size-64 rounded-full bg-metal/15 blur-3xl"></div>

      <div class="relative z-10 fade-up">
        @include('partials.auth-brand-link', ['size' => 'sm', 'variant' => 'dark', 'fallback' => 'accent', 'mb' => 'mb-0'])
      </div>

      <div class="relative z-10 max-w-md space-y-8 fade-up fade-up-delay-1">
        <div class="space-y-4">
          <p class="text-sm font-medium text-metal">@yield('brand_kicker', __('auth.visual_title'))</p>
          <h2 class="text-balance text-3xl font-semibold leading-tight xl:text-4xl">@yield('brand_title', __('auth.visual_desc'))</h2>
          <p class="text-sm leading-8 text-white/65">@yield('brand_lead')</p>
        </div>

        <ul class="space-y-4">
          @foreach ([
              ['title' => __('auth.effective_learning'), 'desc' => $isRtl ? 'كورسات وحصص مباشرة بمستوى احترافي' : 'Live courses and professional teaching'],
              ['title' => __('auth.collaboration'), 'desc' => $isRtl ? 'معلمون معتمدون ودعم مستمر' : 'Verified instructors and ongoing support'],
              ['title' => __('auth.continuous_growth'), 'desc' => $isRtl ? 'تتبّع تقدّمك وابدأ من حيث أنت' : 'Track progress and start where you are'],
          ] as $i => $point)
            <li class="flex gap-3 fade-up fade-up-delay-{{ min($i + 2, 3) }}">
              <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-metal ring-1 ring-white/10" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
              </span>
              <div>
                <p class="text-sm font-semibold text-white">{{ $point['title'] }}</p>
                <p class="mt-0.5 text-xs leading-6 text-white/55">{{ $point['desc'] }}</p>
              </div>
            </li>
          @endforeach
        </ul>
      </div>

      <p class="relative z-10 text-xs text-white/40 fade-up fade-up-delay-3">
        © {{ date('Y') }} {{ config('app.name') }}
      </p>
    </aside>

    {{-- Form column --}}
    <div class="auth-form-col relative flex min-h-screen flex-col">
      {{-- Mobile brand strip with photo --}}
      <div class="auth-mobile-strip relative overflow-hidden bg-ink text-white lg:hidden">
        <div class="auth-brand-photo absolute inset-0 opacity-35" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-ink/70 via-ink/85 to-ink"></div>
        <div class="pointer-events-none absolute -end-10 top-0 size-32 rounded-full bg-accent/25 blur-2xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -start-8 bottom-0 size-28 rounded-full bg-metal/20 blur-2xl" aria-hidden="true"></div>
        <div class="relative z-10 flex items-center justify-between gap-3 px-4 py-3.5 sm:px-5 sm:py-4">
          @include('partials.auth-brand-link', ['size' => 'sm', 'variant' => 'dark', 'fallback' => 'accent', 'mb' => 'mb-0'])
          <div class="flex shrink-0 items-center gap-0.5 rounded-xl border border-white/15 bg-white/5 p-1 text-xs font-semibold backdrop-blur-sm">
            <a href="{{ $langSwitch('ar') }}" class="min-h-8 rounded-lg px-2.5 py-1.5 transition {{ $isRtl ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white' }}" hreflang="ar">عربي</a>
            <a href="{{ $langSwitch('en') }}" class="min-h-8 rounded-lg px-2.5 py-1.5 transition {{ ! $isRtl ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white' }}" hreflang="en">EN</a>
          </div>
        </div>
      </div>

      <header class="auth-desktop-header hidden items-center justify-between gap-3 px-5 py-4 sm:px-8 lg:flex lg:px-10">
        <div class="ms-auto flex flex-wrap items-center justify-end gap-2">
          <div class="flex items-center gap-0.5 rounded-xl border border-line bg-surface p-1 text-xs font-semibold shadow-soft">
            <a href="{{ $langSwitch('ar') }}" class="rounded-lg px-3 py-1.5 transition {{ $isRtl ? 'bg-accent-soft text-accent' : 'text-muted hover:text-ink' }}" hreflang="ar">عربي</a>
            <a href="{{ $langSwitch('en') }}" class="rounded-lg px-3 py-1.5 transition {{ ! $isRtl ? 'bg-accent-soft text-accent' : 'text-muted hover:text-ink' }}" hreflang="en">EN</a>
          </div>
          <a href="{{ route('home') }}" class="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-3 text-xs font-medium text-muted shadow-soft transition hover:border-accent hover:text-accent">
            {{ __('auth.back_to_home') }}
          </a>
        </div>
      </header>

      <main class="auth-form-main page-enter flex flex-1 flex-col justify-center px-4 py-6 sm:px-8 sm:py-8 lg:px-12 lg:py-10 xl:px-16">
        <div class="auth-form-card mx-auto w-full @yield('form_max', 'max-w-[26rem]')">
          @yield('content')
        </div>
      </main>

      <footer class="auth-mobile-footer px-4 pb-5 text-center text-xs text-muted sm:px-8 lg:hidden" style="padding-bottom:max(1.25rem, env(safe-area-inset-bottom))">
        <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center font-medium text-accent transition hover:text-ink">{{ __('auth.back_to_home') }}</a>
      </footer>
    </div>
  </div>
  @stack('scripts')
  @include('partials.timezone-sync')
</body>
</html>
