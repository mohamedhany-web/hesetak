@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $servicePackages = $servicePackages ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'مرحلة دراسية' : 'Academic stage' }}</p>
    <h1>{{ $year->name }}</h1>
    <p class="mc-lead">{{ $year->description ?: $year->tagline }}</p>
    <div class="mc-hero__actions"><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'اختر معلماً' : 'Choose a tutor' }}</a><a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'كل المناهج' : 'All curricula' }}</a></div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'الدروس المتاحة' : 'Available lessons' }}</h2><p>{{ $isRtl ? 'اختر عرضاً مناسباً أو ابدأ من دليل المعلمين.' : 'Choose an option or start from the tutor directory.' }}</p></div>
    @if($classes->isNotEmpty())
      <div class="mc-grid mc-grid--3">
        @foreach($classes as $class)
          <article class="mc-card"><div class="mc-card__body"><h3 class="mc-card__title">{{ $class->title }}</h3><div class="mc-card__meta">@if($class->schoolSubject)<span>{{ $class->schoolSubject->name }}</span>@endif @if($class->instructor)<span>{{ $class->instructor->name }}</span>@endif <span>{{ (int) $class->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</span></div><div class="mc-card__foot"><a href="{{ route('public.groups.show', $class->slug) }}" class="mc-btn mc-btn--sm mc-btn--primary">{{ $isRtl ? 'عرض التفاصيل' : 'View details' }}</a></div></div></article>
        @endforeach
      </div>
    @else
      <div class="mc-empty"><p>{{ $isRtl ? 'لا توجد دروس مرتبطة بهذه المرحلة حالياً.' : 'No lessons are linked to this stage yet.' }}</p><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a tutor' }}</a></div>
    @endif
  </div>
</section>

@if($servicePackages->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'باقات مناسبة' : 'Matching packages' }}</h2></div>
    <div class="mc-packages">
      @foreach($servicePackages->take(6) as $package)
        <article class="mc-package"><h3>{{ $package->name }}</h3><p class="mc-package__hours">{{ $package->formattedPrice() }}</p><p class="mc-package__price">{{ $package->units_count }} {{ $isRtl ? 'حصة' : 'sessions' }}</p><a href="{{ route('public.service-packages.checkout', $package) }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'اشترِ الباقة' : 'Buy package' }}</a></article>
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
