@extends('layouts.student-timeline')

@section('title', __('student_timeline.timeline'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $game = $game ?? [
        'xp' => 0,
        'level' => 1,
        'streak' => ['current' => 0],
        'daily_missions' => collect(),
        'weekly_missions' => collect(),
    ];
    $classes = $classes ?? collect();
    $weekDays = $weekDays ?? collect();
    $upcoming = $upcoming ?? collect();
    $todayItems = $todayItems ?? collect();
    $scheduleRows = $scheduleRows ?? collect();
    $viewMode = $viewMode ?? 'week';
    $sortMode = $sortMode ?? 'classes';
    $searchQuery = $searchQuery ?? '';
    $weekAnchor = ($weekAnchor ?? now())->locale($locale);
    $viewerTz = auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
    $weekAnchor = $weekAnchor->copy()->timezone($viewerTz);
    $cal = $weekAnchor->copy();
    $monthStart = $cal->copy()->startOfMonth()->startOfWeek(\Carbon\Carbon::SATURDAY);
    $monthEnd = $cal->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::FRIDAY);
    $calendarDays = collect();
    for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
        $calendarDays->push($d->copy());
    }
    $markedDates = $weekDays
        ->filter(fn ($day) => ($day->items ?? collect())->isNotEmpty())
        ->mapWithKeys(fn ($day) => [$day->date->toDateString() => true]);
    $weekdays = $isRtl
        ? ['س', 'ح', 'ن', 'ث', 'ر', 'خ', 'ج']
        : ['Sa', 'Su', 'Mo', 'Tu', 'We', 'Th', 'Fr'];
    $subjectTones = ['pink', 'blue', 'purple', 'orange'];
    $subjectIcons = [
        asset('img/student-timeline/sqrt.svg'),
        asset('img/student-timeline/earth.svg'),
        asset('img/student-timeline/clock.png'),
        asset('img/student-timeline/teacher.png'),
    ];
    $todoItems = collect($game['daily_missions'] ?? [])->take(3);
    $scheduleJoinUrl = function ($slot) {
        if (! empty($slot->join_url)) {
            return $slot->join_url;
        }
        if (isset($slot->type, $slot->ref_id) && Route::has('student.schedule.join')) {
            return route('student.schedule.join', ['type' => $slot->type, 'id' => $slot->ref_id]);
        }

        return null;
    };
    $brandLogoUrl = \App\Services\AdminPanelBranding::logoPublicUrl();
    $brandLogoFallback = \App\Services\AdminPanelBranding::inlineFallbackDataUri();
    if ($scheduleRows->isEmpty() && !empty($todayMission)) {
        $scheduleRows = collect([(object) [
            'starts_at' => $todayMission->starts_at,
            'title' => $todayMission->title,
            'join_url' => $todayMission->is_joinable ? $todayMission->join_url : ($todayMission->class_url ?: '#'),
        ]]);
    }
@endphp

