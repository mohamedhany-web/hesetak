@php
    $appLocale = app()->getLocale();
    $appRtl = $appLocale === 'ar';
    $user = auth()->user();
    $firstName = explode(' ', trim((string) ($user?->name ?? '')))[0] ?? '';
    $avatarUrl = $user?->avatarDisplayUrl() ?? \App\Models\User::placeholderAvatarUrl();
    $brandLogoUrl = \App\Services\AdminPanelBranding::logoPublicUrl();
    $brandLogoFallback = \App\Services\AdminPanelBranding::inlineFallbackDataUri();

    $navItems = [
        ['route' => 'dashboard', 'match' => ['dashboard'], 'label' => __('student_timeline.nav_home'), 'icon' => 'home.svg'],
        ['route' => 'student.private-lectures.index', 'match' => ['student.private-lectures.*', 'student.one-to-one-sessions.*'], 'label' => __('student_timeline.nav_lessons'), 'icon' => 'lessons.svg', 'ui' => 'show_private_lessons'],
        ['route' => 'student.learn.index', 'match' => ['student.learn.*'], 'label' => __('student_timeline.nav_learn'), 'icon' => 'lessons.svg', 'ui' => 'show_private_lessons'],
        ['route' => 'student.service-entitlements.index', 'match' => ['student.service-entitlements.*'], 'label' => __('student_timeline.nav_progress'), 'icon' => 'credits.svg', 'ui' => 'show_entitlements'],
        ['route' => 'my-courses.index', 'match' => ['my-courses.*'], 'label' => __('student.my_courses'), 'fa' => 'fas fa-bookmark', 'ui' => 'show_courses'],
        ['route' => 'student.lectures.index', 'match' => ['student.lectures.*'], 'label' => __('student_timeline.nav_lectures'), 'fa' => 'fas fa-chalkboard', 'ui' => 'show_courses', 'course_tools' => true],
        ['route' => 'student.assignments.index', 'match' => ['student.assignments.*'], 'label' => __('student_timeline.nav_assignments'), 'fa' => 'fas fa-tasks', 'ui' => 'show_assignments', 'course_tools' => true],
        ['route' => 'student.exams.index', 'match' => ['student.exams.*'], 'label' => __('student_timeline.nav_exams'), 'fa' => 'fas fa-file-alt', 'ui' => 'show_exams', 'course_tools' => true],
        ['route' => 'calendar', 'match' => ['calendar', 'calendar.events'], 'label' => __('student_timeline.calendar'), 'fa' => 'fas fa-calendar-alt'],
        ['route' => 'orders.index', 'match' => ['orders.*'], 'label' => __('student_timeline.nav_orders'), 'fa' => 'fas fa-receipt', 'ui' => 'show_orders'],
        ['route' => 'student.wallet.index', 'match' => ['student.wallet.*'], 'label' => __('student_timeline.nav_wallet'), 'fa' => 'fas fa-wallet', 'ui' => 'show_wallet'],
        ['route' => 'student.invoices.index', 'match' => ['student.invoices.*'], 'label' => __('student_timeline.nav_invoices'), 'fa' => 'fas fa-file-invoice-dollar', 'ui' => 'show_invoices'],
        ['route' => 'student.certificates.index', 'match' => ['student.certificates.*'], 'label' => __('student_timeline.nav_certificates'), 'fa' => 'fas fa-certificate', 'ui' => 'show_certificates'],
        ['route' => 'referrals.index', 'match' => ['referrals.*'], 'label' => __('student_timeline.nav_referrals'), 'fa' => 'fas fa-user-friends', 'ui' => 'show_referrals'],
        ['route' => 'student.support.index', 'match' => ['student.support.*'], 'label' => __('student_timeline.nav_support'), 'fa' => 'fas fa-headset', 'ui' => 'show_support'],
        ['route' => 'notifications', 'match' => ['notifications*'], 'label' => __('student_timeline.nav_messages'), 'icon' => 'notifications.svg', 'ui' => 'show_notifications'],
        ['route' => 'settings', 'match' => ['settings'], 'label' => __('student_timeline.nav_settings'), 'icon' => 'settings.svg', 'ui' => 'show_settings'],
        ['route' => 'student.live-sessions.index', 'match' => ['student.live-sessions.*', 'student.live-recordings.*'], 'label' => __('student_timeline.nav_live_sessions'), 'fa' => 'fas fa-broadcast-tower', 'ui' => 'show_live_broadcast'],
        ['route' => 'student.library.files', 'match' => ['student.library.home', 'student.library.files', 'student.library.materials'], 'label' => __('student_timeline.lib_files_title'), 'fa' => 'fas fa-folder-open', 'ui' => 'show_libraries'],
        ['route' => 'student.library.curriculum', 'match' => ['student.library.curriculum', 'curriculum-library.*'], 'label' => __('student_timeline.nav_library_curriculum'), 'fa' => 'fas fa-sitemap', 'ui' => 'show_libraries'],
        ['route' => 'student.library.videos', 'match' => ['student.library.videos'], 'label' => __('student_timeline.nav_library_videos'), 'fa' => 'fas fa-film', 'ui' => 'show_libraries'],
        ['route' => 'student.private-messages.index', 'match' => ['student.private-messages.*'], 'label' => __('student_timeline.nav_feed'), 'icon' => 'community.svg', 'ui' => 'show_classes'],
    ];

    if ($user?->isAdmin() && Route::has('admin.dashboard')) {
        $navItems[] = [
            'route' => 'admin.dashboard',
            'match' => ['admin.*'],
            'label' => __('student.admin_panel'),
            'fa' => 'fas fa-shield-alt',
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $appLocale }}" dir="{{ $appRtl ? 'rtl' : 'ltr' }}" class="{{ $appRtl ? 'st-rtl' : 'st-ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', __('student_timeline.timeline'))</title>
    @include('partials.favicon-links')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ route('assets.student-timeline.css') }}?v=st-hesetak-7">
    <script>
        (function () {
            try {
                var mobile = window.matchMedia('(max-width: 768px)').matches;
                if (!mobile && localStorage.getItem('st-rail-open') === '1') {
                    document.documentElement.classList.add('st-rail-pref-open');
                }
            } catch (e) {}
        })();
    </script>
    @php
        $showContentProtection = ! empty(trim((string) ($__env->yieldContent('enable-content-protection') ?? '')));
    @endphp
    @if($showContentProtection)
        <script>
            window.Laravel = { user: { name: '{{ auth()->check() ? auth()->user()->name : "زائر" }}' } };
        </script>
        <script src="{{ route('assets.platform-protection.js') }}?v={{ is_file(public_path('js/platform-protection.js')) ? filemtime(public_path('js/platform-protection.js')) : time() }}"></script>
    @endif
    @stack('styles')
    <script>window.platformCurrencySymbol = @json(currency_symbol()); window.platformCurrency = @json(platform_currency());</script>
