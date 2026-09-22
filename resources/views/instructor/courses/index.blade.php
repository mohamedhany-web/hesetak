@extends('layouts.app')

@section('title', __('instructor.my_courses') . ' - ' . config('app.name'))
@section('page_title', __('instructor.my_courses'))

@section('content')
@php
    $hasFilters = request()->anyFilled(['search', 'status']);
    $markTones = ['', 'id-course__mark--gold', 'id-course__mark--teal', 'id-course__mark--rose'];
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.my_courses') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.course_tools') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.my_courses') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.courses_assigned_to_you') }}</p>
        </div>
        <div class="id-hero__actions">
            @if(Route::has('instructor.lectures.index'))
                <a href="{{ route('instructor.lectures.index') }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                    {{ __('instructor.lectures') }}
                </a>
            @endif
            @if(Route::has('instructor.calendar'))
                <a href="{{ route('instructor.calendar') }}" class="id-btn id-btn--gold">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    {{ __('instructor.my_calendar') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.my_courses') }}">
        <div class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-book-open"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_courses') }}</span>
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
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-ban"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.inactive') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['inactive'] ?? 0) }}</span>
            </span>
        </div>
        <div class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.total_students') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total_students'] ?? 0) }}</span>
            </span>
        </div>
    </section>

    @if(($stats['total'] ?? 0) > 0)
        <section class="id-shortcuts" aria-label="{{ __('instructor.course_tools') }}">
            @if(Route::has('instructor.lectures.index'))
                <a href="{{ route('instructor.lectures.index') }}" class="id-shortcut">
                    <i class="fas fa-chalkboard" aria-hidden="true"></i>
                    {{ __('instructor.lectures') }}
                </a>
            @endif
            @if(Route::has('instructor.assignments.index'))
                <a href="{{ route('instructor.assignments.index') }}" class="id-shortcut">
                    <i class="fas fa-tasks" aria-hidden="true"></i>
                    {{ __('instructor.assignments') }}
                </a>
            @endif
            @if(Route::has('instructor.exams.index'))
                <a href="{{ route('instructor.exams.index') }}" class="id-shortcut">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    {{ __('instructor.exams') }}
                </a>
            @endif
            @if(Route::has('instructor.question-banks.index'))
                <a href="{{ route('instructor.question-banks.index') }}" class="id-shortcut">
                    <i class="fas fa-database" aria-hidden="true"></i>
                    {{ __('instructor.question_banks') }}
                </a>
            @endif
            @if(Route::has('instructor.attendance.index'))
                <a href="{{ route('instructor.attendance.index') }}" class="id-shortcut">
                    <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                    {{ __('instructor.attendance') }}
                </a>
            @endif
            @if(Route::has('instructor.lecture-recordings.index'))
                <a href="{{ route('instructor.lecture-recordings.index') }}" class="id-shortcut">
                    <i class="fas fa-video" aria-hidden="true"></i>
                    {{ __('instructor.lecture_recordings') }}
                </a>
            @endif
        </section>
    @endif

    <section class="id-panel" aria-label="{{ __('common.search') }}">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field id-field--span2">
                    <label for="course-search">{{ __('common.search') }}</label>
                    <input type="text" name="search" id="course-search" value="{{ request('search') }}"
                           placeholder="{{ __('instructor.search_in_course_titles') }}" class="id-input">
                </div>
                <div class="id-field">
                    <label for="course-status">{{ __('common.status') }}</label>
                    <select name="status" id="course-status" class="id-select">
                        <option value="">{{ __('instructor.all_statuses') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('instructor.active_status') }}</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>{{ __('instructor.inactive_status') }}</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    {{ __('common.search') }}
                </button>
                @if($hasFilters)
                    <a href="{{ route('instructor.courses.index') }}" class="id-btn id-btn--outline">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        {{ __('common.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.my_courses') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.my_courses') }}</h2>
            @if($courses->total() > 0)
                <span class="id-panel__badge">{{ number_format($courses->total()) }}</span>
            @endif
        </header>

        @if($courses->count() > 0)
            <div class="id-courses">
                @foreach($courses as $i => $course)
                    @php
                        $markTone = $markTones[$i % count($markTones)];
                        $levelLabel = match ($course->level) {
                            'beginner' => __('instructor.beginner'),
                            'intermediate' => __('instructor.intermediate'),
                            'advanced' => __('instructor.advanced'),
                            default => null,
                        };
                    @endphp
                    <article class="id-course">
                        <div class="id-course__top">
                            <span class="id-course__mark {{ $markTone }}" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                            <span class="id-chip {{ $course->is_active ? 'id-chip--ok' : 'id-chip--rose' }}">
                                <i class="fas {{ $course->is_active ? 'fa-check-circle' : 'fa-ban' }}" aria-hidden="true"></i>
                                {{ $course->is_active ? __('instructor.active_status') : __('instructor.inactive_status') }}
                            </span>
                        </div>

                        <h3 class="id-course__title">{{ $course->title }}</h3>

                        @if($course->description)
                            <p class="id-course__desc">{{ Str::limit(strip_tags((string) $course->description), 110) }}</p>
                        @endif

                        <div class="id-course__tags">
                            @if($course->academicYear)
                                <span class="id-chip id-chip--muted">{{ $course->academicYear->name }}</span>
                            @endif
                            @if($course->academicSubject)
                                <span class="id-chip">{{ $course->academicSubject->name }}</span>
                            @endif
                            @if($levelLabel)
                                <span class="id-chip id-chip--warn">{{ $levelLabel }}</span>
                            @endif
                            @if($course->is_free || $course->effectivePurchasePrice() <= 0)
                                <span class="id-chip id-chip--ok">{{ __('instructor.free') }}</span>
                            @endif
                        </div>

                        <div class="id-course__stats">
                            <div>
                                <b>{{ number_format($course->lectures_count ?? 0) }}</b>
                                <span>{{ __('instructor.lecture_single') }}</span>
                            </div>
                            <div>
                                <b>{{ number_format($course->enrollments_count ?? 0) }}</b>
                                <span>{{ __('instructor.student_single') }}</span>
                            </div>
                        </div>

                        <div class="id-course__foot">
                            <a href="{{ route('instructor.courses.show', $course) }}" class="id-btn id-btn--navy">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                {{ __('instructor.view_details') }}
                            </a>
                            @if(Route::has('instructor.lectures.index'))
                                <a href="{{ route('instructor.lectures.index') }}" class="id-btn id-btn--outline">
                                    <i class="fas fa-chalkboard" aria-hidden="true"></i>
                                    {{ __('instructor.lectures') }}
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="id-pager">
                {{ $courses->links() }}
            </div>
        @else
            <div class="id-empty">
                <div class="id-empty__mark" aria-hidden="true"><i class="fas fa-book-open"></i></div>
                <p>{{ __('instructor.no_courses') }}</p>
                <p class="id-list__meta">{{ __('instructor.courses_description_empty') }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
