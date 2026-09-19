@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $imageUrl = $siteService->publicImageUrl();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'خدمات حصتك' : 'Hesetak services' }}</p>
    <h1>{{ $siteService->name }}</h1>
    @if($siteService->summary)<p class="mc-lead">{{ $siteService->summary }}</p>@endif
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-detail">
      <article class="mc-detail__panel">
        @if($imageUrl)<div class="mc-detail__cover"><img src="{{ $imageUrl }}" alt="{{ $siteService->name }}"></div>@endif
        <div style="padding:1.5rem;line-height:1.9;color:var(--mc-ink-soft)">{!! nl2br(e($siteService->body)) !!}</div>
      </article>
      <aside class="mc-detail__panel" style="padding:1.25rem">
        <h2>{{ $isRtl ? 'ابدأ من هنا' : 'Start here' }}</h2>
        <p>{{ $isRtl ? 'اختر المسار الأقرب لاحتياجك أو تواصل معنا.' : 'Choose the path closest to your goal.' }}</p>
        <div style="display:grid;gap:.65rem">
          <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</a>
          <a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'المناهج' : 'Curricula' }}</a>
          <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'الأسعار والباقات' : 'Pricing' }}</a>
          <a href="{{ route('public.services.index') }}" class="mc-btn mc-btn--md mc-btn--ghost">{{ $isRtl ? 'كل الخدمات' : 'All services' }}</a>
        </div>
      </aside>
    </div>
  </div>
</section>

@if(isset($others) && $others->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'خدمات أخرى' : 'More services' }}</h2></div>
    <div class="mc-grid mc-grid--3">
      @foreach($others as $other)
        <a href="{{ route('public.services.show', $other) }}" class="mc-card"><div class="mc-card__body"><h3 class="mc-card__title">{{ $other->name }}</h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($other->summary ?: $other->body), 100) }}</p></div></a>
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