<header class="st-top">
    <div class="st-top__row st-top__row--primary">
        <a href="{{ route('dashboard') }}" class="st-top__brand" title="{{ config('app.name') }}">
            <span class="st-top__brand-mark">
                <img src="{{ $brandLogoUrl }}" alt="{{ config('app.name') }}" width="36" height="36" loading="eager" decoding="async" onerror="this.onerror=null;this.src='{{ $brandLogoFallback }}';">
            </span>
        </a>

        <h1 class="st-top__title">{{ __('student_timeline.timeline') }}</h1>

        <div class="st-top__actions">
            <div class="st-lang" role="group" aria-label="Language">
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="{{ $locale === 'ar' ? 'is-active' : '' }}">{{ __('student_timeline.lang_ar') }}</a>
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ $locale === 'en' ? 'is-active' : '' }}">{{ __('student_timeline.lang_en') }}</a>
            </div>

            @include('partials.student-timeline-bell')

            <button type="button" class="st-top__menu" id="stTopMenu" aria-expanded="false" aria-controls="stRail" aria-label="{{ __('student_timeline.toggle_sidebar') }}">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="st-top__row st-top__row--secondary">
        <div class="st-datepill" aria-label="{{ $cal->translatedFormat('l, F j') }}">
            <a href="{{ $timelinePrevUrl ?? '#' }}" class="st-datepill__nav" aria-label="{{ $viewMode === 'day' ? __('student_timeline.prev_day') : __('student_timeline.prev_week') }}">
                <i class="fas {{ $isRtl ? 'fa-chevron-right' : 'fa-chevron-left' }}" aria-hidden="true"></i>
            </a>
            <a href="{{ $timelineTodayUrl ?? route('dashboard') }}" class="st-datepill__label">{{ $cal->translatedFormat($isRtl ? 'l، j F' : 'l, F j') }}</a>
            <a href="{{ $timelineNextUrl ?? '#' }}" class="st-datepill__nav" aria-label="{{ $viewMode === 'day' ? __('student_timeline.next_day') : __('student_timeline.next_week') }}">
                <i class="fas {{ $isRtl ? 'fa-chevron-left' : 'fa-chevron-right' }}" aria-hidden="true"></i>
            </a>
        </div>

        <div class="st-top__chips">
            <a href="{{ $timelineSortUrl ?? route('dashboard') }}" class="st-chip {{ $sortMode === 'progress' ? 'is-active' : '' }}" title="{{ $sortMode === 'progress' ? __('student_timeline.sort_by_progress') : __('student_timeline.sort_by_classes') }}">
                <img src="{{ asset('img/student-timeline/filter.svg') }}" alt="" width="14" height="14">
                {{ $sortMode === 'progress' ? __('student_timeline.sort_by_progress') : __('student_timeline.sort_by_classes') }}
            </a>
            <a href="{{ $timelineViewUrl ?? route('dashboard') }}" class="st-chip {{ $viewMode === 'week' ? 'is-active' : '' }}" title="{{ $viewMode === 'week' ? __('student_timeline.week_view') : __('student_timeline.day_view') }}">
                {{ $viewMode === 'week' ? __('student_timeline.week_view') : __('student_timeline.day_view') }}
            </a>
        </div>
    </div>

    <form class="st-search" method="get" action="{{ url()->current() }}" role="search">
        @if(request('week'))
            <input type="hidden" name="week" value="{{ request('week') }}">
        @endif
        @if($viewMode !== 'week')
            <input type="hidden" name="view" value="{{ $viewMode }}">
        @endif
        @if($sortMode !== 'classes')
            <input type="hidden" name="sort" value="{{ $sortMode }}">
        @endif
        @if(request('lang'))
            <input type="hidden" name="lang" value="{{ request('lang') }}">
        @endif
        <i class="fas fa-search text-[11px]"></i>
        <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('student_timeline.search') }}" aria-label="{{ __('student_timeline.search') }}">
    </form>
</header>

@php
    $firstName = explode(' ', trim((string) (auth()->user()->name ?? '')))[0] ?? '';
    $progress = $progress ?? ['percent' => 0, 'attended' => 0, 'completed_sessions' => 0, 'label' => ''];
    $credits = $credits ?? ['total_left' => 0];
    $trialUrl = route('home').'?open_trial=1';
    $exploreUrl = Route::has('public.instructors.index')
        ? route('public.instructors.index')
        : (Route::has('public.pricing') ? route('public.pricing') : route('dashboard'));
    $groupsUrl = $exploreUrl;
    $eventMasks = [
        asset('img/student-timeline/event-mask-1.svg'),
        asset('img/student-timeline/event-mask-2.svg'),
        asset('img/student-timeline/event-mask-3.svg'),
    ];
    $subjMask1 = asset('img/student-timeline/subj-mask-1.svg');
    $subjMask2 = asset('img/student-timeline/subj-mask-2.svg');
@endphp

