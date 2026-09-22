@php
    $figma = asset('images/instructor-panel/figma');
    $user = auth()->user();
    $avatarUrl = method_exists($user, 'avatarDisplayUrl')
        ? $user->avatarDisplayUrl()
        : ($user?->profile_image_url ?? $figma.'/avatar.png');
    $switchLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $switchLabel = $switchLocale === 'ar' ? 'عربي' : 'EN';
    $bookingsUrl = Route::has('instructor.one-to-one-sessions.index')
        ? route('instructor.one-to-one-sessions.index')
        : route('dashboard');
    $calendarUrl = Route::has('instructor.calendar')
        ? route('instructor.calendar')
        : route('dashboard');
    $profileUrl = Route::has('instructor.profile')
        ? route('instructor.profile')
        : route('dashboard');
    $notifUrl = Route::has('instructor.notifications.index')
        ? route('instructor.notifications.index')
        : (Route::has('notifications') ? route('notifications') : '#');
@endphp

<header class="ip-topbar">
    <div class="ip-topbar__start">
        <button type="button" class="su-icon-btn ip-menu-btn" @click="sidebarOpen = !sidebarOpen" aria-label="{{ __('instructor.menu') }}">
            <img src="{{ $figma }}/menu.svg" alt="" class="cd-ico cd-ico--20" width="20" height="20">
        </button>

        <nav class="ip-topbar__tabs" aria-label="{{ __('instructor.dashboards') }}">
            <a href="{{ route('dashboard') }}" class="ip-topbar__tab {{ request()->routeIs('dashboard') ? 'is-on' : '' }}">
                {{ __('instructor.overview') }}
            </a>
            <a href="{{ $bookingsUrl }}" class="ip-topbar__tab {{ request()->routeIs('instructor.one-to-one-sessions.*') ? 'is-on' : '' }}">
                {{ __('instructor.private_lessons') }}
            </a>
            <a href="{{ $calendarUrl }}" class="ip-topbar__tab {{ request()->routeIs('instructor.calendar*') ? 'is-on' : '' }}">
                {{ __('instructor.my_calendar') }}
            </a>
            <span class="ip-topbar__divider" aria-hidden="true"></span>
            <button type="button" class="su-icon-btn" title="{{ __('instructor.search_placeholder') }}" aria-label="{{ __('instructor.search_placeholder') }}">
                <img src="{{ $figma }}/search.svg" alt="" class="cd-ico cd-ico--24" width="24" height="24">
            </button>
        </nav>
    </div>

    <div class="ip-topbar__end">
        <a href="{{ url()->current() }}?lang={{ $switchLocale }}" class="su-lang" title="{{ __('instructor.switch_language') }}">
            <span>{{ $switchLabel }}</span>
        </a>

        <div x-data="themeManager()" x-init="init()">
            <button type="button" class="su-icon-btn" @click="toggle()" :title="dark ? '{{ __('instructor.light_mode') }}' : '{{ __('instructor.dark_mode') }}'">
                <img src="{{ $figma }}/moon.svg" alt="" class="cd-ico" width="14" height="14" x-show="!dark">
                <i class="fas fa-sun text-sm" x-show="dark" x-cloak style="color:var(--su-ink)"></i>
            </button>
        </div>

        <a href="{{ $notifUrl }}" class="su-icon-btn" title="{{ __('instructor.notifications') }}">
            <i class="fas fa-bell text-sm" style="color:var(--su-ink)"></i>
        </a>

        <a href="{{ $profileUrl }}" class="ip-topbar__profile" title="{{ __('instructor.profile') }}">
            <span class="ip-topbar__name">{{ $user?->name }}</span>
            <img src="{{ $avatarUrl }}" alt="" class="ip-topbar__avatar" width="44" height="44">
            <img src="{{ $figma }}/chevron-down.svg" alt="" class="cd-ico" width="10" height="10">
        </a>
    </div>
</header>
