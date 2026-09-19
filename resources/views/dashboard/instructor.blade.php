@extends('layouts.app')

@section('title', __('instructor.dashboard_title'))
@section('page_title', __('instructor.overview'))

@section('content')
@php
    $locale = app()->getLocale();
    $ov = $overview ?? [];
    $totalSessions = (int) ($ov['activity_total'] ?? 0);
    $donutSlices = $ov['donut_slices'] ?? [];
    $donutTotal = max(0, (int) ($ov['donut_total'] ?? 0));
    $activeOnline = (int) ($ov['active_online'] ?? 0);
    $activeOffline = (int) ($ov['active_offline'] ?? 0);
    $incomeAmount = (float) ($ov['income_amount'] ?? 0);
    $activities = collect($ov['activities'] ?? []);
    $firstName = explode(' ', trim((string) (auth()->user()->name ?? '')))[0] ?? '';
    $upcomingPrivateSession = $upcomingPrivateSession ?? null;

    $privateHref = Route::has('instructor.one-to-one-sessions.index')
        ? route('instructor.one-to-one-sessions.index')
        : route('dashboard');
    $calendarHref = Route::has('instructor.calendar')
        ? route('instructor.calendar')
        : route('dashboard');
    $liveHref = Route::has('instructor.live-sessions.index')
        ? route('instructor.live-sessions.index')
        : route('dashboard');
    $availabilityHref = Route::has('instructor.one-to-one-availability.index')
        ? route('instructor.one-to-one-availability.index')
        : $calendarHref;
    $messagesHref = Route::has('instructor.private-messages.index')
        ? route('instructor.private-messages.index')
        : route('dashboard');
    $freeHref = Route::has('instructor.free-trial-bookings.index')
        ? route('instructor.free-trial-bookings.index')
        : $privateHref;
    $withdrawHref = Route::has('instructor.withdrawals.index')
        ? route('instructor.withdrawals.index')
        : null;
    $nextHref = $privateHref;

    $upcomingCount = (int) ($stats['upcoming_private'] ?? 0);
    $liveNow = (int) ($stats['live_now'] ?? 0);
    $studentsCount = (int) ($stats['total_students'] ?? 0);
    $pendingCount = 0;
    foreach ($donutSlices as $slice) {
        if (($slice['key'] ?? '') === 'pending') {
            $pendingCount = (int) ($slice['value'] ?? 0);
        }
    }
    $hasUpcoming = $upcomingCount > 0 || $upcomingPrivateSession;
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => __('instructor.overview'),
    'crumbs' => [
        ['label' => __('instructor.dashboards'), 'url' => route('dashboard')],
        ['label' => __('instructor.overview'), 'url' => null],
    ],
])

