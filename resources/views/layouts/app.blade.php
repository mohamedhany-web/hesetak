@php
    $appLocale = app()->getLocale();
    $appRtl = $appLocale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $appLocale }}" dir="{{ $appRtl ? 'rtl' : 'ltr' }}" class="light {{ $appRtl ? 'st-rtl' : 'st-ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} - @yield('title', __('auth.dashboard'))</title>

    @include('partials.favicon-links')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        navy: { 50:'#f0f4ff',100:'#dbe4ff',200:'#bac8ff',300:'#91a7ff',400:'#748ffc',500:'#5c7cfa',600:'#4c6ef5',700:'#4263eb',800:'#3b5bdb',900:'#364fc7',950:'#0c1222' },
                        brand: { 50:'#FFF8E6',100:'#FFE9A8',200:'#FFD866',300:'#F5B800',400:'#E5AB00',500:'#F5B800',600:'#C99400',700:'#0B3D91',800:'#072A66',900:'#051E4A' },
                        mx: { navy:'#0B3D91', indigo:'#072A66', orange:'#F5B800', rose:'#EEF3FB', gold:'#F5B800', soft:'#F4F7FC' },
                        acad: { blue:'#0B3D91', dark:'#072A66', gold:'#F5B800', canvas:'#F4F7FC', ink:'#0B1220' },
                        surface: { 50:'#fafbfc', 100:'#f4f5f7', 200:'#e8eaed', 300:'#dadce0' }
                    }
                }
            }
        };
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @php
        $useInstructorPanel = auth()->check() && (auth()->user()->isInstructor() || auth()->user()->isTeacher());
    @endphp
    @if($useInstructorPanel)
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ route('assets.student-timeline.css') }}?v=st-hesetak-inst-3">
        @php
            $idCssFile = public_path('css/instructor-dashboard.css');
            $idCssVer = is_file($idCssFile) ? (string) filemtime($idCssFile) : (string) time();
            $suCssFile = public_path('css/instructor-panel.css');
            $suCssVer = is_file($suCssFile) ? (string) filemtime($suCssFile) : (string) time();
        @endphp
        {{-- عبر Laravel routes — على Hostinger /css/* الثابت غالباً 404 --}}
        <link rel="stylesheet" href="{{ route('assets.instructor-panel.css.public') }}?v={{ $suCssVer }}">
        <link rel="stylesheet" href="{{ route('assets.instructor-dashboard.css') }}?v={{ $idCssVer }}">
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
        <style id="instructor-st-compat">
            /* Match student-timeline font stack (beats app-wide Cairo/Tajawal *) */
            body.st-inst-body,
            body.st-inst-body .st-shell,
            body.st-inst-body .st-main,
            body.st-inst-body .id-dash,
            body.st-inst-body .id-page {
                font-family: 'IBM Plex Sans Arabic', 'Cairo', 'Poppins', system-ui, sans-serif !important;
            }
            body.st-inst-body .st-shell *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            body.st-inst-body .st-main *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            body.st-inst-body .id-dash *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            body.st-inst-body .id-page *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon) {
                font-family: 'IBM Plex Sans Arabic', 'Cairo', 'Poppins', system-ui, sans-serif !important;
            }
            html:not([lang="ar"]) body.st-inst-body,
            html:not([lang="ar"]) body.st-inst-body .st-shell *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            html:not([lang="ar"]) body.st-inst-body .st-main *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            html:not([lang="ar"]) body.st-inst-body .id-dash *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon),
            html:not([lang="ar"]) body.st-inst-body .id-page *:not(i):not(.fa):not(.fas):not(.far):not(.fab):not(.fal):not(.fa-solid):not(.fa-regular):not(.fa-brands):not(.fc-icon) {
                font-family: 'Poppins', 'Cairo', system-ui, sans-serif !important;
            }
            /* Restore Font Awesome (broken by font-family !important above) */
            body.st-inst-body i.fa,
            body.st-inst-body i.fas,
            body.st-inst-body i.far,
            body.st-inst-body i.fal,
            body.st-inst-body i.fa-solid,
            body.st-inst-body i.fa-regular,
            body.st-inst-body .fa,
            body.st-inst-body .fas,
            body.st-inst-body .far,
            body.st-inst-body .fal,
            body.st-inst-body .fa-solid,
            body.st-inst-body .fa-regular {
                font-family: "Font Awesome 6 Free" !important;
                font-style: normal;
                font-variant: normal;
                text-rendering: auto;
                -webkit-font-smoothing: antialiased;
                display: inline-block;
                line-height: 1;
            }
            body.st-inst-body i.fas,
            body.st-inst-body i.fa-solid,
            body.st-inst-body .fas,
            body.st-inst-body .fa-solid {
                font-weight: 900 !important;
            }
            body.st-inst-body i.far,
            body.st-inst-body i.fa-regular,
            body.st-inst-body .far,
            body.st-inst-body .fa-regular {
                font-weight: 400 !important;
            }
            body.st-inst-body i.fab,
            body.st-inst-body i.fa-brands,
            body.st-inst-body .fab,
            body.st-inst-body .fa-brands {
                font-family: "Font Awesome 6 Brands" !important;
                font-weight: 400 !important;
            }
            /* FullCalendar nav icons — unicode (more reliable than fcicons under font override) */
            body.st-inst-body .fc-icon,
            body.st-inst-body .fc-icon:before {
                font-family: 'IBM Plex Sans Arabic', 'Cairo', system-ui, sans-serif !important;
                font-style: normal !important;
                font-weight: 900 !important;
                speak: none;
                display: inline-block;
                line-height: 1;
            }
            body.st-inst-body .fc-icon-chevron-left:before { content: "‹" !important; font-size: 1.35em; }
            body.st-inst-body .fc-icon-chevron-right:before { content: "›" !important; font-size: 1.35em; }
            body.st-inst-body .fc-icon-chevrons-left:before { content: "«" !important; }
            body.st-inst-body .fc-icon-chevrons-right:before { content: "»" !important; }
            /* Sidebar icons: readable on navy rail */
            body.st-inst-body .st-rail__icon-box i {
                font-size: 16px !important;
                line-height: 1 !important;
                width: 1em;
                text-align: center;
                color: #fff !important;
                opacity: .9;
            }
            body.st-inst-body .st-rail__link.is-active .st-rail__icon-box i,
            body.st-inst-body .st-rail__link:hover .st-rail__icon-box i {
                opacity: 1;
                color: #C9952A !important;
            }
            /* Calendar page FA icons inside panels */
            body.st-inst-body .id-page .id-kpi__icon i,
            body.st-inst-body .id-page .id-list__ico i,
            body.st-inst-body .id-page .id-empty__mark i,
            body.st-inst-body .id-page .id-hero__meta i,
            body.st-inst-body .id-page .id-act__chev {
                font-family: "Font Awesome 6 Free" !important;
                font-weight: 900 !important;
            }
            body.st-inst-body { background: var(--st-canvas, #F4F7FC); margin: 0; min-height: 100dvh; }
            body.st-inst-body .st-main > .bg-white,
            body.st-inst-body .st-main > .rounded-2xl,
            body.st-inst-body .st-main > .rounded-xl { margin-bottom: 1rem; }
            body.st-inst-body .st-main .su-page-title,
            body.st-inst-body .st-main .ip-page-title { display: none; }
            body.st-inst-body .st-main .bg-white {
                border: 1px solid #E6EEF8;
                box-shadow: 0 4px 14px rgba(22, 58, 104, .04);
            }
            /* su-* pages inside st-shell: use full main width + dashboard canvas tokens */
            body.st-inst-body .st-main > .su-page {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }
            body.st-inst-body .su-page-head__title {
                font-size: clamp(1.15rem, 2vw, 1.45rem);
                font-weight: 900;
                color: #152A4A;
            }
            body.st-inst-body .su-kpi {
                background: #fff;
                border: 1px solid #E6EEF8;
                box-shadow: 0 4px 14px rgba(22, 58, 104, .04);
            }
            body.st-inst-body .su-kpi--1,
            body.st-inst-body .su-kpi--2,
            body.st-inst-body .su-kpi--3,
            body.st-inst-body .su-kpi--4 {
                background: #fff;
            }
            body.st-inst-body .su-card {
                background: #fff;
                border: 1px solid #E6EEF8;
                box-shadow: 0 4px 14px rgba(22, 58, 104, .04);
                border-radius: 18px;
            }
        </style>
    @endif

    @php
        $showContentProtection = !empty(trim((string) ($__env->yieldContent('enable-content-protection') ?? '')));
    @endphp
    @if($showContentProtection)
    <script>
        window.Laravel = { user: { name: '{{ auth()->check() ? auth()->user()->name : "زائر" }}' } };
    </script>
        <script src="{{ route('assets.platform-protection.js') }}?v={{ is_file(public_path('js/platform-protection.js')) ? filemtime(public_path('js/platform-protection.js')) : time() }}"></script>
    @endif

    {{-- نفس منطق الإدارة: الافتراضي فاتح؛ الوضع الداكن فقط عند theme=dark في localStorage --}}
    <script>
        (function() {
            var s = localStorage.getItem('theme');
            if (s === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        })();
    </script>

    <style>
        * { font-family: 'Cairo', 'IBM Plex Sans Arabic', 'Tajawal', system-ui, -apple-system, sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Cairo', 'Tajawal', 'IBM Plex Sans Arabic', sans-serif; }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        html.light { color-scheme: light; }
        html.dark { color-scheme: dark; }
        body { background: #F4F7FC; overflow-x: hidden; }
        .dark body { background: #0B1220; }

        /* ── Sidebar ── */
        .app-sidebar {
            width: 272px;
            background: #fff;
            border-left: 1px solid #E8EEF8;
        }
        .dark .app-sidebar {
            background: #111827;
            border-left-color: #1f2937;
        }
        @media (max-width: 1023px) {
            .app-sidebar { width: 288px; }
        }

        .app-sidebar::-webkit-scrollbar,
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .app-sidebar::-webkit-scrollbar-thumb,
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .dark .app-sidebar::-webkit-scrollbar-thumb,
        .dark .sidebar-scroll::-webkit-scrollbar-thumb { background: #374151; }

        /* Sidebar nav items */
        .s-nav {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 10px;
            font-size: 13px; font-weight: 500;
            color: #4b5563; transition: all .15s;
            border: 1px solid transparent;
        }
        .s-nav:hover { background: #F4F7FC; color: #0B1220; }
        .s-nav.active { background: #EEF3FB; color: #072A66; border-color: #D6E2F5; }
        .dark .s-nav { color: #9ca3af; }
        .dark .s-nav:hover { background: #1f2937; color: #e5e7eb; }
        .dark .s-nav.active { background: #172554; color: #93c5fd; border-color: #1e3a5f; }

        .s-nav .s-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0; transition: all .15s;
        }

        /* ── Header ── */
        .app-header {
            height: 60px; background: rgba(255,255,255,0.92);
            border-bottom: 1px solid #E8EEF8;
            backdrop-filter: blur(12px);
        }
        .dark .app-header {
            background: rgba(17,24,39,0.92); border-bottom-color: #1f2937;
        }

        /* Header buttons */
        .h-btn {
            width: 38px; height: 38px; border-radius: 11px;
            display: inline-flex; align-items: center; justify-content: center;
            color: #5B6577; border: 1px solid #E8EEF8;
            transition: all .15s; background: #fff;
        }
        .h-btn:hover { background: #F4F7FC; color: #0B3D91; border-color: #D6E2F5; }
        .dark .h-btn { color: #9ca3af; border-color: #374151; background: transparent; }
        .dark .h-btn:hover { background: #1f2937; color: #e5e7eb; border-color: #4b5563; }

        /* Search input */
        .search-box {
            background: #F4F7FC; border: 1px solid #E8EEF8;
            border-radius: 12px; padding: 8px 14px;
            transition: all .2s;
        }
        .search-box:focus-within { background: #fff; border-color: #0B3D91; box-shadow: 0 0 0 3px rgba(11,61,145,.12); }
        .dark .search-box { background: #1f2937; border-color: #374151; }
        .dark .search-box:focus-within { background: #111827; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .dark .search-box input { color: #e5e7eb; }

        /* Dropdown */
        .dd-menu {
            background: #fff; border: 1px solid #E8EEF8;
            border-radius: 14px; box-shadow: 0 16px 40px rgba(11,61,145,.12);
            overflow: hidden;
        }
        .dark .dd-menu { background: #1f2937; border-color: #374151; box-shadow: 0 10px 40px rgba(0,0,0,.3); }
        .dd-item { display: flex; align-items: center; transition: background .1s; }
        .dd-item:hover { background: #F4F7FC; }
        .dark .dd-item:hover { background: #374151; }

        /* User avatar */
        .u-avatar {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(145deg, #0B3D91, #072A66);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 13px;
            box-shadow: 0 0 0 2px #F5B80033;
        }
        .u-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }

        /* Logo fix */
        .logo-section img,
        .app-sidebar img[alt*="Logo"] {
            transform: none !important; rotate: 0deg !important;
            object-fit: contain !important; object-position: center !important;
        }

        /* Notification badge */
        .n-badge {
            position: absolute; top: -3px; right: -3px;
            min-width: 16px; height: 16px; padding: 0 4px;
            background: #F5B800; color: #072A66; border-radius: 99px;
            font-size: 9px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #fff;
        }
        .dark .n-badge { border-color: #111827; color: #0B1220; }

        /* Stat mini cards (student sidebar) */
        .stat-mini { border-radius: 8px; padding: 8px 10px; }

        /* Student sidebar nav-item compat */
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            color: #4b5563; transition: all .15s;
        }
        .nav-item:hover { background: #F4F7FC; color: #0B1220; }
        .nav-item.active { background: #EEF3FB; color: #0B3D91; }
        .dark .nav-item { color: #9ca3af; }
        .dark .nav-item:hover { background: #1f2937; color: #e5e7eb; }
        .dark .nav-item.active { background: #172554; color: #60a5fa; }
        .nav-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }

        /* Student sidebar bottom card */
        .user-card-bottom { border-top: 1px solid #E8EEF8; }
        .dark .user-card-bottom { border-top-color: #1f2937; }
        .logo-area { border-bottom: 1px solid #E8EEF8; }
        .dark .logo-area { border-bottom-color: #1f2937; }

        /* ── Student / instructor sidebar (حصتك) ── */
        .ins-sidebar-brand {
            background: linear-gradient(160deg, #1E4E8C 0%, #152A4A 100%);
            border-bottom: 0;
            position: relative;
            color: #fff;
        }
        .dark .ins-sidebar-brand {
            background: linear-gradient(160deg, #1E4E8C 0%, #0F1C33 100%);
            border-bottom: 0;
        }
        .ins-stat-card {
            border-radius: 14px; padding: 12px 14px;
            transition: transform .2s, box-shadow .2s, border-color .2s;
            background: #fff;
            border: 1px solid #E8EEF8;
        }
        .ins-stat-card:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -12px rgba(30,78,140,.18); border-color: #C5D4EF !important; }
        .dark .ins-stat-card { background: #1f2937; border-color: #374151; }
        .dark .ins-stat-card:hover { box-shadow: 0 8px 20px -8px rgba(0,0,0,.35); border-color: #475569 !important; }
        .ins-nav-group {
            font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;
            color: #8A94A6; padding: 16px 16px 6px;
            display: flex; align-items: center;
        }
        .ins-nav-group > span { display: inline-flex; align-items: center; gap: 5px; }
        .dark .ins-nav-group { color: #64748b; }
        .ins-nav {
            display: flex; align-items: center; gap: 11px;
            padding: 9px 12px; border-radius: 12px; margin: 2px 10px;
            font-size: 13px; font-weight: 600; color: #3D4656;
            transition: all .18s ease;
            border: 1px solid transparent;
            position: relative;
            text-decoration: none !important;
        }
        .ins-nav::before {
            content: ''; position: absolute; right: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 0; border-radius: 3px 0 0 3px;
            background: #C9952A;
            transition: height .2s ease;
        }
        .ins-nav:hover { background: #F4F7FC; color: #0B1220; }
        .ins-nav.active { background: #EEF3FB; color: #152A4A; border-color: #D6E2F5; font-weight: 700; }
        .ins-nav.active::before { height: 22px; }
        .dark .ins-nav { color: #9ca3af; }
        .dark .ins-nav:hover { background: #1f2937; color: #f1f5f9; }
        .dark .ins-nav.active { background: #132445; color: #bfdbfe; border-color: #1e3a5f; font-weight: 700; }
        .dark .ins-nav.active::before { background: #C9952A; }
        .ins-nav .ins-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
            background: #EEF3FB; color: #1E4E8C;
            transition: transform .2s, box-shadow .2s, background .2s;
        }
        .ins-nav:hover .ins-icon { transform: scale(1.04); }
        .ins-nav.active .ins-icon { background: #1E4E8C; color: #fff; box-shadow: 0 4px 12px -4px rgba(30,78,140,.45); }
        .ins-nav-badge {
            min-width: 20px; height: 20px; padding: 0 6px;
            border-radius: 10px; font-size: 11px; font-weight: 800;
            display: inline-flex; align-items: center; justify-content: center;
            background: #FFF3CC; color: #8A6A00;
        }
        .ins-user-card {
            background: #F4F7FC;
            border: 1px solid #E8EEF8; border-radius: 14px;
            padding: 12px 14px; transition: all .2s;
        }
        .ins-user-card:hover { border-color: #C5D4EF; box-shadow: 0 4px 12px -4px rgba(30,78,140,.1); }
        .dark .ins-user-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-color: #334155; }
        .dark .ins-user-card:hover { border-color: #475569; box-shadow: 0 4px 12px -4px rgba(0,0,0,.25); }

        .app-quick-link {
            display: none;
            align-items: center; gap: 6px;
            height: 34px; padding: 0 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700;
            color: #1E4E8C; background: #EEF3FB; border: 1px solid #D6E2F5;
            text-decoration: none !important; white-space: nowrap;
            transition: background .15s, border-color .15s;
        }
        .app-quick-link:hover { background: #E0EAF8; border-color: #1E4E8C; }
        .app-quick-link--gold {
            color: #072A66; background: #FFF6D6; border-color: #F5B800;
        }
        .app-quick-link--gold:hover { background: #FFEEC2; }
        @media (min-width: 1100px) {
            .app-quick-link { display: inline-flex; }
        }

        /* ========== DARK MODE — نفس منطق admin.blade.php (بطاقات/نصوص/حقول داخل المحتوى فقط) ========== */
        .dark .stat-card,
        .dark .section-card,
        .dark .glass-card,
        .dark .dashboard-card,
        .dark .list-item-card,
        .dark .card-hover-effect,
        .dark .bg-white { background: #1e293b !important; border-color: #334155 !important; }
        .dark main .bg-gray-50,
        .dark main .bg-gray-100 { background-color: #0c1222 !important; }
        .dark main .min-h-screen.bg-gray-50,
        .dark main .min-h-screen.bg-white { background-color: #0c1222 !important; }
        .dark .focus-within\:bg-white:focus-within { background-color: #1e293b !important; }
        .dark .stat-card:hover,
        .dark .section-card:hover { box-shadow: 0 12px 28px -6px rgba(0, 0, 0, 0.3); border-color: #475569 !important; }
        .dark .stat-card::after { background: linear-gradient(135deg, transparent 60%, rgba(59, 130, 246, 0.05) 100%); }
        .dark .section-card-header { background: rgba(30, 41, 59, 0.8) !important; border-bottom-color: #334155 !important; }
        .dark .section-header { background: rgba(30, 41, 59, 0.6) !important; border-bottom-color: #334155 !important; }
        .dark .list-row { border-bottom-color: #334155 !important; }
        .dark .list-row:hover { background: rgba(51, 65, 85, 0.5) !important; }
        .dark .glass-card:hover { background: rgba(30, 41, 59, 0.95) !important; border-color: #475569 !important; }
        .dark .list-item-card:hover { background: #334155 !important; border-color: #475569 !important; }
        .dark .bg-slate-50 { background: #334155 !important; }
        .dark .bg-slate-100 { background: rgba(51, 65, 85, 0.75) !important; }
        .dark .bg-slate-50\/80 { background: rgba(51, 65, 85, 0.8) !important; }
        .dark .rounded-xl.bg-slate-50 { background: #334155 !important; }
        .dark .border-slate-100 { border-color: #334155 !important; }
        .dark .border-slate-200 { border-color: #475569 !important; }
        .dark main { color: #e2e8f0; }
        .dark main h1, .dark main h2, .dark main h3, .dark main h4, .dark main h5, .dark main h6 { color: #f1f5f9 !important; }
        .dark [class*="text-slate-8"], .dark [class*="text-slate-9"], .dark [class*="text-slate-7"] { color: #e2e8f0 !important; }
        .dark [class*="text-slate-6"], .dark [class*="text-slate-5"] { color: #94a3b8 !important; }
        .dark [class*="text-slate-4"] { color: #cbd5e1 !important; }
        .dark [class*="text-gray-8"], .dark [class*="text-gray-9"], .dark [class*="text-gray-7"] { color: #e2e8f0 !important; }
        .dark [class*="text-gray-6"], .dark [class*="text-gray-5"] { color: #94a3b8 !important; }
        .dark [class*="text-navy-8"], .dark [class*="text-navy-7"] { color: #e2e8f0 !important; }
        /* نصوص بألوان hex / براند ثابتة (لا تُطابق text-slate-*) */
        .dark main [class*="text-mx-indigo"], .dark main [class*="text-mx-navy"] { color: #c7d2fe !important; }
        .dark main [class*="text-[#1C"], .dark main [class*="text-[#1F3"], .dark main [class*="text-[#1F2"], .dark main [class*="text-[#283593]"] { color: #f1f5f9 !important; }
        .dark main [class*="text-[#2CA9BD]"] { color: #67e8f9 !important; }
        .dark main input::placeholder,
        .dark main textarea::placeholder { color: #64748b; }
        .dark main input:not([type="submit"]):not([type="button"]),
        .dark main textarea,
        .dark main select { background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important; }
        .dark main table { border-color: #475569; }
        .dark main th, .dark main td { border-color: #334155; color: #e2e8f0; }
        .dark main thead th { background: #334155 !important; color: #f1f5f9 !important; }
        .dark main tbody tr:hover { background: rgba(51, 65, 85, 0.5) !important; }
        .dark .border-gray-200 { border-color: #475569 !important; }
        .dark main hr { border-color: #334155; }
        .dark main a:not(.btn-primary) { color: #93c5fd; }
        .dark main a:not(.btn-primary):hover { color: #bfdbfe; }
        .dark .bg-emerald-50 { background: rgba(16, 185, 129, 0.15) !important; }
        .dark .bg-rose-50 { background: rgba(244, 63, 94, 0.15) !important; }
        .dark .bg-amber-50 { background: rgba(245, 158, 11, 0.15) !important; }
        .dark .bg-sky-50 { background: rgba(14, 165, 233, 0.15) !important; }
        .dark .bg-indigo-50 { background: rgba(99, 102, 241, 0.15) !important; }
        .dark .border-emerald-100 { border-color: rgba(16, 185, 129, 0.3) !important; }
        .dark .border-rose-100 { border-color: rgba(244, 63, 94, 0.3) !important; }
        .dark .border-amber-100 { border-color: rgba(245, 158, 11, 0.3) !important; }
        .dark .text-emerald-800 { color: #6ee7b7 !important; }
        .dark .text-rose-800 { color: #fda4af !important; }
        .dark .text-amber-600 { color: #fcd34d !important; }
        .dark .text-amber-700 { color: #fde047 !important; }
    </style>

    @stack('styles')
    <script>window.platformCurrencySymbol = @json(currency_symbol()); window.platformCurrency = @json(platform_currency());</script>
</head>
<body class="{{ !empty($useInstructorPanel) ? 'st-inst-body st-dash' : '' }}"
      x-data="{
        sidebarOpen: false,
        railOpen: false,
        isNarrow: window.innerWidth < 1024,
        isCompact: window.innerWidth < 1280,
        init() {
          const sync = () => {
            this.isNarrow = window.innerWidth < 1024;
            this.isCompact = window.innerWidth < 1280;
            if (!this.isNarrow) this.sidebarOpen = false;
            if (!this.isCompact) this.railOpen = false;
          };
          sync();
          window.addEventListener('resize', sync);
        }
      }">

<script>
function themeManager() {
    return {
        dark: false,
        init() {
            this.syncFromDom();
            var self = this;
            window.addEventListener('storage', function(e) {
                if (e.key === 'theme' && e.newValue) {
                    self.dark = e.newValue === 'dark';
                    document.documentElement.classList.toggle('dark', self.dark);
                    document.documentElement.classList.toggle('light', !self.dark);
                }
            });
        },
        syncFromDom() {
            this.dark = document.documentElement.classList.contains('dark');
        },
        toggle() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            document.documentElement.classList.toggle('light', !this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        }
    };
}
</script>

@if(!empty($useInstructorPanel))
    {{-- Same CX as student-timeline (st-*) --}}
    @include('layouts.instructor.st-shell')
    @stack('scripts')
    @include('partials.timezone-sync')
@else
    <div class="flex h-screen overflow-hidden">
        @auth
            <aside x-show="sidebarOpen || window.innerWidth >= 1024"
                   x-transition:enter="transition ease-out duration-200"
                   x-transition:enter-start="opacity-0 translate-x-full"
                   x-transition:enter-end="opacity-100 translate-x-0"
                   x-transition:leave="transition ease-in duration-150"
                   x-transition:leave-start="opacity-100 translate-x-0"
                   x-transition:leave-end="opacity-0 translate-x-full"
                   class="app-sidebar flex-shrink-0 fixed lg:static inset-y-0 right-0 z-50 lg:z-auto overflow-y-auto">
                @include('layouts.student-sidebar')
            </aside>

            <div x-show="sidebarOpen && window.innerWidth < 1024"
                 @click="sidebarOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden"></div>
        @endauth

        <div class="flex flex-col flex-1 min-w-0">
            @auth
                <header class="app-header flex items-center justify-between px-4 md:px-6 flex-shrink-0 sticky top-0 z-30">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden h-btn flex-shrink-0" type="button" aria-label="{{ $appRtl ? 'القائمة' : 'Menu' }}">
                            <i class="fas fa-bars text-sm"></i>
                        </button>

                        <div class="hidden md:flex items-center flex-1 max-w-sm">
                            <div class="search-box flex items-center gap-2 w-full">
                                <i class="fas fa-search text-gray-400 dark:text-gray-500 text-xs"></i>
                                <input type="text" placeholder="{{ __('common.nav_search_placeholder_long') }}" class="flex-1 bg-transparent border-none outline-none text-sm text-gray-700 dark:text-gray-300 placeholder-gray-400 dark:placeholder-gray-500 min-w-0">
                            </div>
                        </div>

                        <div class="hidden lg:flex items-center gap-2 ms-2">
                            @if(Route::has('public.instructors.index'))
                                <a href="{{ route('public.instructors.index') }}" class="app-quick-link app-quick-link--gold">
                                    <i class="fas fa-chalkboard-teacher text-[10px]"></i>
                                    {{ $appRtl ? 'المعلمون' : 'Teachers' }}
                                </a>
                            @endif
                            @if(Route::has('student.live-sessions.index'))
                                <a href="{{ route('student.live-sessions.index') }}" class="app-quick-link">
                                    <i class="fas fa-broadcast-tower text-[10px]"></i>
                                    {{ $appRtl ? 'بث مباشر' : 'Live' }}
                                </a>
                            @endif
                            @if(Route::has('public.courses'))
                                <a href="{{ route('public.courses') }}" class="app-quick-link">
                                    <i class="fas fa-book-open text-[10px]"></i>
                                    {{ $appRtl ? 'الكورسات' : 'Courses' }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div x-data="themeManager()" x-init="init()">
                            <button @click="toggle()" type="button" class="h-btn"
                                    :title="dark ? '{{ $appRtl ? 'الوضع النهاري' : 'Light mode' }}' : '{{ $appRtl ? 'الوضع الليلي' : 'Dark mode' }}'">
                                <i class="text-sm" :class="dark ? 'fas fa-sun text-amber-400' : 'fas fa-moon text-gray-400'"></i>
                            </button>
                        </div>

                        @php
                            $currentUser = auth()->user();
                            $audiences = [null, 'student'];
                            $navNotificationsQuery = $currentUser
                                ? $currentUser->customNotifications()
                                    ->with('sender')
                                    ->whereIn('audience', $audiences)
                                    ->where(function ($q) {
                                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                    })
                                : null;
                            $navUnreadCount = $navNotificationsQuery
                                ? (clone $navNotificationsQuery)->where('is_read', false)->count()
                                : 0;
                            $navRecentNotifications = $navNotificationsQuery
                                ? (clone $navNotificationsQuery)->orderBy('created_at', 'desc')->limit(8)->get()
                                : collect();
                        @endphp

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="h-btn relative">
                                <i class="fas fa-bell text-sm"></i>
                                @if($navUnreadCount > 0)
                                    <span class="n-badge">{{ $navUnreadCount > 99 ? '99+' : $navUnreadCount }}</span>
                                @endif
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-80 dd-menu z-50">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $appRtl ? 'الإشعارات' : 'Notifications' }}</h3>
                                    @if(Route::has('notifications'))
                                        <a href="{{ route('notifications') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                                            {{ $appRtl ? 'عرض الكل' : 'View all' }}
                                        </a>
                                    @endif
                                </div>
                                @if($navRecentNotifications->isNotEmpty())
                                    <div class="max-h-96 overflow-y-auto">
                                        @foreach($navRecentNotifications as $notification)
                                            @php
                                                $notificationUrl = $notification->action_url ?: (Route::has('notifications.show') ? route('notifications.show', $notification) : '#');
                                            @endphp
                                            <a href="{{ $notificationUrl }}"
                                               class="block px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                <div class="flex items-start gap-3">
                                                    <div class="mt-0.5">
                                                        <i class="{{ $notification->type_icon }} text-blue-500 text-sm"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $notification->title }}</p>
                                                            @if(!$notification->is_read)
                                                                <span class="w-2 h-2 rounded-full bg-rose-500 flex-shrink-0"></span>
                                                            @endif
                                                        </div>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $notification->message }}</p>
                                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ optional($notification->created_at)->diffForHumans() }}</p>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-6 text-center text-gray-400 dark:text-gray-500 text-sm">
                                        <i class="fas fa-bell-slash text-xl mb-2 block"></i>
                                        <p>{{ $appRtl ? 'لا توجد إشعارات جديدة' : 'No new notifications' }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                <div class="u-avatar flex-shrink-0">
                                    @if(auth()->user()->profile_image)
                                        <img src="{{ auth()->user()->profile_image_url }}" alt="">
                                    @else
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    @endif
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-gray-400 hidden sm:block"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-56 dd-menu z-50 py-1">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                @if(Route::has('settings'))
                                    <a href="{{ route('settings') }}" class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800">{{ $appRtl ? 'الإعدادات' : 'Settings' }}</a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-start px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20">{{ $appRtl ? 'تسجيل الخروج' : 'Logout' }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>
            @endauth

            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
@endif
@include('partials.timezone-sync')
</body>
</html>
