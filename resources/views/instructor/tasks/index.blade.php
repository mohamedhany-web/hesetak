@extends('layouts.app')

@section('title', __('instructor.tasks_from_management') . ' - ' . config('app.name'))
@section('page_title', __('instructor.tasks_from_management'))

@section('content')
@php
    $locale = app()->getLocale();
    $hasFilters = request()->anyFilled(['search', 'status', 'priority']);
    $requestsHref = Route::has('instructor.management-requests.index')
        ? route('instructor.management-requests.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.tasks_from_management') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.tasks') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.tasks_from_management') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.tasks_assigned_by_management') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($requestsHref)
                <a href="{{ $requestsHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    {{ __('instructor.submit_requests_to_management') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.total_tasks') }}">
        <a href="{{ route('instructor.tasks.index') }}" class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-check-square"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_tasks') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.tasks.index', ['status' => 'pending']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.pending') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['pending'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.tasks.index', ['status' => 'in_progress']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-spinner"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.in_progress') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['in_progress'] ?? 0) }}</span>
            </span>
        </a>
        <a href="{{ route('instructor.tasks.index', ['status' => 'completed']) }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-check-double"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.completed_attempts') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['completed'] ?? 0) }}</span>
            </span>
        </a>
    </section>

    <section class="id-panel" aria-label="{{ __('common.search') }}">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field id-field--span2">
                    <label for="task-search">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="task-search" value="{{ request('search') }}"
                           placeholder="{{ __('instructor.search_in_tasks') }}" class="id-input">
                </div>
                <div class="id-field">
                    <label for="task-status">{{ __('common.status') }}</label>
                    <select name="status" id="task-status" class="id-select">
                        <option value="">{{ __('instructor.all_statuses') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('instructor.pending') }}</option>
                        <option value="in_progress" @selected(request('status') === 'in_progress')>{{ __('instructor.in_progress') }}</option>
                        <option value="completed" @selected(request('status') === 'completed')>{{ __('instructor.completed_attempts') }}</option>
                    </select>
                </div>
                <div class="id-field">
                    <label for="task-priority">{{ __('instructor.priority') }}</label>
                    <select name="priority" id="task-priority" class="id-select">
                        <option value="">{{ __('instructor.all_priorities') }}</option>
                        <option value="low" @selected(request('priority') === 'low')>{{ __('instructor.low') }}</option>
                        <option value="medium" @selected(request('priority') === 'medium')>{{ __('instructor.medium') }}</option>
                        <option value="high" @selected(request('priority') === 'high')>{{ __('instructor.high') }}</option>
                        <option value="urgent" @selected(request('priority') === 'urgent')>{{ __('instructor.urgent') }}</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('common.search') }}
                </button>
                @if($hasFilters)
                    <a href="{{ route('instructor.tasks.index') }}" class="id-btn id-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        {{ __('common.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.tasks_from_management') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.tasks_from_management') }}</h2>
            @if(($stats['total'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['total']) }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($tasks as $task)
                @php
                    $prioChip = match ($task->priority) {
                        'urgent' => 'id-chip--rose',
                        'high' => 'id-chip--warn',
                        'medium' => 'id-chip--muted',
                        default => 'id-chip--muted',
                    };
                    $prioLabel = match ($task->priority) {
                        'urgent' => __('instructor.urgent'),
                        'high' => __('instructor.high'),
                        'medium' => __('instructor.medium'),
                        default => __('instructor.low'),
                    };
                    $stChip = match ($task->status) {
                        'completed' => 'id-chip--ok',
                        'in_progress' => 'id-chip--muted',
                        default => 'id-chip--warn',
                    };
                    $stLabel = match ($task->status) {
                        'completed' => __('instructor.completed_attempts'),
                        'in_progress' => __('instructor.in_progress'),
                        default => __('instructor.pending'),
                    };
                @endphp
                <article class="id-list__row">
                    <span class="id-list__ico {{ $task->status === 'completed' ? 'id-list__ico--teal' : ($task->priority === 'urgent' ? '' : 'id-list__ico--gold') }}" aria-hidden="true"
                          @if($task->priority === 'urgent' && $task->status !== 'completed') style="background:#FCEAEA;color:#C45C5C" @endif>
                        <i class="fas {{ $task->status === 'completed' ? 'fa-check-double' : 'fa-tasks' }}"></i>
                    </span>
                    <div class="id-list__body">
                        <div class="id-list__title" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                            {{ $task->title }}
                            @if($task->assigner)
                                <span class="id-chip id-chip--muted">{{ __('instructor.from_management') }}</span>
                            @endif
                            <span class="id-chip {{ $prioChip }}">{{ $prioLabel }}</span>
                            <span class="id-chip {{ $stChip }}">{{ $stLabel }}</span>
                        </div>
                        @if($task->description)
                            <div class="id-list__meta">{{ Str::limit($task->description, 160) }}</div>
                        @endif
                        <div class="id-list__meta" style="margin-top:4px;display:flex;flex-wrap:wrap;gap:10px">
                            @if($task->relatedCourse)
                                <span><i class="fas fa-book" aria-hidden="true"></i> {{ $task->relatedCourse->title ?? '—' }}</span>
                            @endif
                            @if($task->relatedLecture)
                                <span><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> {{ $task->relatedLecture->title ?? '—' }}</span>
                            @endif
                            @if($task->due_date)
                                <span><i class="fas fa-calendar" aria-hidden="true"></i> {{ $task->due_date->format('Y/m/d') }}</span>
                                @if($task->due_date->isPast() && $task->status != 'completed')
                                    <span class="id-chip id-chip--rose">{{ __('instructor.late') }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="id-list__actions">
                        <a href="{{ route('instructor.tasks.show', $task) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            {{ __('instructor.view_and_submit') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-check-square"></i></span>
                    <p>{{ __('instructor.no_tasks_from_management') }}</p>
                    <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.no_tasks_description') }}</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($tasks, 'hasPages') && $tasks->hasPages())
            <div class="id-pager">{{ $tasks->appends(request()->query())->links() }}</div>
        @endif
    </section>
</div>
@endsection
