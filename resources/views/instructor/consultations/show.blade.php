@extends('layouts.app')

@section('title', __('instructor.cons_show_title'))
@section('page_title', __('instructor.cons_show_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $studentName = $consultation->student->name ?? __('instructor.pm_student_fallback');
    $backHref = route('instructor.consultations.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.cons_show_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.cons_title') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.cons_show_heading', ['name' => $studentName]) }}</h2>
            <p class="id-hero__meta">
                @if($consultation->scheduled_at)
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <x-app-datetime :at="$consultation->scheduled_at" />
                @else
                    {{ __('instructor.cons_subtitle') }}
                @endif
            </p>
        </div>
        <div class="id-hero__actions">
            <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                {{ $consultation->statusLabel() }}
            </span>
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.cons_back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.cons_details') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-money-bill-wave"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.cons_amount') }}</span>
                <span class="id-kpi__value" style="font-size:1.25rem">{{ number_format($consultation->price_amount, 2) }} {{ currency_symbol() }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.cons_duration') }}</span>
                <span class="id-kpi__value">{{ (int) $consultation->duration_minutes }}</span>
                <span class="id-field__hint" style="margin-top:2px">{{ __('instructor.o1o_minutes') }}</span>
            </span>
        </article>
    </section>

    <section class="id-panel">
        <header class="id-panel__head">
            <h2>{{ __('instructor.cons_details') }}</h2>
        </header>

        <div class="id-meta">
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-user" aria-hidden="true"></i></span>
                <span>{{ __('instructor.cons_student') }}</span>
                <strong>{{ $studentName }}</strong>
            </div>
            <div class="id-meta__row">
                <span class="id-meta__ico"><i class="fas fa-tag" aria-hidden="true"></i></span>
                <span>{{ __('instructor.cons_status') }}</span>
                <strong>{{ $consultation->statusLabel() }}</strong>
            </div>
            @if($consultation->scheduled_at)
                <div class="id-meta__row">
                    <span class="id-meta__ico"><i class="fas fa-calendar" aria-hidden="true"></i></span>
                    <span>{{ __('instructor.cons_when') }}</span>
                    <strong><x-app-datetime :at="$consultation->scheduled_at" /></strong>
                </div>
            @endif
        </div>

        @if($consultation->student_message)
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid #EEF2F7">
                <p class="id-field__hint" style="margin-bottom:6px">{{ __('instructor.cons_student_request') }}</p>
                <p style="margin:0;font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63;white-space:pre-line">{{ $consultation->student_message }}</p>
            </div>
        @endif

        @if($consultation->status === \App\Models\ConsultationRequest::STATUS_PAID)
            <div class="id-panel" style="margin-top:16px;padding:16px;box-shadow:none;background:#FFF8E8;border:1px solid #F5D98A">
                <p style="margin:0 0 10px;font-size:14px;font-weight:800;color:#152A4A">جدولة الاستشارة بعد تأكيد الدفع</p>
                <form method="POST" action="{{ route('instructor.consultations.schedule', $consultation) }}" class="id-form" style="display:grid;gap:0.75rem;max-width:22rem">
                    @csrf
                    <label class="id-field">
                        <span class="id-field__label">الموعد</span>
                        <input type="datetime-local" name="scheduled_at" class="id-input" required>
                    </label>
                    <label class="id-field">
                        <span class="id-field__label">المدة (دقيقة)</span>
                        <input type="number" name="duration_minutes" class="id-input" min="15" max="480" value="{{ (int) $consultation->duration_minutes }}">
                    </label>
                    <button type="submit" class="id-btn id-btn--gold" style="width:fit-content">تأكيد الجدولة</button>
                </form>
            </div>
        @endif

        @if($consultation->status === \App\Models\ConsultationRequest::STATUS_SCHEDULED && $consultation->classroomMeeting)
            @php
                $m = $consultation->classroomMeeting;
                $joinUrl = url('classroom/join/'.$m->code);
            @endphp
            <div class="id-panel" style="margin-top:16px;padding:16px;box-shadow:none;background:#F7FAFE">
                <p style="margin:0 0 8px;font-size:14px;font-weight:800;color:#152A4A">
                    {{ __('instructor.cons_appointment') }}:
                    <x-app-datetime :at="$consultation->scheduled_at" />
                </p>
                <p class="id-field__hint" style="margin:0 0 14px;word-break:break-all">
                    {{ __('instructor.cons_guest_link') }}: {{ $joinUrl }}
                </p>
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    <a href="{{ route('instructor.classroom.show', $m) }}" class="id-btn id-btn--outline">
                        <i class="fas fa-cog" aria-hidden="true"></i>
                        {{ __('instructor.cons_room_settings') }}
                    </a>
                    @if(!$m->ended_at)
                        <a href="{{ route('instructor.classroom.room', $m) }}" class="id-btn id-btn--ok">
                            <i class="fas fa-video" aria-hidden="true"></i>
                            {{ __('instructor.cons_enter_room') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
