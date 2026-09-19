@extends('layouts.mycourses-public')

@section('content')
@php
  $brand = __('landing.nav.brand');
  $isRtl = app()->getLocale() === 'ar';
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'تحقق عام' : 'Public verification' }}</p>
    <h1>
      {{ $isRtl ? 'تحقق من شهادة' : 'Verify a certificate' }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    <p class="mc-lead">
      {{ $isRtl
        ? 'أدخل رمز التحقق أو الرقم التسلسلي للتأكد من صحة الشهادة الصادرة عن المنصة.'
        : 'Enter the verification code or serial number to confirm a platform-issued certificate.' }}
    </p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container mc-verify">
    <form method="GET" action="{{ route('public.certificates.verify') }}" class="mc-verify-form" novalidate>
      <label for="verify-code" class="mc-verify-form__label">{{ $isRtl ? 'رمز التحقق أو السيريال' : 'Verification code or serial' }}</label>
      <div class="mc-verify-form__row">
        <input
          id="verify-code"
          type="text"
          name="code"
          value="{{ request('code') }}"
          placeholder="{{ $isRtl ? 'أدخل الرمز هنا' : 'Enter code here' }}"
          required
          autocomplete="off"
        >
        <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary">
          <i class="fas fa-search" aria-hidden="true"></i>
          {{ $isRtl ? 'تحقق' : 'Verify' }}
        </button>
      </div>
    </form>

    @if(isset($certificate))
      @if($certificate && $isValid)
        <div class="mc-verify-result mc-verify-result--ok">
          <div class="mc-verify-result__banner">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <div>
              <h2>{{ $isRtl ? 'شهادة صحيحة ومعتمدة' : 'Valid certificate' }}</h2>
              <p>{{ $isRtl ? 'تم التحقق من صحة هذه الشهادة.' : 'This certificate has been verified.' }}</p>
            </div>
          </div>
          <div class="mc-verify-result__grid">
            <div>
              <h3>{{ $isRtl ? 'معلومات الحاصل' : 'Holder details' }}</h3>
              <p><strong>{{ $isRtl ? 'الاسم' : 'Name' }}:</strong> {{ $certificate->user->name ?? ($isRtl ? 'غير معروف' : 'Unknown') }}</p>
              <p><strong>{{ $isRtl ? 'البريد' : 'Email' }}:</strong> <span dir="ltr">{{ $certificate->user->email ?? '-' }}</span></p>
            </div>
            <div>
              <h3>{{ $isRtl ? 'معلومات الشهادة' : 'Certificate details' }}</h3>
              <p><strong>{{ $isRtl ? 'رقم الشهادة' : 'Number' }}:</strong> <span dir="ltr">{{ $certificate->certificate_number }}</span></p>
              @if($certificate->serial_number)
                <p><strong>{{ $isRtl ? 'السيريال' : 'Serial' }}:</strong> <span dir="ltr">{{ $certificate->serial_number }}</span></p>
              @endif
              <p><strong>{{ $isRtl ? 'تاريخ الإصدار' : 'Issued' }}:</strong> {{ $certificate->issued_at ? $certificate->issued_at->format('Y-m-d') : '-' }}</p>
            </div>
          </div>
          <div class="mc-verify-preview">
            <h3>{{ $isRtl ? 'معاينة الشهادة' : 'Certificate preview' }}</h3>
            <div class="certificate-container">
              @include('components.certificate-templates', [
                'certificate' => $certificate,
                'template' => $certificate->template ?? 'classic',
              ])
            </div>
          </div>
        </div>
      @else
        <div class="mc-verify-result mc-verify-result--err" role="alert">
          <i class="fas fa-times-circle" aria-hidden="true"></i>
          <div>
            <h2>{{ $isRtl ? 'شهادة غير صحيحة' : 'Invalid certificate' }}</h2>
            <p>{{ $error ?? ($isRtl ? 'الشهادة غير موجودة أو غير صالحة.' : 'Certificate not found or invalid.') }}</p>
          </div>
        </div>
      @endif
    @elseif(isset($error))
      <div class="mc-verify-result mc-verify-result--warn" role="status">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <div>
          <h2>{{ $isRtl ? 'تنبيه' : 'Notice' }}</h2>
          <p>{{ $error }}</p>
        </div>
      </div>
    @endif
  </div>
</section>
@endsection

@push('head')
  @include('components.certificate-styles')
@endpush
