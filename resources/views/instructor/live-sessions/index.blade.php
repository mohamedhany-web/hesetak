@extends('layouts.app')

@section('title', __('instructor.ls_title'))
@section('page_title', __('instructor.ls_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $status = request('status');
    $otoHref = Route::has('instructor.one-to-one-sessions.index')
        ? route('instructor.one-to-one-sessions.index')
        : route('dashboard');
    $availHref = Route::has('instructor.one-to-one-availability.index')
        ? route('instructor.one-to-one-availability.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.ls_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.private_lessons') }} · 1:1</p>
            <h2 class="id-hero__title">{{ __('instructor.ls_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.ls_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $otoHref }}" class="id-btn id-btn--gold">{{ __('instructor.o1o_title') }}</a>
            @if($availHref)
                <a href="{{ $availHref }}" class="id-btn id-btn--ghost">{{ __('instructor.o1a_title') }}</a>
            @endif
        </div>
    </section>

    <section class="id-kpis" aria-label="{{ __('instructor.ls_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.ls_total') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['total'] ?? 0) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><span class="id-pulse"></span></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.ls_live_now') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['live'] ?? 0) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.ls_scheduled') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['scheduled'] ?? 0) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-check"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.ls_ended') }}</span>
                <span class="id-kpi__value">{{ number_format($stats['ended'] ?? 0) }}</span>
            </span>
        </article>
    </section>

    <nav class="id-filters" aria-label="{{ __('instructor.ls_all') }}">
        <a href="{{ route('instructor.live-sessions.index') }}" class="id-filter {{ ! $status ? 'is-on' : '' }}">{{ __('instructor.ls_all') }}</a>
        <a href="{{ route('instructor.live-sessions.index', ['status' => 'live']) }}" class="id-filter id-filter--live {{ $status === 'live' ? 'is-on' : '' }}">
            <span class="id-pulse" aria-hidden="true"></span> {{ __('instructor.ls_live') }}
        </a>
        <a href="{{ route('instructor.live-sessions.index', ['status' => 'scheduled']) }}" class="id-filter {{ $status === 'scheduled' ? 'is-on' : '' }}">{{ __('instructor.ls_scheduled') }}</a>
        <a href="{{ route('instructor.live-sessions.index', ['status' => 'pending']) }}" class="id-filter {{ $status === 'pending' ? 'is-on' : '' }}">{{ __('instructor.o1o_pending') }}</a>
        <a href="{{ route('instructor.live-sessions.index', ['status' => 'ended']) }}" class="id-filter {{ $status === 'ended' ? 'is-on' : '' }}">{{ __('instructor.ls_ended') }}</a>
    </nav>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.ls_title') }}">
        <header class="id-panel__head">
            <div>
                <h2>{{ __('instructor.private_lessons') }}</h2>
                <p class="id-panel__hint">{{ __('instructor.ls_readonly_hint') }}</p>
            </div>
            @if(($stats['pending'] ?? 0) > 0)
                <span class="id-panel__badge">{{ number_format($stats['pending']) }} {{ __('instructor.o1o_pending') }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($sessions as $session)
                @php
                    $isPending = $session->status === \App\Models\OneToOneSession::STATUS_PENDING;
                    $isScheduled = $session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED;
                    $isCompleted = $session->status === \App\Models\OneToOneSession::STATUS_COMPLETED;
                    $meeting = $session->classroomMeeting;
                    $isNearNow = $isScheduled
                        && $session->scheduled_at
                        && $session->scheduled_at->between(now()->subMinutes(15), now()->addMinutes(90));
                    $manageUrl = route('instructor.one-to-one-sessions.show', $session);
                    $roomUrl = ($meeting && Route::has('instructor.classroom.room'))
                        ? route('instructor.classroom.room', $meeting)
                        : null;
                @endphp
                <article class="id-list__row {{ $isNearNow ? 'is-live' : '' }}">
                    <span class="id-list__ico {{ $isNearNow ? '' : ($isScheduled ? 'id-list__ico--gold' : ($isCompleted ? 'id-list__ico--teal' : '')) }}" aria-hidden="true" @if($isNearNow) style="background:#FCEAEA;color:#DC2626" @endif>
                        <i class="fas {{ $isNearNow ? 'fa-broadcast-tower' : ($isScheduled ? 'fa-chalkboard-teacher' : ($isPending ? 'fa-clock' : 'fa-check')) }}"></i>
                    </span>
                    <div class="id-list__body">
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:4px">
                            @if($isNearNow)
                                <span class="id-chip id-chip--rose"><span class="id-pulse" aria-hidden="true"></span> {{ __('instructor.ls_live') }}</span>
                            @else
                                <span class="id-chip {{ $isCompleted ? 'id-chip--ok' : ($isPending ? 'id-chip--warn' : '') }}">{{ $session->statusLabel() }}</span>
                            @endif
                            @if($session->course)
                                <span class="id-chip id-chip--muted">{{ Str::limit($session->course->title, 30) }}</span>
                            @endif
                            <span class="id-chip id-chip--muted">{{ __('instructor.o1o_session_number', ['n' => $session->session_number]) }}</span>
                        </div>
                        <div class="id-list__title">{{ $session->student->name ?? '—' }}</div>
                        <div class="id-list__meta">
                            @if($session->scheduled_at)
                                <x-app-datetime :at="$session->scheduled_at" pattern="Y/m/d H:i" />
                            @else
                                {{ __('instructor.o1o_pending') }}
                            @endif
                            · {{ (int) ($session->duration_minutes ?: 50) }} {{ __('instructor.o1o_minutes') }}
                        </div>
                    </div>
                    <div class="id-list__actions">
                        @if($isNearNow && $roomUrl)
                            <a href="{{ $roomUrl }}" class="id-btn id-btn--danger" style="min-height:36px;padding:0 12px;font-size:12px">
                                <i class="fas fa-video" aria-hidden="true"></i> {{ __('instructor.ls_enter') }}
                            </a>
                        @endif
                        <a href="{{ $manageUrl }}" class="id-btn id-btn--outline" style="min-height:36px;padding:0 12px;font-size:12px">
                            {{ __('instructor.o1o_manage') }}
                        </a>
                    </div>
                </article>
            @empty
                <div class="id-empty">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></span>
                    <p>{{ __('instructor.ls_empty_oto') }}</p>
                    <p class="id-field__hint">{{ __('instructor.ls_empty_oto_hint') }}</p>
                    <div class="id-empty__actions">
                        <a href="{{ $otoHref }}" class="id-btn id-btn--navy">{{ __('instructor.o1o_title') }}</a>
                        @if($availHref)
                            <a href="{{ $availHref }}" class="id-btn id-btn--outline">{{ __('instructor.o1a_title') }}</a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        @if(method_exists($sessions, 'hasPages') && $sessions->hasPages())
            <div class="id-pager">{{ $sessions->links() }}</div>
        @endif
    </section>
</div>
@endsection
