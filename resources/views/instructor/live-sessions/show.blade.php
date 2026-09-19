@extends('layouts.app')

@section('title', $liveSession->title)
@section('page_title', $liveSession->title)

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $backHref = route('instructor.live-sessions.index');
@endphp

<div class="id-page">
    <section class="id-hero {{ $liveSession->isLive() ? '' : 'id-hero--soft' }}" aria-label="{{ $liveSession->title }}" @if($liveSession->isLive()) style="background:linear-gradient(135deg,#B91C1C 0%,#7F1D1D 100%)" @endif>
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.ls_title') }}</p>
            <h2 class="id-hero__title">{{ $liveSession->title }}</h2>
            <p class="id-hero__meta" style="font-family:ui-monospace,monospace">{{ $liveSession->room_name }}</p>
        </div>
        <div class="id-hero__actions">
            @if($liveSession->isLive())
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                    <span class="id-pulse" aria-hidden="true"></span> {{ __('instructor.ls_live') }}
                </span>
                <a href="{{ route('instructor.live-sessions.room', $liveSession) }}" class="id-btn id-btn--gold">
                    <i class="fas fa-video" aria-hidden="true"></i>
                    {{ __('instructor.ls_enter_broadcast') }}
                </a>
            @elseif($liveSession->isScheduled())
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ __('instructor.ls_scheduled') }}</span>
                <form method="POST" action="{{ route('instructor.live-sessions.start', $liveSession) }}">
                    @csrf
                    <button type="submit" class="id-btn id-btn--gold">
                        <i class="fas fa-play" aria-hidden="true"></i>
                        {{ __('instructor.ls_start_now') }}
                    </button>
                </form>
            @elseif($liveSession->isEnded())
                <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">{{ __('instructor.ls_ended') }}</span>
            @endif
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.ls_back_list') }}
            </a>
        </div>
    </section>

    @if($liveSession->isLive())
        <div class="id-alert" style="background:#FCEAEA;color:#B91C1C;border:1px solid rgba(185,28,28,.2)">
            <span class="id-pulse" aria-hidden="true"></span>
            <span>{{ __('instructor.ls_live_banner', ['ago' => $liveSession->started_at?->diffForHumans()]) }}</span>
        </div>
    @endif

    <div class="id-grid">
        <div style="display:flex;flex-direction:column;gap:12px;min-width:0">
            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.ls_details') }}</h2>
                </header>
                <div class="id-dl">
                    <div class="id-dl__item">
                        <label>{{ __('instructor.ls_course') }}</label>
                        <div>{{ $liveSession->course?->title ?? __('instructor.ls_general') }}</div>
                    </div>
                    <div class="id-dl__item">
                        <label>{{ __('instructor.ls_when') }}</label>
                        <div><x-app-datetime :at="$liveSession->scheduled_at" pattern="Y/m/d H:i" /></div>
                    </div>
                    <div class="id-dl__item">
                        <label>{{ __('instructor.ls_duration') }}</label>
                        <div>{{ $liveSession->duration_for_humans }}</div>
                    </div>
                    <div class="id-dl__item">
                        <label>{{ __('instructor.ls_max') }}</label>
                        <div>{{ $liveSession->max_participants }}</div>
                    </div>
                </div>
                @if($liveSession->description)
                    <p style="margin:16px 0 0;padding-top:14px;border-top:1px solid #EEF2F7;font-size:13px;font-weight:600;line-height:1.6;color:#6B7A93">
                        {{ $liveSession->description }}
                    </p>
                @endif
            </section>

            <section class="id-panel">
                <header class="id-panel__head">
                    <h2>{{ __('instructor.ls_attendance') }}</h2>
                    <span class="id-panel__badge">{{ $attendees->count() }}</span>
                </header>
                <div class="id-list">
                    @forelse($attendees as $att)
                        <div class="id-list__row">
                            <span class="id-list__ico {{ $att->role_in_session === 'instructor' ? 'id-list__ico--gold' : '' }}" aria-hidden="true">
                                <i class="fas {{ $att->role_in_session === 'instructor' ? 'fa-chalkboard-teacher' : 'fa-user-graduate' }}"></i>
                            </span>
                            <div class="id-list__body">
                                <div class="id-list__title">{{ $att->user?->name }}</div>
                                <div class="id-list__meta">
                                    {{ __('instructor.ls_joined') }} {{ $att->joined_at?->format('H:i') }}
                                    @if($att->left_at)
                                        — {{ __('instructor.ls_left') }} {{ $att->left_at->format('H:i') }}
                                    @endif
                                </div>
                            </div>
                            <span class="id-chip id-chip--muted">{{ $att->duration_for_humans }}</span>
                        </div>
                    @empty
                        <div class="id-empty" style="border:0;background:transparent;padding:20px 8px">
                            <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-users"></i></span>
                            <p>{{ __('instructor.ls_no_attendance') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="id-panel" style="text-align:center">
            @if($liveSession->isScheduled())
                <span class="id-kpi__icon" style="margin:0 auto 12px;width:56px;height:56px;font-size:20px" aria-hidden="true">
                    <i class="fas fa-clock"></i>
                </span>
                <p style="margin:0;font-weight:800;color:#152A4A">{{ __('instructor.ls_status_scheduled') }}</p>
                <p class="id-field__hint" style="margin-top:6px">{{ $liveSession->scheduled_at?->diffForHumans() }}</p>
            @elseif($liveSession->isLive())
                <span class="id-kpi__icon id-kpi__icon--rose" style="margin:0 auto 12px;width:56px;height:56px" aria-hidden="true">
                    <span class="id-pulse"></span>
                </span>
                <p style="margin:0;font-weight:900;color:#DC2626">{{ __('instructor.ls_status_live_now') }}</p>
            @elseif($liveSession->isEnded())
                <span class="id-kpi__icon id-kpi__icon--teal" style="margin:0 auto 12px;width:56px;height:56px;font-size:20px" aria-hidden="true">
                    <i class="fas fa-check"></i>
                </span>
                <p style="margin:0;font-weight:800;color:#152A4A">{{ __('instructor.ls_status_ended') }}</p>
                <p class="id-field__hint" style="margin-top:6px">{{ __('instructor.ls_duration') }}: {{ $liveSession->duration_for_humans }}</p>
            @endif
        </aside>
    </div>
</div>
@endsection
