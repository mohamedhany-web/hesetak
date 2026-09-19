@extends('layouts.app')

@section('title', __('instructor.exams'))
@section('page_title', __('instructor.exams'))

@section('content')
@php
    $locale = app()->getLocale();
    $hasFilters = request()->anyFilled(['course_id', 'is_active', 'search']);
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.exams') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.courses') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.exams') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.manage_exams_attempts') }}</p>
        </div>
        <div class="id-hero__actions">
            @if(Route::has('instructor.courses.index'))
                <a href="{{ route('instructor.courses.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-book" aria-hidden="true"></i>
                    {{ __('instructor.courses') }}
                </a>
            @endif
            <a href="{{ route('instructor.exams.create') }}" class="id-btn id-btn--gold">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ __('instructor.create_exam') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.exams') }}">
        <div class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.active') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['active'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-redo"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.attempts') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total_attempts'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-check-double"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.completed_attempts') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['completed_attempts'] ?? 0) }}</span>
            </span>
        </div>
    </section>

    <section class="id-panel" aria-label="{{ __('common.search') }}">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field">
                    <label for="course_id">{{ __('instructor.online_course') }}</label>
                    <select name="course_id" id="course_id" class="id-select">
                        <option value="">{{ __('instructor.all_online_courses') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="is_active">{{ __('common.status') }}</label>
                    <select name="is_active" id="is_active" class="id-select">
                        <option value="">{{ __('instructor.all') }}</option>
                        <option value="1" @selected(request('is_active') === '1')>{{ __('instructor.active') }}</option>
                        <option value="0" @selected(request('is_active') === '0')>{{ __('instructor.inactive') }}</option>
                    </select>
                </div>
                <div class="id-field id-field--span2">
                    <label for="search">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           placeholder="{{ __('instructor.search_placeholder') }}" class="id-input">
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('common.search') }}
                </button>
                @if($hasFilters)
                    <a href="{{ route('instructor.exams.index') }}" class="id-btn id-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        {{ __('common.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.exams') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.exams') }}</h2>
            @if(($stats['total'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['total']) }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($exams as $exam)
                <article class="id-list__row">
                    <span class="id-list__ico {{ $exam->is_active ? 'id-list__ico--teal' : 'id-list__ico--gold' }}" aria-hidden="true">
                        <i class="fas fa-clipboard-check"></i>
                    </span>
                    <div class="id-list__body">
                        <div class="id-list__title" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                            {{ $exam->title }}
                            <span class="id-chip {{ $exam->is_active ? 'id-chip--ok' : 'id-chip--warn' }}">
                                {{ $exam->is_active ? __('instructor.active_status') : __('instructor.inactive_status') }}
                            </span>
                            @if($exam->advancedCourse)
                                <span class="id-chip id-chip--muted">{{ Str::limit($exam->advancedCourse->title, 36) }}</span>
                            @endif
                        </div>
                        @if($exam->description)
                            <div class="id-list__meta">{{ Str::limit($exam->description, 120) }}</div>
                        @endif
                        <div class="id-list__meta" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:10px">
                            <span><i class="fas fa-clock" aria-hidden="true"></i> {{ $exam->duration_minutes }} {{ __('instructor.minutes') }}</span>
                            <span><i class="fas fa-star" aria-hidden="true"></i> {{ $exam->total_marks }} {{ __('instructor.marks') }}</span>
                            <span><i class="fas fa-question-circle" aria-hidden="true"></i> {{ $exam->questions_count }} {{ __('instructor.question_single') }}</span>
                            <span><i class="fas fa-redo" aria-hidden="true"></i> {{ $exam->attempts_count }} {{ __('instructor.attempt_single') }}</span>
                        </div>
                    </div>
                    <div class="id-list__actions">
                        <a href="{{ route('instructor.exams.questions.manage', $exam) }}" class="id-btn id-btn--outline id-btn--sm">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            {{ __('instructor.questions') }}
                        </a>
                        <a href="{{ route('instructor.exams.show', $exam) }}" class="id-btn id-btn--navy id-btn--sm">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            {{ __('common.view') }}
                        </a>
                        <form action="{{ route('instructor.exams.destroy', $exam) }}" method="POST"
                              onsubmit="return confirm(@json(__('instructor.confirm_delete_exam')));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="id-icon-btn" title="{{ __('instructor.delete_exam_title') }}">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                    <p>{{ __('instructor.no_exams') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_exams_description') }}</p>
                    <div class="id-empty__actions">
                        <a href="{{ route('instructor.exams.create') }}" class="id-btn id-btn--navy">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            {{ __('instructor.create_exam') }}
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        @if(method_exists($exams, 'hasPages') && $exams->hasPages())
            <div class="id-pager">{{ $exams->appends(request()->query())->links() }}</div>
        @endif
    </section>
</div>
@endsection
