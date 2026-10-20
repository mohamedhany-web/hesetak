@php $studentLocale = app()->getLocale(); $studentRtl = $studentLocale === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ $studentLocale }}" dir="{{ $studentRtl ? 'rtl' : 'ltr' }}" class="light">
<head>
    <script>
        (function() {
            var s = localStorage.getItem('theme');
            var d = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && d)) {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            }
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name') }} - @yield('title', __('auth.dashboard'))</title>

    @include('partials.favicon-links')

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    heading: ['Tajawal', 'IBM Plex Sans Arabic', 'sans-serif'],
                    body: ['IBM Plex Sans Arabic', 'Tajawal', 'sans-serif'],
                },
                colors: {
                    navy: { 50:'#f0f4ff',100:'#dbe4ff',200:'#bac8ff',300:'#91a7ff',400:'#748ffc',500:'#5c7cfa',600:'#4c6ef5',700:'#4263eb',800:'#3b5bdb',900:'#364fc7',950:'#0F172A' },
                    brand: { 50:'#ecfeff',100:'#cffafe',200:'#a5f3fc',300:'#67e8f9',400:'#22d3ee',500:'#06b6d4',600:'#0891b2',700:'#0e7490',800:'#155e75',900:'#164e63' }
                }
            }
        }
    }
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        * { font-family: 'IBM Plex Sans Arabic', 'Tajawal', system-ui, sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Tajawal', 'IBM Plex Sans Arabic', sans-serif; }
        html, body { margin: 0; padding: 0; height: 100%; overflow-x: hidden; }
        body { background: #f8fafc; transition: background-color 0.2s; }
        html.dark body { background: #0f172a; }
        [x-cloak] { display: none !important; }

        .student-sidebar {
            background: #ffffff;
            border-left: 1px solid rgba(226, 232, 240, 0.8);
            width: 272px;
            box-shadow: -1px 0 8px rgba(0, 0, 0, 0.03);
        }

        .nav-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.5rem 0.75rem; border-radius: 0.75rem;
            color: #475569; font-size: 0.8125rem; font-weight: 500;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; cursor: pointer;
        }
        .nav-item:hover { background: #f1f5f9; color: #1e293b; }
        .nav-item.active {
            background: linear-gradient(135deg, #ecfeff, #f0f9ff);
            color: #0891b2; font-weight: 600;
        }
        .nav-item.active::before {
            content: ''; position: absolute; top: 6px; bottom: 6px;
            width: 3px; border-radius: 0 3px 3px 0;
            background: linear-gradient(180deg, #06b6d4, #0891b2);
        }
        [dir="rtl"] .nav-item.active::before { right: 0; }
        [dir="ltr"] .nav-item.active::before { left: 0; }
        .nav-item .nav-icon {
            width: 34px; height: 34px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8125rem; flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .nav-item:hover .nav-icon { transform: scale(1.06); }
        .nav-item.active .nav-icon { box-shadow: 0 2px 8px rgba(6, 182, 212, 0.2); }

        .top-navbar {
            height: 68px;
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid rgba(226, 232, 240, 0.7);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .search-field {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 12px; padding: 0.5rem 0.875rem;
            transition: all 0.2s ease;
        }
        .search-field:focus-within {
            border-color: #06b6d4; background: #fff;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }

        .action-btn {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            background: #f8fafc; border: 1px solid #e2e8f0;
            color: #64748b; transition: all 0.2s ease;
            position: relative;
        }
        .action-btn:hover { background: #ecfeff; border-color: #a5f3fc; color: #0891b2; }

        .notif-dot {
            position: absolute; top: -2px; right: -2px;
            min-width: 16px; height: 16px; padding: 0 3px;
            background: #ef4444; border-radius: 9999px;
            font-size: 9px; color: white; font-weight: 700;
            border: 2px solid white;
            display: flex; align-items: center; justify-content: center;
        }

        .user-btn { transition: all 0.2s ease; border-radius: 12px; padding: 4px 6px; }
        .user-btn:hover { background: #f8fafc; }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 13px;
            box-shadow: 0 1px 4px rgba(6, 182, 212, 0.25);
            transition: transform 0.2s ease;
        }
        .user-btn:hover .user-avatar { transform: scale(1.05); }

        .dropdown-menu {
            background: white; border: 1px solid #e2e8f0;
            border-radius: 14px; box-shadow: 0 10px 40px -8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .dropdown-link {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.75rem; border-radius: 0.5rem;
            font-size: 0.8125rem; color: #475569;
            transition: background 0.15s ease;
        }
        .dropdown-link:hover { background: #f8fafc; }

        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(6, 182, 212, 0.2); border-radius: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(6, 182, 212, 0.35); }

        .logo-area {
            background: linear-gradient(135deg, #f8fafc 0%, #ecfeff 100%);
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }

        .stat-mini {
            border-radius: 10px; padding: 0.5rem; transition: all 0.2s ease;
        }
        .stat-mini:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.04); }

        .user-card-bottom {
            background: linear-gradient(135deg, #f8fafc 0%, #ecfeff 100%);
            border-top: 1px solid rgba(226, 232, 240, 0.6);
        }

        @media (max-width: 1024px) {
            .student-sidebar { width: 300px; max-width: 84vw; }
        }
        @media (max-width: 640px) {
            .student-sidebar { width: 280px; max-width: 88vw; }
            .top-navbar { height: 58px; }
            .action-btn { width: 36px; height: 36px; }
            .user-avatar { width: 32px; height: 32px; font-size: 12px; }
        }

        /* ========== DARK MODE — لوحة الطالب (نصوص/بطاقات/هيدر متناسقة مع app) ========== */
        html.dark .student-sidebar {
            background: #1e293b !important;
            border-left-color: #334155 !important;
            box-shadow: -1px 0 12px rgba(0, 0, 0, 0.2);
            color: #e2e8f0;
        }
        html.dark .ins-nav-group { color: #94a3b8 !important; }
        html.dark .top-navbar {
            background: rgba(15, 23, 42, 0.95) !important;
            border-bottom-color: #334155 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        html.dark .logo-area {
            background: linear-gradient(135deg, #0f172a 0%, #164e63 100%) !important;
            border-bottom-color: #334155 !important;
        }
        html.dark .user-card-bottom {
            background: linear-gradient(135deg, #0f172a 0%, #164e63 100%) !important;
            border-top-color: #334155 !important;
        }
        html.dark .search-field {
            background: #334155 !important;
            border-color: #475569 !important;
        }
        html.dark .search-field:focus-within {
            background: #1e293b !important;
            border-color: #22d3ee;
            box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.15);
        }
        html.dark .search-field input { color: #e2e8f0 !important; }
        html.dark .search-field input::placeholder { color: #64748b; }
        html.dark .search-field kbd {
            background: #475569 !important;
            border-color: #64748b !important;
            color: #cbd5e1 !important;
        }
        html.dark .action-btn {
            background: #334155 !important;
            border-color: #475569 !important;
            color: #94a3b8 !important;
        }
        html.dark .action-btn:hover {
            background: #0e7490 !important;
            border-color: #22d3ee !important;
            color: #ecfeff !important;
        }
        html.dark .dropdown-menu {
            background: #1e293b !important;
            border-color: #475569 !important;
            box-shadow: 0 10px 40px -8px rgba(0, 0, 0, 0.45);
        }
        html.dark .dropdown-link { color: #cbd5e1 !important; }
        html.dark .dropdown-link:hover { background: #334155 !important; color: #f1f5f9 !important; }
        html.dark .notif-dot { border-color: #1e293b; }
        html.dark main {
            background: #0f172a !important;
            color: #e2e8f0;
        }
        html.dark main h1, html.dark main h2, html.dark main h3, html.dark main h4, html.dark main h5, html.dark main h6 {
            color: #f1f5f9 !important;
        }
        html.dark .stat-card,
        html.dark .section-card,
        html.dark .glass-card,
        html.dark .dashboard-card,
        html.dark .list-item-card,
        html.dark .card-hover-effect,
        html.dark main .bg-white { background: #1e293b !important; border-color: #334155 !important; }
        html.dark main .bg-gray-50,
        html.dark main .bg-gray-100 { background-color: #0f172a !important; }
        html.dark main .min-h-screen.bg-gray-50,
        html.dark main .min-h-screen.bg-white { background-color: #0f172a !important; }
        html.dark .focus-within\:bg-white:focus-within { background-color: #1e293b !important; }
        html.dark .bg-slate-50, html.dark .bg-slate-50\/80 { background: rgba(51, 65, 85, 0.45) !important; }
        html.dark .dropdown-menu [class*="from-brand-50"] {
            background-image: linear-gradient(to right, rgba(8, 145, 178, 0.22), rgba(30, 41, 59, 0.95)) !important;
        }
        html.dark .border-slate-100, html.dark .border-slate-200 { border-color: #334155 !important; }
        html.dark [class*="text-slate-8"], html.dark [class*="text-slate-9"], html.dark [class*="text-slate-7"] { color: #e2e8f0 !important; }
        html.dark [class*="text-slate-6"], html.dark [class*="text-slate-5"] { color: #94a3b8 !important; }
        html.dark [class*="text-slate-4"] { color: #cbd5e1 !important; }
        html.dark [class*="text-gray-8"], html.dark [class*="text-gray-9"], html.dark [class*="text-gray-7"] { color: #e2e8f0 !important; }
        html.dark [class*="text-gray-6"], html.dark [class*="text-gray-5"] { color: #94a3b8 !important; }
        html.dark main [class*="text-mx-indigo"], html.dark main [class*="text-mx-navy"] { color: #c7d2fe !important; }
        html.dark main [class*="text-[#1C"], html.dark main [class*="text-[#1F3"], html.dark main [class*="text-[#1F2"], html.dark main [class*="text-[#283593]"] { color: #f1f5f9 !important; }
        html.dark main [class*="text-[#2CA9BD]"] { color: #67e8f9 !important; }
        html.dark main input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]),
        html.dark main textarea,
        html.dark main select { background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important; }
        html.dark main input::placeholder, html.dark main textarea::placeholder { color: #64748b; }
        html.dark main table { border-color: #475569; }
        html.dark main th, html.dark main td { border-color: #334155; color: #e2e8f0; }
        html.dark main thead th { background: #334155 !important; color: #f1f5f9 !important; }
        html.dark main tbody tr:hover { background: rgba(51, 65, 85, 0.45) !important; }
        html.dark main hr { border-color: #334155; }
        html.dark main a:not(.btn-primary) { color: #7dd3fc; }
        html.dark main a:not(.btn-primary):hover { color: #bae6fd; }
        html.dark .bg-emerald-50 { background: rgba(16, 185, 129, 0.15) !important; }
        html.dark .bg-rose-50 { background: rgba(244, 63, 94, 0.15) !important; }
        html.dark .border-emerald-200 { border-color: rgba(16, 185, 129, 0.35) !important; }
        html.dark .border-rose-200 { border-color: rgba(244, 63, 94, 0.35) !important; }
        html.dark .text-emerald-700 { color: #6ee7b7 !important; }
        html.dark .text-rose-700 { color: #fda4af !important; }
    </style>
    @stack('styles')
</head>
<body x-data="{ sidebarOpen: window.innerWidth >= 1024 }"
      x-init="window.addEventListener('resize', () => { sidebarOpen = window.innerWidth >= 1024; })">
    <div class="flex h-screen overflow-hidden">
        @auth
            <aside x-show="sidebarOpen || window.innerWidth >= 1024"
                   x-transition:enter="transition ease-out duration-150"
                   x-transition:enter-start="opacity-0 translate-x-full"
                   x-transition:enter-end="opacity-100 translate-x-0"
                   x-transition:leave="transition ease-in duration-100"
                   x-transition:leave-start="opacity-100 translate-x-0"
                   x-transition:leave-end="opacity-0 translate-x-full"
                   class="student-sidebar flex-shrink-0 fixed lg:static inset-y-0 right-0 z-50 lg:z-auto"
                   style="will-change: transform, opacity;">
                @include('layouts.student-sidebar')
            </aside>

            <div x-show="sidebarOpen && window.innerWidth < 1024"
                 @click="sidebarOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden"></div>
        @endauth

        <div class="flex flex-col flex-1 min-w-0">
            @auth
                <header class="top-navbar flex items-center justify-between px-4 sm:px-6 lg:px-8 flex-shrink-0 sticky top-0 z-30">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center text-brand-600 bg-brand-50 hover:bg-brand-100 transition-colors flex-shrink-0">
                            <i class="fas fa-bars text-sm"></i>
                        </button>
                        <div class="search-field flex items-center gap-2 flex-1 max-w-xl">
                            <i class="fas fa-search text-brand-500 text-xs"></i>
                            <input type="text" placeholder="{{ __('common.nav_search_placeholder') }}"
                                   class="flex-1 bg-transparent border-none outline-none text-sm text-slate-700 placeholder-slate-400 font-medium min-w-0">
                            <kbd class="hidden lg:flex items-center gap-0.5 px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold text-slate-500 border border-slate-200 flex-shrink-0">
                                Ctrl K
                            </kbd>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="hidden lg:flex items-center gap-2">
                            <a href="{{ route('academic-years') }}" class="action-btn" title="{{ __('landing.nav.courses') }}">
                                <i class="fas fa-search text-xs"></i>
                            </a>
                            <a href="{{ route('my-courses.index') }}" class="action-btn" title="{{ __('common.my_courses_title') }}">
                                <i class="fas fa-book-open text-xs"></i>
                            </a>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="action-btn">
                                <i class="fas fa-bell text-xs"></i>
                                <span class="notif-dot">3</span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-80 dropdown-menu z-50">
                                <div class="p-3.5 border-b border-slate-100 bg-gradient-to-r from-brand-50 to-slate-50">
                                    <h3 class="font-heading font-bold text-slate-800 text-sm flex items-center gap-2">
                                        <i class="fas fa-bell text-brand-500"></i>
                                        الإشعارات
                                    </h3>
                                </div>
                                <div class="p-6 text-center text-slate-400 text-sm">
                                    <i class="fas fa-bell-slash text-2xl mb-2 opacity-30"></i>
                                    <p>لا توجد إشعارات جديدة</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="user-btn flex items-center gap-2">
                                <div class="user-avatar flex-shrink-0">
                                    @if(auth()->user()->profile_image)
                                        <img src="{{ auth()->user()->profile_image_url }}" alt="" class="w-full h-full rounded-[10px] object-cover">
                                    @else
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    @endif
                                </div>
                                <div class="hidden sm:block text-right min-w-0">
                                    <div class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ __('student.student_role') }}</div>
                                </div>
                                <i class="fas fa-chevron-down text-[10px] text-slate-400 hidden sm:block transition-transform" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute left-0 mt-2 w-60 dropdown-menu z-50">
                                <div class="p-3.5 border-b border-slate-100 bg-gradient-to-r from-brand-50 to-slate-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center text-white font-bold text-sm shadow-md flex-shrink-0">
                                            @if(auth()->user()->profile_image)
                                                <img src="{{ auth()->user()->profile_image_url }}" alt="" class="w-full h-full rounded-xl object-cover">
                                            @else
                                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-slate-800 text-sm truncate">{{ auth()->user()->name }}</div>
                                            <div class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-1.5">
                                    <a href="{{ route('profile') }}" class="dropdown-link">
                                        <i class="fas fa-user w-4 text-brand-500"></i> الملف الشخصي
                                    </a>
                                    <a href="{{ route('settings') }}" class="dropdown-link">
                                        <i class="fas fa-cog w-4 text-slate-400"></i> الإعدادات
                                    </a>
                                    <hr class="my-1.5 border-slate-100">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full dropdown-link text-rose-600">
                                            <i class="fas fa-sign-out-alt w-4"></i> تسجيل الخروج
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
            @endauth

            <main class="flex-1 overflow-auto bg-slate-50/80 min-w-0 w-full">
                <div class="w-full max-w-full space-y-4 p-4 sm:p-6 lg:p-8">
                    @include('partials.admin-flash')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
    @include('partials.timezone-sync')
</body>
</html>
