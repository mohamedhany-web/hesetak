{{-- Instructor frame: same CX as student-timeline (st-*) — full instructor nav --}}
@php
    $appLocale = app()->getLocale();
    $appRtl = $appLocale === 'ar';
    $user = auth()->user();
    $firstName = explode(' ', trim((string) ($user?->name ?? '')))[0] ?? '';
    $avatarUrl = method_exists($user, 'avatarDisplayUrl')
        ? $user->avatarDisplayUrl()
        : ($user?->profile_image_url ?? \App\Models\User::placeholderAvatarUrl());
    $brandLogoUrl = \App\Services\AdminPanelBranding::logoPublicUrl();
    $brandLogoFallback = \App\Services\AdminPanelBranding::inlineFallbackDataUri();

    $isInstructor = $user && ($user->isInstructor() || $user->isTeacher() || in_array(strtolower((string) $user->role), ['teacher', 'instructor'], true));
    $showCourses = instructor_ui('show_courses', false);
    $teachingCourseIds = $user ? $user->teachingAdvancedCourseIds() : collect();
    $myCoursesCount = $teachingCourseIds->count();
    $hasTeachingCourses = $showCourses && $myCoursesCount > 0;
    $canAccessCurriculumLibrary = instructor_ui('show_libraries', true) && $user && $user->isAcademyWorkingInstructor();

    $navItems = [
        ['route' => 'dashboard', 'match' => ['dashboard'], 'label' => __('instructor.overview'), 'fa' => 'fas fa-home'],
        ['route' => 'instructor.courses.index', 'match' => ['instructor.courses.*'], 'label' => __('instructor.my_courses'), 'fa' => 'fas fa-book-open', 'when' => $hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.view.courses'))],
        ['route' => 'instructor.one-to-one-sessions.index', 'match' => ['instructor.one-to-one-sessions.*'], 'label' => __('instructor.private_lessons'), 'fa' => 'fas fa-chalkboard-teacher'],
        ['route' => 'instructor.one-to-one-availability.index', 'match' => ['instructor.one-to-one-availability.*'], 'label' => __('student.one_to_one_availability_title'), 'fa' => 'fas fa-calendar-week'],
        ['route' => 'instructor.free-trial-bookings.index', 'match' => ['instructor.free-trial-bookings.*'], 'label' => app()->getLocale() === 'ar' ? 'حصص مجانية' : 'Free sessions', 'fa' => 'fas fa-gift'],
        ['route' => 'instructor.calendar', 'match' => ['instructor.calendar', 'instructor.calendar.events'], 'label' => __('instructor.my_calendar'), 'fa' => 'fas fa-calendar-alt'],
        ['route' => 'instructor.live-sessions.index', 'match' => ['instructor.live-sessions.*'], 'label' => __('instructor.live_broadcast'), 'fa' => 'fas fa-broadcast-tower', 'when' => instructor_ui('show_live_broadcast', true)],
        ['route' => 'instructor.tutoring-bookings.index', 'match' => ['instructor.tutoring-bookings.*'], 'label' => __('instructor.group_bookings'), 'fa' => 'fas fa-calendar-check', 'when' => instructor_ui('show_tutoring', false)],
        ['route' => 'instructor.tutoring-cohorts.index', 'match' => ['instructor.tutoring-cohorts.*'], 'label' => __('instructor.class_command'), 'fa' => 'fas fa-layer-group', 'when' => instructor_ui('show_group_classes', false)],
        ['route' => 'instructor.tutor-work-schedule.index', 'match' => ['instructor.tutor-work-schedule.*'], 'label' => __('instructor.group_work_schedule'), 'fa' => 'fas fa-users', 'when' => instructor_ui('show_group_classes', false)],
        ['route' => 'instructor.private-messages.index', 'match' => ['instructor.private-messages.*'], 'label' => __('instructor.student_messages'), 'fa' => 'fas fa-comments'],
        ['route' => 'instructor.notifications.index', 'match' => ['instructor.notifications.*'], 'label' => __('instructor.notifications'), 'fa' => 'fas fa-bell'],
        ['route' => 'instructor.consultations.index', 'match' => ['instructor.consultations.*'], 'label' => __('instructor.student_consultations'), 'fa' => 'fas fa-comments-dollar'],
        ['route' => 'instructor.libraries.curriculum.index', 'match' => ['instructor.libraries.curriculum.*'], 'label' => __('instructor.curriculum_library'), 'fa' => 'fas fa-sitemap', 'when' => $canAccessCurriculumLibrary],
        ['route' => 'instructor.libraries.materials.index', 'match' => ['instructor.libraries.materials.*'], 'label' => __('instructor.materials_library'), 'fa' => 'fas fa-folder-open', 'when' => $canAccessCurriculumLibrary],
        ['route' => 'instructor.libraries.videos.index', 'match' => ['instructor.libraries.videos.*'], 'label' => __('instructor.videos_for_students'), 'fa' => 'fas fa-film', 'when' => $canAccessCurriculumLibrary],
        ['route' => 'instructor.lecture-recordings.index', 'match' => ['instructor.lecture-recordings.*'], 'label' => __('instructor.lecture_recordings'), 'fa' => 'fas fa-video', 'when' => $showCourses],
        ['route' => 'instructor.lectures.index', 'match' => ['instructor.lectures.*'], 'label' => __('instructor.lectures'), 'fa' => 'fas fa-chalkboard', 'when' => $hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.manage.lectures'))],
        ['route' => 'instructor.assignments.index', 'match' => ['instructor.assignments.*'], 'label' => __('instructor.assignments'), 'fa' => 'fas fa-tasks', 'when' => $showCourses && ($isInstructor || $user->hasPermission('instructor.manage.assignments'))],
        ['route' => 'instructor.exams.index', 'match' => ['instructor.exams.*'], 'label' => __('instructor.exams'), 'fa' => 'fas fa-clipboard-check', 'when' => $showCourses && ($isInstructor || $user->hasPermission('instructor.manage.exams'))],
        ['route' => 'instructor.question-banks.index', 'match' => ['instructor.question-banks.*', 'instructor.questions.*'], 'label' => __('instructor.question_banks'), 'fa' => 'fas fa-database', 'when' => $showCourses && $isInstructor],
        ['route' => 'instructor.attendance.index', 'match' => ['instructor.attendance.*'], 'label' => __('instructor.attendance'), 'fa' => 'fas fa-clipboard-list', 'when' => $hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.manage.attendance'))],
        ['route' => 'instructor.tasks.index', 'match' => ['instructor.tasks.*'], 'label' => __('instructor.tasks_from_management'), 'fa' => 'fas fa-check-square', 'when' => $isInstructor || $user->hasPermission('instructor.view.tasks')],
        ['route' => 'instructor.management-requests.index', 'match' => ['instructor.management-requests.*'], 'label' => __('instructor.submit_requests_to_management'), 'fa' => 'fas fa-paper-plane', 'when' => $isInstructor || $user->hasPermission('instructor.view.tasks')],
        ['route' => 'instructor.agreements.index', 'match' => ['instructor.agreements.*'], 'label' => __('instructor.agreements_system'), 'fa' => 'fas fa-handshake'],
        ['route' => 'instructor.transfer-account.index', 'match' => ['instructor.transfer-account.*'], 'label' => __('instructor.transfer_account'), 'fa' => 'fas fa-university'],
        ['route' => 'instructor.withdrawals.index', 'match' => ['instructor.withdrawals.*'], 'label' => __('instructor.withdrawal_requests'), 'fa' => 'fas fa-money-bill-wave'],
        ['route' => 'instructor.personal-branding.edit', 'match' => ['instructor.personal-branding.*'], 'label' => __('instructor.personal_branding'), 'fa' => 'fas fa-user-tie'],
        ['route' => 'admin.dashboard', 'match' => ['admin.*'], 'label' => __('instructor.admin_panel'), 'fa' => 'fas fa-shield-alt', 'when' => $user && $user->isAdmin()],
        ['route' => 'instructor.profile', 'match' => ['instructor.profile*'], 'label' => __('instructor.profile'), 'fa' => 'fas fa-user'],
        ['route' => 'settings', 'match' => ['settings'], 'label' => __('instructor.settings'), 'fa' => 'fas fa-cog', 'when' => $user && $user->hasPermission('student.view.settings')],
    ];
@endphp

<div class="st-shell{{ $appRtl ? ' is-rtl' : ' is-ltr' }} is-wide" id="stShell">
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

    <button type="button" class="st-rail-backdrop" id="stRailBackdrop" aria-label="{{ __('instructor.close_menu') }}" tabindex="-1"></button>

    <aside class="st-rail" id="stRail" aria-label="{{ __('instructor.overview') }}">
        <div class="st-rail__head">
            <a href="{{ route('dashboard') }}" class="st-rail__brand" title="{{ config('app.name') }}">
                <span class="st-rail__mark">
                    <img src="{{ $brandLogoUrl }}" alt="{{ config('app.name') }}" class="st-rail__logo" width="40" height="40" loading="eager" decoding="async" onerror="this.onerror=null;this.src='{{ $brandLogoFallback }}';">
                </span>
                <span class="st-rail__brand-name">{{ config('app.name') }}</span>
            </a>
            <button type="button" class="st-rail__toggle" id="stRailToggle" aria-expanded="false" aria-controls="stRail" title="{{ __('instructor.menu') }}">
                <i class="fas fa-angles-{{ $appRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                <span class="st-rail__toggle-text">{{ __('instructor.menu') }}</span>
            </button>
        </div>

        <a href="{{ route('instructor.profile') }}" class="st-rail__profile" title="{{ __('instructor.profile') }}">
            <img src="{{ $avatarUrl }}" alt="" class="st-rail__avatar" width="40" height="40">
            <div class="st-rail__who">
                <span class="st-rail__name">{{ $firstName }}</span>
                <span class="st-rail__role">{{ __('instructor.instructor_panel') }}</span>
            </div>
        </a>

        <nav class="st-rail__nav">
            @foreach($navItems as $item)
                @php
                    if (! Route::has($item['route'])) {
                        continue;
                    }
                    if (array_key_exists('when', $item) && ! $item['when']) {
                        continue;
                    }
                    $href = route($item['route']);
                    $active = request()->routeIs(...$item['match']);
                @endphp
                <a href="{{ $href }}" class="st-rail__link {{ $active ? 'is-active' : '' }}" title="{{ $item['label'] }}" aria-label="{{ $item['label'] }}">
                    <span class="st-rail__icon-box">
                        <i class="{{ $item['fa'] }}" aria-hidden="true"></i>
                    </span>
                    <span class="st-rail__label">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <form method="POST" action="{{ route('logout') }}" class="st-rail__logout" style="margin:8px 10px 0">
                @csrf
                <button type="submit" class="st-rail__link" style="width:100%;border:0;background:transparent;cursor:pointer;font:inherit;color:inherit">
                    <span class="st-rail__icon-box"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></span>
                    <span class="st-rail__label">{{ __('instructor.logout') }}</span>
                </button>
            </form>
        </nav>

        <div class="st-rail__foot" hidden aria-hidden="true"></div>
    </aside>

    <main class="st-main">
        @php
            $instPageTitle = trim($__env->yieldContent('page_title'));
            if ($instPageTitle === '') {
                $instPageTitle = trim($__env->yieldContent('title'));
            }
            if ($instPageTitle === '') {
                $instPageTitle = __('instructor.instructor_panel');
            }
        @endphp
        @unless(request()->routeIs('dashboard'))
            @include('partials.student-timeline-top', [
                'locale' => $appLocale,
                'pageTitle' => $instPageTitle,
                'crumbs' => [
                    ['label' => __('instructor.instructor_panel'), 'url' => route('dashboard')],
                    ['label' => $instPageTitle, 'url' => null],
                ],
            ])
        @endunless

        @if(session('success'))
            <div class="st-flash st-flash--ok">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="st-flash st-flash--ok">{{ session('info') }}</div>
        @endif
        @if(session('error'))
            <div class="st-flash st-flash--err">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>

<script>
(function () {
    var shell = document.getElementById('stShell');
    var btn = document.getElementById('stRailToggle');
    var backdrop = document.getElementById('stRailBackdrop');
    if (!shell || !btn) return;

    var isRtl = document.documentElement.getAttribute('dir') === 'rtl';
    var openLabel = @json(__('instructor.menu'));
    var closeLabel = @json(__('instructor.close_menu'));
    var mq = window.matchMedia('(max-width: 768px)');

    function isMobile() { return mq.matches; }
    function isOpen() { return shell.classList.contains('is-rail-open'); }

    function syncToggleIcon(open) {
        var icon = btn.querySelector('i');
        var text = btn.querySelector('.st-rail__toggle-text');
        if (icon) {
            if (isMobile()) {
                icon.className = open ? 'fas fa-xmark' : 'fas fa-bars';
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
            if (topIcon && isMobile()) topIcon.className = open ? 'fas fa-xmark' : 'fas fa-bars';
            topMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    function setOpen(open, persist) {
        shell.classList.toggle('is-rail-open', open);
        document.documentElement.classList.toggle('st-rail-pref-open', open && !isMobile());
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        syncToggleIcon(open);
        document.documentElement.classList.toggle('st-rail-lock', open && isMobile());
        document.body.classList.toggle('st-rail-lock', open && isMobile());
        if (persist !== false && !isMobile()) {
            try { localStorage.setItem('st-rail-open', open ? '1' : '0'); } catch (e) {}
        }
    }

    btn.addEventListener('click', function () { setOpen(!isOpen(), true); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false, true); });
    document.addEventListener('click', function (e) {
        var topMenu = e.target.closest('#stTopMenu');
        if (topMenu) { e.preventDefault(); setOpen(!isOpen(), true); }
    });
    shell.addEventListener('click', function (e) {
        var link = e.target.closest('.st-rail a[href]');
        if (link && isMobile()) setOpen(false, false);
    });
    mq.addEventListener('change', function () {
        if (isMobile()) setOpen(false, false);
        else {
            var prefer = false;
            try { prefer = localStorage.getItem('st-rail-open') === '1'; } catch (e) {}
            setOpen(prefer, false);
        }
    });
    syncToggleIcon(isOpen());
})();
</script>
