@extends('layouts.app')

@section('title', __('instructor.o1o_schedule_session'))
@section('page_title', __('instructor.o1o_schedule_session'))

@section('content')
@php
    $locale = app()->getLocale();
    $backHref = route('instructor.one-to-one-sessions.index');
    $availHref = Route::has('instructor.one-to-one-availability.index')
        ? route('instructor.one-to-one-availability.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.o1o_schedule_session') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.o1o_title') }}</p>
            <h2 class="id-hero__title">{{ $session->course->title ?? '—' }}</h2>
            <p class="id-hero__meta">
                {{ $session->student->name ?? __('instructor.pm_student_fallback') }}
                — {{ __('instructor.o1o_session_number', ['n' => $session->session_number]) }}
            </p>
        </div>
        <div class="id-hero__actions">
            <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                {{ $session->statusLabel() }}
            </span>
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel" aria-label="{{ __('instructor.o1o_schedule_session') }}">
        @if($session->status === \App\Models\OneToOneSession::STATUS_SCHEDULED && $session->classroomMeeting)
            @php $m = $session->classroomMeeting; @endphp
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.cons_appointment') }}</h2>
                    <p class="id-panel__hint">
                        <x-app-datetime :at="$session->scheduled_at" />
                    </p>
                </div>
            </header>

            <div class="id-list" style="margin-bottom:16px">
                <div class="id-list__row">
                    <span class="id-list__ico id-list__ico--teal" aria-hidden="true"><i class="fas fa-video"></i></span>
                    <div class="id-list__body">
                        <div class="id-list__title">{{ $session->student->name ?? '—' }}</div>
                        <div class="id-list__meta">{{ $session->course->title ?? '—' }}</div>
                    </div>
                    <div class="id-list__actions">
                        <a href="{{ route('instructor.classroom.show', $m) }}" class="id-btn id-btn--outline" style="min-height:36px;padding:0 12px;font-size:12px">
                            <i class="fas fa-cog" aria-hidden="true"></i>
                            {{ __('instructor.cons_room_settings') }}
                        </a>
                        @if(!$m->ended_at)
                            <a href="{{ route('instructor.classroom.room', $m) }}" class="id-btn id-btn--navy" style="min-height:36px;padding:0 12px;font-size:12px">
                                <i class="fas fa-video" aria-hidden="true"></i>
                                {{ __('instructor.cons_enter_room') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @php
                $canMarkComplete = $m->started_at
                    && $session->scheduled_at
                    && $session->scheduled_at->lte(now()->addMinutes(15));
            @endphp
            @if($canMarkComplete)
                <form method="POST" action="{{ route('instructor.one-to-one-sessions.complete', $session) }}" onsubmit="return confirm(@json(__('instructor.o1o_complete_confirm')))">
                    @csrf
                    <button type="submit" class="id-btn id-btn--gold">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        {{ __('instructor.o1o_mark_complete') }}
                    </button>
                </form>
            @else
                <div class="id-alert id-alert--info">
                    <i class="fas fa-info-circle" aria-hidden="true"></i>
                    <span>{{ __('instructor.o1o_complete_requires_room') }}</span>
                </div>
            @endif

        @elseif($session->status === \App\Models\OneToOneSession::STATUS_PENDING)
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.o1o_schedule_session') }}</h2>
                    <p class="id-panel__hint">{{ __('instructor.o1o_pending_hint') }}</p>
                </div>
                @if($availHref)
                    <a href="{{ $availHref }}" class="id-link">{{ __('instructor.o1a_title') }}</a>
                @endif
            </header>

            <form method="POST" action="{{ route('instructor.one-to-one-sessions.schedule', $session) }}" class="id-form">
                @csrf
                @php $tzCurrent = old('timezone', auth()->user()?->timezoneCode()); @endphp
                @include('partials.timezone-select', [
                    'value' => $tzCurrent,
                    'class' => 'id-input',
                    'labelClass' => 'block text-[12px] font-extrabold text-[#3A4A63] mb-1.5',
                ])
                <div class="id-field">
                    <label for="scheduled_at">{{ __('instructor.o1o_datetime_label') }}</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at" required
                           class="id-input"
                           min="{{ now()->timezone($tzCurrent)->addHour()->format('Y-m-d\TH:i') }}">
                    @error('scheduled_at')
                        <p class="id-field__err">{{ $message }}</p>
                    @enderror
                </div>
                <div class="id-field">
                    <label for="duration_minutes">{{ __('instructor.o1o_duration_label') }}</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" value="50" min="50" max="50" readonly class="id-input">
                    <p class="id-field__hint">{{ __('instructor.o1o_duration_fixed') }}</p>
                </div>
                <div>
                    <button type="submit" class="id-btn id-btn--gold">
                        <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                        {{ __('instructor.o1o_schedule_session') }}
                    </button>
                </div>
            </form>

        @elseif($session->status === \App\Models\OneToOneSession::STATUS_COMPLETED && $session->classroomMeeting)
            @php $m = $session->classroomMeeting; @endphp
            <header class="id-panel__head">
                <div>
                    <h2>{{ __('instructor.cons_appointment') }}</h2>
                    <p class="id-panel__hint">
                        @if($session->scheduled_at)
                            <x-app-datetime :at="$session->scheduled_at" />
                        @endif
                    </p>
                </div>
                <span class="id-chip id-chip--ok">{{ $session->statusLabel() }}</span>
            </header>

            @if($m->hasBrowserRecording())
                <div class="id-alert id-alert--ok" style="margin-bottom:14px">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>تم رفع التسجيل — متاح للطالب من صفحة الحصة.</span>
                </div>
                @if($m->recording_download_url || $m->recording_audio_download_url)
                    <a href="{{ $m->recording_download_url ?: $m->recording_audio_download_url }}" target="_blank" rel="noopener" class="id-btn id-btn--navy">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                        مشاهدة التسجيل
                    </a>
                @endif
            @else
                <div class="id-empty">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-film"></i></span>
                    <p>لا يوجد تسجيل مرفوع لهذه الحصة بعد.</p>
                </div>
            @endif
        @else
            <div class="id-empty">
                <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-info-circle"></i></span>
                <p>{{ $session->statusLabel() }}</p>
                <div class="id-empty__actions">
                    <a href="{{ $backHref }}" class="id-btn id-btn--navy">{{ __('instructor.back') }}</a>
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
