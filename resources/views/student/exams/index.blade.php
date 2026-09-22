@extends('layouts.student-timeline')

@section('title', __('student.exams_page_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $availableExams = $availableExams ?? collect();
    $completedExams = $availableExams->filter(function ($exam) {
        return $exam->last_attempt && $exam->last_attempt->status === 'completed';
    });
    $canAttemptCount = $availableExams->where('can_attempt', true)->count();
    $avgScore = $completedExams->where('best_score', '!=', null)->avg('best_score');
    $nextExam = $availableExams->first(fn ($exam) => $exam->can_attempt);
    $tones = ['blue', 'pink', 'orange', 'purple'];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student.exams_page_title'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student.exams_page_title'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

@if($nextExam)
    <section class="st-join-hero" aria-label="{{ __('student.start_exam') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ __('student.available') }}</p>
            <h2 class="st-join-hero__title">{{ $nextExam->title }}</h2>
            <p class="st-join-hero__meta">
                {{ $nextExam->offlineCourse->title ?? $nextExam->course->title ?? '—' }}
                @if($nextExam->duration_minutes)
                    · {{ $nextExam->duration_minutes }} {{ __('student.minutes') }}
                @endif
            </p>
        </div>
        <div class="st-join-hero__actions">
            <a href="{{ route('student.exams.show', $nextExam) }}" class="st-pill st-pill--solid st-pill--lg">
                <i class="fas fa-play" aria-hidden="true"></i>
                {{ __('student.start_exam') }}
            </a>
        </div>
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student.exams_page_title') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.available') }}</p>
        <p class="st-stat-card__value">{{ $availableExams->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.completed') }}</p>
        <p class="st-stat-card__value">{{ $completedExams->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.can_attempt_label') }}</p>
        <p class="st-stat-card__value">{{ $canAttemptCount }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student.avg_results_label') }}</p>
        <p class="st-stat-card__value">{{ $avgScore ? number_format($avgScore, 1) : 0 }}%</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.exams_page_title') }}</h2>
        <p>{{ __('student.exams_subtitle') }}</p>
    </div>
    @if(Route::has('my-courses.index'))
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--outline">{{ __('student.my_courses_link') }}</a>
    @endif
</section>

@if($availableExams->count() > 0)
    <section class="st-lesson-list" aria-label="{{ __('student.exams_page_title') }}">
        @foreach($availableExams as $i => $exam)
            @php $tone = $tones[$i % count($tones)]; @endphp
            <article class="st-lesson-card st-lesson-card--{{ $tone }}">
                <div class="st-lesson-card__body">
                    <p class="st-lesson-card__kicker">
                        {{ $exam->offlineCourse->title ?? $exam->course->title ?? '—' }}
                        @if($exam->offline_course_id)
                            · {{ __('student.offline_badge') }}
                        @elseif(optional($exam->course)->academicSubject)
                            · {{ $exam->course->academicSubject->name }}
                        @endif
                    </p>
                    <h4 class="st-lesson-card__title">{{ $exam->title }}</h4>
                    <p class="st-lesson-card__meta">
                        {{ $exam->duration_minutes }} {{ __('student.minutes') }}
                        · {{ $exam->questions_count }} {{ __('student.question_singular') }}
                        · {{ __('student.passing_marks_label') }} {{ $exam->passing_marks }}%
                        · {{ __('student.attempts_label') }}:
                        {{ $exam->attempts_allowed == 0 ? __('student.unlimited_attempts') : $exam->attempts_allowed }}
                    </p>
                    <div class="st-lesson-card__tags">
                        @if($exam->can_attempt)
                            <span class="st-tag">{{ __('student.available') }}</span>
                        @else
                            <span class="st-tag">{{ __('student.not_available_now') }}</span>
                        @endif
                        @if($exam->best_score !== null)
                            <span class="st-tag">{{ __('student.best_score_label') }}: {{ number_format($exam->best_score, 1) }}%</span>
                        @endif
                        @if($exam->prevent_tab_switch || $exam->require_camera || $exam->require_microphone)
                            <span class="st-tag">{{ __('student.protected_exam') }}</span>
                        @endif
                    </div>
                    @if($exam->description)
                        <p class="st-lesson-card__meta" style="margin-top:6px">{{ \Illuminate\Support\Str::limit($exam->description, 120) }}</p>
                    @endif
                </div>
                <div class="st-lesson-card__actions">
                    @if($exam->can_attempt)
                        <a href="{{ route('student.exams.show', $exam) }}" class="st-pill st-pill--solid">{{ __('student.start_exam') }}</a>
                    @elseif($exam->user_attempts >= $exam->attempts_allowed && $exam->attempts_allowed > 0)
                        <span class="st-pill st-pill--outline" aria-disabled="true">{{ __('student.attempts_exhausted') }}</span>
                    @else
                        <span class="st-pill st-pill--outline" aria-disabled="true">{{ __('student.not_available_now') }}</span>
                    @endif
                    @if($exam->last_attempt && $exam->show_results_immediately && $exam->last_attempt->status === 'completed')
                        <a href="{{ route('student.exams.result', [$exam, $exam->last_attempt]) }}" class="st-pill st-pill--outline">{{ __('student.view_result') }}</a>
                    @endif
                </div>
            </article>
        @endforeach
    </section>
@else
    <div class="st-empty">
        <p>{{ __('student.no_exams_available') }}</p>
        <p class="st-lesson-card__meta">{{ __('student.no_exams_desc') }}</p>
        <a href="{{ route('my-courses.index') }}" class="st-pill st-pill--solid">{{ __('student.view_my_courses') }}</a>
    </div>
@endif
@endsection
