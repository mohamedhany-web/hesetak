@extends('layouts.student-timeline')

@section('title', app()->getLocale() === 'ar' ? 'تفاصيل الاستشارة' : 'Consultation details')
@section('page_title', app()->getLocale() === 'ar' ? 'تفاصيل الاستشارة' : 'Consultation')

@section('content')
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $instructor = $consultation->instructor;
    $avatar = $instructor?->avatarDisplayUrl() ?? \App\Models\User::placeholderAvatarUrl();
    $title = $isRtl
        ? ('استشارة مع '.($instructor->name ?? 'المعلم'))
        : ('Consultation with '.($instructor->name ?? 'teacher'));
@endphp

@include('partials.student-timeline-top', [
    'locale' => $locale,
    'pageTitle' => $title,
    'crumbs' => [
        ['label' => __('student_timeline.school_gate'), 'url' => route('dashboard')],
        ['label' => $isRtl ? 'الاستشارات' : 'Consultations', 'url' => route('consultations.index')],
        ['label' => '#'.$consultation->id, 'url' => null],
    ],
])

@if(session('success'))
    <div class="st-flash st-flash--ok">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="st-flash st-flash--err">{{ session('error') }}</div>
@endif

<section class="st-msg-intro">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $isRtl ? 'رقم الطلب' : 'Request' }} #{{ $consultation->id }} · {{ $consultation->statusLabel() }}</p>
    </div>
    <a href="{{ route('consultations.index') }}" class="st-pill st-pill--outline">
        <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
        {{ $isRtl ? 'رجوع' : 'Back' }}
    </a>
</section>

<section class="st-teacher-facts" aria-label="{{ $title }}">
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-coins"></i></span>
        <div>
            <strong>{{ number_format((float) $consultation->price_amount, 2) }} {{ currency_symbol() }}</strong>
            <small>{{ $isRtl ? 'المبلغ' : 'Amount' }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
        <div>
            <strong>{{ (int) $consultation->duration_minutes }}</strong>
            <small>{{ $isRtl ? 'دقيقة' : 'minutes' }}</small>
        </div>
    </article>
    <article class="st-teacher-fact">
        <span class="st-teacher-fact__icon" aria-hidden="true"><i class="fas fa-info-circle"></i></span>
        <div>
            <strong>{{ $consultation->statusLabel() }}</strong>
            <small>{{ $isRtl ? 'الحالة' : 'Status' }}</small>
        </div>
    </article>
</section>

<section class="st-settings-block" style="margin-top:1rem">
    <div class="st-settings-block__head">
        <img src="{{ $avatar }}" alt="" width="40" height="40" style="border-radius:999px;object-fit:cover">
        <div>
            <h3>{{ $instructor->name ?? '—' }}</h3>
            <p>
                @if($consultation->scheduled_at)
                    <x-app-datetime :at="$consultation->scheduled_at" pattern="Y-m-d · g:i A" />
                @else
                    {{ $isRtl ? 'لم يُحدد الموعد بعد' : 'Not scheduled yet' }}
                @endif
            </p>
        </div>
    </div>

    @if($consultation->platformWallet)
        <div class="st-flash st-flash--ok" style="margin:1rem 0 0">
            <strong>{{ $isRtl ? 'حساب التحويل' : 'Transfer account' }}:</strong>
            {{ $consultation->platformWallet->name ?? \App\Models\Wallet::typeLabel($consultation->platformWallet->type) }}
            @if($consultation->platformWallet->account_number)
                — <span dir="ltr">{{ $consultation->platformWallet->account_number }}</span>
            @endif
        </div>
    @endif

    @if($consultation->student_message)
        <div style="margin-top:1rem">
            <p class="st-field__hint" style="margin-bottom:0.35rem">{{ $isRtl ? 'رسالتك' : 'Your message' }}</p>
            <p style="margin:0;white-space:pre-line;line-height:1.7;font-weight:600;color:#3A4A63">{{ $consultation->student_message }}</p>
        </div>
    @endif

    @if($consultation->payment_proof)
        <div style="margin-top:1.25rem">
            <p class="st-field__hint" style="margin-bottom:0.5rem">{{ $isRtl ? 'إيصال الدفع' : 'Payment proof' }}</p>
            <a href="{{ storage_asset($consultation->payment_proof) }}" target="_blank" rel="noopener">
                <img src="{{ storage_asset($consultation->payment_proof) }}" alt="" style="max-height:280px;width:auto;max-width:100%;border-radius:14px;border:1px solid #E2E8F0">
            </a>
        </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_PAYMENT_REPORTED && $consultation->paidViaPlatformAccounts())
        <div class="st-flash st-flash--ok" style="margin-top:1rem">
            {{ $isRtl
                ? 'تم استلام الطلب والإيصال. الإدارة تتحقق من التحويل ثم يُحدد الموعد.'
                : 'Request and receipt received. Admin will verify payment, then schedule the session.' }}
        </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_AWAITING_VERIFICATION)
        <div class="st-flash st-flash--ok" style="margin-top:1rem">
            {{ $isRtl ? 'بانتظار مراجعة الإدارة.' : 'Awaiting admin review.' }}
        </div>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_PENDING)
        @if($settings->payment_instructions)
            <div class="st-flash" style="margin-top:1rem;white-space:pre-line">{{ $settings->payment_instructions }}</div>
        @endif
        <form method="POST" action="{{ route('consultations.report-payment', $consultation) }}" class="st-form" style="margin-top:1rem;display:grid;gap:0.75rem;max-width:28rem">
            @csrf
            <label class="st-field">
                <span class="st-field__label">{{ $isRtl ? 'مرجع التحويل (اختياري)' : 'Payment reference (optional)' }}</span>
                <input type="text" name="payment_reference" class="st-input" value="{{ old('payment_reference', $consultation->payment_reference) }}">
            </label>
            <button type="submit" class="st-pill st-pill--solid" style="width:fit-content">
                {{ $isRtl ? 'أبلغت عن إتمام التحويل' : 'I reported the transfer' }}
            </button>
        </form>
    @endif

    @if($consultation->status === \App\Models\ConsultationRequest::STATUS_SCHEDULED && $consultation->classroomMeeting)
        @php $joinUrl = url('classroom/join/'.$consultation->classroomMeeting->code); @endphp
        <div class="st-flash st-flash--ok" style="margin-top:1rem;display:grid;gap:0.65rem">
            <strong>{{ $isRtl ? 'موعد الجلسة' : 'Session time' }}</strong>
            <span><x-app-datetime :at="$consultation->scheduled_at" pattern="Y-m-d · g:i A" /></span>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center">
                <input type="text" readonly value="{{ $joinUrl }}" class="st-input" style="flex:1;min-width:12rem" dir="ltr">
                <button type="button" class="st-pill st-pill--outline" onclick="navigator.clipboard.writeText(@js($joinUrl))">
                    {{ $isRtl ? 'نسخ الرابط' : 'Copy link' }}
                </button>
            </div>
        </div>
    @endif
</section>
@endsection