<div class="id-dash">
    @if($hasUpcoming)
        <section class="id-hero" aria-label="{{ __('instructor.overview') }}">
            <div class="id-hero__copy">
                <p class="id-hero__kicker">{{ __('instructor.welcome') }}، {{ $firstName }}</p>
                <h2 class="id-hero__title">
                    @if($upcomingPrivateSession)
                        {{ $upcomingPrivateSession->student?->name ?? __('instructor.private_lessons') }}
                    @else
                        {{ __('instructor.cd_next_title') }}
                    @endif
                </h2>
                <p class="id-hero__meta">
                    @if($upcomingPrivateSession)
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        {{ optional($upcomingPrivateSession->scheduled_at)->translatedFormat('D j M · g:i A') ?? '' }}
                    @else
                        {{ __('instructor.private_lessons') }}
                    @endif
                </p>
            </div>
            <div class="id-hero__actions">
                <a href="{{ $nextHref }}" class="id-btn id-btn--gold">{{ __('instructor.private_lessons') }}</a>
                <a href="{{ $calendarHref }}" class="id-btn id-btn--ghost">{{ __('instructor.my_calendar') }}</a>
            </div>
        </section>
    @else
        <section class="id-hero id-hero--soft" aria-label="{{ __('instructor.overview') }}">
            <div class="id-hero__copy">
                <p class="id-hero__kicker">{{ __('instructor.welcome') }}، {{ $firstName }}</p>
                <h2 class="id-hero__title">{{ __('instructor.cd_no_upcoming') }}</h2>
                <p class="id-hero__meta">{{ __('student.one_to_one_availability_title') }}</p>
            </div>
            <div class="id-hero__actions">
                <a href="{{ $availabilityHref }}" class="id-btn id-btn--gold">{{ __('student.one_to_one_availability_title') }}</a>
                <a href="{{ $privateHref }}" class="id-btn id-btn--ghost">{{ __('instructor.private_lessons') }}</a>
            </div>
        </section>
    @endif

    <section class="id-kpis" aria-label="{{ __('instructor.cd_activity_title') }}">
        <a href="{{ $privateHref }}" class="id-kpi">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.private_lessons') }}</span>
                <span class="id-kpi__value">{{ number_format($upcomingCount) }}</span>
            </span>
        </a>
        <a href="{{ $availabilityHref }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-calendar-week"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('student.one_to_one_availability_title') }}</span>
                <span class="id-kpi__value">{{ number_format($pendingCount) }}</span>
            </span>
        </a>
        <a href="{{ $liveHref }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-broadcast-tower"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.live_broadcast') }}</span>
                <span class="id-kpi__value">{{ number_format($liveNow) }}</span>
            </span>
        </a>
        <a href="{{ $calendarHref }}" class="id-kpi">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.cd_this_week') }}</span>
                <span class="id-kpi__value">{{ number_format($totalSessions) }}</span>
            </span>
        </a>
    </section>

    <section class="id-shortcuts" aria-label="{{ __('instructor.nav_pages') }}">
        <a href="{{ $privateHref }}" class="id-shortcut">
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
            <span>{{ __('instructor.private_lessons') }}</span>
        </a>
        <a href="{{ $availabilityHref }}" class="id-shortcut">
            <i class="fas fa-clock" aria-hidden="true"></i>
            <span>{{ __('student.one_to_one_availability_title') }}</span>
        </a>
        <a href="{{ $freeHref }}" class="id-shortcut">
            <i class="fas fa-gift" aria-hidden="true"></i>
            <span>{{ $locale === 'ar' ? 'حصص مجانية' : 'Free sessions' }}</span>
        </a>
        <a href="{{ $messagesHref }}" class="id-shortcut">
            <i class="fas fa-comments" aria-hidden="true"></i>
            <span>{{ __('instructor.student_messages') }}</span>
        </a>
        <a href="{{ $calendarHref }}" class="id-shortcut">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <span>{{ __('instructor.my_calendar') }}</span>
        </a>
        <a href="{{ $liveHref }}" class="id-shortcut">
            <i class="fas fa-broadcast-tower" aria-hidden="true"></i>
            <span>{{ __('instructor.live_broadcast') }}</span>
        </a>
    </section>

    <div class="id-grid">
        <section class="id-panel" aria-label="{{ __('instructor.cd_workload_title') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.cd_workload_title') }}</h2>
                <span class="id-panel__badge">{{ number_format($donutTotal) }} {{ __('instructor.cd_total') }}</span>
            </header>
            <div class="id-workload">
                @forelse($donutSlices as $slice)
                    @php
                        $pct = $donutTotal > 0 ? round(((int) $slice['value'] / $donutTotal) * 100) : 0;
                        $tone = $slice['tone'] ?? 'full';
                    @endphp
                    <div class="id-workload__row">
                        <div class="id-workload__meta">
                            <span class="id-dot id-dot--{{ $tone }}"></span>
                            <span>{{ $slice['label'] }}</span>
                            <strong>{{ number_format((int) $slice['value']) }}</strong>
                        </div>
                        <div class="id-workload__bar" aria-hidden="true"><span style="width: {{ max(4, min(100, $pct)) }}%"></span></div>
                    </div>
                @empty
                    <div class="id-workload__row">
                        <div class="id-workload__meta">
                            <span class="id-dot"></span>
                            <span>{{ __('instructor.cd_upcoming') }}</span>
                            <strong>{{ number_format($activeOnline) }}</strong>
                        </div>
                        <div class="id-workload__bar" aria-hidden="true"><span style="width: {{ $activeOnline + $activeOffline > 0 ? max(4, round($activeOnline / max(1, $activeOnline + $activeOffline) * 100)) : 0 }}%"></span></div>
                    </div>
                    <div class="id-workload__row">
                        <div class="id-workload__meta">
                            <span class="id-dot id-dot--soft"></span>
                            <span>{{ __('instructor.cd_past') }}</span>
                            <strong>{{ number_format($activeOffline) }}</strong>
                        </div>
                        <div class="id-workload__bar" aria-hidden="true"><span style="width: {{ $activeOnline + $activeOffline > 0 ? max(4, round($activeOffline / max(1, $activeOnline + $activeOffline) * 100)) : 0 }}%"></span></div>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="id-panel id-panel--income" aria-label="{{ __('instructor.cd_income_title') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.cd_income_title') }}</h2>
            </header>
            <p class="id-income__amount">{{ number_format($incomeAmount, 0) }} <small>{{ currency_symbol() }}</small></p>
            <p class="id-income__meta">{{ number_format($studentsCount) }} {{ __('instructor.student_single') }}</p>
            <div class="id-income__actions">
                @if($withdrawHref)
                    <a href="{{ $withdrawHref }}" class="id-btn id-btn--navy">{{ __('instructor.withdrawal_requests') }}</a>
                @endif
                <a href="{{ $calendarHref }}" class="id-btn id-btn--outline">{{ __('instructor.my_calendar') }}</a>
            </div>
        </section>
    </div>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.cd_activities') }}">
        <header class="id-panel__head">
            <div>
                <h2>{{ __('instructor.cd_activities') }}</h2>
                <p class="id-panel__hint">{{ __('instructor.cd_activities_hint') }}</p>
            </div>
            <a href="{{ $privateHref }}" class="id-link">{{ __('instructor.private_lessons') }}</a>
        </header>

        @if($activities->isNotEmpty())
            <ul class="id-act">
                @foreach($activities as $act)
                    <li>
                        <a href="{{ $act['url'] }}" class="id-act__row">
                            <span class="id-act__ico" aria-hidden="true"><i class="fas fa-bolt"></i></span>
                            <span class="id-act__body">
                                <strong>{{ $act['title'] }}</strong>
                                <small>{{ $act['meta'] ?? '' }}</small>
                            </span>
                            <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="id-empty">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-stream"></i></span>
                <p>{{ __('instructor.cd_no_activities') }}</p>
                <div class="id-empty__actions">
                    <a href="{{ $privateHref }}" class="id-btn id-btn--navy">{{ __('instructor.private_lessons') }}</a>
                    <a href="{{ $calendarHref }}" class="id-btn id-btn--outline">{{ __('instructor.my_calendar') }}</a>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