{{-- Greeting — same event-card language --}}
<section class="st-event-card st-event-card--blue st-biz-banner">
    <img class="st-event-card__mask" src="{{ $eventMasks[1] }}" alt="" width="160" height="160">
    <div class="st-biz-banner__row">
        <div>
            <p class="st-event-card__kicker">{{ __('student_timeline.school_gate') }}</p>
            <h3>{{ $greeting ?? '' }}، {{ $firstName }}</h3>
            <p class="st-event-card__sub">
                @if(!empty($primaryClass))
                    {{ __('student_timeline.you_are_in') }}
                    {{ $primaryClass->title }}
                    @if($primaryClass->year_name) · {{ $primaryClass->year_name }} @endif
                @else
                    {{ __('student_timeline.hero_out') }}
                @endif
            </p>
        </div>
        <div class="st-biz-banner__actions">
            @if(!empty($todayMission) && $todayMission->is_joinable)
                <a href="{{ $todayMission->join_url }}" class="st-pill st-pill--light">{{ __('student_timeline.join_class_now') }}</a>
            @elseif(Route::has('student.private-lectures.index'))
                <a href="{{ route('student.private-lectures.index') }}" class="st-pill st-pill--light">{{ __('student_timeline.my_classes') }}</a>
            @elseif(Route::has('student.classes.index') && student_ui('show_classes'))
                <a href="{{ Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard') }}" class="st-pill st-pill--light">{{ __('student_timeline.my_classes') }}</a>
            @endif
            <a href="{{ $exploreUrl }}" class="st-pill st-pill--ghost">{{ __('student_timeline.explore_school') }}</a>
        </div>
    </div>
</section>

{{-- KPI — same subject-card language --}}
<section class="st-stats" aria-label="{{ __('student_timeline.school_gate') }}">
    <article class="st-subject st-subject--blue st-stat-card">
        <img class="st-subject__blob" src="{{ $subjMask1 }}" alt="" width="132" height="132">
        <p class="st-stat-card__label">{{ __('student_timeline.path_progress') }}</p>
        <p class="st-stat-card__value">{{ $progress['percent'] }}%</p>
        <div class="st-stat-card__bar"><span style="width: {{ $progress['percent'] }}%"></span></div>
        <p class="st-stat-card__hint">{{ $progress['label'] }}</p>
    </article>
    <article class="st-subject st-subject--pink st-stat-card">
        <img class="st-subject__blob" src="{{ $subjMask2 }}" alt="" width="132" height="132">
        <p class="st-stat-card__label">{{ __('student_timeline.attendance') }}</p>
        <p class="st-stat-card__value">{{ $progress['attended'] }}</p>
        <p class="st-stat-card__hint">{{ __('student_timeline.of_completed', ['count' => $progress['completed_sessions']]) }}</p>
    </article>
    <article class="st-subject st-subject--orange st-stat-card">
        <img class="st-subject__blob" src="{{ $subjMask1 }}" alt="" width="132" height="132">
        <p class="st-stat-card__label">{{ __('student_timeline.session_credits') }}</p>
        <p class="st-stat-card__value">{{ $credits['total_left'] }}</p>
        <p class="st-stat-card__hint">
            @if(Route::has('student.service-entitlements.index'))
                <a href="{{ route('student.service-entitlements.index') }}">{{ __('student_timeline.view_credits') }}</a>
            @else
                —
            @endif
        </p>
    </article>
    <article class="st-subject st-subject--purple st-stat-card">
        <img class="st-subject__blob" src="{{ $subjMask2 }}" alt="" width="132" height="132">
        <p class="st-stat-card__label">{{ __('student_timeline.active_classes') }}</p>
        <p class="st-stat-card__value">{{ $classes->count() }}</p>
        <p class="st-stat-card__hint">⭐ {{ number_format($game['xp'] ?? 0) }} · Lv {{ $game['level'] ?? 1 }} · 🔥 {{ $game['streak']['current'] ?? 0 }}</p>
    </article>
</section>

