@extends('layouts.app')

@section('title', 'تفاصيل الحصة المجانية')
@section('page_title', 'تفاصيل الحصة المجانية')

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="id-page">
    <section class="id-hero" aria-label="تفاصيل الحصة المجانية">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">حجز #{{ $booking->id }}</p>
            <h2 class="id-hero__title">{{ $booking->name }}</h2>
            <p class="id-hero__meta">
                <i class="fas fa-clock" aria-hidden="true"></i>
                <x-app-datetime :at="$booking->starts_at" :timezone="$booking->timezone" pattern="Y-m-d · g:i A" />
                · {{ (int) $booking->duration_minutes }} دقيقة
            </p>
        </div>
        <div class="id-hero__actions">
            <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                {{ $booking->status }}
            </span>
            <a href="{{ route('instructor.free-trial-bookings.index') }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                رجوع
            </a>
        </div>
    </section>

    <section class="id-kpis" aria-label="تفاصيل">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-calendar"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">الموعد</span>
                <span style="font-size:14px;font-weight:800;color:#152A4A;margin-top:4px;line-height:1.35">
                    <x-app-datetime :at="$booking->starts_at" :timezone="$booking->timezone" pattern="Y-m-d · g:i A" />
                </span>
                <span class="id-field__hint" style="margin-top:4px">{{ (int) $booking->duration_minutes }} دقيقة</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-phone"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">التواصل</span>
                <span style="font-size:14px;font-weight:800;color:#152A4A;margin-top:4px;word-break:break-all">{{ $booking->email ?: '—' }}</span>
                <span class="id-field__hint" style="margin-top:4px" dir="ltr">{{ $booking->phone ?: '—' }}</span>
                @if($booking->whatsappUrl())
                    <a href="{{ $booking->whatsappUrl() }}" target="_blank" rel="noopener" class="id-link" style="margin-top:6px">واتساب</a>
                @endif
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-bullseye"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">الغرض / الحالة</span>
                <span style="font-size:14px;font-weight:800;color:#152A4A;margin-top:4px">{{ $booking->goalLabel() }}</span>
                <span class="id-chip" style="margin-top:8px;width:fit-content">{{ $booking->status }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--rose" aria-hidden="true"><i class="fas fa-gift"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">النوع</span>
                <span style="font-size:14px;font-weight:800;color:#152A4A;margin-top:4px">حصة مجانية</span>
            </span>
        </article>
    </section>

    @if($booking->notes)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2>ملاحظات</h2>
            </header>
            <p style="margin:0;font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63">{{ $booking->notes }}</p>
        </section>
    @endif
</div>
@endsection