</head>
<body class="st-dash">

<script>
(function () {
  try {
    if (localStorage.getItem('st-rail-open') === '1') {
      window.__stRailPreferOpen = true;
    }
  } catch (e) {}
})();
</script>

<div class="st-shell{{ $appRtl ? ' is-rtl' : ' is-ltr' }}{{ View::hasSection('events') ? ' has-events' : ' is-wide' }}" id="stShell">
    <script>
    (function () {
        var shell = document.getElementById('stShell');
        if (!shell) return;
        try {
            if (window.matchMedia('(min-width: 769px)').matches && localStorage.getItem('st-rail-open') === '1') {
                shell.classList.add('is-rail-open');
            }
        } catch (e) {}
    })();
    </script>
    <button type="button" class="st-rail-backdrop" id="stRailBackdrop" aria-label="{{ __('student_timeline.close_sidebar') }}" tabindex="-1"></button>
    <aside class="st-rail" id="stRail" aria-label="{{ __('student_timeline.nav_home') }}">
        <div class="st-rail__head">
            <a href="{{ route('dashboard') }}" class="st-rail__brand" title="{{ config('app.name') }}">
                <span class="st-rail__mark">
                    <img src="{{ $brandLogoUrl }}" alt="{{ config('app.name') }}" class="st-rail__logo" width="40" height="40" loading="eager" decoding="async" onerror="this.onerror=null;this.src='{{ $brandLogoFallback }}';">
                </span>
                <span class="st-rail__brand-name">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="st-rail__toggle" id="stRailToggle" aria-expanded="false" aria-controls="stRail" title="{{ __('student_timeline.toggle_sidebar') }}">
                <i class="fas fa-angles-{{ $appRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                <span class="st-rail__toggle-text">{{ __('student_timeline.open_sidebar') }}</span>
            </button>
        </div>

        <a href="{{ route('profile') }}" class="st-rail__profile" title="{{ __('student_timeline.profile') }}">
            <img src="{{ $avatarUrl }}" alt="" class="st-rail__avatar" width="40" height="40">
            <div class="st-rail__who">
                <span class="st-rail__name">{{ $firstName }}</span>
                <span class="st-rail__role">{{ __('student_timeline.student_role') }}</span>
            </div>
        </a>

        <nav class="st-rail__nav">
            @foreach($navItems as $item)
                @php
                    if (! Route::has($item['route'])) {
                        continue;
                    }
                    if (! empty($item['ui']) && ! student_ui($item['ui'], true)) {
                        continue;
                    }
                    // الواجبات كانت مربوطة بالمكتبة؛ للكورسات المسجّلة يكفي تفعيل show_assignments
                    if (! empty($item['needs_libraries']) && ! student_ui('show_libraries', true) && empty($item['course_tools'])) {
                        continue;
                    }
                    $href = route($item['route']);
                    $active = request()->routeIs(...$item['match']);
                @endphp
                <a href="{{ $href }}" class="st-rail__link {{ $active ? 'is-active' : '' }}" title="{{ $item['label'] }}" aria-label="{{ $item['label'] }}">
                    <span class="st-rail__icon-box">
                        @if(! empty($item['icon']))
                            <img class="st-rail__icon" src="{{ asset('img/student-timeline/nav/'.$item['icon']) }}" alt="" width="24" height="24">
                        @elseif(! empty($item['fa']))
                            <i class="{{ $item['fa'] }}" aria-hidden="true" style="font-size:16px;opacity:.88"></i>
                        @endif
                    </span>
                    <span class="st-rail__label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="st-rail__foot" hidden aria-hidden="true"></div>
    </aside>

    <main class="st-main">
        @yield('content')
    </main>

    <aside class="st-events" aria-label="{{ __('student_timeline.events') }}">
        @yield('events')
    </aside>