@if(!empty($todayMission))
    @php
        $missionUrl = $todayMission->is_joinable
            ? $todayMission->join_url
            : ($todayMission->class_url ?: '#');
    @endphp
    <a href="{{ $missionUrl }}" class="st-event-card st-event-card--green st-biz-banner">
        <img class="st-event-card__mask" src="{{ $eventMasks[0] }}" alt="" width="160" height="160">
        <div class="st-biz-banner__row">
            <div>
                <p class="st-event-card__kicker">
                    {{ $todayMission->is_today ? __('student_timeline.today_mission') : __('student_timeline.next_mission') }}
                </p>
                <h3>{{ $todayMission->title }}</h3>
                <p class="st-event-card__sub">
                    {{ $todayMission->subtitle }}
                    · <x-app-datetime :at="$todayMission->starts_at" pattern="D g:i A" />
                    · {{ $todayMission->duration_minutes }} {{ __('student_timeline.minutes') }}
                </p>
            </div>
            <span class="st-pill st-pill--light">
                {{ $todayMission->is_joinable ? __('student_timeline.continue_learning') : __('student_timeline.open_class') }}
            </span>
        </div>
    </a>
@endif

<section id="st-subjects">
    <div class="st-section-head">
        <div>
            <h2>{{ __('student_timeline.subjects') }}</h2>
            <p>{{ __('student_timeline.upcoming_classes') }}</p>
        </div>
        @if(Route::has('student.private-lectures.index'))
            <a class="st-see" href="{{ route('student.private-lectures.index') }}">{{ __('student_timeline.see_all') }}</a>
        @elseif(Route::has('student.classes.index') && student_ui('show_classes'))
            <a class="st-see" href="{{ Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard') }}">{{ __('student_timeline.see_all') }}</a>
        @endif
    </div>

    @if($classes->isNotEmpty())
        <div class="st-subjects">
            @foreach($classes->take(6) as $i => $class)
                @php
                    $tone = $subjectTones[$i % count($subjectTones)];
                    $icon = $subjectIcons[$i % count($subjectIcons)];
                    $mask = $i % 2 === 0
                        ? asset('img/student-timeline/subj-mask-1.svg')
                        : asset('img/student-timeline/subj-mask-2.svg');
                    $label = $class->subject_name ?: $class->title;
                @endphp
                <a href="{{ $class->url }}" class="st-subject st-subject--{{ $tone }}" title="{{ $class->title }} · {{ $class->progress_percent }}%">
                    <img class="st-subject__blob" src="{{ $mask }}" alt="" width="132" height="132">
                    <span class="st-subject__icon">
                        <img src="{{ $icon }}" alt="" width="22" height="22">
                    </span>
                    <img class="st-subject__more" src="{{ asset('img/student-timeline/ellipsis.svg') }}" alt="" width="24" height="24">
                    <div class="st-subject__foot">
                        <h3 class="st-subject__name">{{ $label }}</h3>
                        <span class="st-subject__meta">{{ $class->completed_sessions }}/{{ $class->total_sessions }} · {{ $class->progress_percent }}%</span>
                        <span class="st-subject__bar" aria-hidden="true"><i style="width: {{ max(4, (int) $class->progress_percent) }}%"></i></span>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="st-empty-row">
            <div class="st-subject st-subject--blue" style="min-width: 200px;">
                <img class="st-subject__blob" src="{{ asset('img/student-timeline/subj-mask-1.svg') }}" alt="" width="132" height="132">
                <h3 class="st-subject__name">{{ __('student_timeline.no_classes') }}</h3>
            </div>
            <div class="st-biz-banner__actions">
                @if(Route::has('public.instructors.index'))
                    <a href="{{ route('public.instructors.index') }}" class="st-pill st-pill--solid">{{ __('student_timeline.browse_teachers') }}</a>
                @endif
                <a href="{{ $trialUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.placement_test') }}</a>
                @if(Route::has('public.pricing'))
                    <a href="{{ route('public.pricing') }}" class="st-pill st-pill--outline">{{ __('student_timeline.browse_school') }}</a>
                @endif
            </div>
        </div>
    @endif
</section>

