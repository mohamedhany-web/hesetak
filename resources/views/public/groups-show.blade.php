@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $user = auth()->user();
  $servicePackages = $servicePackages ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $group->isIndividual() ? '1:1' : ($isRtl ? 'مسار مرتبط بمنهج' : 'Curriculum path') }}</p>
    <h1>{{ $group->title }}</h1>
    <p class="mc-lead">{{ $group->description }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if(session('success'))<div class="mc-empty" style="margin-bottom:1rem;color:#065f46">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mc-empty" style="margin-bottom:1rem;color:#991b1b">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="mc-detail">
      <article class="mc-detail__panel">
        @if($group->imageUrl())<div class="mc-detail__cover"><img src="{{ $group->imageUrl() }}" alt="{{ $group->title }}"></div>@endif
        <div style="padding:1.25rem">
          <div class="mc-card__meta">
            @if($group->instructor)<span><i class="fas fa-user-graduate"></i> {{ $group->instructor->name }}</span>@endif
            <span><i class="fas fa-clock"></i> {{ $group->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</span>
            <span><i class="fas fa-tag"></i> {{ $group->formattedPrice() }}</span>
          </div>
        </div>
      </article>

      <aside class="mc-detail__panel" style="padding:1.25rem">
        <h2>{{ $isRtl ? 'احجز موعداً' : 'Book a lesson' }}</h2>
        <form method="POST" action="{{ route('public.groups.book', $group->slug) }}">
          @csrf
          @php $hasCredit = auth()->check() && (int) ($creditUnits ?? 0) > 0; @endphp
          @if($hasCredit)<input type="hidden" name="use_credit" value="1">@endif
          @if($slots->isEmpty())
            <p>{{ $isRtl ? 'لا توجد مواعيد متاحة حالياً.' : 'No slots are available.' }}</p>
            <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم آخر' : 'Find another tutor' }}</a>
          @else
            <div class="mc-field"><label>{{ $isRtl ? 'اختر الموعد' : 'Choose a time' }}</label>
              @foreach($slots as $slot)
                <label style="display:flex;gap:.5rem;padding:.65rem;border:1px solid var(--mc-line);border-radius:10px;margin-bottom:.45rem"><input type="radio" name="starts_at" value="{{ $slot['starts_at'] }}" required @checked(old('starts_at') === $slot['starts_at'])><span>{{ $slot['label'] }}</span></label>
              @endforeach
            </div>
            @guest
              <div class="mc-field"><label for="guest_name">{{ $isRtl ? 'الاسم' : 'Name' }}</label><input id="guest_name" class="mc-input" type="text" name="guest_name" value="{{ old('guest_name') }}" required></div>
              <div class="mc-field"><label for="guest_phone">{{ $isRtl ? 'الهاتف' : 'Phone' }}</label><input id="guest_phone" class="mc-input" type="text" name="guest_phone" value="{{ old('guest_phone') }}"></div>
              <div class="mc-field"><label for="guest_email">{{ $isRtl ? 'البريد' : 'Email' }}</label><input id="guest_email" class="mc-input" type="email" name="guest_email" value="{{ old('guest_email') }}"></div>
            @else
              <input type="hidden" name="guest_name" value="{{ $user->name }}"><input type="hidden" name="guest_email" value="{{ $user->email }}"><input type="hidden" name="guest_phone" value="{{ $user->phone }}">
            @endguest
            @unless($hasCredit)<div class="mc-field"><label for="student_notes">{{ $isRtl ? 'ملاحظات' : 'Notes' }}</label><textarea id="student_notes" name="student_notes" class="mc-textarea">{{ old('student_notes') }}</textarea></div>@endunless
            <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary">{{ $hasCredit ? ($isRtl ? 'تأكيد من الرصيد' : 'Confirm with credit') : ($isRtl ? 'إرسال طلب الحجز' : 'Request booking') }}</button>
          @endif
        </form>
      </aside>
    </div>
  </div>
</section>

@if($group->isCollective() && ($cohorts ?? collect())->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'المواعيد المتاحة' : 'Available schedules' }}</h2></div>
    <div class="mc-grid mc-grid--3">
      @foreach($cohorts as $cohort)
        <article class="mc-card"><div class="mc-card__body"><h3 class="mc-card__title">{{ $cohort->title }}</h3><div class="mc-card__meta"><span>{{ $cohort->statusLabel() }}</span><span>{{ $cohort->seatsLeft() }} {{ $isRtl ? 'متبقٍ' : 'left' }}</span></div>@if($cohort->isEnrollmentOpen())<a href="{{ auth()->check() ? route('public.groups.checkout', ['slug' => $group->slug, 'cohort' => $cohort->id]) : route('login') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'التحق الآن' : 'Join now' }}</a>@endif</div></article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if($group->isIndividual() && ($packages ?? collect())->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'باقات الحصص الفردية' : 'Private lesson packages' }}</h2></div>
    <div class="mc-packages">
      @foreach($packages as $package)
        <article class="mc-package {{ $package->is_featured ? 'mc-package--recommended' : '' }}"><h3>{{ $package->name }}</h3><p class="mc-package__hours">{{ $package->formattedPrice() }}</p><p class="mc-package__price">{{ $package->sessions_count }} {{ $isRtl ? 'حصة' : 'sessions' }}</p><a href="{{ auth()->check() ? route('public.groups.checkout', ['slug' => $group->slug, 'package' => $package->id]) : route('login') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'اشترك الآن' : 'Subscribe' }}</a></article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if($servicePackages->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container"><div class="mc-section-head"><h2>{{ $isRtl ? 'باقات مناسبة' : 'Matching packages' }}</h2></div><div class="mc-packages">
    @foreach($servicePackages as $package)
      <article class="mc-package"><h3>{{ $package->name }}</h3><p class="mc-package__hours">{{ $package->formattedPrice() }}</p><p class="mc-package__price">{{ $package->units_count }} {{ $isRtl ? 'حصة' : 'sessions' }}</p><a href="{{ route('public.service-packages.checkout', $package) }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'اشترِ الباقة' : 'Buy package' }}</a></article>
    @endforeach
  </div></div>
</section>
@endif
@endsection
