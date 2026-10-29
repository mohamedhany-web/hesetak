@extends('layouts.app')

@section('title', __('instructor.lecture_attendance') . ' - ' . $lecture->title)
@section('page_title', __('instructor.lecture_attendance'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp
<div class="su-page">
    <div class="su-page-head">
        <div class="min-w-0">
            <nav class="su-crumb-inline" aria-label="breadcrumb">
                <a href="{{ route('instructor.attendance.index') }}">{{ __('instructor.attendance_absence') }}</a>
                <span>/</span>
                <strong style="color:var(--su-ink)">{{ Str::limit($lecture->title, 40) }}</strong>
            </nav>
            <h1 class="su-page-head__title">{{ $lecture->title }}</h1>
            <div class="su-chip-row">
                @if($lecture->course)
                    <span class="su-chip su-soft-1"><i class="fas fa-book" aria-hidden="true"></i> {{ Str::limit($lecture->course->title, 30) }}</span>
                @endif
                @if($lecture->scheduled_at)
                <span class="su-chip su-soft-2">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    {{ $lecture->scheduled_at->format('Y/m/d H:i') }}
                </span>
                @endif
                @php
                    $chip = match ($lecture->status) {
                        'scheduled' => 'su-soft-1',
                        'in_progress' => 'su-chip--warn',
                        'completed' => 'su-chip--ok',
                        default => 'su-chip--off',
                    };
                    $statusLabel = match ($lecture->status) {
                        'scheduled' => __('instructor.scheduled_lecture'),
                        'in_progress' => __('instructor.in_progress'),
                        'completed' => __('instructor.completed'),
                        default => __('instructor.cancelled_lecture'),
                    };
                @endphp
                <span class="su-chip {{ $chip }}">{{ $statusLabel }}</span>
            </div>
        </div>
        <div class="su-page-head__actions">
            <a href="{{ route('instructor.lectures.show', $lecture) }}" class="su-btn su-btn--primary">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back_to_lecture') }}
            </a>
            <a href="{{ route('instructor.attendance.index') }}" class="su-btn">
                <i class="fas fa-list" aria-hidden="true"></i>
                {{ __('instructor.attendance_list') }}
            </a>
        </div>
    </div>

    <section class="su-kpi-row" style="margin-bottom:20px">
        <div class="su-kpi su-kpi--1">
            <div class="su-kpi__l">{{ __('instructor.present') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($attendanceStats['present'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--2">
            <div class="su-kpi__l">{{ __('instructor.late') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($attendanceStats['late'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-clock" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--3">
            <div class="su-kpi__l">{{ __('instructor.partial') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($attendanceStats['partial'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-user-clock" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--4">
            <div class="su-kpi__l">{{ __('instructor.absent') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($attendanceStats['absent'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-times-circle" aria-hidden="true"></i></div>
            </div>
        </div>
        <div class="su-kpi su-kpi--1">
            <div class="su-kpi__l">{{ __('instructor.total') }}</div>
            <div class="su-kpi__row">
                <div class="su-kpi__v">{{ number_format($attendanceStats['total_students'] ?? 0) }}</div>
                <div class="su-kpi__d"><i class="fas fa-users" aria-hidden="true"></i></div>
            </div>
        </div>
    </section>

    <div class="su-detail-grid">
        <div style="min-width:0">
            <section class="su-card su-card--flush">
                <div class="su-section-head" style="padding:14px 16px;border-bottom:1px solid var(--su-line,rgba(0,0,0,.06))">
                    <h2 class="su-card__title" style="margin:0">
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                        {{ __('instructor.attendance_records') }}
                    </h2>
                    <span class="su-chip">{{ __('instructor.total') }}: {{ $attendanceStats['total_students'] ?? 0 }}</span>
                </div>
                <div class="su-table-wrap" style="border:0;border-radius:0;background:transparent">
                    @if($enrollments->count() > 0)
                        <table class="su-table">
                            <thead>
                                <tr>
                                    <th>{{ __('instructor.student') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th>{{ __('instructor.attendance_minutes') }}</th>
                                    <th>{{ __('instructor.percentage') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    @php
                                        $record = $attendanceRecords->get($enrollment->user_id);
                                        $attendanceMinutes = $record && isset($record->attendance_minutes) ? $record->attendance_minutes : 0;
                                        $percentage = $record && $record->attendance_percentage ? $record->attendance_percentage : 0;
                                        $pctWidth = $lecture->duration_minutes > 0 ? min(($attendanceMinutes / $lecture->duration_minutes) * 100, 100) : 0;
                                        $statusChip = match ($record?->status) {
                                            'present' => 'su-chip--ok',
                                            'late' => 'su-chip--warn',
                                            'partial' => 'su-soft-1',
                                            'absent' => 'su-chip--off',
                                            default => '',
                                        };
                                        $statusText = match ($record?->status) {
                                            'present' => __('instructor.present'),
                                            'late' => __('instructor.late'),
                                            'partial' => __('instructor.partial'),
                                            'absent' => __('instructor.absent'),
                                            default => __('instructor.unspecified'),
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong style="font-weight:600">{{ $enrollment->user->name ?? __('instructor.unspecified') }}</strong>
                                            <div style="font-size:12px;color:var(--su-ink-40)">{{ $enrollment->user->email ?? '' }}</div>
                                        </td>
                                        <td><span class="su-chip {{ $statusChip }}">{{ $statusText }}</span></td>
                                        <td class="tabular-nums">
                                            {{ $attendanceMinutes }} / {{ $lecture->duration_minutes }}
                                            <div style="margin-top:6px;height:6px;width:6rem;background:var(--su-line,rgba(0,0,0,.08));border-radius:999px;overflow:hidden">
                                                <div style="height:100%;width:{{ $pctWidth }}%;background:var(--su-accent,#3b82f6);border-radius:999px"></div>
                                            </div>
                                        </td>
                                        <td class="tabular-nums">
                                            {{ number_format($percentage, 1) }}%
                                            @if($percentage >= 80)
                                                <i class="fas fa-check-circle" style="color:#16a34a" aria-hidden="true"></i>
                                            @elseif($percentage >= 50)
                                                <i class="fas fa-exclamation-circle" style="color:#d97706" aria-hidden="true"></i>
                                            @else
                                                <i class="fas fa-times-circle" style="color:#dc2626" aria-hidden="true"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="su-empty" style="padding:48px 16px">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <p>{{ __('instructor.no_enrolled_students') }}</p>
                            <p style="color:var(--su-ink-40);font-size:13px;margin:0">{{ __('instructor.no_enrolled_students_desc') }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px">
            @php $total = max(($attendanceStats['total_students'] ?? 0), 1); @endphp
            <section class="su-card">
                <h2 class="su-card__title"><i class="fas fa-chart-bar" aria-hidden="true"></i> {{ __('instructor.attendance_details') }}</h2>
                <div class="su-meta-list">
                    @foreach([
                        ['present', __('instructor.present'), 'su-chip--ok'],
                        ['late', __('instructor.late'), 'su-chip--warn'],
                        ['partial', __('instructor.partial'), 'su-soft-1'],
                        ['absent', __('instructor.absent'), 'su-chip--off'],
                    ] as [$key, $label, $chipClass])
                        <div class="su-meta-row" style="flex-direction:column;align-items:stretch;gap:6px">
                            <div style="display:flex;justify-content:space-between;font-size:13px">
                                <span>{{ $label }}</span>
                                <strong class="tabular-nums">{{ $attendanceStats[$key] ?? 0 }}</strong>
                            </div>
                            <div style="height:6px;background:var(--su-line,rgba(0,0,0,.08));border-radius:999px;overflow:hidden">
                                <div style="height:100%;width:{{ (($attendanceStats[$key] ?? 0) / $total) * 100 }}%;background:currentColor;border-radius:999px;opacity:.7" class="{{ $chipClass }}"></div>
                            </div>
                        </div>
                    @endforeach
                    <div class="su-meta-row">
                        <span>{{ __('instructor.total') }}</span>
                        <strong class="tabular-nums">{{ $attendanceStats['total_students'] ?? 0 }}</strong>
                    </div>
                </div>
            </section>

            <section class="su-card">
                <h2 class="su-card__title"><i class="fas fa-info-circle" aria-hidden="true"></i> {{ __('instructor.lecture_info') }}</h2>
                <div class="su-meta-list">
                    <div class="su-meta-row">
                        <span class="su-meta-ico su-soft-1"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.date_and_time') }}:</span>
                        <strong>{{ $lecture->scheduled_at->format('Y/m/d H:i') }}</strong>
                    </div>
                    <div class="su-meta-row">
                        <span class="su-meta-ico su-soft-2"><i class="fas fa-clock" aria-hidden="true"></i></span>
                        <span>{{ __('instructor.duration') }}:</span>
                        <strong>{{ $lecture->duration_minutes }} {{ __('instructor.minutes') }}</strong>
                    </div>
                    <div class="su-meta-row">
                        <span class="su-meta-ico su-soft-3"><i class="fas fa-info" aria-hidden="true"></i></span>
                        <span>{{ __('common.status') }}:</span>
                        <span class="su-chip {{ $chip }}">{{ $statusLabel }}</span>
                    </div>
                    @if($lecture->course)
                        <div class="su-meta-row">
                            <span class="su-meta-ico su-soft-4"><i class="fas fa-book" aria-hidden="true"></i></span>
                            <span>{{ __('instructor.course') }}:</span>
                            <strong>{{ Str::limit($lecture->course->title, 28) }}</strong>
                        </div>
                    @endif
                </div>
            </section>

            <section class="su-card">
                <h2 class="su-card__title"><i class="fas fa-bolt" aria-hidden="true"></i> {{ __('instructor.quick_actions') }}</h2>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <a href="{{ route('instructor.lectures.show', $lecture) }}" class="su-btn" style="justify-content:flex-start">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                        {{ __('instructor.view_lecture') }}
                    </a>
                    @if($lecture->course)
                        <a href="{{ route('instructor.courses.show', $lecture->course) }}" class="su-btn" style="justify-content:flex-start">
                            <i class="fas fa-book" aria-hidden="true"></i>
                            {{ __('instructor.view_course') }}
                        </a>
                    @endif
                    <a href="{{ route('instructor.attendance.index') }}" class="su-btn" style="justify-content:flex-start">
                        <i class="fas fa-list" aria-hidden="true"></i>
                        {{ __('instructor.attendance_list') }}
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