<section class="st-cal" aria-label="{{ __('student_timeline.calendar') }}">
    <div class="st-cal__todo">
        <div class="st-cal__todo-head">
            <div class="st-cal__daynum">{{ $cal->format('d') }}</div>
            <div class="st-cal__month">{{ $cal->translatedFormat('F') }}</div>
            <div class="st-cal__year">{{ $cal->format('Y') }}</div>
        </div>

        <div class="st-cal__todo-body">
            <div class="st-cal__label">{{ __('student_timeline.todo_list') }}</div>
            @forelse($todoItems as $mission)
                <div class="st-cal__note {{ $loop->even ? 'st-cal__note--dark' : '' }}">
                    {{ $mission->title }}
                    @if(!empty($mission->completed)) ✓ @else · {{ $mission->progress }}/{{ $mission->target }} @endif
                </div>
            @empty
                <div class="st-cal__note">{{ __('student_timeline.notes_to_be_made') }}</div>
                <div class="st-cal__note st-cal__note--dark">{{ __('student_timeline.dont_forget_activities') }}</div>
            @endforelse

            <div class="st-cal__sched-label">
                {{ $viewMode === 'day' ? __('student_timeline.day_view') : __('student_timeline.my_week') }}
            </div>
            @forelse($scheduleRows as $row)
                @php $schedHref = $scheduleJoinUrl($row); @endphp
                @if($schedHref)
                    <a href="{{ $schedHref }}" class="st-cal__row st-cal__row--link">
                        <span>{{ trim(($row->day_short ?? '').' · '.($row->starts_at ? \App\Support\AppTimezone::formatFor($row->starts_at, $viewerTz, 'g:i A', $locale) : '—'), ' ·') }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($row->title ?? '', 28) }}</span>
                    </a>
                @else
                    <div class="st-cal__row">
                        <span>{{ trim(($row->day_short ?? '').' · '.($row->starts_at ? \App\Support\AppTimezone::formatFor($row->starts_at, $viewerTz, 'g:i A', $locale) : '—'), ' ·') }}</span>
                        <span>{{ \Illuminate\Support\Str::limit($row->title ?? '', 28) }}</span>
                    </div>
                @endif
            @empty
                <div class="st-cal__row">
                    <span>—</span>
                    <span>{{ __('student_timeline.no_events') }}</span>
                </div>
            @endforelse
        </div>
    </div>

    <div class="st-cal__grid">
        <img class="st-cal__grid-bg" src="{{ asset('img/student-timeline/cal-wave.svg') }}" alt="" aria-hidden="true">
        <div class="st-cal__grid-head">
            <h3>{{ __('student_timeline.calendar') }}</h3>
            <div class="st-cal__months">
                <a href="{{ $timelineMonthPrevUrl ?? '#' }}" class="st-cal__month-link" aria-label="{{ __('student_timeline.prev_month') }}">
                    {{ $cal->copy()->subMonth()->translatedFormat('M') }}
                    <small>{{ $cal->copy()->subMonth()->format('Y') }}</small>
                </a>
                <span class="is-active" aria-current="true">
                    {{ $cal->translatedFormat('M') }}
                    <small>{{ $cal->format('Y') }}</small>
                </span>
                <a href="{{ $timelineMonthNextUrl ?? '#' }}" class="st-cal__month-link" aria-label="{{ __('student_timeline.next_month') }}">
                    {{ $cal->copy()->addMonth()->translatedFormat('M') }}
                    <small>{{ $cal->copy()->addMonth()->format('Y') }}</small>
                </a>
            </div>
        </div>
        <div class="st-cal__board">
            <div class="st-weekdays" role="row">
                @foreach($weekdays as $wd)
                    <span class="st-weekday">{{ $wd }}</span>
                @endforeach
            </div>
            <div class="st-days" role="grid">
                @foreach($calendarDays as $day)
                    @php
                        $isCurrentMonth = $day->month === $cal->month;
                        $isToday = $day->isToday();
                        $isMarked = $markedDates->has($day->toDateString());
                    @endphp
                    <span class="st-day {{ ! $isCurrentMonth ? 'is-muted' : '' }} {{ $isToday ? 'is-today' : '' }} {{ $isMarked && ! $isToday ? 'is-mark' : '' }}">
                        @if($isMarked && ! $isToday)
                            <img class="st-day__ring" src="{{ asset('img/student-timeline/day-circle.svg') }}" alt="" width="37" height="37">
                        @endif
                        <span class="st-day__num">{{ $day->day }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if(!empty($recommendedYear) || !empty($placement?->admin_notes))
    <section class="st-event-card st-event-card--purple st-biz-banner st-path-card">
        <img class="st-event-card__mask" src="{{ $eventMasks[1] }}" alt="" width="160" height="160">
        <div class="st-path-card__body">
            <p class="st-event-card__kicker">{{ __('student_timeline.school_path') }}</p>
            @if(!empty($recommendedYear))
                <h3 class="st-text-auto">{{ $recommendedYear->name }}</h3>
                @if($recommendedYear->tagline)
                    <p class="st-event-card__sub st-text-auto">{{ $recommendedYear->tagline }}</p>
                @endif
                @if(Route::has('public.curricula'))
                    <a href="{{ route('public.curricula') }}" class="st-pill st-pill--light">{{ __('student_timeline.open_year_path') }}</a>
                @elseif(Route::has('public.school.year'))
                    <a href="{{ (Route::has('public.school.year') ? route('public.school.year', $recommendedYear->slug) : (Route::has('public.curricula') ? route('public.curricula') : route('dashboard'))) }}" class="st-pill st-pill--light">{{ __('student_timeline.open_year_path') }}</a>
                @endif
            @endif
            @if(!empty($placement?->admin_notes))
                <p class="st-event-card__sub st-text-auto" style="margin-top:10px;">{{ $placement->admin_notes }}</p>
            @endif
        </div>
    </section>
@endif
@endsection

@section('events')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $game = $game ?? ['weekly_missions' => collect(), 'daily_missions' => collect()];
    $upcoming = $upcoming ?? collect();
    $weekDays = $weekDays ?? collect();
    $eventTones = ['green', 'purple', 'orange'];
    $eventMasks = [
        asset('img/student-timeline/event-mask-1.svg'),
        asset('img/student-timeline/event-mask-2.svg'),
        asset('img/student-timeline/event-mask-3.svg'),
    ];
    $eventCards = collect();
    if (!empty($todayMission)) {
        $eventCards->push((object) [
            'title' => $todayMission->title,
            'subtitle' => $todayMission->subtitle,
            'meta' => $todayMission->starts_at
                ? \App\Support\AppTimezone::formatFor($todayMission->starts_at, $viewerTz, $isRtl ? 'l · g:i A' : 'D · g:i A', $locale)
                : null,
            'url' => $todayMission->is_joinable ? $todayMission->join_url : ($todayMission->class_url ?: '#'),
            'person' => $primaryClass->instructor_name ?? null,
        ]);
    }
    foreach ($upcoming->take(3) as $session) {
        if (!empty($todayMission) && isset($todayMission->session_id) && (int) $todayMission->session_id === (int) ($session->id ?? 0)) {
            continue;
        }
        $eventCards->push((object) [
            'title' => $session->title
                ?? (method_exists($session, 'displayTitle') ? $session->displayTitle() : __('student_timeline.events')),
            'subtitle' => $session->subtitle
                ?? ($session->cohort?->title ?: ($session->tutoringGroup?->title ?: '')),
            'meta' => ($session->starts_at ?? null)
                ? \App\Support\AppTimezone::formatFor($session->starts_at, $viewerTz, $isRtl ? 'l · g:i A' : 'D · g:i A', $locale)
                : null,
            'url' => $session->join_url
                ?? (\Illuminate\Support\Facades\Route::has('student.schedule.join')
                    ? route('student.schedule.join', ['type' => ($session->kind ?? 'class') === 'private' ? 'private' : 'class', 'id' => $session->id])
                    : '#'),
            'person' => $session->instructor_name ?? null,
        ]);
        if ($eventCards->count() >= 3) {
            break;
        }
    }
    foreach (($game['weekly_missions'] ?? collect())->take(3) as $mission) {
        if ($eventCards->count() >= 3) {
            break;
        }
        $eventCards->push((object) [
            'title' => $mission->title,
            'subtitle' => $mission->description,
            'meta' => '+'.$mission->xp_reward.' XP',
            'url' => Route::has('student.private-lectures.index')
                ? route('student.private-lectures.index')
                : route('dashboard'),
            'person' => null,
        ]);
    }
    $nextDayItems = $weekDays
        ->filter(fn ($day) => empty($day->is_today) && ($day->date?->gt(now()->startOfDay()) ?? false))
        ->flatMap(fn ($day) => $day->items ?? collect())
        ->take(2);
@endphp

<div class="st-events__top">
    <h2>{{ __('student_timeline.events') }}</h2>
    <div class="st-events__filter-wrap" data-st-filter>
        <button type="button" class="st-events__filter" id="stEventsFilterBtn" aria-expanded="false" aria-haspopup="true" aria-controls="stEventsFilterMenu">
            <img src="{{ asset('img/student-timeline/filter.svg') }}" alt="" width="14" height="14">
            <span data-st-filter-label>{{ __('student_timeline.filter') }}</span>
        </button>
        <div class="st-events__filter-menu" id="stEventsFilterMenu" role="menu" hidden>
            <button type="button" role="menuitem" data-st-filter-value="all">{{ __('student_timeline.filter_all') }}</button>
            <button type="button" role="menuitem" data-st-filter-value="activities">{{ __('student_timeline.filter_activities') }}</button>
            <button type="button" role="menuitem" data-st-filter-value="reminders">{{ __('student_timeline.filter_reminders') }}</button>
        </div>
    </div>
</div>

<div class="st-tabs" data-st-tabs>
    <button type="button" class="is-active" data-tab="activities">{{ __('student_timeline.activities') }}</button>
    <button type="button" data-tab="reminders">{{ __('student_timeline.reminders') }}</button>
</div>

<div data-tab-panel="activities">
    @forelse($eventCards->take(3) as $i => $card)
        <a href="{{ $card->url }}" class="st-event-card st-event-card--{{ $eventTones[$i % 3] }}">
            <img class="st-event-card__mask" src="{{ $eventMasks[$i % 3] }}" alt="" width="160" height="160">
            <h3>{{ $card->title }}</h3>
            <p class="st-event-card__sub">{{ $card->subtitle }}</p>
            <div class="st-event-card__meta">
                @if($card->person)
                    <img src="{{ asset('img/student-timeline/teacher.png') }}" alt="" width="16" height="16">
                    <span>{{ $card->person }}</span>
                @else
                    <img src="{{ asset('img/student-timeline/location.svg') }}" alt="" width="14" height="14">
                    <span>{{ $card->meta }}</span>
                @endif
            </div>
            @if($card->person && $card->meta)
                <div class="st-event-card__meta">
                    <img src="{{ asset('img/student-timeline/clock.png') }}" alt="" width="14" height="14">
                    <span>{{ $card->meta }}</span>
                </div>
            @endif
        </a>
    @empty
        <p class="st-events__empty">{{ __('student_timeline.no_events') }}</p>
    @endforelse
</div>

<div data-tab-panel="reminders" hidden>
    @forelse(($game['daily_missions'] ?? collect()) as $mission)
        <a href="{{ Route::has('student.private-lectures.index') ? route('student.private-lectures.index') : route('dashboard') }}" class="st-event-card st-event-card--orange">
            <img class="st-event-card__mask" src="{{ $eventMasks[2] }}" alt="" width="160" height="160">
            <h3>{{ $mission->title }}</h3>
            <p class="st-event-card__sub">{{ $mission->description }}</p>
            <div class="st-event-card__meta">
                <span>{{ $mission->progress }}/{{ $mission->target }} · +{{ $mission->xp_reward }} XP</span>
            </div>
        </a>
    @empty
        <p class="st-events__empty">{{ __('student_timeline.dont_forget_activities') }}</p>
    @endforelse
</div>

<div class="st-events__next" data-st-next-day>
    <h3>{{ __('student_timeline.next_day') }}</h3>
    @forelse($nextDayItems as $i => $slot)
        <a href="{{ $slot->join_url ?: '#' }}" class="st-event-card st-event-card--{{ $eventTones[($i + 1) % 3] }}" style="min-height:110px;margin-bottom:10px;">
            <img class="st-event-card__mask" src="{{ $eventMasks[($i + 1) % 3] }}" alt="" width="140" height="140">
            <h3>{{ $slot->title }}</h3>
            <div class="st-event-card__meta">
                <img src="{{ asset('img/student-timeline/clock.png') }}" alt="" width="14" height="14">
                <span>{{ $slot->starts_at ? \App\Support\AppTimezone::formatFor($slot->starts_at, $viewerTz, 'g:i A', $locale) : '—' }}</span>
            </div>
        </a>
    @empty
        <p class="st-events__empty">{{ __('student_timeline.no_events') }}</p>
    @endforelse
</div>

<div class="st-events__see">
    <a href="{{ Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard') }}">{{ __('student_timeline.see_all') }}</a>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var labels = {
        all: @json(__('student_timeline.filter_all')),
        activities: @json(__('student_timeline.filter_activities')),
        reminders: @json(__('student_timeline.filter_reminders')),
        default: @json(__('student_timeline.filter'))
    };
    var wrap = document.querySelector('[data-st-filter]');
    var btn = document.getElementById('stEventsFilterBtn');
    var menu = document.getElementById('stEventsFilterMenu');
    var labelEl = wrap ? wrap.querySelector('[data-st-filter-label]') : null;
    var nextDay = document.querySelector('[data-st-next-day]');
    var tabBtns = document.querySelectorAll('[data-st-tabs] button');
    var panels = document.querySelectorAll('[data-tab-panel]');
    var current = 'all';

    function setTab(tab) {
        tabBtns.forEach(function (b) {
            b.classList.toggle('is-active', b.getAttribute('data-tab') === tab);
        });
        panels.forEach(function (panel) {
            var name = panel.getAttribute('data-tab-panel');
            if (current === 'all') {
                panel.hidden = name !== 'activities';
            } else {
                panel.hidden = name !== tab;
            }
        });
        if (nextDay) {
            nextDay.hidden = current === 'reminders';
        }
    }

    function applyFilter(value) {
        current = value;
        if (labelEl) {
            labelEl.textContent = labels[value] || labels.default;
        }
        if (menu) {
            menu.querySelectorAll('[data-st-filter-value]').forEach(function (item) {
                item.classList.toggle('is-active', item.getAttribute('data-st-filter-value') === value);
            });
        }
        setTab(value === 'reminders' ? 'reminders' : 'activities');
        closeMenu();
    }

    function closeMenu() {
        if (!menu || !btn) return;
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        if (!menu || !btn) return;
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
    }

    tabBtns.forEach(function (tabBtn) {
        tabBtn.addEventListener('click', function () {
            var tab = tabBtn.getAttribute('data-tab');
            current = tab;
            if (labelEl) {
                labelEl.textContent = labels[tab] || labels.default;
            }
            if (menu) {
                menu.querySelectorAll('[data-st-filter-value]').forEach(function (item) {
                    item.classList.toggle('is-active', item.getAttribute('data-st-filter-value') === tab);
                });
            }
            setTab(tab);
            closeMenu();
        });
    });

    if (btn && menu) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (menu.hidden) openMenu();
            else closeMenu();
        });
        menu.querySelectorAll('[data-st-filter-value]').forEach(function (item) {
            item.addEventListener('click', function () {
                applyFilter(item.getAttribute('data-st-filter-value'));
            });
        });
        document.addEventListener('click', function (e) {
            if (wrap && !wrap.contains(e.target)) closeMenu();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    }
})();
</script>
@endpush
