@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_lectures'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $courseLectures = $courseLectures ?? collect();
    $private = $private ?? collect();
    $searchQuery = $searchQuery ?? '';
    $filter = $filter ?? 'all';
    $nextOpen = $nextOpen ?? null;
    $tones = ['blue', 'pink', 'orange', 'purple'];
    $showCourses = $filter === 'all' || $filter === 'courses';
    $showPrivate = $filter === 'all' || $filter === 'private';
    $avatarFallback = \App\Models\User::placeholderAvatarUrl();
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_lectures'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_lectures'), 'url' => null],
    ],
    'toolbarView' => 'student.lectures._toolbar',
    'toolbarData' => [
        'searchQuery' => $searchQuery,
        'filter' => $filter,
        'courseCount' => $courseLectures->count(),
        'privateCount' => $private->count(),
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($nextOpen)
    <section class="st-join-hero" aria-label="{{ __('student_timeline.open_lecture') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">
                {{ $nextOpen->kind === 'private' ? __('student_timeline.next_private_lesson') : __('student_timeline.next_course_lecture') }}
            </p>
            <h2 class="st-join-hero__title">{{ $nextOpen->title }}</h2>
            <p class="st-join-hero__meta">
                @if($nextOpen->meta)
                    {{ $nextOpen->meta }}
                    @if($nextOpen->at) · @endif
                @endif
                @if($nextOpen->at)
                    {{ $nextOpen->at->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A') }}
                @endif
            </p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $nextOpen->open_url }}" class="st-pill st-pill--solid st-pill--lg">
                <i class="fas fa-play" aria-hidden="true"></i>
                {{ $nextOpen->kind === 'private' ? __('student_timeline.join_now') : __('student_timeline.open_lecture') }}
            </a>
        </div>
    </section>
@else
    <section class="st-join-hero st-join-hero--muted" aria-label="{{ __('student_timeline.nav_lectures') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.nav_lectures') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.lectures_title') }}</h2>
            <p class="st-join-hero__meta">{{ __('student_timeline.lectures_courses_hint') }}</p>
        </div>
        <div class="st-join-hero__actions">
            @if(Route::has('my-courses.index'))
                <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--solid">{{ __('student.my_courses') }}</a>
            @endif
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.nav_lectures') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.filter_all') }}</p>
        <p class="st-stat-card__value">{{ $courseLectures->count() + $private->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.lectures_courses') }}</p>
        <p class="st-stat-card__value">{{ $courseLectures->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.lectures_private') }}</p>
        <p class="st-stat-card__value">{{ $private->count() }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student_timeline.lectures_title') }}</h2>
        <p>{{ __('student_timeline.lectures_courses_hint') }}</p>
    </div>
    @if(Route::has('my-courses.index'))
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--outline">{{ __('student.my_courses') }}</a>
    @endif
</section>

@if($showCourses)
    <section class="st-lecture-block" aria-label="{{ __('student_timeline.lectures_courses') }}">
        <div class="st-lecture-block__head">
            <h3>{{ __('student_timeline.lectures_courses') }}</h3>
            @if(Route::has('my-courses.index'))
                <a class="st-see" href="{{ route('my-courses.index') }}">{{ __('student_timeline.see_all') }}</a>
            @endif
        </div>

        <div class="st-lesson-list">
            @forelse($courseLectures as $i => $lecture)
                @php
                    $tone = $tones[$i % count($tones)];
                    $openUrl = $lecture->course_id
                        ? route('my-courses.lectures.show', [$lecture->course_id, $lecture->id])
                        : null;
                    $hasRecording = filled($lecture->recording_url) || filled($lecture->recording_file_path);
                @endphp
                <article class="st-lesson-card st-lesson-card--{{ $tone }}">
                    <div class="st-lesson-card__body">
                        <p class="st-lesson-card__kicker">{{ $lecture->course?->title ?: __('student_timeline.course') }}</p>
                        <h4 class="st-lesson-card__title">{{ $lecture->title }}</h4>
                        <p class="st-lesson-card__meta">
                            @if($lecture->instructor?->name)
                                {{ $lecture->instructor->name }}
                            @endif
                            @if($lecture->scheduled_at)
                                @if($lecture->instructor?->name) · @endif
                                {{ $lecture->scheduled_at->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'd M · g:i A' : 'M j · g:i A') }}
                            @endif
                            @if($lecture->duration_minutes)
                                · {{ $lecture->duration_minutes }} {{ __('student.minutes') }}
                            @endif
                        </p>
                        <div class="st-lesson-card__tags">
                            @if($hasRecording)
                                <span class="st-tag">{{ __('student_timeline.has_recording') }}</span>
                            @endif
                            @if($lecture->status)
                                <span class="st-tag">{{ $lecture->status }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="st-lesson-card__actions">
                        @if($openUrl)
                            <a href="{{ $openUrl }}" class="st-pill st-pill--solid">{{ __('student_timeline.open_lecture') }}</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="st-empty">
                    <p>{{ __('student_timeline.no_course_lectures') }}</p>
                    @if(Route::has('public.courses'))
                        <a href="{{ route('public.courses') }}" class="st-pill st-pill--solid">{{ __('student.browse_courses') }}</a>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
@endif

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
                    $canJoin = $session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED
                        && $session->scheduled_at
                        && $session->scheduled_at->lte(now()->addMinutes(30))
                        && $session->scheduled_at->gte(now()->subMinutes(50));
                @endphp
                <article class="st-lesson-card st-lesson-card--{{ $tone }}">
                    <div class="st-lesson-card__avatar">
                        <img src="{{ $session->instructor?->avatarDisplayUrl() ?: $avatarFallback }}" alt="" width="48" height="48">
                    </div>
                    <div class="st-lesson-card__body">
                        <p class="st-lesson-card__kicker">{{ __('student_timeline.private_lesson') }}</p>
                        <h4 class="st-lesson-card__title">{{ $session->course?->title ?: __('student_timeline.private_lesson') }}</h4>
                        <p class="st-lesson-card__meta">
                            {{ $session->instructor?->name }}
                            @if($session->scheduled_at)
                                · {{ $session->scheduled_at->timezone(config('app.timezone'))->translatedFormat($isRtl ? 'd M · g:i A' : 'M j · g:i A') }}
                            @endif
                        </p>
                    </div>
                    <div class="st-lesson-card__actions">
                        @if($canJoin && Route::has('student.schedule.join'))
                            <a href="{{ route('student.schedule.join', ['type' => 'private', 'id' => $session->id]) }}" class="st-pill st-pill--solid">{{ __('student_timeline.join_now') }}</a>
                        @elseif(Route::has('student.private-lectures.index'))
                            <a href="{{ route('student.private-lectures.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.see_all') }}</a>
                        @endif
                    </div>
                </article>
            @empty
                @if($filter === 'private')
                    <div class="st-empty">
                        <p>{{ __('student_timeline.no_private_lectures') }}</p>
                    </div>
                @endif
            @endforelse
        </div>
    </section>
@endif
@endsection
