@extends('layouts.mycourses-public')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'هدية باقة' : 'Package gift' }}</p>
    <h1>{{ $gift->servicePackage?->name ?? ($isRtl ? 'باقتك' : 'Your package') }}</h1>
    <p class="mc-lead">
      @if($gift->isGranted())
        {{ $isRtl ? 'تم تفعيل الهدية على حسابك.' : 'This gift is active on your account.' }}
      @elseif($gift->status === 'pending_payment')
        {{ $isRtl ? 'بانتظار تأكيد الدفع.' : 'Waiting for payment confirmation.' }}
      @else
        {{ $isRtl ? 'تعذّر تفعيل الهدية تلقائياً — تواصل مع الدعم.' : 'Could not activate automatically — contact support.' }}
      @endif
    </p>
  </div>
</section>
<section class="mc-section">
  <div class="mc-container" style="max-width:560px">
    <article class="mc-card" style="padding:1.25rem">
      <p>{{ $isRtl ? 'من' : 'From' }}: <strong>{{ $gift->buyer?->name ?? '—' }}</strong></p>
      @if($gift->message)
        <p style="margin-top:.75rem">«{{ $gift->message }}»</p>
      @endif
      <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem">
        @auth
          <a href="{{ route('dashboard') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'لوحة الطالب' : 'Student hub' }}</a>
        @else
          <a href="{{ route('login') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'تسجيل الدخول' : 'Sign in' }}</a>
          <a href="{{ route('password.request') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'تعيين كلمة المرور' : 'Set password' }}</a>
        @endauth
      </div>
    </article>
  </div>
</section>
@endsection
