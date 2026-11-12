@extends('layouts.student-timeline')

@section('title', __('student_timeline.calendar'))

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $viewerTz = $viewerTz ?? auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
    $upcoming = $upcoming ?? collect();
    $typeLabels = $typeLabels ?? [];
    $eventTones = $eventTones ?? [];
    $next = $upcoming->first();
    $nextType = $next->type ?? null;
    $joinTypes = ['one_to_one', 'class', 'group', 'lecture', 'consultation'];
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('student_timeline.calendar'),
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => __('student_timeline.calendar'), 'url' => null],
    ],
])

@if($next)
    <section class="st-join-hero" aria-label="{{ __('student_timeline.calendar_upcoming') }}">
        <div class="st-join-hero__copy">
            <p class="st-join-hero__kicker">{{ $typeLabels[$nextType] ?? __('student.other_events') }}</p>
            <h2 class="st-join-hero__title">{{ $next->title }}</h2>
            <p class="st-join-hero__meta">
                <x-app-datetime :at="$next->start_date" :timezone="$viewerTz" :pattern="$isRtl ? 'l، d M · g:i A' : 'D, M j · g:i A'" />
                · {{ \App\Support\AppTimezone::label($viewerTz) }}
            </p>
        </div>
        @if(! empty($next->url))
            <div class="st-join-hero__actions">
                <a href="{{ $next->url }}" class="st-pill st-pill--solid st-pill--lg">
                    {{ in_array($nextType, $joinTypes, true) ? __('student_timeline.join_now') : __('student_timeline.calendar_open') }}
                </a>
            </div>
        @endif
    </section>
@endif

<section class="st-stats st-stats--classes" aria-label="{{ __('student_timeline.calendar') }}">
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.calendar_upcoming') }}</p>
        <p class="st-stat-card__value">{{ $stats['upcoming'] ?? $upcoming->count() }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.cal_exam') }}</p>
        <p class="st-stat-card__value">{{ $stats['exams'] ?? 0 }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.cal_lecture') }}</p>
        <p class="st-stat-card__value">{{ $stats['lectures'] ?? 0 }}</p>
    </article>
    <article class="st-stat-card">
        <p class="st-stat-card__label">{{ __('student_timeline.cal_assignment') }}</p>
        <p class="st-stat-card__value">{{ $stats['assignments'] ?? 0 }}</p>
    </article>
</section>

<section class="st-msg-intro">
    <div>
        <h2>{{ __('student.calendar_title') }}</h2>
        <p>{{ __('student_timeline.calendar_hint') }} · {{ __('student_timeline.oto_your_clock') }}: {{ \App\Support\AppTimezone::label($viewerTz) }}</p>
    </div>
</section>

<section class="st-panel st-fc" aria-label="{{ __('student_timeline.calendar') }}">
    <div id="calendar"></div>
    <div class="st-fc__legend">
        <span><i style="background:#EF4444"></i> {{ __('student_timeline.cal_exam') }}</span>
        <span><i style="background:#3B82F6"></i> {{ __('student_timeline.cal_lecture') }}</span>
        <span><i style="background:#F59E0B"></i> {{ __('student_timeline.cal_assignment') }}</span>
        <span><i style="background:#7c3aed"></i> {{ __('student_timeline.cal_private') }}</span>
        <span><i style="background:#F5B800"></i> {{ __('student_timeline.cal_class') }}</span>
        <span><i style="background:#0B3D91"></i> {{ __('student_timeline.cal_group') }}</span>
        <span><i style="background:#059669"></i> {{ __('student_timeline.cal_consult') }}</span>
    </div>
</section>
@endsection

@section('events')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $viewerTz = $viewerTz ?? auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
    $upcoming = $upcoming ?? collect();
    $typeLabels = $typeLabels ?? [];
    $eventTones = $eventTones ?? [];
@endphp
<div class="st-events__top">
    <h2>{{ __('student_timeline.calendar_upcoming') }}</h2>
</div>
@forelse($upcoming->take(10) as $event)
    @php
        $href = $event->url ?? null;
        $tone = $eventTones[$event->type ?? ''] ?? 'blue';
    @endphp
    @if($href)
        <a href="{{ $href }}" class="st-event-card st-event-card--{{ $tone }}">
            <p class="st-event-card__kicker">{{ $typeLabels[$event->type ?? ''] ?? __('student.other_events') }}</p>
            <h3>{{ $event->title }}</h3>
            <p class="st-event-card__sub">
                <x-app-datetime :at="$event->start_date" :timezone="$viewerTz" :pattern="$isRtl ? 'D j M · g:i A' : 'D, M j · g:i A'" />
            </p>
        </a>
    @else
        <article class="st-event-card st-event-card--{{ $tone }}">
            <p class="st-event-card__kicker">{{ $typeLabels[$event->type ?? ''] ?? __('student.other_events') }}</p>
            <h3>{{ $event->title }}</h3>
            <p class="st-event-card__sub">
                <x-app-datetime :at="$event->start_date" :timezone="$viewerTz" :pattern="$isRtl ? 'D j M · g:i A' : 'D, M j · g:i A'" />
            </p>
        </article>
    @endif
@empty
    <p class="st-events__empty">{{ __('student_timeline.calendar_empty_upcoming') }}</p>
@endforelse
<div class="st-events__see">
    <a href="{{ route('dashboard') }}">{{ __('student_timeline.school_gate') }}</a>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
@if(app()->getLocale() === 'ar')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/ar.js"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('calendar');
    if (!el) return;
    var isRtl = @json(app()->getLocale() === 'ar');
    var calendar = new FullCalendar.Calendar(el, {
        locale: isRtl ? 'ar' : 'en',
        direction: isRtl ? 'rtl' : 'ltr',
        // بدون timeZone مسمّى: الأحداث تصل بـ ISO مع offset من السيرفر (لا يحتاج Luxon)
        initialView: 'dayGridMonth',
        headerToolbar: {
            start: 'title',
            center: '',
            end: 'today dayGridMonth,timeGridWeek,timeGridDay prev,next'
        },
        buttonText: {
            today: @json(__('student_timeline.calendar_today')),
            month: @json(__('student_timeline.calendar_month')),
            week: @json(__('student_timeline.calendar_week')),
            day: @json(__('student_timeline.calendar_day'))
        },
        events: @json(route('calendar.events')),
        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        height: 'auto',
        contentHeight: 560,
        firstDay: 6,
        navLinks: true,
        nowIndicator: true,
        dayMaxEvents: 3,
        moreLinkClick: 'popover'
    });
    calendar.render();
});
</script>
@endpush
