@extends('layouts.mycourses-public')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $catalog = $catalog ?? [];
  $years = $catalog['years'] ?? collect();
  $subjects = $catalog['subjects'] ?? collect();
  $tracks = $catalog['tracks'] ?? [];
  $packages = $catalog['packages'] ?? collect();
  $selYear = $catalog['selected_year_id'] ?? null;
  $selSubject = $catalog['selected_subject_id'] ?? null;
  $selTrack = $catalog['selected_curriculum_type'] ?? 'saudi';
  $pkg = $selectedPackage;
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'هدية تعليمية' : 'Gift' }}</p>
    <h1>{{ $isRtl ? 'أهدِ باقة لصديق' : 'Gift a package to a friend' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'بعد الدفع نفتح حساب المستلم (إن لزم) ونفعّل الباقة ونرسل تأكيداً لكما.' : 'After payment we create the recipient account if needed, activate the package, and email you both.' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="max-width:720px">
    @if(session('error'))
      <div class="mc-empty" style="color:#991b1b;margin-bottom:1rem">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('public.gift-package.store') }}" class="mc-card" style="padding:1.25rem">
      @csrf

      <div class="mc-field" style="margin-bottom:1rem">
        <label>{{ $isRtl ? 'المرحلة' : 'Stage' }}</label>
        <select name="academic_year_id" class="mc-input" id="giftYear">
          @foreach($years as $y)
            <option value="{{ $y->id }}" @selected((int)$selYear === (int)$y->id)>{{ $y->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="mc-field" style="margin-bottom:1rem">
        <label>{{ $isRtl ? 'نوع المنهج' : 'Curriculum track' }}</label>
        <select name="curriculum_type" class="mc-input" id="giftTrack">
          @foreach($tracks as $key => $meta)
            <option value="{{ $key }}" @selected($selTrack === $key)>{{ $meta['label'] }}</option>
          @endforeach
        </select>
      </div>

      @if($subjects->isNotEmpty())
        <div class="mc-field" style="margin-bottom:1rem">
          <label>{{ $isRtl ? 'المادة (اختياري)' : 'Subject (optional)' }}</label>
          <select name="academic_subject_id" class="mc-input">
            <option value="">{{ $isRtl ? '— الكل —' : '— Any —' }}</option>
            @foreach($subjects as $s)
              <option value="{{ $s->id }}" @selected((int)$selSubject === (int)$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="mc-field" style="margin-bottom:1rem">
        <label>{{ $isRtl ? 'الباقة' : 'Package' }}</label>
        <select name="service_package_id" class="mc-input" required id="giftPackage">
          @foreach($packages as $row)
            <option value="{{ $row['id'] }}" @selected($pkg && (int)$pkg->id === (int)$row['id'])
              data-total="{{ $row['display_price'] }}" data-currency="{{ $row['currency'] }}">
              {{ $row['name'] }} — {{ number_format($row['display_price'], 2) }} {{ $row['currency'] }}
            </option>
          @endforeach
        </select>
        @if($quote)
          <p class="mc-lead" style="margin-top:.5rem;font-size:.9rem">
            {{ $isRtl ? 'سعر الحصة' : 'Per session' }}:
            <strong dir="ltr">{{ number_format($quote['unit'], 2) }} {{ $quote['currency'] }}</strong>
            · {{ $isRtl ? 'الإجمالي' : 'Total' }}:
            <strong dir="ltr">{{ number_format($quote['total'], 2) }} {{ $quote['currency'] }}</strong>
          </p>
        @endif
      </div>

      <div class="mc-field" style="margin-bottom:1rem">
        <label for="recipient_email">{{ $isRtl ? 'بريد المستلم' : 'Recipient email' }}</label>
        <input id="recipient_email" type="email" name="recipient_email" required value="{{ old('recipient_email') }}" class="mc-input" dir="ltr">
      </div>
      <div class="mc-field" style="margin-bottom:1rem">
        <label for="recipient_name">{{ $isRtl ? 'اسم المستلم' : 'Recipient name' }}</label>
        <input id="recipient_name" type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="mc-input">
      </div>
      <div class="mc-field" style="margin-bottom:1rem">
        <label for="recipient_phone">{{ $isRtl ? 'جوال المستلم (اختياري)' : 'Recipient phone (optional)' }}</label>
        <input id="recipient_phone" type="text" name="recipient_phone" value="{{ old('recipient_phone') }}" class="mc-input" dir="ltr">
      </div>
      <div class="mc-field" style="margin-bottom:1rem">
        <label for="message">{{ $isRtl ? 'رسالة قصيرة' : 'Short message' }}</label>
        <textarea id="message" name="message" rows="3" class="mc-input">{{ old('message') }}</textarea>
      </div>

      @auth
        <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'متابعة للدفع' : 'Continue to payment' }}</button>
      @else
        <a href="{{ route('login', ['redirect' => route('public.gift-package.show')]) }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'سجّل الدخول للإهداء' : 'Sign in to gift' }}</a>
      @endauth
    </form>
  </div>
</section>
@endsection
