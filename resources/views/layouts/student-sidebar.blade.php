@php
    $user = auth()->user();
    $isStudent = $user && ($user->role === 'student' || strtolower((string) $user->role) === 'student');
    $closeSidebar = 'if (window.innerWidth < 1024) setTimeout(() => { sidebarOpen = false }, 50)';
    $isRtl = app()->getLocale() === 'ar';

    $weekAppts = 0;
    if ($user && function_exists('student_ui')) {
        try {
            $weekAppts = \App\Services\StudentScheduleService::weekAppointments($user)->count();
        } catch (\Throwable $e) {
            $weekAppts = 0;
        }
    }

    $creditUnits = 0;
    if ($user && \Illuminate\Support\Facades\Schema::hasTable('student_service_entitlements')) {
        try {
            $creditUnits = (int) \App\Models\StudentServiceEntitlement::query()
                ->where('user_id', $user->id)
                ->where('units_remaining', '>', 0)
                ->sum('units_remaining');
        } catch (\Throwable $e) {
            $creditUnits = 0;
        }
    }
@endphp

<div class="flex flex-col h-full">
    <div class="ins-sidebar-brand flex items-center gap-3 px-4 py-5 flex-shrink-0 relative">
        <button @click="if (window.innerWidth < 1024) sidebarOpen = false" type="button"
                class="lg:hidden absolute top-3 left-3 w-8 h-8 rounded-lg bg-white/15 text-white hover:bg-white/25 flex items-center justify-center transition-colors z-10"
                aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}">
            <i class="fas fa-times text-xs"></i>
        </button>
        <div class="w-11 h-11 rounded-xl bg-[#C9952A] text-[#152A4A] flex items-center justify-center flex-shrink-0 shadow-lg shadow-black/20 overflow-hidden">
            <img src="{{ public_img_url('brand/hesetak-mark.png') }}" alt="" class="w-9 h-9 object-contain" width="36" height="36" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
            <i class="fas fa-graduation-cap text-lg" style="display:none"></i>
        </div>
        <div class="flex-1 min-w-0 relative z-10">
            <h2 class="text-base font-extrabold text-white leading-tight truncate">{{ __('landing.nav.brand') }}</h2>
            <p class="text-[11px] text-white/70 font-medium mt-0.5">{{ $isRtl ? 'لوحة الطالب' : 'Student panel' }}</p>
        </div>
    </div>

    <div class="px-3 py-3 flex-shrink-0">
        <div class="rounded-2xl border border-[#E8EEF8] dark:border-gray-700 bg-[#F4F7FC] dark:bg-gray-800/80 p-3">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-[11px] font-bold text-[#5B6577] dark:text-gray-400">{{ $isRtl ? 'مواعيد هذا الأسبوع' : 'This week' }}</span>
                <span class="text-sm font-black text-[#1E4E8C] dark:text-blue-300 tabular-nums">{{ $weekAppts }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-1">
                <a href="{{ route('dashboard') }}" class="rounded-xl bg-white dark:bg-gray-900 border border-[#E8EEF8] dark:border-gray-700 px-2.5 py-2 text-center hover:border-[#C9952A]/50 transition-colors">
                    <p class="text-lg font-black text-[#C9952A] tabular-nums leading-none"><i class="fas fa-calendar-week text-sm"></i></p>
                    <p class="text-[10px] font-bold text-[#8A94A6] mt-1">{{ $isRtl ? 'جدولي' : 'Schedule' }}</p>
                </a>
                <a href="{{ Route::has('student.service-entitlements.index') ? route('student.service-entitlements.index') : route('dashboard') }}"
                   class="rounded-xl bg-white dark:bg-gray-900 border border-[#E8EEF8] dark:border-gray-700 px-2.5 py-2 text-center hover:border-[#1E4E8C]/30 transition-colors">
                    <p class="text-lg font-black text-[#1E4E8C] dark:text-blue-300 tabular-nums leading-none">{{ $creditUnits }}</p>
                    <p class="text-[10px] font-bold text-[#8A94A6] mt-1">{{ $isRtl ? 'رصيد حصص' : 'Credits' }}</p>
                </a>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto sidebar-scroll px-0 py-1 space-y-0.5 min-h-0">
        @if($isStudent || ($user && $user->hasAnyPermission('student.view.courses', 'student.view.my-courses', 'student.view.orders', 'student.view.invoices', 'student.view.wallet', 'student.view.certificates', 'student.view.achievements', 'student.view.exams', 'student.view.calendar', 'student.view.notifications', 'student.view.profile', 'student.view.settings')))

            <div class="ins-nav-group">
                <span><i class="fas fa-home text-[9px] opacity-50"></i> {{ $isRtl ? 'لوحة حصتك' : 'Hesetak panel' }}</span>
            </div>
            <a href="{{ route('dashboard') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-home"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'الرئيسية' : 'Home' }}</span>
            </a>

            <div class="ins-nav-group mt-2">
                <span><i class="fas fa-chalkboard-teacher text-[9px] opacity-50"></i> {{ $isRtl ? 'تعلّمي' : 'Learning' }}</span>
            </div>

            @if(student_ui('show_private_lessons', true) && Route::has('student.private-lectures.index'))
            <a href="{{ route('student.private-lectures.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.private-lectures.*') || request()->routeIs('student.one-to-one-sessions.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-chalkboard-teacher"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'حصصي الخاصة' : 'Private lessons' }}</span>
            </a>
            @endif

            @if(student_ui('show_entitlements', true) && Route::has('student.service-entitlements.index'))
            <a href="{{ route('student.service-entitlements.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.service-entitlements.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-coins"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'رصيد الحصص' : 'Session credits' }}</span>
            </a>
            @endif

            @if(student_ui('show_courses', false) && Route::has('my-courses.index'))
            <div class="ins-nav-group mt-2">
                <span><i class="fas fa-graduation-cap text-[9px] opacity-50"></i> {{ $isRtl ? 'الكورسات المسجّلة' : 'Recorded courses' }}</span>
            </div>
            <a href="{{ route('my-courses.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('my-courses.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-bookmark"></i></span>
                <span class="flex-1 truncate">{{ __('student.my_courses') }}</span>
            </a>
            @if(Route::has('public.courses'))
            <a href="{{ route('public.courses') }}" @click="{{ $closeSidebar }}" class="ins-nav">
                <span class="ins-icon"><i class="fas fa-compass"></i></span>
                <span class="flex-1 truncate">{{ __('student.browse_courses') }}</span>
            </a>
            @endif
            @if(Route::has('student.lectures.index'))
            <a href="{{ route('student.lectures.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.lectures.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-chalkboard"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'المحاضرات' : 'Lectures' }}</span>
            </a>
            @endif
            @if(student_ui('show_assignments', false) && Route::has('student.assignments.index'))
            <a href="{{ route('student.assignments.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-tasks"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'الواجبات' : 'Assignments' }}</span>
            </a>
            @endif
            @if(student_ui('show_exams', false) && Route::has('student.exams.index'))
            <a href="{{ route('student.exams.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.exams.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-file-alt"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'الاختبارات' : 'Exams' }}</span>
            </a>
            @endif
            @endif

            @if(Route::has('public.instructors.index'))
            <a href="{{ route('public.instructors.index') }}" @click="{{ $closeSidebar }}" class="ins-nav">
                <span class="ins-icon"><i class="fas fa-user-graduate"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</span>
            </a>
            @endif

            <div class="ins-nav-group mt-2">
                <span><i class="fas fa-wallet text-[9px] opacity-50"></i> {{ $isRtl ? 'المالية' : 'Billing' }}</span>
            </div>

            @if(student_ui('show_wallet', false) && Route::has('student.wallet.index'))
            <a href="{{ route('student.wallet.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.wallet.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-wallet"></i></span>
                <span class="flex-1 truncate">{{ __('student.wallet') }}</span>
            </a>
            @endif

            @if(student_ui('show_orders', true) && Route::has('orders.index'))
            <a href="{{ route('orders.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-receipt"></i></span>
                <span class="flex-1 truncate">{{ __('student.orders') }}</span>
            </a>
            @endif

            @if(student_ui('show_invoices', false) && Route::has('student.invoices.index'))
            <a href="{{ route('student.invoices.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.invoices.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                <span class="flex-1 truncate">{{ __('student.invoices') }}</span>
            </a>
            @endif

            @if(student_ui('show_certificates', false) && Route::has('student.certificates.index'))
            <a href="{{ route('student.certificates.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.certificates.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-award"></i></span>
                <span class="flex-1 truncate">{{ __('student.certificates') }}</span>
            </a>
            @endif

            <div class="ins-nav-group mt-2">
                <span><i class="fas fa-user text-[9px] opacity-50"></i> {{ $isRtl ? 'الحساب' : 'Account' }}</span>
            </div>

            @if(student_ui('show_support', true) && Route::has('student.support.index'))
            <a href="{{ route('student.support.index') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('student.support.*') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-headset"></i></span>
                <span class="flex-1 truncate">{{ $isRtl ? 'الدعم' : 'Support' }}</span>
            </a>
            @endif

            @if(student_ui('show_notifications', true))
            <a href="{{ route('notifications') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('notifications') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-bell"></i></span>
                <span class="flex-1 truncate">{{ __('student.notifications') }}</span>
            </a>
            @endif

            @if(student_ui('show_profile', true))
            <a href="{{ route('profile') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('profile') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-user"></i></span>
                <span class="flex-1 truncate">{{ __('student.profile') }}</span>
            </a>
            @endif

            @if(student_ui('show_settings', true))
            <a href="{{ route('settings') }}" @click="{{ $closeSidebar }}"
               class="ins-nav {{ request()->routeIs('settings') ? 'active' : '' }}">
                <span class="ins-icon"><i class="fas fa-cog"></i></span>
                <span class="flex-1 truncate">{{ __('student.settings') }}</span>
            </a>
            @endif
        @endif

        @if($user && ($user->isAdmin() || $user->isInstructor()))
            <div class="ins-nav-group mt-2">
                <span><i class="fas fa-exchange-alt text-[9px] opacity-50"></i> {{ $isRtl ? 'لوحة أخرى' : 'Other panel' }}</span>
            </div>
            @if($user->isAdmin())
                <a href="{{ route('admin.dashboard') }}" @click="{{ $closeSidebar }}" class="ins-nav">
                    <span class="ins-icon"><i class="fas fa-shield-alt"></i></span>
                    <span class="flex-1 truncate">{{ __('student.admin_panel') }}</span>
                </a>
            @endif
        @endif
    </nav>

    <div class="px-3 py-3 flex-shrink-0 border-t border-[#E8EEF8] dark:border-gray-700/80">
        <div class="ins-user-card flex items-center gap-3">
            <div class="u-avatar flex-shrink-0 w-10 h-10 rounded-xl">
                @if($user?->profile_image)
                    <img src="{{ $user->profile_image_url }}" alt="" class="w-full h-full object-cover rounded-xl">
                @else
                    {{ mb_substr($user?->name ?? 'U', 0, 1) }}
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate leading-tight">{{ $user?->name }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ __('student.student_role') }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                @csrf
                <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-500 dark:text-red-400 flex items-center justify-center transition-colors" title="{{ $isRtl ? 'تسجيل الخروج' : 'Log out' }}">
                    <i class="fas fa-sign-out-alt text-xs"></i>
                </button>
            </form>
        </div>
    </div>
</div>
