@extends('layouts.app')

@section('title', __('instructor.assignments'))
@section('page_title', __('instructor.assignments'))

@section('content')
@php
    $locale = app()->getLocale();
    $hasFilters = request()->anyFilled(['course_id', 'status', 'search']);
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.assignments') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.courses') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.assignments') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.manage_assignments_submissions') }}</p>
        </div>
        <div class="id-hero__actions">
            @if(Route::has('instructor.courses.index'))
                <a href="{{ route('instructor.courses.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-book" aria-hidden="true"></i>
                    {{ __('instructor.courses') }}
                </a>
            @endif
            <button type="button" onclick="openCreateModal()" class="id-btn id-btn--gold">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ __('instructor.create_assignment') }}
            </button>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.assignments') }}">
        <div class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-tasks"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.published') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['published'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.draft') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['draft'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-file-upload"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.submissions') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total_submissions'] ?? 0) }}</span>
            </span>
        </div>
    </section>

    <section class="id-panel" aria-label="{{ __('common.search') }}">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field">
                    <label for="course_id">{{ __('instructor.courses') }}</label>
                    <select name="course_id" id="course_id" class="id-select">
                        <option value="">{{ __('instructor.all_courses') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="status">{{ __('common.status') }}</label>
                    <select name="status" id="status" class="id-select">
                        <option value="">{{ __('instructor.all') }}</option>
                        <option value="published" @selected(request('status') === 'published')>{{ __('instructor.published') }}</option>
                        <option value="draft" @selected(request('status') === 'draft')>{{ __('instructor.draft') }}</option>
                        <option value="archived" @selected(request('status') === 'archived')>{{ __('instructor.archived') }}</option>
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
                    <a href="{{ route('instructor.assignments.index') }}" class="id-btn id-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        {{ __('common.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.assignments') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.assignments') }}</h2>
            @if(($stats['total'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['total']) }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($assignments as $assignment)
                @php
                    $chip = match ($assignment->status) {
                        'published' => 'id-chip--ok',
                        'draft' => 'id-chip--warn',
                        default => 'id-chip--muted',
                    };
                    $statusLabel = match ($assignment->status) {
                        'published' => __('instructor.published'),
                        'draft' => __('instructor.draft'),
                        default => __('instructor.archived'),
                    };
                @endphp
                <article class="id-list__row">
                    <span class="id-list__ico id-list__ico--gold" aria-hidden="true">
                        <i class="fas fa-tasks"></i>
                    </span>
                    <div class="id-list__body">
                        <div class="id-list__title" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                            {{ $assignment->title }}
                            <span class="id-chip {{ $chip }}">{{ $statusLabel }}</span>
                            @if($assignment->course)
                                <span class="id-chip id-chip--muted">{{ Str::limit($assignment->course->title, 40) }}</span>
                            @endif
                        </div>
                        @if($assignment->description)
                            <div class="id-list__meta">{{ Str::limit($assignment->description, 120) }}</div>
                        @endif
                        <div class="id-list__meta" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:10px">
                            @if($assignment->due_date)
                                <span><i class="fas fa-calendar" aria-hidden="true"></i> {{ $assignment->due_date->format('Y/m/d') }}</span>
                            @endif
                            <span><i class="fas fa-inbox" aria-hidden="true"></i> {{ $assignment->submissions_count }} {{ __('instructor.submission_single') }}</span>
                            <span><i class="fas fa-star" aria-hidden="true"></i> {{ $assignment->max_score }} {{ __('instructor.score_marks') }}</span>
                        </div>
                    </div>
                    <div class="id-list__actions">
                        <a href="{{ route('instructor.assignments.submissions', $assignment) }}" class="id-btn id-btn--outline id-btn--sm">
                            <i class="fas fa-list" aria-hidden="true"></i>
                            {{ __('instructor.submissions') }}
                        </a>
                        <a href="{{ route('instructor.assignments.show', $assignment) }}" class="id-btn id-btn--navy id-btn--sm">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            {{ __('common.view') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-tasks"></i></span>
                    <p>{{ __('instructor.no_assignments') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_assignments_description') }}</p>
                    <div class="id-empty__actions">
                        <button type="button" onclick="openCreateModal()" class="id-btn id-btn--navy">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            {{ __('instructor.create_assignment') }}
                        </button>
                    </div>
                </div>
            @endforelse
        </div>

        @if(method_exists($assignments, 'hasPages') && $assignments->hasPages())
            <div class="id-pager">{{ $assignments->appends(request()->query())->links() }}</div>
        @endif
    </section>
</div>

{{-- Create assignment modal --}}
<div id="createAssignmentModal" class="id-modal hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="id-modal__panel id-modal__panel--lg" id="modalPanel" onclick="event.stopPropagation()">
        <div class="id-modal__head">
            <h3 id="modal-title">
                <i class="fas fa-tasks" aria-hidden="true" style="color:#C9952A;margin-inline-end:6px"></i>
                {{ __('instructor.create_assignment_modal_title') }}
            </h3>
            <button type="button" onclick="closeCreateModal()" class="id-icon-btn" style="background:#F1F4F8;color:#6B7A93" aria-label="{{ __('common.cancel') }}">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <p class="id-modal__sub" style="padding:0 20px;margin:8px 0 0">{{ __('instructor.create_assignment_modal_subtitle') }}</p>
        <div class="id-modal__body">
            @include('instructor.assignments.create-form', ['courses' => $courses, 'isModal' => true])
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    var modal = document.getElementById('createAssignmentModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            if (typeof updateLessonsOnCourseChange === 'function') updateLessonsOnCourseChange();
        }, 100);
    }
}
function closeCreateModal() {
    var modal = document.getElementById('createAssignmentModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        var form = document.getElementById('assignmentForm');
        if (form) {
            form.reset();
            var lessonSelect = document.getElementById('lesson_id');
            if (lessonSelect && lessonSelect.children.length > 1) {
                while (lessonSelect.children.length > 1) lessonSelect.removeChild(lessonSelect.lastChild);
            }
        }
    }
}
document.getElementById('createAssignmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeCreateModal(); });
</script>
@endsection
