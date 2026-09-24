@extends('layouts.student-timeline')

@section('title', __('student_timeline.nav_progress_hub'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $comparison = $comparison ?? [];
    $current = $comparison['current'] ?? [];
    $previous = $comparison['previous'] ?? [];
    $deltas = $comparison['deltas'] ?? [];
    $strengths = $comparison['strengths'] ?? [];
    $improvements = $comparison['improvements'] ?? [];
    $suggestions = $suggestions ?? collect();
    $trend = $comparison['trend'] ?? 'stable';
    $trendLabel = match ($trend) {
        'up' => $isRtl ? 'تصاعدي' : 'Up',
        'down' => $isRtl ? 'تراجع' : 'Down',
        default => $isRtl ? 'مستقر' : 'Stable',
    };
    $fmt = function ($v, $suffix = '') {
        if ($v === null || $v === '') {
            return '—';
        }

        return $v.$suffix;
    };
    $deltaLabel = function ($d) {
        if ($d === null) {
            return '—';
        }
        $sign = $d > 0 ? '+' : '';

        return $sign.$d;
    };
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.nav_progress_hub'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.nav_progress_hub'), 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-join-hero" aria-label="{{ __('student_timeline.nav_progress_hub') }}">
    <div class="st-join-hero__copy">
        <p class="st-join-hero__kicker">{{ $comparison['period']['label'] ?? '' }}</p>
        <h2 class="st-join-hero__title">{{ __('student_timeline.progress_hub_title') }}</h2>
        <p class="st-join-hero__meta">
            {{ __('student_timeline.progress_hub_vs') }}
            {{ $comparison['previous_period']['label'] ?? '' }}
            — {{ __('student_timeline.progress_hub_trend') }}: {{ $trendLabel }}
        </p>
    </div>
    <div class="st-join-hero__actions">
        <form method="POST" action="{{ route('student.progress.refresh-adaptive') }}">
            @csrf
            <button type="submit" class="st-pill st-pill--outline">{{ __('student_timeline.progress_refresh_adaptive') }}</button>
        </form>
        <form method="POST" action="{{ route('student.progress.send-family') }}">
            @csrf
            <button type="submit" class="st-pill st-pill--solid">{{ __('student_timeline.progress_send_family') }}</button>
        </form>
    </div>
</section>

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.progress_metrics') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.progress_exam_avg') }}</p>
        <p class="st-stat-card__value">{{ $fmt($current['exam_average'] ?? null, '%') }}</p>
        <p class="st-stat-card__hint">{{ $deltaLabel($deltas['exam_average'] ?? null) }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.progress_attendance') }}</p>
        <p class="st-stat-card__value">{{ $fmt($current['attendance_percent'] ?? null, '%') }}</p>
        <p class="st-stat-card__hint">{{ $deltaLabel($deltas['attendance_percent'] ?? null) }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.progress_sessions') }}</p>
        <p class="st-stat-card__value">{{ (int) ($current['sessions_completed'] ?? 0) }}</p>
        <p class="st-stat-card__hint">{{ $deltaLabel($deltas['sessions_completed'] ?? null) }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.progress_courses') }}</p>
        <p class="st-stat-card__value">{{ $fmt($current['course_progress_percent'] ?? null, '%') }}</p>
        <p class="st-stat-card__hint">{{ $deltaLabel($deltas['course_progress_percent'] ?? null) }}</p>
    </article>
</section>

<div class="st-grid st-grid--2" style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));margin-top:1.25rem">
    <section class="st-panel" style="background:var(--st-surface, #fff);border:1px solid var(--st-line, #e5e7eb);border-radius:1rem;padding:1.1rem">
        <h3 style="margin:0 0 .75rem;font-size:1.05rem">{{ __('student_timeline.progress_strengths') }}</h3>
        <ul style="margin:0;padding-inline-start:1.1rem;line-height:1.7">
            @foreach($strengths as $s)
                <li>{{ $s }}</li>
            @endforeach
        </ul>
    </section>
    <section class="st-panel" style="background:var(--st-surface, #fff);border:1px solid var(--st-line, #e5e7eb);border-radius:1rem;padding:1.1rem">
        <h3 style="margin:0 0 .75rem;font-size:1.05rem">{{ __('student_timeline.progress_improvements') }}</h3>
        <ul style="margin:0;padding-inline-start:1.1rem;line-height:1.7">
            @forelse($improvements as $i)
                <li>{{ $i }}</li>
            @empty
                <li>{{ __('student_timeline.progress_no_alerts') }}</li>
            @endforelse
        </ul>
    </section>
</div>

<section style="margin-top:1.5rem">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.85rem">
        <h3 style="margin:0;font-size:1.1rem">{{ __('student_timeline.progress_adaptive_title') }}</h3>
        <div style="display:flex;flex-wrap:wrap;gap:.5rem">
            <a href="{{ route('public.recorded-courses') }}" class="st-pill st-pill--outline">{{ __('student_timeline.progress_recorded_track') }}</a>
            <a href="{{ route('public.books') }}" class="st-pill st-pill--outline">{{ __('student_timeline.progress_books_track') }}</a>
        </div>
    </div>
    @forelse($suggestions as $sug)
        <article style="background:var(--st-surface, #fff);border:1px solid var(--st-line, #e5e7eb);border-radius:1rem;padding:1rem 1.1rem;margin-bottom:.65rem;display:flex;flex-wrap:wrap;gap:.75rem;justify-content:space-between;align-items:center">
            <div>
                <p style="margin:0;font-weight:700">{{ $sug->title }}</p>
                @if($sug->reason)
                    <p style="margin:.35rem 0 0;opacity:.8;font-size:.9rem">{{ $sug->reason }}</p>
                @endif
                <p style="margin:.35rem 0 0;font-size:.75rem;opacity:.65">{{ $sug->priority }} · {{ $sug->kind }}</p>
            </div>
            @if($sug->action_url)
                <a href="{{ $sug->action_url }}" class="st-pill st-pill--solid">{{ __('student_timeline.progress_open_action') }}</a>
            @endif
        </article>
    @empty
        <p style="opacity:.75">{{ __('student_timeline.progress_no_suggestions') }}</p>
    @endforelse
</section>

@if(!empty($shareUrl))
    <section class="st-class-id" style="margin-top:1.5rem" aria-label="{{ __('student_timeline.class_user_id') }}">
        <div class="st-class-id__copy">
            <p class="st-class-id__kicker">{{ __('student_timeline.progress_share_kicker') }}</p>
            <p class="st-class-id__hint" dir="ltr" style="word-break:break-all">{{ $shareUrl }}</p>
        </div>
        <a href="{{ $shareUrl }}" class="st-pill st-pill--outline" target="_blank" rel="noopener">{{ __('student_timeline.progress_open_share') }}</a>
    </section>
@endif
@endsection
