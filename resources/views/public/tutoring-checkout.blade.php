@extends('layouts.mycourses-public')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'إتمام الحجز' : 'Checkout' }}</p>
    <h1>{{ $isRtl ? 'راجع طلب الاشتراك' : 'Review your subscription' }}</h1>
    <p class="mc-lead">{{ $group->title }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="max-width:640px">
    @if($errors->any())<div class="mc-empty" style="margin-bottom:1rem;color:#991b1b">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <article class="mc-card" style="padding:1.5rem">
      <div style="padding:1rem;background:var(--mc-surface-2);border-radius:12px;margin-bottom:1rem">
        @if($package)
          <h2 style="margin:0 0 .5rem">{{ $package->name }}</h2>
          <p>{{ $package->sessions_count }} {{ $isRtl ? 'حصة' : 'sessions' }} · {{ $package->duration_months }} {{ $isRtl ? 'شهر' : 'months' }}</p>
          @if($package->formattedOriginalPrice())<p style="text-decoration:line-through">{{ $package->formattedOriginalPrice() }}</p>@endif
          <strong class="mc-card__price">{{ $package->formattedPrice() }}</strong>
        @elseif($cohort)
          <h2 style="margin:0 0 .5rem">{{ $cohort->title }}</h2>
          <p>{{ $cohort->statusLabel() }} · {{ $cohort->seatsLeft() }} {{ $isRtl ? 'مقعد متبقٍ' : 'seats left' }}</p>
          <strong class="mc-card__price">{{ $group->formattedPrice() }}</strong>
        @endif
      </div>

      <form method="POST" action="{{ route('public.groups.checkout.store', $group->slug) }}">
        @csrf
        @if($package)<input type="hidden" name="package_id" value="{{ $package->id }}">@endif
        @if($cohort)<input type="hidden" name="cohort_id" value="{{ $cohort->id }}">@endif
        @if($startsAt)<input type="hidden" name="starts_at" value="{{ $startsAt }}">@endif
        <div class="mc-field"><label for="payment_method">{{ $isRtl ? 'طريقة الدفع' : 'Payment method' }}</label><select id="payment_method" name="payment_method" class="mc-select" required><option value="online">{{ $isRtl ? 'دفع أونلاين / محفظة' : 'Online / wallet' }}</option><option value="wallet_transfer">{{ $isRtl ? 'تحويل محفظة' : 'Wallet transfer' }}</option><option value="admin_review">{{ $isRtl ? 'طلب مراجعة إدارية' : 'Admin review' }}</option></select></div>
        @if($wallets->isNotEmpty())
          <div class="mc-field"><label for="wallet_id">{{ $isRtl ? 'المحفظة المستلمة (اختياري)' : 'Receiving wallet (optional)' }}</label><select id="wallet_id" name="wallet_id" class="mc-select"><option value="">−</option>@foreach($wallets as $wallet)<option value="{{ $wallet->id }}">{{ $wallet->name ?: $wallet->type }} {{ $wallet->account_number ? '· '.$wallet->account_number : '' }}</option>@endforeach</select></div>
        @endif
        <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary" style="width:100%"><i class="fas fa-lock"></i> {{ $isRtl ? 'تأكيد الطلب' : 'Confirm order' }}</button>
      </form>
      <a href="{{ route('public.groups.show', $group->slug) }}" class="mc-btn mc-btn--md mc-btn--ghost" style="margin-top:.75rem">{{ $isRtl ? 'العودة للتفاصيل' : 'Back to details' }}</a>
    </article>
  </div>
</section>
@endsection
