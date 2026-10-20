@php $locale = app()->getLocale(); $rtl = $locale === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('admin.community_dashboard')) - {{ config('app.name') }}</title>
    @include('partials.favicon-links')
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>* { font-family: 'Tajawal', 'Cairo', sans-serif; }</style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-gray-900 min-h-screen">
    <div class="flex min-h-screen">
        <!-- شريط جانبي ـ مجتمع البيانات (كما في الصورة المرجعية) -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 {{ $rtl ? 'lg:right-0' : 'lg:left-0' }} bg-slate-900 border-{{ $rtl ? 'l' : 'r' }} border-slate-700/50 z-30">
            <!-- الهيدر: مجتمع البيانات والذكاء الاصطناعي -->
            <div class="p-4 border-b border-slate-700/50">
                <div class="flex items-center gap-2">
                    <span class="text-slate-400"><i class="fas fa-chevron-up text-xs"></i></span>
                    <span class="w-10 h-10 rounded-xl bg-slate-700/80 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-users-cog text-cyan-400"></i>
                    </span>
                    <span class="text-white font-bold text-sm">{{ __('public.community_heading') }}</span>
                </div>
            </div>
            <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
                <p class="px-3 py-2 text-xs font-bold text-cyan-400/90 uppercase tracking-wide">مراقبة عامة</p>
                <a href="{{ route('admin.community.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.dashboard') || request()->routeIs('admin.community') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span>لوحة المراقبة</span>
                </a>
                <p class="px-3 py-2 mt-2 text-xs font-bold text-cyan-400/90 uppercase tracking-wide">المحتوى</p>
                <a href="{{ route('admin.community.competitions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.competitions.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-trophy w-5"></i>
                    <span>{{ __('admin.community_competitions') }}</span>
                </a>
                <a href="{{ route('admin.community.datasets.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.datasets.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-database w-5"></i>
                    <span>{{ __('admin.community_datasets') }}</span>
                </a>
                <p class="px-3 py-2 mt-2 text-xs font-bold text-cyan-400/90 uppercase tracking-wide">المساهمون والمراجعة</p>
                <a href="{{ route('admin.community.submissions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.submissions.index') || request()->routeIs('admin.community.submissions.dataset.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-paper-plane w-5"></i>
                    <span>مراجعة تقديمات البيانات</span>
                </a>
                <a href="{{ route('admin.community.submissions.models.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.submissions.models.*') || request()->routeIs('admin.community.submissions.model.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-brain w-5"></i>
                    <span>تقديمات النماذج (Model Zoo)</span>
                </a>
                <a href="{{ route('admin.community.contributors.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.contributors.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-user-plus w-5"></i>
                    <span>إنشاء وإدارة المساهمين</span>
                </a>
                <p class="px-3 py-2 mt-2 text-xs font-bold text-cyan-400/90 uppercase tracking-wide">أخرى</p>
                <a href="{{ route('admin.community.discussions.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.discussions.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-comments w-5"></i>
                    <span>{{ __('admin.community_discussions') }}</span>
                </a>
                <a href="{{ route('admin.community.settings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-white {{ request()->routeIs('admin.community.settings.*') ? 'bg-slate-700/80 text-white' : '' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span>{{ __('admin.community_settings') }}</span>
                </a>
            </nav>
            <div class="p-3 border-t border-slate-700/50">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white text-sm">
                    <i class="fas fa-arrow-left w-4"></i>
                    <span>{{ __('admin.dashboard') }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-red-300 w-full text-sm">
                        <i class="fas fa-sign-out-alt w-4"></i>
                        <span>{{ __('auth.logout') }}</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex flex-col flex-1 min-w-0 {{ $rtl ? 'lg:mr-64' : 'lg:ml-64' }}">
            <header class="flex-shrink-0 sticky top-0 z-20 bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.community.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-semibold">{{ __('admin.community_section') }}</a>
                    <span class="text-slate-400">/</span>
                    <span class="text-slate-900 font-bold">@yield('header', __('admin.community_dashboard'))</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-600">{{ auth()->user()->name }}</span>
                    <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="{{ __('admin.dashboard') }}">
                        <i class="fas fa-th-large"></i>
                    </a>
                </div>
            </header>

            <main class="flex-1 overflow-auto bg-gray-50 min-w-0 w-full">
                <div class="w-full max-w-full space-y-4 p-4 sm:p-6 lg:p-8">
                    @include('partials.admin-flash')
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
