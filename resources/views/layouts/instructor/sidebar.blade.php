@php
    $user = auth()->user();
    $isInstructor = $user && ($user->isInstructor() || $user->isTeacher() || in_array(strtolower((string) $user->role), ['teacher', 'instructor'], true));
    $closeSidebar = 'if (isNarrow) setTimeout(() => { sidebarOpen = false }, 50)';
    $figma = asset('images/instructor-panel/figma');
    $brandLogo = \App\Services\AdminPanelBranding::logoPublicUrl();
    $avatarUrl = method_exists($user, 'avatarDisplayUrl')
        ? $user->avatarDisplayUrl()
        : ($user->profile_image_url ?? asset('images/instructor-panel/figma/avatar.png'));

    $teachingCourseIds = $user->teachingAdvancedCourseIds();
    $myCoursesCount = $teachingCourseIds->count();
    $showCourses = instructor_ui('show_courses', false);
    $hasTeachingCourses = $showCourses && $myCoursesCount > 0;
    $canAccessCurriculumLibrary = instructor_ui('show_libraries', true) && $user->isAcademyWorkingInstructor();
    $totalStudents = (! $showCourses || $teachingCourseIds->isEmpty())
        ? 0
        : \App\Models\StudentCourseEnrollment::whereIn('advanced_course_id', $teachingCourseIds)->where('status', 'active')->distinct('user_id')->count('user_id');

    $tbUpcoming = 0;
    if (\Illuminate\Support\Facades\Schema::hasTable('tutoring_group_bookings')) {
        $tbUpcoming = \App\Models\TutoringGroupBooking::where('instructor_id', $user->id)
            ->where('status', 'confirmed')
            ->where('starts_at', '>=', now())
            ->count();
    }
    $liveCount = 0;
    try {
        $liveCount = \App\Models\LiveSession::where('instructor_id', $user->id)->where('status', 'live')->count();
    } catch (\Throwable $e) {
    }

    $bookingsUrl = Route::has('instructor.one-to-one-sessions.index') ? route('instructor.one-to-one-sessions.index') : route('dashboard');
    $liveUrl = Route::has('instructor.live-sessions.index') ? route('instructor.live-sessions.index') : route('dashboard');
    $calendarUrl = Route::has('instructor.calendar') ? route('instructor.calendar') : route('dashboard');
    $messagesUrl = Route::has('instructor.private-messages.index') ? route('instructor.private-messages.index') : route('dashboard');
    $profileUrl = Route::has('instructor.profile') ? route('instructor.profile') : route('dashboard');
@endphp

{{-- Figma Community: 80px icon rail + expandable text nav --}}
<div class="cd-rail" aria-label="{{ __('instructor.menu') }}">
    <button type="button" class="cd-rail__menu" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="{{ __('instructor.menu') }}">
        <img src="{{ $figma }}/menu.svg" alt="" class="cd-ico cd-ico--20" width="20" height="20">
    </button>

    <div class="cd-rail__sep" aria-hidden="true"></div>

    <a href="{{ route('dashboard') }}" @click="{{ $closeSidebar }}" title="{{ config('app.name') }}">
        <img src="{{ $brandLogo }}" alt="" class="cd-rail__brand" width="40" height="40" onerror="this.src='{{ $figma }}/frame15.svg'">
    </a>

    <div class="cd-rail__cluster">
        <a href="{{ route('dashboard') }}" class="cd-rail__btn {{ request()->routeIs('dashboard') ? 'is-on' : '' }}" title="{{ __('instructor.overview') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/command.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
        <a href="{{ $bookingsUrl }}" class="cd-rail__btn {{ request()->routeIs('instructor.one-to-one-sessions.*') ? 'is-on' : '' }}" title="{{ __('instructor.private_lessons') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/pie.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
        <a href="{{ $calendarUrl }}" class="cd-rail__btn {{ request()->routeIs('instructor.calendar*') ? 'is-on' : '' }}" title="{{ __('instructor.my_calendar') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/clock.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
        <a href="{{ $liveUrl }}" class="cd-rail__btn {{ request()->routeIs('instructor.live-sessions.*') ? 'is-on' : '' }}" title="{{ __('instructor.live_broadcast') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/globe.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
        <a href="{{ $profileUrl }}" class="cd-rail__btn {{ request()->routeIs('instructor.profile*') ? 'is-on' : '' }}" title="{{ __('instructor.profile') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/loader.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
    </div>

    <div class="cd-rail__foot">
        <div class="cd-rail__sep" aria-hidden="true"></div>
        <a href="{{ $messagesUrl }}" class="cd-rail__btn {{ request()->routeIs('instructor.private-messages.*') ? 'is-on' : '' }}" title="{{ __('instructor.student_messages') }}" @click="{{ $closeSidebar }}">
            <img src="{{ $figma }}/message.svg" alt="" class="cd-ico" width="14" height="14">
        </a>
    </div>
