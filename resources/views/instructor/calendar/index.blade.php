@extends('layouts.app')

@section('title', __('instructor.my_calendar'))
@section('page_title', __('instructor.my_calendar'))

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet">
@endpush

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $locale = app()->getLocale();
    $viewerTz = $viewerTz ?? auth()->user()?->timezoneCode() ?? \App\Support\AppTimezone::academy();
    $fcLocale = $isRtl ? 'ar' : 'en';
    $upcoming = collect($events ?? [])->filter(fn ($e) => ($e->start_date ?? now()) >= now())->take(12);
    $next = $upcoming->first();
    $privateHref = Route::has('instructor.one-to-one-sessions.index')
        ? route('instructor.one-to-one-sessions.index')
        : route('dashboard');
    $availHref = Route::has('instructor.one-to-one-availability.index')
        ? route('instructor.one-to-one-availability.index')
        : null;
@endphp

<div class="id-page">
    @if($next)
        <section class="id-hero" aria-label="{{ __('instructor.upcoming') }}">
            <div class="id-hero__copy">
                <p class="id-hero__kicker">{{ __('instructor.upcoming') }}</p>
                <h2 class="id-hero__title">{{ $next->title }}</h2>
                <p class="id-hero__meta">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <x-app-datetime :at="$next->start_date" :timezone="$viewerTz" pattern="D j M · g:i A" />
                    · {{ \App\Support\AppTimezone::label($viewerTz) }}
                </p>
            </div>
            <div class="id-hero__actions">
                @if(! empty($next->url))
                    <a href="{{ $next->url }}" class="id-btn id-btn--gold">{{ __('instructor.o1o_manage') }}</a>
                @endif
                <a href="{{ $privateHref }}" class="id-btn id-btn--ghost">{{ __('instructor.private_lessons') }}</a>
            </div>
        </section>
    @else
        <section class="id-hero id-hero--soft" aria-label="{{ __('instructor.my_calendar') }}">
            <div class="id-hero__copy">
                <p class="id-hero__kicker">{{ __('instructor.my_calendar') }}</p>
                <h2 class="id-hero__title">{{ __('instructor.calendar_no_upcoming') }}</h2>
                <p class="id-hero__meta">
                    {{ __('instructor.calendar_subtitle') }}
                    · {{ \App\Support\AppTimezone::label($viewerTz) }}
                </p>
            </div>
            <div class="id-hero__actions">
                @if($availHref)
                    <a href="{{ $availHref }}" class="id-btn id-btn--gold">{{ __('instructor.o1a_title') }}</a>
                @endif
                <a href="{{ $privateHref }}" class="id-btn id-btn--ghost">{{ __('instructor.private_lessons') }}</a>
            </div>
        </section>
    @endif

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.my_calendar') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.calendar_total') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.upcoming') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['upcoming'] ?? 0) }}</span>
            </span>
        </article>
    </section>

    <div class="id-cal-grid">
        <section class="id-panel id-panel--flush st-fc" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" aria-label="{{ __('instructor.my_calendar') }}">
            <div id="calendar"></div>
            <div class="st-fc__legend">
                <span><i style="background:#7c3aed"></i> {{ __('instructor.cal_private') }}</span>
                <span><i style="background:#a8c5da"></i> {{ __('instructor.cal_classroom') }}</span>
                <span><i style="background:#a1e3cb"></i> {{ __('instructor.cal_consultation') }}</span>
                <span><i style="background:#ef4444"></i> {{ __('instructor.cal_live') }}</span>
            </div>
        </section>

        <aside class="id-panel id-cal-side" aria-label="{{ __('instructor.upcoming') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.upcoming') }}</h2>
                <span class="id-panel__badge">{{ number_format($upcoming->count()) }}</span>
            </header>
            <div class="id-list id-cal-side__list">
                @forelse($upcoming as $event)
                    <a href="{{ $event->url ?? '#' }}" class="id-list__row">
                        <span class="id-list__ico" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
                        <span class="id-list__body">
                            <span class="id-list__title">{{ $event->title }}</span>
                            <span class="id-list__meta">
                                <x-app-datetime :at="$event->start_date" :timezone="$viewerTz" pattern="D j M · g:i A" />
                            </span>
                        </span>
                        <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="id-empty">
                        <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-calendar-times"></i></span>
                        <p>{{ __('instructor.calendar_no_upcoming') }}</p>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/locales/ar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var isRtl = @json($isRtl);
    var calendarEl = document.getElementById('calendar');
    if (!calendarEl || typeof FullCalendar === 'undefined') {
        if (calendarEl) {
            calendarEl.innerHTML = '<div class="id-empty" style="padding:40px"><p>{{ $isRtl ? 'تعذر تحميل التقويم' : 'Calendar failed to load' }}</p></div>';
        }
        return;
    }

    var isMobile = window.matchMedia('(max-width: 640px)').matches;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: @json($fcLocale),
        direction: isRtl ? 'rtl' : 'ltr',
        timeZone: @json($viewerTz),
        initialView: isMobile ? 'listWeek' : 'timeGridWeek',
        headerToolbar: isRtl
            ? { right: 'prev,next today', center: 'title', left: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
        buttonText: isRtl
            ? { today: 'اليوم', month: 'شهر', week: 'أسبوع', day: 'يوم', list: 'قائمة' }
            : { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
        events: {
            url: @json(route('instructor.calendar.events')),
            failure: function () {
                console.error('Failed to load calendar events');
            }
        },
        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        height: 'auto',
        contentHeight: isMobile ? 480 : 620,
        firstDay: isRtl ? 6 : 0,
        navLinks: true,
        dayMaxEvents: 3,
        nowIndicator: true,
        stickyHeaderDates: true
    });
    calendar.render();

    window.addEventListener('resize', function () {
        var mobile = window.matchMedia('(max-width: 640px)').matches;
        calendar.setOption('contentHeight', mobile ? 480 : 620);
    });
});
</script>
@endpush
