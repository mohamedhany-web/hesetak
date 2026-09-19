@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $groups = $groups ?? $courses ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'خيارات مرتبطة بالمناهج' : 'Curriculum options' }}</p>
    <h1>{{ $isRtl ? 'مسارات دراسية متاحة' : 'Available study paths' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'الأولوية في حصتك للدروس الفردية 1:1. قد تظهر هنا مسارات محدودة مرتبطة بمنهج أو مادة عند توفرها.' : 'Hesetak is 1:1 first. Limited curriculum paths may appear here when available.' }}</p>
    <div class="mc-hero__actions"><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'احجز حصة فردية' : 'Book 1:1' }}</a><a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'استكشف المناهج' : 'Browse curricula' }}</a></div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if($groups->isNotEmpty())
      <div class="mc-grid mc-grid--3">
        @foreach($groups as $item)
          <a href="{{ route('public.groups.show', $item->slug) }}" class="mc-card">
            @if($item->imageUrl())<div class="mc-card__media"><img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" loading="lazy"></div>@endif
            <div class="mc-card__body"><h2 class="mc-card__title">{{ $item->title }}</h2><div class="mc-card__meta"><span>{{ $item->instructor->name ?? ($isRtl ? 'معلم على المنصة' : 'Platform tutor') }}</span><span>{{ $item->duration_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</span></div><div class="mc-card__foot"><strong class="mc-card__price">{{ $item->formattedPrice() }}</strong><span class="mc-track__cta">{{ $isRtl ? 'التفاصيل ←' : 'Details →' }}</span></div></div>
          </a>
        @endforeach
      </div>
      @if(method_exists($groups, 'hasPages') && $groups->hasPages())<div style="margin-top:1.5rem">{{ $groups->withQueryString()->links() }}</div>@endif
    @else
      <div class="mc-empty"><p>{{ $isRtl ? 'لا توجد مسارات منشورة حالياً. يمكنك البدء بحصة فردية حسب منهجك.' : 'No paths are published. Start with a private lesson.' }}</p><a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a tutor' }}</a></div>
    @endif
  </div>
</section>
@endsection
