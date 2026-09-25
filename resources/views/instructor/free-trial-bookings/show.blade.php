@extends('layouts.app')

@section('title', 'تفاصيل الحصة المجانية')
@section('page_title', 'تفاصيل الحصة المجانية')

@section('content')
@php
    $locale = app()->getLocale();
    $isPending = $booking->status === \App\Models\FreeTrialBooking::STATUS_PENDING;
    $availableSlots = $availableSlots ?? collect();
@endphp

<div class="id-page">
    @if(session('success'))
        <div class="id-alert id-alert--ok" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="id-alert id-alert--err" style="margin-bottom:1rem">{{ $errors->first() }}</div>
    @endif

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
                <span class="id-kpi__label">الموعد المقترح</span>
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
                @if($booking->oneToOneSession)
                    <a href="{{ route('instructor.one-to-one-sessions.show', $booking->oneToOneSession) }}" class="id-link" style="margin-top:6px">فتح الحصة المرتبطة</a>
                @endif
            </span>
        </article>
    </section>

    @if($booking->notes)
        <section class="id-panel">
            <header class="id-panel__head">
                <h2>ملاحظات</h2>
            </header>
            <p style="margin:0;font-size:14px;font-weight:600;line-height:1.7;color:#3A4A63;white-space:pre-line">{{ $booking->notes }}</p>
        </section>
    @endif

    @if($isPending)
        <section class="id-panel" style="margin-top:1rem">
            <header class="id-panel__head">
                <h2>قبول أو رفض الطلب</h2>
            </header>
            <p class="id-field__hint" style="margin:0 0 1rem">اقبل الموعد المقترح مباشرة (بدون اشتراط جدول توافر)، أو عدّله، أو ارفض مع سبب مختصر.</p>

            <form method="POST" action="{{ route('instructor.free-trial-bookings.accept', $booking) }}" class="id-form" style="display:grid;gap:0.85rem;margin-bottom:1.25rem">
                @csrf
                <label class="id-field">
                    <span class="id-field__label">موعد التأكيد</span>
                    <input type="datetime-local" name="starts_at" class="id-input" value="{{ optional($booking->starts_at)?->timezone(auth()->user()->timezoneCode() ?? config('app.timezone'))->format('Y-m-d\\TH:i') }}" required>
                    <span class="id-field__hint">يمكنك تأكيد الموعد مباشرة حتى بدون نشر جدول توافر أسبوعي.</span>
                </label>
                @if($availableSlots->isNotEmpty())
                    <label class="id-field">
                        <span class="id-field__label">أو اختر من نوافذ توافرك (اختياري)</span>
                        <select class="id-input" onchange="if(this.value){ this.form.starts_at.value = this.value; }">
                            <option value="">— أبقِ الموعد أعلاه —</option>
                            @foreach($availableSlots as $slot)
                                <option value="{{ $slot['starts_at']->timezone(auth()->user()->timezoneCode() ?? config('app.timezone'))->format('Y-m-d\\TH:i') }}">{{ $slot['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="id-field">
                    <span class="id-field__label">ملاحظة للمعلم (اختياري)</span>
                    <textarea name="notes" class="id-input" rows="2" maxlength="2000" placeholder="مثال: تم التأكيد على الموعد"></textarea>
                </label>
                <button type="submit" class="id-btn id-btn--gold" style="width:fit-content">
                    <i class="fas fa-check" aria-hidden="true"></i>
                    قبول وتأكيد الموعد
                </button>
            </form>

            <form method="POST" action="{{ route('instructor.free-trial-bookings.reject', $booking) }}" class="id-form" style="display:grid;gap:0.85rem;padding-top:1rem;border-top:1px dashed #E2E8F0" onsubmit="return confirm('تأكيد رفض الطلب؟');">
                @csrf
                <label class="id-field">
                    <span class="id-field__label">سبب الرفض (اختياري)</span>
                    <input type="text" name="reason" class="id-input" maxlength="1000" placeholder="مثال: الموعد غير مناسب هذا الأسبوع">
                </label>
                <button type="submit" class="id-btn id-btn--ghost" style="width:fit-content;color:#b91c1c;border-color:#fecaca">
                    <i class="fas fa-times" aria-hidden="true"></i>
                    رفض الطلب
                </button>
            </form>
        </section>
    @endif
</div>
@endsection
