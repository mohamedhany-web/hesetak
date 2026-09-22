@php $adminLocale = app()->getLocale(); $adminRtl = $adminLocale === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ $adminLocale }}" dir="{{ $adminRtl ? 'rtl' : 'ltr' }}" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('auth.dashboard')) - {{ config('app.name') }}</title>
    
    @include('partials.favicon-links')

    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('partials.atheer-head')
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
    <script>
        document.addEventListener('alpine:init', function () {
            Alpine.data('adminNavNotifications', function (config) {
                config = config || {};
                var initialUnread = Number(config.unread) || 0;
                var initialItems = Array.isArray(config.items) ? config.items : [];
                var pollUrl = config.pollUrl || '';
                return {
                    openNotif: false,
                    unread: initialUnread,
                    lastSynced: initialUnread,
                    firstPoll: true,
                    items: initialItems.slice(),
                    pollUrl: pollUrl,
                    audioUnlocked: false,
                    _pollTimer: null,
                    init: function () {
                        var self = this;
                        document.body.addEventListener('click', function () { self.audioUnlocked = true; }, { once: true });
                        document.body.addEventListener('keydown', function () { self.audioUnlocked = true; }, { once: true });
                        this._pollTimer = setInterval(function () { self.poll(); }, 5000);
                        this.poll();
                    },
                    destroy: function () {
                        if (this._pollTimer) clearInterval(this._pollTimer);
                    },
                    poll: async function () {
                        try {
                            var token = document.querySelector('meta[name="csrf-token"]');
                            var res = await fetch(this.pollUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                                },
                                credentials: 'same-origin'
                            });
                            if (!res.ok) return;
                            var d = await res.json();
                            if (!this.firstPoll && d.unread_count > this.lastSynced) {
                                this.playBeep();
                            }
                            this.firstPoll = false;
                            this.lastSynced = d.unread_count;
                            this.unread = d.unread_count;
                            this.items = Array.isArray(d.items) ? d.items : [];
                        } catch (e) { /* ignore */ }
                    },
                    playBeep: function () {
                        if (!this.audioUnlocked) return;
                        try {
                            var Ctx = window.AudioContext || window.webkitAudioContext;
                            if (!Ctx) return;
                            var ctx = new Ctx();
                            var osc = ctx.createOscillator();
                            var gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.value = 880;
                            gain.gain.setValueAtTime(0.0001, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.22);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(ctx.currentTime);
                            osc.stop(ctx.currentTime + 0.25);
                            setTimeout(function () { ctx.close(); }, 400);
                        } catch (e) { /* ignore */ }
                    }
                };
            });
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    <script>
        // حصتك brand tokens for admin (navy #1E4E8C · gold #C9952A)
        (function () {
            var base = (typeof tailwind !== 'undefined' && tailwind.config) ? tailwind.config : {};
            var extend = (base.theme && base.theme.extend) ? base.theme.extend : {};
            var colors = Object.assign({}, extend.colors || {}, {
                canvas: '#F4F7FC',
                'canvas-muted': '#E8EEF6',
                accent: '#1E4E8C',
                'accent-soft': '#E8EEF6',
                gold: '#C9952A',
                'gold-soft': '#F7F1E8',
                metal: '#C9952A',
                navy: {
                    50: '#e8eef6', 100: '#d5e0ef', 200: '#b0c4df',
                    300: '#7a9cc6', 400: '#4a7ab0', 500: '#1e4e8c',
                    600: '#184888', 700: '#152a4a', 800: '#11223c',
                    900: '#0d1a2e', 950: '#091220',
                },
                brand: { DEFAULT: '#1E4E8C', light: '#2A5FA0', dark: '#152A4A' }
            });
            tailwind.config = Object.assign({}, base, {
                darkMode: 'class',
                theme: Object.assign({}, base.theme || {}, {
                    extend: Object.assign({}, extend, {
                        fontFamily: Object.assign({}, extend.fontFamily || {}, {
                            heading: ['IBM Plex Sans Arabic', 'sans-serif'],
                            body: ['IBM Plex Sans Arabic', 'sans-serif'],
                            sans: ['IBM Plex Sans Arabic', 'Segoe UI', 'Tahoma', 'sans-serif'],
                        }),
                        colors: colors,
                    }),
                }),
            });
        })();
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body.admin-body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        [x-cloak] { display: none !important; }

        /* Sidebar / topbar skin: public/css/admin-atheer.css */

        /* ========== CARDS ========== */
        .stat-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;
        }
        .stat-card::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent 60%, rgba(30, 78, 140, 0.03) 100%);
            pointer-events: none; border-radius: 16px;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
            border-color: rgba(30, 78, 140, 0.14);
        }
        .stat-card:active { transform: translateY(-1px); }

        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.125rem; color: white;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover .stat-icon { transform: scale(1.08) rotate(2deg); }

        .section-card {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .section-card:hover {
            box-shadow: 0 12px 28px -6px rgba(15, 23, 42, 0.06);
            border-color: rgba(30, 78, 140, 0.1);
        }
        .section-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(241, 245, 249, 0.9);
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(248, 250, 252, 0.4);
        }

        .list-row {
            display: flex; align-items: center; gap: 0.875rem;
            padding: 0.75rem 1.5rem;
            transition: background 0.12s ease;
            border-bottom: 1px solid rgba(241, 245, 249, 0.7);
        }
        .list-row:last-child { border-bottom: none; }
        .list-row:hover { background: rgba(248, 250, 252, 0.7); }

        /* ========== DARK MODE — بطاقات ونصوص كل الصفحات ========== */
        .dark .stat-card,
        .dark .section-card,
        .dark .glass-card,
        .dark .dashboard-card,
        .dark .list-item-card,
        .dark .card-hover-effect,
        .dark .bg-white { background: #1e293b !important; border-color: #334155 !important; }
        /* بطاقات شبه شفافة (مثل سجل النشاطات) — بدونها يبقى النص الفاتح على خلفية فاتحة */
        .dark [class*="bg-white/95"],
        .dark [class*="bg-white/85"],
        .dark [class*="bg-white/80"],
        .dark [class*="bg-white/70"] {
            background-color: rgba(30, 41, 59, 0.97) !important;
            border-color: #334155 !important;
        }
        .dark [class*="bg-slate-50/50"],
        .dark [class*="bg-slate-50/60"] {
            background-color: rgba(30, 41, 59, 0.65) !important;
        }
        .dark main .bg-gray-50,
        .dark .content-wrapper .bg-gray-50 { background-color: #0f172a !important; }
        .dark main .bg-gray-100,
        .dark .content-wrapper .bg-gray-100 { background-color: #1e293b !important; }
        .dark main .min-h-screen.bg-white,
        .dark .content-wrapper .min-h-screen.bg-white { background-color: #0f172a !important; }
        /* شريط البحث والحقول: Tailwind focus-within:bg-white يبقى أبيضاً في الوضع الداكن */
        .dark .focus-within\:bg-white:focus-within { background-color: #1e293b !important; }
        .dark .hover\:bg-white:hover { background-color: #334155 !important; }
        .dark main [class*="bg-slate-200"],
        .dark .content-wrapper [class*="bg-slate-200"] { background-color: #475569 !important; }
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
        .dark .bg-slate-50\/80 { background: rgba(51, 65, 85, 0.8) !important; }
        .dark .rounded-xl.bg-slate-50 { background: #334155 !important; }
        .dark .border-slate-100 { border-color: #334155 !important; }
        .dark .border-slate-200 { border-color: #475569 !important; }
        .dark main,
        .dark .content-wrapper { color: #e2e8f0; }
        .dark main h1, .dark main h2, .dark main h3, .dark main h4, .dark main h5, .dark main h6,
        .dark .content-wrapper h1, .dark .content-wrapper h2, .dark .content-wrapper h3,
        .dark .content-wrapper h4, .dark .content-wrapper h5, .dark .content-wrapper h6 { color: #f1f5f9 !important; }
        .dark [class*="text-slate-8"], .dark [class*="text-slate-9"], .dark [class*="text-slate-7"] { color: #e2e8f0 !important; }
        .dark [class*="text-slate-6"], .dark [class*="text-slate-5"] { color: #94a3b8 !important; }
        .dark [class*="text-slate-4"] { color: #cbd5e1 !important; }
        .dark [class*="text-gray-8"], .dark [class*="text-gray-9"], .dark [class*="text-gray-7"] { color: #e2e8f0 !important; }
        .dark [class*="text-gray-6"], .dark [class*="text-gray-5"] { color: #94a3b8 !important; }
        .dark [class*="text-navy-8"], .dark [class*="text-navy-7"] { color: #e2e8f0 !important; }
        .dark main [class*="text-mx-indigo"], .dark main [class*="text-mx-navy"],
        .dark .content-wrapper [class*="text-mx-indigo"], .dark .content-wrapper [class*="text-mx-navy"] { color: #c7d2fe !important; }
        .dark main [class*="text-[#1C"], .dark main [class*="text-[#1F3"], .dark main [class*="text-[#1F2"], .dark main [class*="text-[#283593]"],
        .dark .content-wrapper [class*="text-[#1C"], .dark .content-wrapper [class*="text-[#1F3"], .dark .content-wrapper [class*="text-[#1F2"], .dark .content-wrapper [class*="text-[#283593]"] { color: #f1f5f9 !important; }
        .dark main [class*="text-[#2CA9BD]"], .dark .content-wrapper [class*="text-[#2CA9BD]"] { color: #67e8f9 !important; }
        .dark .content-wrapper input::placeholder,
        .dark .content-wrapper textarea::placeholder { color: #64748b; }
        .dark .content-wrapper input:not([type="submit"]):not([type="button"]),
        .dark .content-wrapper textarea,
        .dark .content-wrapper select { background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important; }
        .dark table { border-color: #475569; }
        .dark th, .dark td { border-color: #334155; color: #e2e8f0; }
        .dark thead th { background: #334155 !important; color: #f1f5f9 !important; }
        .dark tbody tr:hover { background: rgba(51, 65, 85, 0.5) !important; }
        .dark .border-gray-200 { border-color: #475569 !important; }
        .dark hr { border-color: #334155; }
        .dark a:not(.btn-primary):not(.sidebar-link):not(.sidebar-sub-link) { color: #93c5fd; }
        .dark a:not(.btn-primary):not(.sidebar-link):not(.sidebar-sub-link):hover { color: #bfdbfe; }
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

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(16px) saturate(180%);
            border: 1px solid rgba(226, 232, 240, 0.5);
            border-radius: 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.06);
        }

        /* ========== ANIMATIONS ========== */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .animate-fade-in-1 { animation-delay: 0.06s; }
        .animate-fade-in-2 { animation-delay: 0.12s; }
        .animate-fade-in-3 { animation-delay: 0.18s; }
        .animate-fade-in-4 { animation-delay: 0.24s; }
        .animate-fade-in-5 { animation-delay: 0.30s; }

        /* ========== BUTTONS ========== */
        .btn-primary { background: #1E4E8C; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; }
        .btn-primary:hover { background: #152A4A; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(30, 78, 140, 0.28); }
        .btn-primary:active { transform: translateY(0); }
        .btn-secondary { background: #64748b; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; }
        .btn-secondary:hover { background: #475569; }
        .btn-success { background: #059669; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; }
        .btn-success:hover { background: #047857; }
        .btn-danger { background: #dc2626; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-warning { background: #C9952A; color: #152A4A; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; transition: all 0.2s; }
        .btn-warning:hover { background: #b8861f; }

        /* ========== COMPAT for other admin pages ========== */
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; border-radius: 0.5rem; color: #475569; transition: all 0.2s; }
        .nav-link:hover { background: #f1f5f9; color: #1e293b; }
        .nav-link.active { background: #E8EEF6; color: #1E4E8C; }
        .dashboard-card { background: white; border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 16px; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .dashboard-card:hover { box-shadow: 0 16px 32px -8px rgba(0, 0, 0, 0.06); }
        .card-hover-effect { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .card-hover-effect:hover { transform: translateY(-2px); }
        .card-icon { background: linear-gradient(135deg, #1E4E8C, #2A5FA0); box-shadow: 0 4px 14px rgba(30, 78, 140, 0.28); }
        .card-icon:hover { transform: scale(1.08); }
        .section-header { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(226, 232, 240, 0.6); background: rgba(248, 250, 252, 0.4); }
        .list-item-card { background: white; border: 1px solid rgba(226, 232, 240, 0.6); border-radius: 12px; transition: all 0.2s; }
        .list-item-card:hover { background: #f8fafc; border-color: rgba(30, 78, 140, 0.14); }

        /* ========== FOCUS STATES ========== */
        button:focus-visible, a:focus-visible, input:focus-visible {
            outline: 2px solid rgba(30, 78, 140, 0.45);
            outline-offset: 2px;
            border-radius: 0.375rem;
        }
    </style>
    
    @stack('styles')
    {{-- بعد الـ styles المضمّنة حتى لا تُلغى قواعد الشِل --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/admin-atheer.css') }}">
    <script>window.platformCurrencySymbol = @json(currency_symbol()); window.platformCurrency = @json(platform_currency());</script>
</head>
<body class="admin-body font-sans antialiased"
      x-data="{
          sidebarOpen: false,
          darkMode: document.documentElement.classList.contains('dark')
      }"
      x-init="
          function persistTheme(isDark) {
              if (isDark) { document.documentElement.classList.add('dark'); document.documentElement.classList.remove('light'); }
              else { document.documentElement.classList.remove('dark'); document.documentElement.classList.add('light'); }
              localStorage.setItem('theme', isDark ? 'dark' : 'light');
          }
          $watch('darkMode', v => persistTheme(v));
          window.addEventListener('close-sidebar', () => sidebarOpen = false);
          window.addEventListener('resize', () => { if (window.innerWidth >= 1024) sidebarOpen = false; });
      "
      @close-sidebar.window="sidebarOpen = false">

@php
    $adminNavSettingsUrl = auth()->user()->hasPermission('manage.system-settings')
        ? route('admin.system-settings.edit')
        : route('admin.profile');
    $adminUnreadCount = \App\Models\Notification::where('user_id', auth()->id())->unread()->valid()->count();
    $adminUnreadNotifications = \App\Models\Notification::where('user_id', auth()->id())->unread()->valid()->orderByDesc('created_at')->limit(5)->get();
    $adminNavItems = $adminUnreadNotifications->map(fn ($n) => [
        'id' => $n->id,
        'title' => $n->title,
        'message' => $n->message,
        'priority' => $n->priority,
        'href' => $n->action_url ?: route('admin.notifications.show', $n),
        'time' => $n->created_at->diffForHumans(),
        'icon' => $n->type_icon,
    ])->values();
    $adminNavBellConfig = [
        'unread' => (int) $adminUnreadCount,
        'items' => $adminNavItems->all(),
        'pollUrl' => route('admin.api.nav-notifications'),
    ];
@endphp

<div class="admin-shell flex h-[100dvh] overflow-hidden">
    {{-- Desktop sidebar: ثابت الارتفاع، التمرير داخل الـ nav فقط --}}
    <aside class="admin-sidebar admin-sidebar-desktop hidden h-full w-[280px] shrink-0 flex-col overflow-hidden text-white lg:flex">
        @include('layouts.admin-sidebar')
    </aside>

    {{-- Mobile drawer --}}
    <div id="admin-drawer" class="fixed inset-0 z-50 lg:hidden" x-show="sidebarOpen" x-cloak>
        <button type="button" class="absolute inset-0 bg-ink/50" @click="sidebarOpen = false" aria-label="إغلاق"></button>
        <aside class="drawer-panel absolute inset-y-0 right-0 flex h-full w-[min(88vw,300px)] flex-col overflow-hidden bg-ink text-white shadow-lift admin-sidebar">
            <div class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-white/10 px-4">
                @php
                    $drawerLogoUrl = $adminPanelLogoUrl
                        ?: \App\Services\AdminPanelBranding::defaultPublicMarkUrl();
                    $drawerLogoVer = @filemtime(public_path(\App\Services\AdminPanelBranding::PUBLIC_MARK_PATH)) ?: time();
                    if (is_string($drawerLogoUrl) && ! str_contains($drawerLogoUrl, '?') && str_contains($drawerLogoUrl, 'hesetak-mark')) {
                        $drawerLogoUrl .= '?v='.$drawerLogoVer;
                    }
                @endphp
                <div class="flex min-w-0 items-center gap-2.5">
                    <div class="sidebar-brand-mark">
                        <img
                            src="{{ $drawerLogoUrl }}"
                            alt="{{ config('app.name') }}"
                            width="44"
                            height="44"
                            onerror="this.onerror=null;this.src='{{ \App\Services\AdminPanelBranding::inlineFallbackDataUri() }}';"
                        >
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold leading-tight text-white">{{ config('app.name') }}</p>
                        <p class="truncate text-[10px] font-medium text-white/50">{{ __('admin.admin_panel') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-press px-2 text-xl leading-none text-white/80" @click="sidebarOpen = false" aria-label="إغلاق">×</button>
            </div>
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                @include('layouts.admin-sidebar', ['adminSidebarDrawer' => true])
            </div>
        </aside>
    </div>

    <div class="admin-main-column flex h-full min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
        <header class="admin-topbar shrink-0 z-40">
            <div class="flex h-[72px] items-center gap-2 px-3 sm:gap-3 sm:px-4 md:px-6">
                <button type="button" @click="sidebarOpen = true" class="btn-press inline-flex size-10 shrink-0 items-center justify-center rounded-xl lg:hidden" aria-label="فتح القائمة">
                    <i class="fas fa-bars text-sm text-ink"></i>
                </button>

                <div class="admin-topbar-title">
                    <p class="truncate text-[11px] font-medium text-muted">مركز التحكم · {{ config('app.name') }}</p>
                    <h1 class="truncate text-base font-semibold text-ink sm:text-lg md:text-xl">
                        @hasSection('header')
                            @yield('header')
                        @else
                            @yield('page_title', 'لوحة الإدارة')
                        @endif
                    </h1>
                </div>

                <form class="mx-auto hidden min-w-0 max-w-md flex-1 md:block" onsubmit="return false">
                    <input type="search" class="admin-topbar-search" placeholder="بحث سريع: طلب، كورس، طالب…" aria-label="بحث سريع">
                </form>

                <div class="admin-topbar-actions ms-auto flex items-center gap-1 sm:gap-1.5 md:gap-2">
                    <a href="{{ route('admin.ops-guide.index') }}" class="btn-press hidden rounded-xl bg-accent-soft px-3 py-2 text-sm text-accent sm:inline-flex">مساعد التشغيل</a>

                    <div class="relative" x-data="adminNavNotifications({{ \Illuminate\Support\Js::from($adminNavBellConfig) }})" @click.outside="openNotif = false">
                        <button type="button" @click="openNotif = !openNotif" class="btn-press relative inline-flex size-10 items-center justify-center rounded-xl hover:bg-canvas" aria-label="الإشعارات">
                            <i class="fas fa-bell text-sm text-ink"></i>
                            <span x-show="unread > 0" x-cloak class="absolute top-2 left-2 size-2 rounded-full bg-danger"></span>
                        </button>
                        <div x-show="openNotif" x-cloak
                             class="admin-dropdown-panel absolute left-0 z-50 mt-2 max-h-[min(420px,70vh)] overflow-hidden rounded-2xl border border-line bg-surface shadow-lift">
                            <div class="flex items-center justify-between border-b border-line bg-canvas/80 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink">أحدث الإشعارات</p>
                                    <p class="mt-0.5 text-xs text-muted" x-text="unread > 0 ? ('لديك ' + unread + ' إشعار غير مقروء') : 'لا توجد إشعارات جديدة حالياً'"></p>
                                </div>
                                <a href="{{ route('admin.notifications.inbox') }}" class="shrink-0 text-xs font-semibold text-accent">عرض الكل</a>
                            </div>
                            <div class="max-h-[min(320px,55vh)] overflow-y-auto">
                                <template x-for="item in items" :key="item.id">
                                    <a :href="item.href" class="flex items-start gap-3 border-b border-line/70 px-4 py-3 transition hover:bg-canvas last:border-b-0">
                                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-xl bg-accent-soft text-xs text-accent"><i :class="item.icon"></i></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-xs font-semibold text-ink" x-text="item.title"></p>
                                            <p class="mt-0.5 line-clamp-2 text-xs text-muted" x-text="item.message"></p>
                                            <p class="mt-1 text-[10px] text-muted" x-text="item.time"></p>
                                        </div>
                                    </a>
                                </template>
                                <div x-show="items.length === 0" class="px-4 py-6 text-center text-xs text-muted">لا توجد إشعارات جديدة</div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ url('/') }}" class="btn-press hidden items-center rounded-xl border border-line px-3 py-2 text-sm xl:inline-flex">الموقع</a>

                    <div class="relative ms-0.5 sm:ms-1" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click.stop="open = !open" class="flex items-center gap-2 rounded-xl bg-canvas px-1.5 py-1.5 sm:px-2 md:px-3" :aria-expanded="open">
                            @if(auth()->user()->profile_image)
                                <img src="{{ auth()->user()->profile_image_url }}" alt="" class="size-8 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling?.classList.remove('hidden');">
                                <div class="hidden size-8 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                            @else
                                <div class="flex size-8 items-center justify-center rounded-full bg-ink text-xs font-bold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                            @endif
                            <div class="hidden leading-tight md:block">
                                <p class="max-w-[100px] truncate text-xs font-semibold text-ink">{{ auth()->user()->name }}</p>
                                <p class="text-[10px] text-muted">صلاحيات كاملة</p>
                            </div>
                        </button>
                        <div x-show="open" x-cloak class="admin-dropdown-panel absolute left-0 z-[9999] mt-2 overflow-hidden rounded-2xl border border-line bg-surface shadow-lift" style="width:min(14rem,calc(100vw - 1.5rem))">
                            <div class="border-b border-line bg-canvas/80 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-muted">{{ auth()->user()->email ?? auth()->user()->phone }}</p>
                            </div>
                            <div class="py-1.5">
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] text-ink-soft hover:bg-canvas">لوحة التحكم</a>
                                <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] text-ink-soft hover:bg-canvas">الملف الشخصي</a>
                                <a href="{{ $adminNavSettingsUrl }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px] text-ink-soft hover:bg-canvas">إعدادات النظام</a>
                            </div>
                            <div class="border-t border-line py-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-start text-[13px] text-ink-soft hover:bg-danger/10 hover:text-danger">تسجيل الخروج</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- المحتوى يتمرّر هنا فقط — السايدبار ثابت --}}
        <main class="admin-main-scroll min-h-0 flex-1 overflow-x-hidden overflow-y-auto">
            <div class="admin-content-pad page-enter space-y-5" data-admin-page>
                @if(session('success'))
                    <div class="flex items-center gap-3 rounded-2xl border border-line bg-success/10 px-4 py-3 text-success sm:px-5" role="alert">
                        <span class="min-w-0 flex-1 text-sm font-medium">{{ session('success') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="flex items-center gap-3 rounded-2xl border border-line bg-danger/10 px-4 py-3 text-danger sm:px-5" role="alert">
                        <span class="min-w-0 flex-1 text-sm font-medium">{{ session('error') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="flex items-center gap-3 rounded-2xl border border-line bg-metal/15 px-4 py-3 text-[#7a5c2e] sm:px-5" role="alert">
                        <span class="min-w-0 flex-1 text-sm font-medium">{{ session('warning') }}</span>
                        <button type="button" onclick="this.parentElement.remove()" class="shrink-0 text-muted hover:text-ink" aria-label="إغلاق">×</button>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</div>

@stack('scripts')
@include('partials.timezone-sync')

<script>
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a');
    if (link && window.innerWidth < 1024) {
      var sidebar = link.closest('#admin-drawer');
      if (sidebar) window.dispatchEvent(new CustomEvent('close-sidebar'));
    }
  }, true);
</script>
</body>
</html>
