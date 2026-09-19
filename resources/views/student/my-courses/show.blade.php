@extends('layouts.student-timeline')

@section('title', $course->title)

@section('content')
@php
    $locale = app()->getLocale();
    $progress = (int) ($progress ?? 0);
    $completedLessons = (int) ($completedLessons ?? 0);
    $totalLessons = (int) ($totalLessons ?? 0);
    $coursePoints = (float) ($coursePoints ?? 0);
    $sections = $sections ?? collect();
    $subject = $course->academicSubject->name ?? null;
    $year = $course->academicYear->name ?? null;
    $teacher = $course->teacher->name ?? null;
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $course->title,
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student.my_courses'), 'url' => route('my-courses.index')],
        ['label' => $course->title, 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ $course->title }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ __('student_timeline.courses_kicker') }}</p>
        <h2 class="st-join-hero__title">{{ $course->title }}</h2>
        <p class="st-join-hero__meta">
            {{ collect([$year, $subject, $teacher])->filter()->implode(' · ') ?: __('student.course_fallback') }}
        </p>
        <div class="st-learn-chips st-learn-chips--light" style="margin-top:10px">
            <span>{{ $progress }}% {{ __('student.progress') }}</span>
            <span>{{ $completedLessons }}/{{ $totalLessons }} {{ __('student.completed') }}</span>
            <span><i class="fas fa-star" aria-hidden="true"></i> {{ number_format($coursePoints, 0) }}</span>
        </div>
    </div>
    <div class="st-join-hero__actions">
        <a href="{{ route('my-courses.learn', $course) }}" class="st-pill st-pill--solid st-pill--lg">
            <i class="fas fa-play" aria-hidden="true"></i>
            {{ $progress > 0 ? __('student.continue_learning') : __('student_timeline.courses_start') }}
        </a>
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--outline">{{ __('student_timeline.courses_back') }}</a>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.courses_stats') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.progress') }}</p>
        <p class="st-stat-card__value">{{ $progress }}%</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.completed') }}</p>
        <p class="st-stat-card__value">{{ $completedLessons }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.courses_items') }}</p>
        <p class="st-stat-card__value">{{ $totalLessons }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.courses_points') }}</p>
        <p class="st-stat-card__value">{{ number_format($coursePoints, 0) }}</p>
    </article>
</section>

<section class="st-course-detail" aria-label="{{ __('student_timeline.courses_overview') }}">
    <div class="st-course-detail__hero">
        @if($course->thumbnail)
            <img src="{{ storage_asset($course->thumbnail) }}" alt="{{ $course->title }}" class="st-course-detail__thumb">
        @else
            <div class="st-course-detail__thumb st-course-detail__thumb--empty" aria-hidden="true">
                <i class="fas fa-graduation-cap"></i>
            </div>
        @endif
        <div class="st-course-detail__progress-wrap">
            <div class="st-course-detail__progress-row">
                <span>{{ __('student.progress') }}</span>
                <strong>{{ $progress }}%</strong>
            </div>
            <div class="st-course-card__progress" aria-hidden="true">
                <span style="width: {{ max(4, min(100, $progress)) }}%"></span>
            </div>
        </div>
    </div>

    <div class="st-learn-panel st-course-detail__panel">
        <h3>{{ __('student_timeline.courses_overview') }}</h3>
        <p class="st-learn-note">{{ $course->description ?: __('student_timeline.courses_no_desc') }}</p>
        <div class="st-learn-chips" style="margin-top:12px">
            @if($course->level)
                <span>{{ __('student_timeline.courses_level') }}: {{ $course->level }}</span>
            @endif
            @if($course->duration_hours)
                <span>{{ __('student_timeline.courses_duration') }}: {{ $course->duration_hours }} {{ __('student.hours_label') }}</span>
            @endif
            @if($subject)
                <span>{{ $subject }}</span>
            @endif
            @if($year)
                <span>{{ $year }}</span>
            @endif
        </div>
    </div>
</section>

@if($sections->count() > 0)
    <section class="st-msg-intro">
        <h2>{{ __('student_timeline.courses_curriculum') }}</h2>
        <p>{{ __('student_timeline.courses_curriculum_hint') }}</p>
    </section>

    <div class="st-course-sections">
        @foreach($sections as $section)
            @php
                $itemCount = $section->activeItems->filter(fn ($ci) => $ci->item)->count();
            @endphp
            <article class="st-learn-panel st-course-section">
                <div class="st-course-section__top">
                    <h3>{{ $section->title }}</h3>
                    <span class="st-learn-badge">{{ $itemCount }}</span>
                </div>
                @if($section->description)
                    <p class="st-learn-note">{{ $section->description }}</p>
                @endif
                <div class="st-course-section__items">
                    @foreach($section->activeItems as $curriculumItem)
                        @php
                            $item = $curriculumItem->item;
                            if (! $item) {
                                continue;
                            }
                            $label = $item->title ?? __('student.course_fallback');
                            $kind = class_basename($item);
                            $kindLabel = match ($kind) {
                                'Lecture' => __('student_timeline.courses_kind_lecture'),
                                'CourseLesson' => __('student_timeline.courses_kind_lesson'),
                                'Assignment' => __('student_timeline.courses_kind_assignment'),
                                'AdvancedExam', 'Exam' => __('student_timeline.courses_kind_exam'),
                                default => __('student.course_fallback'),
                            };
                            $icon = match ($kind) {
                                'Lecture' => 'fa-video',
                                'CourseLesson' => 'fa-play-circle',
                                'Assignment' => 'fa-tasks',
                                'AdvancedExam', 'Exam' => 'fa-clipboard-check',
                                default => 'fa-bookmark',
                            };
                        @endphp
                        <div class="st-learn-row">
                            <div>
                                <strong><i class="fas {{ $icon }}" aria-hidden="true" style="margin-inline-end:6px;opacity:.7"></i>{{ $label }}</strong>
                                <small>{{ $kindLabel }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
@endif

<div class="st-course-cta-bar">
    <a href="{{ route('my-courses.learn', $course) }}" class="st-pill st-pill--solid st-pill--lg">
        <i class="fas fa-play" aria-hidden="true"></i>
        {{ $progress > 0 ? __('student.continue_learning') : __('student_timeline.courses_start') }}
    </a>
</div>
@endsection
