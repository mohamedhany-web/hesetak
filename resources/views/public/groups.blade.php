@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $schoolYears = $schoolYears ?? collect();
  $schoolSubjects = $schoolSubjects ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'المناهج والدروس الخاصة' : 'Curricula and private lessons' }}</p>
    <h1>{{ $isRtl ? 'دعم دراسي يبدأ بحصة فردية' : 'Curriculum support starts 1:1' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'اختر نوع المنهج والمادة، ثم ابحث عن معلم معتمد يناسب مستوى الطالب وموعده.' : 'Choose a curriculum and subject, then find a matching approved tutor.' }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</a>
      <a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'استكشف المناهج' : 'Browse curricula' }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-steps">
      <article class="mc-step"><div class="mc-step__n">1</div><h3>{{ $isRtl ? 'حدد المنهج والمادة' : 'Choose curriculum' }}</h3><p>{{ $isRtl ? 'ابدأ من احتياج الطالب الحقيقي.' : 'Start from the student need.' }}</p></article>
      <article class="mc-step"><div class="mc-step__n">2</div><h3>{{ $isRtl ? 'اختر المعلم' : 'Choose a tutor' }}</h3><p>{{ $isRtl ? 'راجع التخصص والمواعيد والتقييمات.' : 'Review specialty and availability.' }}</p></article>
      <article class="mc-step"><div class="mc-step__n">3</div><h3>{{ $isRtl ? 'احجز 1:1' : 'Book 1:1' }}</h3><p>{{ $isRtl ? 'تعلم أونلاين وتابع تقرير الحصة.' : 'Learn online and follow progress.' }}</p></article>
    </div>
  </div>
</section>

@if($schoolYears->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'تصفّح حسب المرحلة' : 'Browse by stage' }}</h2></div>
    <div class="mc-grid mc-grid--3">
      @foreach($schoolYears as $year)
        @continue(blank($year->slug ?? null))
        <a href="{{ route('public.school.year', $year->slug) }}" class="mc-card"><div class="mc-card__body"><h3 class="mc-card__title">{{ $year->name }}</h3>@if($year->tagline)<p>{{ $year->tagline }}</p>@endif<span class="mc-track__cta">{{ $isRtl ? 'عرض المواد والدروس ←' : 'View subjects →' }}</span></div></a>
      @endforeach
    </div>
  </div>
</section>
@endif
@endsection