</div>

<script>
(function () {
    var shell = document.getElementById('stShell');
    var rail = document.getElementById('stRail');
    var btn = document.getElementById('stRailToggle');
    var backdrop = document.getElementById('stRailBackdrop');
    if (!shell || !btn) return;

    var isRtl = document.documentElement.getAttribute('dir') === 'rtl';
    var openLabel = @json(__('student_timeline.open_sidebar'));
    var closeLabel = @json(__('student_timeline.close_sidebar'));
    var mq = window.matchMedia('(max-width: 768px)');

    function isMobile() {
        return mq.matches;
    }

    function isOpen() {
        return shell.classList.contains('is-rail-open');
    }

    function syncToggleIcon(open) {
        var mobileIcon = open ? 'fas fa-xmark' : 'fas fa-bars';
        var icon = btn.querySelector('i');
        var text = btn.querySelector('.st-rail__toggle-text');
        if (icon) {
            if (isMobile()) {
                icon.className = mobileIcon;
            } else {
                icon.className = open
                    ? ('fas fa-angles-' + (isRtl ? 'right' : 'left'))
                    : ('fas fa-angles-' + (isRtl ? 'left' : 'right'));
            }
        }
        if (text) text.textContent = open ? closeLabel : openLabel;
        var topMenu = document.getElementById('stTopMenu');
        if (topMenu) {
            var topIcon = topMenu.querySelector('i');
            if (topIcon && isMobile()) topIcon.className = mobileIcon;
            topMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    function setScrollLock(locked) {
        document.documentElement.classList.toggle('st-rail-lock', locked);
        document.body.classList.toggle('st-rail-lock', locked);
    }

    function setOpen(open, persist) {
        shell.classList.toggle('is-rail-open', open);
        document.documentElement.classList.toggle('st-rail-pref-open', open && !isMobile());
        setScrollLock(open && isMobile());
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (rail) rail.setAttribute('aria-hidden', isMobile() && !open ? 'true' : 'false');
        syncToggleIcon(open);

        if (persist !== false && !isMobile()) {
            try { localStorage.setItem('st-rail-open', open ? '1' : '0'); } catch (e) {}
        }
    }

    var preferOpen = false;
    try { preferOpen = localStorage.getItem('st-rail-open') === '1' || window.__stRailPreferOpen === true; } catch (e) {}
    setOpen(isMobile() ? false : (shell.classList.contains('is-rail-open') || preferOpen), false);

    btn.addEventListener('click', function () {
        setOpen(!isOpen());
    });

    var topMenu = document.getElementById('stTopMenu');
    if (topMenu) {
        topMenu.addEventListener('click', function () {
            setOpen(!isOpen());
        });
    }

    shell.addEventListener('click', function (e) {
        if (!isMobile() || !isOpen()) return;
        var link = e.target.closest('.st-rail a[href]');
        if (link) setOpen(false);
    });

    if (backdrop) {
        backdrop.addEventListener('click', function () { setOpen(false); });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            setOpen(false);
        }
    });

    function onViewportChange() {
        if (isMobile()) {
            setOpen(false, false);
        } else {
            var stored = false;
            try { stored = localStorage.getItem('st-rail-open') === '1'; } catch (e) {}
            setOpen(stored, false);
        }
        syncToggleIcon(isOpen());
    }

    if (typeof mq.addEventListener === 'function') mq.addEventListener('change', onViewportChange);
    else if (typeof mq.addListener === 'function') mq.addListener(onViewportChange);
})();
</script>
@stack('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
@include('partials.timezone-sync')
</body>
</html>
