@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $items = $items ?? [];
  $packages = $packages ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'Cart' : 'Cart' }}</p>
    <h1>{{ $isRtl ? 'سلة الباقات' : 'Package cart' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'يمكنك جمع أكثر من باقة ثم الانتقال للدفع عبر البوابة.' : 'Add multiple packages then pay via the gateway.' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1rem;max-width:820px">
    @if(session('success'))
      <div class="mc-co-alert is-ok">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="mc-co-alert is-err">{{ session('error') }}</div>
    @endif

    @forelse($items as $item)
      @php $pkg = $packages->get($item['package_id'] ?? 0); @endphp
      <article class="mc-card" style="padding:1rem;display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center">
        <div>
          <strong>{{ $item['label'] ?? ($pkg->name ?? '—') }}</strong>
          <p style="margin:.35rem 0 0;color:var(--mc-muted);font-size:.9rem">
            {{ (int)($item['sessions'] ?? 0) }} {{ $isRtl ? 'حصة' : 'sessions' }}
            @if(!empty($item['weekly_sessions'])) · {{ (int)$item['weekly_sessions'] }} {{ $isRtl ? '/أسبوع' : '/week' }} @endif
            @if(!empty($item['instructor_id'])) · {{ $isRtl ? 'معلم #' : 'Teacher #' }}{{ (int)$item['instructor_id'] }} @endif
          </p>
          @if(!empty($item['schedule_notes']))
            <p style="margin:.35rem 0 0;font-size:.85rem;white-space:pre-wrap">{{ \Illuminate\Support\Str::limit($item['schedule_notes'], 180) }}</p>
          @endif
        </div>
        <div style="display:flex;align-items:center;gap:.75rem">
          <strong>{{ format_money((float)($item['total'] ?? 0), 2) }}</strong>
          <form method="post" action="{{ route('public.package-cart.remove') }}">
            @csrf
            <input type="hidden" name="key" value="{{ $item['key'] ?? '' }}">
            <button type="submit" class="mc-btn mc-btn--sm mc-btn--outline">{{ $isRtl ? 'حذف' : 'Remove' }}</button>
          </form>
        </div>
      </article>
    @empty
      <div class="mc-empty">{{ $isRtl ? 'السلة فارغة.' : 'Cart is empty.' }}</div>
    @endforelse

    @if(count($items))
      <div class="mc-card" style="padding:1.25rem;display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center">
        <div>
          <p class="mc-eyebrow">{{ $isRtl ? 'الإجمالي' : 'Total' }}</p>
          <strong class="mc-package__hours">{{ format_money($total, 2) }}</strong>
        </div>
        <form method="post" action="{{ route('public.package-cart.checkout') }}">
          @csrf
          <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'إتمام الدفع عبر البوابة' : 'Checkout via gateway' }}</button>
        </form>
      </div>
    @endif

    <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'متابعة التسوق' : 'Continue shopping' }}</a>
  </div>
</section>
@endsection
