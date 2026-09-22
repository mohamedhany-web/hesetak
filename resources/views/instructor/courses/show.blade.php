@extends('layouts.app')

@section('title', __('instructor.course_details') . ' - ' . $course->title)
@section('page_title', $course->title)

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<div class="su-page" x-data="{ activeTab: 'overview' }">
    <div class="su-page-head">
        <div class="min-w-0">
            <nav class="su-crumb-inline" aria-label="breadcrumb">
                <a href="{{ route('instructor.courses.index') }}">{{ __('instructor.my_courses') }}</a>
                <span>/</span>
                <strong style="color:var(--su-ink)">{{ $course->title }}</strong>
            </nav>
            <h1 class="su-page-head__title">{{ $course->title }}</h1>
            <div class="su-chip-row">
                <span class="su-chip {{ $course->is_active ? 'su-chip--ok' : 'su-chip--off' }}">
                    <i class="fas {{ $course->is_active ? 'fa-check-circle' : 'fa-ban' }}" aria-hidden="true"></i>
                    {{ $course->is_active ? __('instructor.active_status') : __('instructor.inactive_status') }}
                </span>
                @if($course->is_featured)
                    <span class="su-chip su-chip--warn">
                        <i class="fas fa-star" aria-hidden="true"></i> {{ __('instructor.featured') }}
                    </span>
                @endif
                @if($course->academicYear)
                    <span class="su-chip su-soft-1">
                        <i class="fas fa-graduation-cap" aria-hidden="true"></i> {{ $course->academicYear->name }}
                    </span>
                @endif
                @if($course->academicSubject)
                    <span class="su-chip su-soft-2">
                        <i class="fas fa-book" aria-hidden="true"></i> {{ $course->academicSubject->name }}
                    </span>
                @endif
            </div>
        </div>
        <div class="su-page-head__actions">
            <a href="{{ route('instructor.courses.index') }}" class="su-btn">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </div>

    <section class="su-kpi-row" style="margin-bottom:20px">
        <div class="su-kpi su-kpi--1">
            <div class="su-kpi__l">{{ __('instructor.lecture_single') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($stats['total_lectures'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--2">
            <div class="su-kpi__l">{{ __('instructor.exam_single') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($stats['total_exams'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-clipboard-check" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--3">
            <div class="su-kpi__l">{{ __('instructor.assignment_single') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($stats['total_assignments'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-tasks" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--4">
            <div class="su-kpi__l">{{ __('instructor.student_single') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($stats['total_students'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-user-graduate" aria-hidden="true"></i></div>
            </div>
        </div>
    </section>

    <section class="su-card" style="padding:16px">
        <div class="su-tabs-bar" role="tablist">
            <button type="button" class="su-tab" :class="{ 'is-on': activeTab === 'overview' }" @click="activeTab = 'overview'">
                <i class="fas fa-chart-line" aria-hidden="true"></i> {{ __('instructor.overview') }}
            </button>
            <button type="button" class="su-tab" :class="{ 'is-on': activeTab === 'lectures' }" @click="activeTab = 'lectures'">
                <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> {{ __('instructor.lectures') }}
                @if(($stats['upcoming_lectures'] ?? 0) > 0)
                    <span class="su-tab__badge">{{ $stats['upcoming_lectures'] }}</span>
                @endif
            </button>
            <button type="button" class="su-tab" :class="{ 'is-on': activeTab === 'exams' }" @click="activeTab = 'exams'">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i> {{ __('instructor.exams') }}
            </button>
            <button type="button" class="su-tab" :class="{ 'is-on': activeTab === 'assignments' }" @click="activeTab = 'assignments'">
                <i class="fas fa-tasks" aria-hidden="true"></i> {{ __('instructor.assignments') }}
                @if(($stats['pending_submissions'] ?? 0) > 0)
                    <span class="su-tab__badge">{{ $stats['pending_submissions'] }}</span>
                @endif
            </button>
            <button type="button" class="su-tab" :class="{ 'is-on': activeTab === 'students' }" @click="activeTab = 'students'">
                <i class="fas fa-user-graduate" aria-hidden="true"></i> {{ __('instructor.students') }}
                @if(($stats['total_students'] ?? 0) > 0)
                    <span class="su-tab__badge">{{ $stats['total_students'] }}</span>
                @endif
            </button>
        </div>

        <div style="padding:16px 4px 4px">
            {{-- Overview --}}
            <div x-show="activeTab === 'overview'" x-cloak>
                <div class="su-detail-grid">
                    <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
                        @if($course->thumbnail)
                            <img src="{{ storage_asset($course->thumbnail) }}" alt="{{ $course->title }}" class="su-cover">
                        @endif

                        <div class="su-card" style="background:var(--su-bg);margin:0">
                            <h2 class="su-card__title">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                {{ __('instructor.course_info') }}
                            </h2>
                            <div class="su-dl">
                                <div class="su-dl__item">
                                    <label>{{ __('instructor.title') }}</label>
                                    <div>{{ $course->title }}</div>
                                </div>
                                @if($course->instructor)
                                    <div class="su-dl__item">
                                        <label>{{ __('instructor.instructor_label') }}</label>
                                        <div>{{ $course->instructor->name }}</div>
                                    </div>
                                @endif
                                @if($course->level)
                                    <div class="su-dl__item">
                                        <label>{{ __('instructor.level') }}</label>
                                        <div>
                                            @if($course->level == 'beginner') {{ __('instructor.beginner') }}
                                            @elseif($course->level == 'intermediate') {{ __('instructor.intermediate') }}
                                            @elseif($course->level == 'advanced') {{ __('instructor.advanced') }}
                                            @else {{ __('instructor.level_unspecified') }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                <div class="su-dl__item">
                                    <label>{{ __('instructor.price') }}</label>
                                    <div class="tabular-nums">
                                        @if(!$course->is_free && $course->effectivePurchasePrice() > 0)
                                            @if($course->hasPromotionalPrice())
                                                <span style="display:block;font-size:12px;color:var(--su-ink-40);text-decoration:line-through;font-weight:500">{{ number_format($course->listPriceAmount(), 2) }} $</span>
                                            @endif
                                            {{ number_format($course->effectivePurchasePrice(), 2) }} $
                                        @else
                                            <span style="color:#15803d">{{ __('instructor.free') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="su-dl__item">
                                    <label>{{ __('instructor.course_duration') }}</label>
                                    <div>
                                        {{ $course->duration_hours ?? 0 }} {{ __('instructor.hour') }}
                                        @if($course->duration_minutes && $course->duration_minutes > 0)
                                            {{ __('instructor.and') }} {{ $course->duration_minutes }} {{ __('instructor.minutes') }}
                                        @endif
                                    </div>
                                </div>
                                @if($course->programming_language)
                                    <div class="su-dl__item">
                                        <label>{{ __('instructor.programming_language') }}</label>
                                        <div>{{ $course->programming_language }}</div>
                                    </div>
                                @endif
                            </div>

                            @if($course->description)
                                <div class="su-prose-box">
                                    <label>{{ __('instructor.description') }}</label>
                                    <div class="su-prose-body">{{ $course->description }}</div>
                                </div>
                            @endif
                            @if($course->objectives)
                                <div class="su-prose-box">
                                    <label>{{ __('instructor.objectives') }}</label>
                                    <div class="su-prose-body">{{ $course->objectives }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <aside style="display:flex;flex-direction:column;gap:16px;min-width:0">
                        <div class="su-card" style="background:var(--su-bg);margin:0">
                            <h2 class="su-card__title">
                                <i class="fas fa-bolt" aria-hidden="true"></i>
                                {{ __('instructor.quick_actions') }}
                            </h2>
                            <div class="su-action-list">
                                <a href="{{ route('instructor.lectures.create', ['course_id' => $course->id]) }}" class="su-action-link">
                                    <span class="su-action-link__ico su-soft-1"><i class="fas fa-video" aria-hidden="true"></i></span>
                                    <strong>{{ __('instructor.add_lecture') }}</strong>
                                </a>
                                <a href="{{ route('instructor.exams.create', ['course_id' => $course->id]) }}" class="su-action-link">
                                    <span class="su-action-link__ico su-soft-2"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
                                    <strong>{{ __('instructor.create_exam') }}</strong>
                                </a>
                                <a href="{{ route('instructor.assignments.create', ['course_id' => $course->id]) }}" class="su-action-link">
                                    <span class="su-action-link__ico su-soft-4"><i class="fas fa-tasks" aria-hidden="true"></i></span>
                                    <strong>{{ __('instructor.create_assignment') }}</strong>
                                </a>
                            </div>
                        </div>

                        <div class="su-card" style="background:var(--su-bg);margin:0">
                            <h2 class="su-card__title">
                                <i class="fas fa-chart-bar" aria-hidden="true"></i>
                                {{ __('instructor.statistics') }}
                            </h2>
                            <div class="su-stat-lines">
                                <div class="su-stat-line su-soft-1">
                                    <span>{{ __('instructor.upcoming_lectures') }}</span>
                                    <strong>{{ $stats['upcoming_lectures'] ?? 0 }}</strong>
                                </div>
                                <div class="su-stat-line su-soft-2">
                                    <span>{{ __('instructor.active_exams') }}</span>
                                    <strong>{{ $stats['active_exams'] ?? 0 }}</strong>
                                </div>
                                <div class="su-stat-line su-soft-4">
                                    <span>{{ __('instructor.pending_submissions') }}</span>
                                    <strong>{{ $stats['pending_submissions'] ?? 0 }}</strong>
                                </div>
                                <div class="su-stat-line su-soft-3">
                                    <span>{{ __('instructor.enrolled_students') }}</span>
                                    <strong>{{ $stats['total_students'] ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            {{-- Lectures --}}
            <div x-show="activeTab === 'lectures'" x-cloak>
                <div class="su-section-head">
                    <h3><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> {{ __('instructor.lectures') }} ({{ $lectures->total() }})</h3>
                    <a href="{{ route('instructor.lectures.create', ['course_id' => $course->id]) }}" class="su-btn su-btn--primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.add_lecture') }}
                    </a>
                </div>
                @if($lectures->count() > 0)
                    <div class="su-list">
                        @foreach($lectures as $lecture)
                            <div class="su-list-item">
                                <span class="su-list-item__ico su-soft-1"><i class="fas fa-video" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $lecture->title }}</div>
                                    <div class="su-list-item__meta">
                                        {{ optional($lecture->scheduled_at)->format('Y/m/d H:i') }}
                                        @if($lecture->lesson)
                                            · {{ $lecture->lesson->title }}
                                        @endif
                                    </div>
                                </div>
                                <span class="su-chip
                                    @if($lecture->status == 'scheduled') su-soft-1
                                    @elseif($lecture->status == 'in_progress') su-chip--warn
                                    @elseif($lecture->status == 'completed') su-chip--ok
                                    @else su-chip--off
                                    @endif">
                                    @if($lecture->status == 'scheduled') {{ __('instructor.scheduled_lecture') }}
                                    @elseif($lecture->status == 'in_progress') {{ __('instructor.in_progress') }}
                                    @elseif($lecture->status == 'completed') {{ __('instructor.completed') }}
                                    @else {{ __('instructor.cancelled_lecture') }}
                                    @endif
                                </span>
                                <div class="su-list-item__actions">
                                    <a href="{{ route('instructor.lectures.show', $lecture) }}" class="su-icon-link" title="{{ __('common.view') }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('instructor.lectures.edit', $lecture) }}" class="su-icon-link su-icon-link--ghost" title="{{ __('common.edit') }}"><i class="fas fa-edit" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="su-pager">{{ $lectures->links() }}</div>
                @else
                    <div class="su-empty">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                        <p>{{ __('instructor.no_lectures') }}</p>
                        <a href="{{ route('instructor.lectures.create', ['course_id' => $course->id]) }}" class="su-btn su-btn--primary">
                            <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.add_new_lecture') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Exams --}}
            <div x-show="activeTab === 'exams'" x-cloak>
                <div class="su-section-head">
                    <h3><i class="fas fa-clipboard-check" aria-hidden="true"></i> {{ __('instructor.exams') }} ({{ $exams->total() }})</h3>
                    <a href="{{ route('instructor.exams.create') }}" class="su-btn su-btn--primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.create_exam') }}
                    </a>
                </div>
                @if($exams->count() > 0)
                    <div class="su-list">
                        @foreach($exams as $exam)
                            <div class="su-list-item">
                                <span class="su-list-item__ico su-soft-2"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $exam->title }}</div>
                                    <div class="su-list-item__meta">
                                        {{ $exam->duration_minutes }} {{ __('instructor.minutes') }}
                                        · {{ $exam->questions_count }} {{ __('instructor.question_single') }}
                                        @if($exam->lesson) · {{ $exam->lesson->title }} @endif
                                    </div>
                                </div>
                                <span class="su-chip {{ $exam->is_active ? 'su-chip--ok' : 'su-chip--warn' }}">
                                    <i class="fas {{ $exam->is_active ? 'fa-check-circle' : 'fa-ban' }}" aria-hidden="true"></i>
                                    {{ $exam->is_active ? __('instructor.active_status') : __('instructor.inactive_status') }}
                                </span>
                                <div class="su-list-item__actions">
                                    <a href="{{ route('instructor.exams.show', $exam) }}" class="su-icon-link"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="su-pager">{{ $exams->links() }}</div>
                @else
                    <div class="su-empty">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        <p>{{ __('instructor.no_exams') }}</p>
                        <a href="{{ route('instructor.exams.create') }}" class="su-btn su-btn--primary">
                            <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.create_new_exam') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Assignments --}}
            <div x-show="activeTab === 'assignments'" x-cloak>
                <div class="su-section-head">
                    <h3><i class="fas fa-tasks" aria-hidden="true"></i> {{ __('instructor.assignments') }} ({{ $assignments->total() }})</h3>
                    <a href="{{ route('instructor.assignments.create') }}" class="su-btn su-btn--primary">
                        <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.create_assignment') }}
                    </a>
                </div>
                @if($assignments->count() > 0)
                    <div class="su-list">
                        @foreach($assignments as $assignment)
                            <div class="su-list-item">
                                <span class="su-list-item__ico su-soft-4"><i class="fas fa-tasks" aria-hidden="true"></i></span>
                                <div class="su-list-item__body">
                                    <div class="su-list-item__title">{{ $assignment->title }}</div>
                                    <div class="su-list-item__meta">
                                        {{ $assignment->submissions_count }} {{ __('instructor.submission_single') }}
                                        @if($assignment->due_date)
                                            · {{ $assignment->due_date->format('Y/m/d') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="su-chip
                                    @if($assignment->status == 'published') su-chip--ok
                                    @elseif($assignment->status == 'draft') su-chip--warn
                                    @else su-soft-2
                                    @endif">
                                    @if($assignment->status == 'published') {{ __('instructor.published') }}
                                    @elseif($assignment->status == 'draft') {{ __('instructor.draft') }}
                                    @else {{ __('instructor.archived') }}
                                    @endif
                                </span>
                                <div class="su-list-item__actions">
                                    <a href="{{ route('instructor.assignments.show', $assignment) }}" class="su-icon-link"><i class="fas fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('instructor.assignments.submissions', $assignment) }}" class="su-icon-link su-icon-link--ghost"><i class="fas fa-list" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="su-pager">{{ $assignments->links() }}</div>
                @else
                    <div class="su-empty">
                        <i class="fas fa-tasks" aria-hidden="true"></i>
                        <p>{{ __('instructor.no_assignments') }}</p>
                        <a href="{{ route('instructor.assignments.create') }}" class="su-btn su-btn--primary">
                            <i class="fas fa-plus" aria-hidden="true"></i> {{ __('instructor.create_assignment_modal_title') }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- Students --}}
            <div x-show="activeTab === 'students'" x-cloak>
                <div class="su-section-head">
                    <h3><i class="fas fa-user-graduate" aria-hidden="true"></i> {{ __('instructor.enrolled_students') }} ({{ $enrollments->total() }})</h3>
                </div>
                @if($enrollments->count() > 0)
                    <div class="su-table-wrap">
                        <table class="su-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.name') }}</th>
                                    <th>{{ __('instructor.email') }}</th>
                                    <th>{{ __('instructor.phone') }}</th>
                                    <th>{{ __('instructor.registration_date') }}</th>
                                    <th>{{ __('instructor.progress_label') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    @php
                                        $student = $enrollment->student ?? $enrollment->user;
                                        $progress = (float) ($enrollment->progress ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="su-person">
                                                <span class="su-avatar">{{ mb_substr($student->name ?? __('instructor.student_single'), 0, 1) }}</span>
                                                <strong>{{ $student->name ?? __('instructor.not_specified') }}</strong>
                                            </div>
                                        </td>
                                        <td style="color:var(--su-ink-40)">{{ $student->email ?? __('instructor.not_specified') }}</td>
                                        <td style="color:var(--su-ink-40)">{{ $student->phone ?? __('instructor.not_specified') }}</td>
                                        <td style="color:var(--su-ink-40)">{{ optional($enrollment->enrolled_at ?? $enrollment->created_at)->format('Y/m/d') }}</td>
                                        <td>
                                            <span class="tabular-nums">{{ number_format($progress, 0) }}%</span>
                                        </td>
                                        <td>
                                            <span class="su-chip su-chip--ok">
                                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                {{ $enrollment->status_text ?? __('instructor.active_status') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="su-pager">{{ $enrollments->appends(request()->except('students_page'))->links() }}</div>
                @else
                    <div class="su-empty">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i>
                        <p>{{ __('instructor.no_enrolled_students') }}</p>
                        <p>{{ __('instructor.no_enrolled_description') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
