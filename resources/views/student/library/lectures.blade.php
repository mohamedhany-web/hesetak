@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_lectures'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $private = $private ?? collect();
    $classes = $classes ?? collect();
    $searchQuery = $searchQuery ?? '';
    $filter = $filter ?? 'all';
    $nextJoinable = $nextJoinable ?? null;
    $tones = ['blue', 'pink', 'orange', 'purple'];
    $showPrivate = $filter === 'all' || $filter === 'private';
    $showClasses = $filter === 'all' || $filter === 'classes';
    $avatarFallback = asset('img/student-timeline/avatar.png');
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_lectures'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_lectures'), 'url' => null],
    ],
    'toolbarView' => 'student.library._lectures-toolbar',
    'toolbarData' => [
        'searchQuery' => $searchQuery,
        'filter' => $filter,
        'privateCount' => $private->count(),
        'classCount' => $classes->count(),
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($nextJoinable)
    <section class="st-join-hero" aria-label="{{ __('student_timeline.join_class_now') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">
                {{ $nextJoinable->kind === 'private' ? __('student_timeline.next_private_lesson') : __('student_timeline.next_class') }}
            </p>
            <h2 class="st-join-hero__title">{{ $nextJoinable->title }}</h2>
            <p class="st-join-hero__meta">
                @if($nextJoinable->meta)
                    {{ $nextJoinable->meta }} ·
                @endif
                {{ $nextJoinable->at?->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A') }}
            </p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $nextJoinable->join_url }}" class="st-pill st-pill--solid st-pill--lg">
                <i class="fas fa-video" aria-hidden="true"></i>
                {{ __('student_timeline.join_now') }}
            </a>
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.nav_lectures') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.filter_all') }}</p>
        <p class="st-stat-card__value">{{ $private->count() + $classes->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.lectures_private') }}</p>
        <p class="st-stat-card__value">{{ $private->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.lectures_groups') }}</p>
        <p class="st-stat-card__value">{{ $classes->count() }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student_timeline.lectures_title') }}</h2>
        <p>{{ __('student_timeline.lectures_page_hint') }}</p>
    </div>
</section>

@if($showPrivate)
    <section class="st-lecture-block" aria-label="{{ __('student_timeline.lectures_private') }}">
        <div class="st-lecture-block__head">
            <h3>{{ __('student_timeline.lectures_private') }}</h3>
            @if(Route::has('student.private-lectures.index'))
                <a class="st-see" href="{{ route('student.private-lectures.index') }}">{{ __('student_timeline.see_all') }}</a>
            @endif
        </div>

        <div class="st-lesson-list">
            @forelse($private as $i => $session)
                @php
                    $tone = $tones[$i % count($tones)];
                    $title = $session->course?->title ?: __('student_timeline.private_lesson');
                    $instructor = $session->instructor;
                    $avatar = ($instructor && $instructor->profile_image)
                        ? $instructor->profile_image_url
                        : $avatarFallback;
                    $joinUrl = route('student.schedule.join', ['type' => 'private', 'id' => $session->id]);
                    $statusLabel = \App\Models\OneToOneSession::statusLabels()[$session->status] ?? $session->status;
                @endphp
                <article class="st-lesson-card st-lesson-card--{{ $tone }}">
                    <div class="st-lesson-card__main">
                        <img class="st-lesson-card__avatar" src="{{ $avatar }}" alt="" width="48" height="48">
                        <div class="st-lesson-card__copy">
                            <div class="st-lesson-card__badges">
                                <span class="st-lesson-card__badge">{{ __('student_timeline.private_lesson') }}</span>
                                <span class="st-lesson-card__mins">{{ $statusLabel }}</span>
                            </div>
                            <h3>{{ $title }}</h3>
                            <p class="st-lesson-card__meta">
                                @if($instructor)
                                    {{ $instructor->name }} ·
                                @endif
                                {{ $session->scheduled_at?->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'd M · g:i A' : 'M j · g:i A') ?: __('student_timeline.awaiting_schedule') }}
                            </p>
                        </div>
                    </div>
                    <div class="st-lesson-card__foot">
                        <a href="{{ $joinUrl }}" class="st-pill st-pill--solid">
                            <i class="fas fa-video" aria-hidden="true"></i>
                            {{ __('student_timeline.join_now') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="st-empty-panel">
                    <h3>{{ __('student_timeline.no_private_in_lectures') }}</h3>
                    <p>{{ __('student_timeline.no_private_lessons_hint') }}</p>
                    @if(Route::has('student.private-lectures.index'))
                        <div class="st-biz-banner__actions">
                            <a href="{{ route('student.private-lectures.index') }}" class="st-pill st-pill--solid">{{ __('student_timeline.nav_lessons') }}</a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
@endif

@if($showClasses)
    <section class="st-lecture-block" aria-label="{{ __('student_timeline.lectures_groups') }}">
        <div class="st-lecture-block__head">
            <h3>{{ __('student_timeline.lectures_groups') }}</h3>
            @if(Route::has('student.classes.index'))
                <a class="st-see" href="{{ (Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard')) }}">{{ __('student_timeline.see_all') }}</a>
            @endif
        </div>

        <div class="st-lesson-list">
            @forelse($classes as $i => $session)
                @php
                    $tone = $tones[$i % count($tones)];
                    $joinUrl = route('student.schedule.join', ['type' => 'class', 'id' => $session->id]);
                    $joinable = method_exists($session, 'isJoinable') && $session->isJoinable();
                @endphp
                <article class="st-lesson-card st-lesson-card--{{ $tone }}">
                    <div class="st-lesson-card__main">
                        <div class="st-assign-card__icon" aria-hidden="true">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="st-lesson-card__copy">
                            <div class="st-lesson-card__badges">
                                <span class="st-lesson-card__badge">{{ __('student_timeline.group_session') }}</span>
                                @if($joinable)
                                    <span class="st-lesson-card__mins" style="color:#0a5c3a">{{ __('student_timeline.live_now') }}</span>
                                @endif
                            </div>
                            <h3>{{ $session->displayTitle() }}</h3>
                            <p class="st-lesson-card__meta">
                                {{ $session->cohort?->title ?: ($session->tutoringGroup?->title ?: '') }}
                                @if($session->starts_at)
                                    · {{ $session->starts_at->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'd M · g:i A' : 'M j · g:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="st-lesson-card__foot">
                        <a href="{{ $joinUrl }}" class="st-pill {{ $joinable ? 'st-pill--solid' : 'st-pill--outline' }}">
                            <i class="fas fa-video" aria-hidden="true"></i>
                            {{ __('student_timeline.join_now') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="st-empty-panel">
                    <h3>{{ __('student_timeline.no_group_lectures') }}</h3>
                    <p>{{ __('student_timeline.no_group_lectures_hint') }}</p>
                    @if(Route::has('student.classes.index'))
                        <div class="st-biz-banner__actions">
                            <a href="{{ Route::has('student.classes.index') ? route('student.classes.index') : route('dashboard') }}" class="st-pill st-pill--solid">{{ __('student_timeline.my_classes') }}</a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
@endif
@endsection

@section('events')
<div class="st-events__top">
    <h2>{{ __('student_timeline.quick_links') }}</h2>
</div>

@if(Route::has('student.private-lectures.index'))
    <a href="{{ route('student.private-lectures.index') }}" class="st-event-card st-event-card--orange">
        <h3>{{ __('student_timeline.nav_lessons') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.private_lessons_hint') }}</p>
    </a>
@endif

@if(Route::has('student.classes.index'))
    <a href="{{ route('student.classes.index') }}" class="st-event-card st-event-card--blue">
        <h3>{{ __('student_timeline.my_classes') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.classes_hint') }}</p>
    </a>
@endif

@if(Route::has('student.library.videos'))
    <a href="{{ route('student.library.videos') }}" class="st-event-card st-event-card--pink">
        <h3>{{ __('student_timeline.nav_library_videos') }}</h3>
        <p class="st-event-card__sub">{{ __('student_timeline.videos_side_hint') }}</p>
    </a>
@endif

<div class="st-events__see">
    <a href="{{ route('dashboard') }}">{{ __('student_timeline.school_gate') }}</a>
</div>
@endsection
