@extends('layouts.app')

@section('title', __('instructor.exam_details'))
@section('page_title', __('instructor.exam_details') . ': ' . $exam->title)

@section('content')
@php
    $locale = app()->getLocale();
@endphp

<div class="id-page" x-data="{ activeTab: 'questions' }">
    <section class="id-hero" aria-label="{{ $exam->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.exams') }}</p>
            <h2 class="id-hero__title">{{ $exam->title }}</h2>
            <p class="id-hero__meta" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                    {{ $exam->is_active ? __('instructor.active') : __('instructor.inactive') }}
                </span>
                @if($exam->advancedCourse)
                    <span>{{ $exam->advancedCourse->title }}</span>
                @endif
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.exams.questions.manage', $exam) }}" class="id-btn id-btn--gold">
                <i class="fas fa-cogs" aria-hidden="true"></i>
                {{ __('instructor.manage_questions') }}
            </a>
            <a href="{{ route('instructor.exams.edit', $exam) }}" class="id-btn id-btn--ghost">
                <i class="fas fa-edit" aria-hidden="true"></i>
                {{ __('common.edit') }}
            </a>
            <a href="{{ route('instructor.exams.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.exam_details') }}">
        <div class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-question-circle"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.questions_count') }}</span>
                <span class="id-kpi__value">{{ number_format($exam->questions->count()) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-users"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.attempts_count') }}</span>
                <span class="id-kpi__value">{{ number_format($attemptStats['total'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-check-double"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.completed_count') }}</span>
                <span class="id-kpi__value">{{ number_format($attemptStats['completed'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-star"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.average_score_label') }}</span>
                <span class="id-kpi__value">{{ number_format($attemptStats['average_score'] ?? 0, 1) }}</span>
            </span>
        </div>
    </section>

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.exam_info') }}</h2>
        </header>
        <div class="id-meta">
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-heading" aria-hidden="true"></i></span>
                <span>{{ __('instructor.title') }}</span>
                <strong>{{ $exam->title }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-book" aria-hidden="true"></i></span>
                <span>{{ __('instructor.course_label') }}</span>
                <strong>
                    {{ $exam->advancedCourse->title ?? '—' }}
                    @if($exam->advancedCourse && $exam->advancedCourse->academicSubject)
                        <span class="id-chip id-chip--muted" style="margin-inline-start:6px">{{ $exam->advancedCourse->academicSubject->name }}</span>
                    @endif
                </strong>
            </div>
            @if($exam->lesson)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-chalkboard" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.lesson_label') }}</span>
                    <strong>{{ $exam->lesson->title }}</strong>
                </div>
            @endif
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-clock" aria-hidden="true"></i></span>
                <span>{{ __('instructor.duration_minutes') }}</span>
                <strong class="tabular-nums">{{ $exam->duration_minutes }} {{ __('instructor.minute_unit') }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-star" aria-hidden="true"></i></span>
                <span>{{ __('instructor.total_score_label') }}</span>
                <strong class="tabular-nums">{{ $exam->total_marks }} {{ __('instructor.point_unit') }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-trophy" aria-hidden="true"></i></span>
                <span>{{ __('instructor.passing_marks_label') }}</span>
                <strong class="tabular-nums">{{ $exam->passing_marks }} {{ __('instructor.point_unit') }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-redo" aria-hidden="true"></i></span>
                <span>{{ __('instructor.attempts_allowed_label') }}</span>
                <strong>{{ $exam->attempts_allowed == 0 ? __('instructor.unlimited') : $exam->attempts_allowed }}</strong>
            </div>
        </div>
        @if($exam->description)
            <div style="margin-top:16px">
                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.description') }}</p>
                <p class="id-prose">{{ $exam->description }}</p>
            </div>
        @endif
        @if($exam->instructions)
            <div style="margin-top:16px">
                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.instructions_label') }}</p>
                <p class="id-prose">{{ $exam->instructions }}</p>
            </div>
        @endif
    </section>

    <section class="id-panel id-panel--wide">
        <div class="id-tabs" role="tablist">
            <button type="button" class="id-tab" :class="{ 'is-on': activeTab === 'questions' }" @click="activeTab = 'questions'">
                <i class="fas fa-question-circle" aria-hidden="true"></i>
                {{ __('instructor.questions_tab') }} ({{ $exam->questions->count() }})
            </button>
            <button type="button" class="id-tab" :class="{ 'is-on': activeTab === 'attempts' }" @click="activeTab = 'attempts'">
                <i class="fas fa-users" aria-hidden="true"></i>
                {{ __('instructor.attempts_tab') }} ({{ $attempts->total() }})
            </button>
            <button type="button" class="id-tab" :class="{ 'is-on': activeTab === 'settings' }" @click="activeTab = 'settings'">
                <i class="fas fa-cogs" aria-hidden="true"></i>
                {{ __('instructor.settings_tab') }}
            </button>
        </div>

        <div x-show="activeTab === 'questions'" x-cloak>
            <header class="id-panel__head" style="padding:0 0 12px">
                <h2 style="font-size:15px">{{ __('instructor.exam_questions_title') }}</h2>
                <a href="{{ route('instructor.exams.questions.manage', $exam) }}" class="id-btn id-btn--outline id-btn--sm">
                    <i class="fas fa-cogs" aria-hidden="true"></i>
                    {{ __('instructor.manage_questions') }}
                </a>
            </header>
            @if($exam->questions->count() > 0)
                <div class="id-list">
                    @foreach($exam->questions as $index => $question)
                        <article class="id-list__row">
                            <span class="id-list__ico" aria-hidden="true" style="font-weight:800">{{ $index + 1 }}</span>
                            <div class="id-list__body">
                                <div class="id-list__title" style="font-size:14px">{{ Str::limit($question->question, 80) }}</div>
                                <div class="id-list__meta">
                                    {{ $question->pivot->marks ?? 1 }} {{ __('instructor.point_unit') }}
                                    @if($question->type) · {{ $question->type }} @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="id-empty" style="border:0;background:transparent;padding:24px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-question-circle"></i></span>
                    <p>{{ __('instructor.no_questions') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.add_questions_hint') }}</p>
                    <div class="id-empty__actions">
                        <a href="{{ route('instructor.exams.questions.manage', $exam) }}" class="id-btn id-btn--navy">
                            <i class="fas fa-cogs" aria-hidden="true"></i>
                            {{ __('instructor.manage_questions') }}
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'attempts'" x-cloak>
            <header class="id-panel__head" style="padding:0 0 12px">
                <h2 style="font-size:15px">{{ __('instructor.student_attempts_title') }}</h2>
            </header>
            @if($attempts->count() > 0)
                <div class="id-table-wrap">
                    <table class="id-table">
                        <thead>
                            <tr>
                                <th>{{ __('instructor.students') }}</th>
                                <th>{{ __('instructor.result_label') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempts as $attempt)
                                @php
                                    $chip = match ($attempt->status) {
                                        'completed' => 'id-chip--ok',
                                        'in_progress' => 'id-chip--warn',
                                        default => 'id-chip--muted',
                                    };
                                    $label = match ($attempt->status) {
                                        'completed' => __('instructor.completed_status'),
                                        'in_progress' => __('instructor.in_progress_status'),
                                        default => __('instructor.not_completed'),
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $attempt->user->name ?? '—' }}</strong>
                                        <div class="muted">{{ $attempt->user->email ?? '—' }}</div>
                                    </td>
                                    <td class="tabular-nums">
                                        @if($attempt->status === 'completed' && $attempt->score !== null)
                                            <strong>{{ number_format($attempt->score, 1) }} / {{ $exam->total_marks }}</strong>
                                            <div class="muted">{{ number_format(($attempt->score / max($exam->total_marks, 1)) * 100, 1) }}%</div>
                                        @else
                                            <span class="muted">{{ __('instructor.not_completed') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="id-chip {{ $chip }}">{{ $label }}</span></td>
                                    <td class="tabular-nums muted">
                                        {{ $attempt->submitted_at ? $attempt->submitted_at->format('Y-m-d H:i') : $attempt->created_at->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(method_exists($attempts, 'hasPages') && $attempts->hasPages())
                    <div class="id-pager">{{ $attempts->links() }}</div>
                @endif
            @else
                <div class="id-empty" style="border:0;background:transparent;padding:24px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-users"></i></span>
                    <p>{{ __('instructor.no_attempts') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_attempts_desc') }}</p>
                </div>
            @endif
        </div>

        <div x-show="activeTab === 'settings'" x-cloak>
            <header class="id-panel__head" style="padding:0 0 12px">
                <h2 style="font-size:15px">{{ __('instructor.exam_settings_title') }}</h2>
            </header>
            <div class="id-meta">
                @foreach([
                    ['randomize_questions', __('instructor.randomize_questions')],
                    ['randomize_options', __('instructor.randomize_options')],
                    ['show_results_immediately', __('instructor.show_results_immediately')],
                    ['show_correct_answers', __('instructor.show_correct_answers')],
                    ['show_explanations', __('instructor.show_explanations')],
                    ['allow_review', __('instructor.allow_review')],
                ] as $item)
                    @php $attr = $item[0]; $name = $item[1]; @endphp
                    <div class="id-meta__row">
                        <span class="id-meta__ico"><i class="fas fa-sliders-h" aria-hidden="true"></i></span>
                        <span>{{ $name }}</span>
                        <strong>
                            <span class="id-chip {{ $exam->$attr ? 'id-chip--ok' : 'id-chip--muted' }}">
                                {{ $exam->$attr ? __('instructor.enabled') : __('instructor.inactive') }}
                            </span>
                        </strong>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
