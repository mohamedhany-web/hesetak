@extends('layouts.student-timeline')

@section('title', __('student.my_courses'))

@section('content')
@php
    $locale = app()->getLocale();
    $tones = ['blue', 'orange', 'purple', 'pink'];
    $masks = [
        asset('img/student-timeline/event-mask-1.svg'),
        asset('img/student-timeline/event-mask-2.svg'),
        asset('img/student-timeline/event-mask-3.svg'),
    ];
    $browseUrl = Route::has('public.courses') ? route('public.courses') : route('dashboard');
    $subsUrl = Route::has('student.my-course-subscriptions') ? route('student.my-course-subscriptions') : null;
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student.my_courses'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student.my_courses'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('info'))
    <div class="st-flash st-flash--ok">{{ session('info') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($activeCourses->count() > 0)
    <section class="st-join-hero" aria-label="{{ __('student.my_courses') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.courses_kicker') }}</p>
            <h2 class="st-join-hero__title">{{ __('student_timeline.courses_ready_title', ['count' => $stats['total_active']]) }}</h2>
            <p class="st-join-hero__meta">{{ __('student.my_courses_subtitle') }}</p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $browseUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student.browse_courses') }}</a>
            @if($subsUrl)
                <a href="{{ $subsUrl }}" class="st-pill st-pill--outline">{{ __('student.course_subscriptions_nav') }}</a>
            @endif
        </div>
    </section>
@else
    <section class="st-join-hero st-join-hero--muted" aria-label="{{ __('student.my_courses') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student_timeline.courses_kicker') }}</p>
            <h2 class="st-join-hero__title">{{ __('student.no_active_courses_my') }}</h2>
            <p class="st-join-hero__meta">{{ __('student.no_active_courses_desc') }}</p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ $browseUrl }}" class="st-pill st-pill--solid st-pill--lg">{{ __('student.browse_courses_btn') }}</a>
            <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.courses_stats') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.active_label') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['total_active'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.completed') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['total_completed'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.hours_label') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['total_hours'] }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.avg_progress_label') }}</p>
        <p class="st-stat-card__value">{{ (int) $stats['avg_progress'] }}%</p>
    </article>
</section>

<section class="st-msg-intro" aria-label="{{ __('student.my_courses') }}">
    <h2>{{ __('student_timeline.courses_list_title') }}</h2>
    <p>{{ __('student_timeline.courses_list_hint') }}</p>
</section>

@if($activeCourses->count() > 0)
    <section class="st-course-grid" aria-label="{{ __('student.my_courses') }}">
        @foreach($activeCourses as $i => $course)
            @php
                $progress = (int) ($course->pivot->progress ?? 0);
                $isCompleted = $progress >= 100;
                $tone = $tones[$i % count($tones)];
                $mask = $masks[$i % count($masks)];
                $lessonsCount = $course->lessons->count();
                $points = (float) ($course->student_points ?? 0);
            @endphp
            <article class="st-course-card st-course-card--{{ $tone }}">
                <a href="{{ route('my-courses.show', $course) }}" class="st-course-card__media" title="{{ $course->title }}">
                    @if($course->thumbnail)
                        <img src="{{ storage_asset($course->thumbnail) }}" alt="" loading="lazy">
                    @else
                        <img class="st-course-card__blob" src="{{ $mask }}" alt="" width="120" height="120">
                        <span class="st-course-card__placeholder" aria-hidden="true">
                            <i class="fas fa-graduation-cap"></i>
                        </span>
                    @endif
                    <span class="st-course-card__badge {{ $isCompleted ? 'is-ok' : '' }}">
                        {{ $isCompleted ? __('student.completed_badge') : __('student.active_badge') }}
                    </span>
                </a>
                <a href="{{ route('my-courses.show', $course) }}" class="st-course-card__main">
                    <h3 class="st-course-card__name">{{ $course->title }}</h3>
                    <p class="st-course-card__sub">
                        {{ $course->academicSubject->name ?? __('student.course_fallback') }}
                        · {{ $course->teacher->name ?? '—' }}
                        · {{ $lessonsCount }} {{ __('student.lesson_singular') }}
                    </p>
                    <div class="st-course-card__progress" aria-hidden="true">
                        <span style="width: {{ max(4, min(100, $progress)) }}%"></span>
                    </div>
                    <p class="st-course-card__meta">
                        {{ $progress }}%
                        · <i class="fas fa-star" aria-hidden="true"></i> {{ number_format($points, 0) }}
                    </p>
                </a>
                <div class="st-course-card__foot">
                    <a href="{{ route('my-courses.learn', $course) }}" class="st-pill st-pill--solid">
                        <i class="fas fa-play" aria-hidden="true"></i>
                        {{ __('student.continue_learning') }}
                    </a>
                    <a href="{{ route('my-courses.show', $course) }}" class="st-pill st-pill--outline">{{ __('student_timeline.courses_open') }}</a>
                </div>
            </article>
        @endforeach
    </section>

    @if($activeCourses->hasPages())
        <div class="st-pager">{{ $activeCourses->links() }}</div>
    @endif
@else
    <div class="st-empty-panel">
        <h3>{{ __('student.no_active_courses_my') }}</h3>
        <p>{{ __('student.no_active_courses_desc') }}</p>
        <div class="st-biz-banner__actions">
            <a href="{{ $browseUrl }}" class="st-pill st-pill--solid">{{ __('student.browse_courses_btn') }}</a>
            <a href="{{ route('dashboard') }}" class="st-pill st-pill--outline">{{ __('student_timeline.school_gate') }}</a>
        </div>
    </div>
@endif
@endsection
