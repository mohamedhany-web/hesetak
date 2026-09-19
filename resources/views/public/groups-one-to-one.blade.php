@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $groups = $groups ?? $courses ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">1:1</p>
    <h1>{{ $isRtl ? 'حصص فردية تناسب هدف الطالب' : 'Private lessons built around the student' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'اختر من الفرص المتاحة، أو ابدأ من دليل المعلمين للحصول على خيارات أوسع.' : 'Choose an available option or start from the tutor directory.' }}</p>
    <div class="mc-hero__actions"><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'دليل المعلمين' : 'Tutor directory' }}</a><a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'المناهج' : 'Curricula' }}</a></div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if($groups->isNotEmpty())
      <div class="mc-grid mc-grid--3">
        @foreach($groups as $item)
          <a href="{{ route('public.groups.show', $item->slug) }}" class="mc-card">
            @if($item->imageUrl())<div class="mc-card__media"><img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy"></div>@endif
            <div class="mc-card__body"><span class="mc-card__badge" style="position:static;align-self:flex-start">1:1</span><h2 class="mc-card__title">{{ $item->title }}</h2><div class="mc-card__meta"><span>{{ $item->instructor->name ?? ($isRtl ? 'معلم معتمد' : 'Approved tutor') }}</span><span>{{ $item->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</span></div><div class="mc-card__foot"><strong class="mc-card__price">{{ $item->formattedPrice() }}</strong><span class="mc-track__cta">{{ $isRtl ? 'التفاصيل ←' : 'Details →' }}</span></div></div>
          </a>
        @endforeach
      </div>
      @if(method_exists($groups, 'hasPages') && $groups->hasPages())<div style="margin-top:1.5rem">{{ $groups->withQueryString()->links() }}</div>@endif
    @else
      <div class="mc-empty"><p>{{ $isRtl ? 'لا توجد عروض فردية منشورة هنا حالياً. استخدم دليل المعلمين للحجز.' : 'No private offers are listed here. Use the tutor directory.' }}</p><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a tutor' }}</a></div>
    @endif
  </div>
</section>
@endsection
