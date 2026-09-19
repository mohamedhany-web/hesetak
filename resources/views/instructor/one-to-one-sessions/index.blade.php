@extends('layouts.app')

@section('title', __('instructor.o1o_title'))
@section('page_title', __('instructor.o1o_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $lessonDuration = (int) ($lessonDuration ?? 50);
    $todayCount = method_exists($todaysSchedule ?? null, 'count')
        ? $todaysSchedule->count()
        : count($todaysSchedule ?? []);
    $studentsCount = method_exists($students ?? null, 'count')
        ? $students->count()
        : count($students ?? []);
    $sessionsTotal = method_exists($sessions, 'total')
        ? $sessions->total()
        : (method_exists($sessions, 'count') ? $sessions->count() : 0);
    $availHref = Route::has('instructor.one-to-one-availability.index')
        ? route('instructor.one-to-one-availability.index')
        : null;
    $calHref = Route::has('instructor.calendar')
        ? route('instructor.calendar')
        : route('dashboard');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.o1o_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.private_lessons') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.o1o_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.o1o_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($availHref)
                <a href="{{ $availHref }}" class="id-btn id-btn--gold">{{ __('instructor.o1a_title') }}</a>
            @endif
            <a href="{{ $calHref }}" class="id-btn id-btn--ghost">{{ __('instructor.my_calendar') }}</a>
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.cd_activity_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1o_today_schedule') }}</span>
                <span class="id-kpi__value">{{ number_format($todayCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-users"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1o_students') }}</span>
                <span class="id-kpi__value">{{ number_format($studentsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-list"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1o_title') }}</span>
                <span class="id-kpi__value">{{ number_format($sessionsTotal) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.o1o_minutes') }}</span>
                <span class="id-kpi__value">{{ $lessonDuration }}</span>
            </span>
        </article>
    </section>

    @if(($newAssignments ?? collect())->isNotEmpty())
        <section class="id-panel" aria-label="{{ __('instructor.o1o_new_student') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.o1o_new_student') }}</h2>
            </header>
            <div class="id-list">
                @foreach($newAssignments as $assignment)
                    @php
                        $student = $assignment->student;
                        $age = $student?->birth_date?->age;
                        $related = ($students ?? collect())->first(fn ($row) => (int) ($row['student']->id ?? 0) === (int) ($student->id ?? 0));
                    @endphp
                    <article class="id-panel" style="padding:16px;box-shadow:none;margin:0">
                        <div style="margin-bottom:10px">
                            <span class="id-chip id-chip--warn">{{ __('instructor.o1o_new_student') }}</span>
                        </div>
                        <p style="margin:0 0 12px;font-size:13px;font-weight:700;color:#3A4A63">
                            {{ __('instructor.o1o_new_student_body', ['name' => $student->name ?? __('instructor.pm_student_fallback')]) }}
                        </p>
                        <div class="id-meta">
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-user" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_student') }}</span>
                                <strong>{{ $student->name ?? '—' }}</strong>
                            </div>
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-birthday-cake" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_age') }}</span>
                                <strong>{{ $age !== null ? $age : '—' }}</strong>
                            </div>
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-book" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_subject_scope') }}</span>
                                <strong>{{ $related['course']->title ?? $assignment->scopeLabel() }}</strong>
                            </div>
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-list-ol" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_lessons') }}</span>
                                <strong>{{ $related['total'] ?? '—' }}</strong>
                            </div>
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-play" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_plan_starts') }}</span>
                                <strong>{{ optional($related['starts_at'] ?? $assignment->starts_at)->format('Y-m-d') ?? '—' }}</strong>
                            </div>
                            <div class="id-meta__row">
                                <span class="id-meta__ico"><i class="fas fa-flag-checkered" aria-hidden="true"></i></span>
                                <span>{{ __('instructor.o1o_plan_ends') }}</span>
                                <strong>{{ optional($related['ends_at'] ?? $assignment->ends_at)->format('Y-m-d') ?? '—' }}</strong>
                            </div>
                            @if(($related['notes'] ?? $assignment->notes ?? null))
                                <div class="id-meta__row">
                                    <span class="id-meta__ico"><i class="fas fa-sticky-note" aria-hidden="true"></i></span>
                                    <span>{{ __('instructor.o1o_notes') }}</span>
                                    <strong>{{ $related['notes'] ?? $assignment->notes }}</strong>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="id-panel" aria-label="{{ __('instructor.o1o_today_schedule') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.o1o_today_schedule') }}</h2>
            <span class="id-panel__badge">{{ number_format($todayCount) }}</span>
        </header>
        <div class="id-list">
            @forelse($todaysSchedule ?? [] as $slot)
                @php
                    $dur = (int) ($slot->duration_minutes ?: $lessonDuration);
                    $end = $slot->scheduled_at?->copy()->addMinutes($dur);
                @endphp
                <div class="id-list__row">
                    <span class="id-list__ico" aria-hidden="true"><i class="fas fa-clock"></i></span>
                    <div class="id-list__body">
                        <div class="id-list__title">
                            {{ $slot->course->title ?? __('instructor.cal_private') }}
                            — {{ $slot->student->name ?? '—' }}
                        </div>
                        <div class="id-list__meta">
                            <span class="tabular-nums"><x-app-datetime :at="$slot->scheduled_at" pattern="g:i A" /></span>
                            · {{ $dur }} {{ __('instructor.o1o_minutes') }}
                            @if($end)
                                · <x-app-datetime :at="$slot->scheduled_at" pattern="g:i A" />–<x-app-datetime :at="$end" pattern="g:i A" />
                            @endif
                        </div>
                    </div>
                    <div class="id-list__actions">
                        <span class="id-chip id-chip--ok">{{ __('instructor.o1o_upcoming') }}</span>
                        <a href="{{ route('instructor.one-to-one-sessions.show', $slot) }}" class="id-btn id-btn--navy" style="min-height:36px;padding:0 12px;font-size:12px">
                            {{ __('instructor.o1o_manage') }}
                        </a>
                    </div>
                </div>
            @empty
                <div class="id-empty">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-calendar"></i></span>
                    <p>{{ __('instructor.o1o_no_today') }}</p>
                    @if($availHref)
                        <div class="id-empty__actions">
                            <a href="{{ $availHref }}" class="id-btn id-btn--navy">{{ __('instructor.o1a_title') }}</a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </section>

    @if(($students ?? collect())->isNotEmpty())
        <section class="id-panel" aria-label="{{ __('instructor.o1o_students') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.o1o_students') }}</h2>
                <span class="id-panel__badge">{{ number_format($studentsCount) }}</span>
            </header>
            <div class="id-list">
                @foreach($students as $row)
                    <div class="id-list__row">
                        <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ $row['student']->name ?? '—' }}</div>
                            <div class="id-list__meta">
                                {{ $row['course']->title ?? '' }}
                                · {{ $row['pending'] }} {{ __('instructor.o1o_pending') }}
                                · {{ $row['scheduled'] }} {{ __('instructor.o1o_scheduled') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.o1o_title') }}">
        <header class="id-panel__head">
            <div>
                <h2>{{ __('instructor.o1o_title') }}</h2>
                <p class="id-panel__hint">{{ __('instructor.o1o_subtitle') }}</p>
            </div>
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.o1o_student') }}</th>
                        <th>{{ __('instructor.o1o_subject_scope') }}</th>
                        <th>#</th>
                        <th>{{ __('instructor.o1o_status') }}</th>
                        <th>{{ __('instructor.o1o_time') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td><strong>{{ $session->student->name ?? '—' }}</strong></td>
                            <td><span class="muted">{{ $session->course->title ?? '—' }}</span></td>
                            <td class="tabular-nums">{{ $session->session_number }}</td>
                            <td><span class="id-chip id-chip--muted">{{ $session->statusLabel() }}</span></td>
                            <td class="tabular-nums">
                                @if($session->scheduled_at)
                                    <x-app-datetime :at="$session->scheduled_at" pattern="Y-m-d H:i" />
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td class="id-table__end">
                                <a href="{{ route('instructor.one-to-one-sessions.show', $session) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                    {{ __('instructor.o1o_manage') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-user-graduate"></i></span>
                                    <p>{{ __('instructor.o1o_empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($sessions, 'links') && $sessions->hasPages())
            <div class="id-pager">{{ $sessions->links() }}</div>
        @endif
    </section>
</div>
@endsection