</div>

<div class="cd-nav-panel su-nav-scroll">
    <div class="su-brand" style="padding:8px 4px 12px;gap:8px">
        <img src="{{ $avatarUrl }}" alt="" width="24" height="24" class="su-brand__avatar" style="width:24px;height:24px;border-radius:999px;object-fit:cover">
        <span class="su-brand__name">{{ config('app.name') }}</span>
    </div>

    @if($isInstructor || $user->hasAnyPermission('instructor.view.courses', 'instructor.manage.lectures', 'instructor.manage.assignments', 'instructor.manage.exams', 'instructor.manage.attendance', 'instructor.view.tasks'))
        <div class="su-sec">{{ __('instructor.dashboards') }}</div>

        <a href="{{ route('dashboard') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-chart-pie"></i></span>
            <span class="su-link__txt">{{ __('instructor.overview') }}</span>
        </a>
        @if($hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.view.courses')))
        <a href="{{ route('instructor.courses.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.courses.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-book-open"></i></span>
            <span class="su-link__txt">{{ __('instructor.my_courses') }}</span>
            <span class="su-link__badge">{{ $myCoursesCount }}</span>
        </a>
        @endif
        @if(instructor_ui('show_tutoring', false) && Route::has('instructor.tutoring-bookings.index'))
        <a href="{{ route('instructor.tutoring-bookings.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.tutoring-bookings.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-calendar-check"></i></span>
            <span class="su-link__txt">{{ __('instructor.group_bookings') }}</span>
            @if($tbUpcoming > 0)<span class="su-link__badge">{{ $tbUpcoming }}</span>@endif
        </a>
        @endif
        @if(instructor_ui('show_live_broadcast', true) && Route::has('instructor.live-sessions.index'))
        <a href="{{ route('instructor.live-sessions.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.live-sessions.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-broadcast-tower"></i></span>
            <span class="su-link__txt">{{ __('instructor.live_broadcast') }}</span>
            @if($liveCount > 0)<span class="su-link__badge">{{ $liveCount }}</span>@endif
        </a>
        @endif

        <div class="su-sec">{{ __('instructor.nav_pages') }}</div>

        @if(instructor_ui('show_group_classes', false) && Route::has('instructor.tutoring-cohorts.index'))
        <a href="{{ route('instructor.tutoring-cohorts.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.tutoring-cohorts.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-layer-group"></i></span>
            <span class="su-link__txt">{{ __('instructor.class_command') }}</span>
        </a>
        @endif
        @if(Route::has('instructor.private-messages.index'))
        <a href="{{ route('instructor.private-messages.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.private-messages.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-comments"></i></span>
            <span class="su-link__txt">{{ __('instructor.student_messages') }}</span>
        </a>
        @endif
        @if(Route::has('instructor.notifications.index'))
        <a href="{{ route('instructor.notifications.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.notifications.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-bell"></i></span>
            <span class="su-link__txt">{{ __('instructor.notifications') }}</span>
        </a>
        @endif
        @if(instructor_ui('show_group_classes', false) && Route::has('instructor.tutor-work-schedule.index'))
        <a href="{{ route('instructor.tutor-work-schedule.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.tutor-work-schedule.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-users"></i></span>
            <span class="su-link__txt">{{ __('instructor.group_work_schedule') }}</span>
        </a>
        @endif
        @if(Route::has('instructor.one-to-one-sessions.index'))
        <a href="{{ route('instructor.one-to-one-sessions.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.one-to-one-sessions.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-chalkboard-teacher"></i></span>
            <span class="su-link__txt">{{ __('instructor.private_lessons') }}</span>
        </a>
        @endif
        @if(Route::has('instructor.one-to-one-availability.index'))
        <a href="{{ route('instructor.one-to-one-availability.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.one-to-one-availability.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-calendar-week"></i></span>
            <span class="su-link__txt">{{ __('student.one_to_one_availability_title') }}</span>
        </a>
        @endif
        @if(Route::has('instructor.consultations.index'))
        <a href="{{ route('instructor.consultations.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.consultations.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-comments-dollar"></i></span>
            <span class="su-link__txt">{{ __('instructor.student_consultations') }}</span>
        </a>
        @endif
        @if($canAccessCurriculumLibrary && Route::has('instructor.libraries.curriculum.index'))
        <a href="{{ route('instructor.libraries.curriculum.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.libraries.curriculum.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-sitemap"></i></span>
            <span class="su-link__txt">{{ __('instructor.curriculum_library') }}</span>
        </a>
        @endif
        @if($canAccessCurriculumLibrary && Route::has('instructor.libraries.materials.index'))
        <a href="{{ route('instructor.libraries.materials.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.libraries.materials.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-folder-open"></i></span>
            <span class="su-link__txt">{{ __('instructor.materials_library') }}</span>
        </a>
        @endif
        @if($canAccessCurriculumLibrary && Route::has('instructor.libraries.videos.index'))
        <a href="{{ route('instructor.libraries.videos.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.libraries.videos.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-film"></i></span>
            <span class="su-link__txt">{{ __('instructor.videos_for_students') }}</span>
        </a>
        @endif
        @if($showCourses && Route::has('instructor.lecture-recordings.index'))
        <a href="{{ route('instructor.lecture-recordings.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.lecture-recordings.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-video"></i></span>
            <span class="su-link__txt">{{ __('instructor.lecture_recordings') }}</span>
        </a>
        @endif
        @if($hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.manage.lectures')))
        <a href="{{ route('instructor.lectures.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.lectures.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-chalkboard"></i></span>
            <span class="su-link__txt">{{ __('instructor.lectures') }}</span>
        </a>
        @endif
        @if($showCourses && ($isInstructor || $user->hasPermission('instructor.manage.assignments')))
        <a href="{{ route('instructor.assignments.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.assignments.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-tasks"></i></span>
            <span class="su-link__txt">{{ __('instructor.assignments') }}</span>
        </a>
        @endif
        @if($showCourses && ($isInstructor || $user->hasPermission('instructor.manage.exams')))
        <a href="{{ route('instructor.exams.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.exams.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-clipboard-check"></i></span>
            <span class="su-link__txt">{{ __('instructor.exams') }}</span>
        </a>
        @endif
        @if($showCourses && $isInstructor)
        <a href="{{ route('instructor.question-banks.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.question-banks.*') || request()->routeIs('instructor.questions.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-database"></i></span>
            <span class="su-link__txt">{{ __('instructor.question_banks') }}</span>
        </a>
        @endif
        @if($hasTeachingCourses && ($isInstructor || $user->hasPermission('instructor.manage.attendance')))
        <a href="{{ route('instructor.attendance.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.attendance.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-clipboard-list"></i></span>
            <span class="su-link__txt">{{ __('instructor.attendance') }}</span>
        </a>
        @endif
        @if($isInstructor || $user->hasPermission('instructor.view.tasks'))
        <a href="{{ route('instructor.tasks.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.tasks.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-check-square"></i></span>
            <span class="su-link__txt">{{ __('instructor.tasks_from_management') }}</span>
        </a>
        @endif
        @if(($isInstructor || $user->hasPermission('instructor.view.tasks')) && Route::has('instructor.management-requests.index'))
        <a href="{{ route('instructor.management-requests.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.management-requests.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-paper-plane"></i></span>
            <span class="su-link__txt">{{ __('instructor.submit_requests_to_management') }}</span>
        </a>
        @endif
        <a href="{{ route('instructor.agreements.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.agreements.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-handshake"></i></span>
            <span class="su-link__txt">{{ __('instructor.agreements_system') }}</span>
        </a>
        <a href="{{ route('instructor.transfer-account.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.transfer-account.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-university"></i></span>
            <span class="su-link__txt">{{ __('instructor.transfer_account') }}</span>
        </a>
        <a href="{{ route('instructor.withdrawals.index') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.withdrawals.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-money-bill-wave"></i></span>
            <span class="su-link__txt">{{ __('instructor.withdrawal_requests') }}</span>
        </a>
        <a href="{{ route('instructor.personal-branding.edit') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.personal-branding.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-user-tie"></i></span>
            <span class="su-link__txt">{{ __('instructor.personal_branding') }}</span>
        </a>
    @endif

    <div class="su-sec">{{ __('instructor.nav_account') }}</div>
    @if($user->isAdmin())
        <a href="{{ route('admin.dashboard') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('admin.*') ? 'is-active' : '' }}">
            <span class="su-link__ico"><i class="fas fa-shield-alt"></i></span>
            <span class="su-link__txt">{{ __('instructor.admin_panel') }}</span>
        </a>
    @endif
    <a href="{{ route('instructor.profile') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('instructor.profile*') ? 'is-active' : '' }}">
        <span class="su-link__ico"><i class="fas fa-user"></i></span>
        <span class="su-link__txt">{{ __('instructor.profile') }}</span>
        @if($totalStudents > 0)<span class="su-link__badge">{{ $totalStudents }}</span>@endif
    </a>
    @if($user->hasPermission('student.view.settings'))
    <a href="{{ route('settings') }}" @click="{{ $closeSidebar }}" class="su-link {{ request()->routeIs('settings') ? 'is-active' : '' }}">
        <span class="su-link__ico"><i class="fas fa-cog"></i></span>
        <span class="su-link__txt">{{ __('instructor.settings') }}</span>
    </a>
    @endif
    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <button type="submit" class="su-link" style="width:100%;border:0;background:transparent;cursor:pointer;text-align:inherit;font:inherit">
            <span class="su-link__ico"><i class="fas fa-sign-out-alt"></i></span>
            <span class="su-link__txt">{{ __('instructor.logout') }}</span>
        </button>
    </form>
</div>
