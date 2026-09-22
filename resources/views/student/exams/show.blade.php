@extends('layouts.student-timeline')

@section('title', $exam->title)

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $courseTitle = $exam->offlineCourse->title ?? $exam->course->title ?? '—';
    $canAttempt = $exam->canAttempt(auth()->id());
    $questionsCount = $exam->examQuestions->count();
    $bestScore = $previousAttempts->where('status', 'completed')->max('percentage');
    $lastAttempt = $previousAttempts->where('status', 'completed')->first();
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student.exams_page_title'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student.exams_page_title'), 'url' => route('student.exams.index')],
        ['label' => Str::limit($exam->title, 40), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ $exam->title }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">
            {{ $exam->isAvailable() ? __('student.available') : __('student_timeline.exam_unavailable') }}
            @if($exam->offline_course_id)
                · {{ __('student_timeline.exam_offline') }}
            @endif
        </p>
        <h2 class="st-join-hero__title">{{ $exam->title }}</h2>
        <p class="st-join-hero__meta">
            {{ $courseTitle }}
            @if($exam->duration_minutes)
                · {{ $exam->duration_minutes }} {{ __('student.minutes') }}
            @endif
            · {{ $questionsCount }} {{ __('student_timeline.exam_questions') }}
        </p>
    </div>
    <div class="st-join-hero__actions">
        @if($canAttempt)
            <button type="button" onclick="confirmStart()" class="st-pill st-pill--solid st-pill--lg">
                <i class="fas fa-play" aria-hidden="true"></i>
                {{ __('student.start_exam') }}
            </button>
        @endif
        <a href="{{ route('student.exams.index') }}" class="st-pill st-pill--outline">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
            {{ __('student_timeline.exam_back') }}
        </a>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.exam_info') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.exam_duration') }}</p>
        <p class="st-stat-card__value">{{ $exam->duration_minutes }}</p>
        <p class="st-stat-card__hint">{{ __('student.minutes') }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.exam_questions') }}</p>
        <p class="st-stat-card__value">{{ $questionsCount }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.exam_pass_mark') }}</p>
        <p class="st-stat-card__value">{{ $exam->passing_marks }}</p>
        <p class="st-stat-card__hint">/ {{ $exam->total_marks }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.exam_attempts') }}</p>
        <p class="st-stat-card__value">{{ $previousAttempts->count() }}</p>
        <p class="st-stat-card__hint">
            {{ $exam->attempts_allowed == 0 ? __('student_timeline.exam_unlimited') : __('student_timeline.exam_of', ['n' => $exam->attempts_allowed]) }}
        </p>
    </article>
</section>

<div class="st-order-show">
    <div class="st-order-show__main">
        <section class="st-order-panel">
            <div class="st-order-panel__head">
                <h2>{{ __('student_timeline.exam_details') }}</h2>
            </div>
            <div class="st-order-panel__body">
                @if($exam->description)
                    <p class="st-learn-note" style="margin:0 0 12px">{{ $exam->description }}</p>
                @endif
                @if($exam->instructions)
                    <div class="st-exam-note">
                        <strong>{{ __('student_timeline.exam_instructions') }}</strong>
                        <p>{{ $exam->instructions }}</p>
                    </div>
                @endif
                @if(! $exam->description && ! $exam->instructions)
                    <p class="st-learn-note" style="margin:0">{{ __('student_timeline.exam_no_extra') }}</p>
                @endif
            </div>
        </section>

        @if($previousAttempts->count() > 0)
            <section class="st-order-panel">
                <div class="st-order-panel__head">
                    <h2>{{ __('student_timeline.exam_previous') }}</h2>
                </div>
                <div class="st-order-panel__body" style="padding:12px">
                    @foreach($previousAttempts as $index => $attempt)
                        <article class="st-learn-row" style="padding:10px 4px">
                            <div>
                                <strong>{{ __('student_timeline.exam_attempt_n', ['n' => $index + 1]) }}</strong>
                                <small>
                                    {{ $attempt->created_at->format('Y-m-d H:i') }}
                                    · {{ $attempt->formatted_time }}
                                </small>
                            </div>
                            <div class="st-learn-chips">
                                @if($attempt->status === 'completed')
                                    <span>{{ number_format($attempt->percentage, 1) }}%</span>
                                    <span>{{ $attempt->result_status }}</span>
                                    @if($exam->show_results_immediately)
                                        <a href="{{ route('student.exams.result', [$exam, $attempt]) }}" class="st-pill st-pill--outline" style="height:auto;padding:4px 10px;font-size:12px">{{ __('student_timeline.exam_view_result') }}</a>
                                    @endif
                                @else
                                    <span>{{ __('student_timeline.exam_incomplete') }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <aside class="st-order-show__side">
        <section class="st-order-panel">
            <div class="st-order-panel__head">
                <h2>{{ __('student_timeline.exam_info') }}</h2>
            </div>
            <div class="st-order-panel__body">
                <div class="st-learn-row"><strong>{{ __('student_timeline.exam_duration') }}</strong><small>{{ $exam->duration_minutes }} {{ __('student.minutes') }}</small></div>
                <div class="st-learn-row"><strong>{{ __('student_timeline.exam_questions') }}</strong><small>{{ $questionsCount }}</small></div>
                <div class="st-learn-row"><strong>{{ __('student_timeline.exam_total_marks') }}</strong><small>{{ $exam->total_marks }}</small></div>
                <div class="st-learn-row"><strong>{{ __('student_timeline.exam_pass_mark') }}</strong><small>{{ $exam->passing_marks }}</small></div>
                <div class="st-learn-row">
                    <strong>{{ __('student_timeline.exam_allowed') }}</strong>
                    <small>{{ $exam->attempts_allowed == 0 ? __('student_timeline.exam_unlimited') : $exam->attempts_allowed }}</small>
                </div>
                @if($exam->start_time)
                    <div class="st-learn-row"><strong>{{ __('student_timeline.exam_starts') }}</strong><small>{{ $exam->start_time->format('Y-m-d H:i') }}</small></div>
                @endif
                @if($exam->end_time)
                    <div class="st-learn-row"><strong>{{ __('student_timeline.exam_ends') }}</strong><small>{{ $exam->end_time->format('Y-m-d H:i') }}</small></div>
                @endif
            </div>
        </section>

        @if($exam->prevent_tab_switch || $exam->require_camera || $exam->require_microphone || $exam->auto_submit)
            <div class="st-exam-security">
                <strong><i class="fas fa-shield-alt" aria-hidden="true"></i> {{ __('student_timeline.exam_security') }}</strong>
                <ul>
                    @if($exam->prevent_tab_switch)<li>{{ __('student_timeline.exam_no_tab') }}</li>@endif
                    @if($exam->require_camera)<li>{{ __('student_timeline.exam_camera') }}</li>@endif
                    @if($exam->require_microphone)<li>{{ __('student_timeline.exam_mic') }}</li>@endif
                    @if($exam->auto_submit)<li>{{ __('student_timeline.exam_auto_submit') }}</li>@endif
                </ul>
            </div>
        @endif

        <section class="st-order-panel">
            <div class="st-order-panel__body" style="text-align:center">
                @if($canAttempt)
                    <p class="st-learn-note" style="margin:0 0 14px">{{ __('student_timeline.exam_start_hint') }}</p>
                    <form action="{{ route('student.exams.start', $exam) }}" method="POST" id="start-exam-form">
                        @csrf
                        <button type="button" onclick="confirmStart()" class="st-pill st-pill--solid st-pill--lg" style="width:100%;justify-content:center">
                            <i class="fas fa-play" aria-hidden="true"></i>
                            {{ __('student.start_exam') }}
                        </button>
                    </form>
                @else
                    <div class="st-empty-panel" style="box-shadow:none;padding:18px 8px">
                        <h3>{{ __('student_timeline.exam_cannot_start') }}</h3>
                        <p>
                            @if($previousAttempts->count() >= $exam->attempts_allowed && $exam->attempts_allowed > 0)
                                {{ __('student_timeline.exam_attempts_exhausted', ['n' => $exam->attempts_allowed]) }}
                            @elseif(! $exam->isAvailable())
                                {{ __('student_timeline.exam_unavailable') }}
                            @else
                                {{ __('student_timeline.exam_not_allowed') }}
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </section>

        @if($bestScore !== null)
            <section class="st-order-panel">
                <div class="st-order-panel__head">
                    <h2>{{ __('student_timeline.exam_best') }}</h2>
                </div>
                <div class="st-order-panel__body" style="text-align:center">
                    <p class="st-stat-card__value" style="margin:0">{{ number_format($bestScore, 1) }}%</p>
                    @if($exam->show_results_immediately && $lastAttempt)
                        <a href="{{ route('student.exams.result', [$exam, $lastAttempt]) }}" class="st-pill st-pill--outline" style="margin-top:12px">{{ __('student_timeline.exam_view_result') }}</a>
                    @endif
                </div>
            </section>
        @endif
    </aside>
</div>

<div id="confirmModal" class="st-exam-modal hidden" role="dialog" aria-modal="true" aria-labelledby="exam-confirm-title">
    <div class="st-exam-modal__card" onclick="event.stopPropagation()">
        <div class="st-exam-modal__ico" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 id="exam-confirm-title">{{ __('student_timeline.exam_confirm_title') }}</h3>
        <p>{{ __('student_timeline.exam_confirm_body') }}</p>
        @if($exam->prevent_tab_switch)
            <div class="st-exam-modal__warn">{{ __('student_timeline.exam_no_tab') }}</div>
        @endif
        <div class="st-exam-modal__actions">
            <button type="button" onclick="startExam()" class="st-pill st-pill--solid">{{ __('student_timeline.exam_confirm_go') }}</button>
            <button type="button" onclick="closeModal()" class="st-pill st-pill--outline">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmStart() { document.getElementById('confirmModal').classList.remove('hidden'); }
function closeModal() { document.getElementById('confirmModal').classList.add('hidden'); }
function startExam() { document.getElementById('start-exam-form').submit(); }
document.getElementById('confirmModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>
@endpush
@endsection
