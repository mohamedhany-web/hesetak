@extends('layouts.mycourses-public')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'خدمات حصتك' : 'Hesetak services' }}</p>
    <h1>{{ $isRtl ? 'اختر طريقك للتعلم' : 'Choose your learning path' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'حصص فردية مع معلمين معتمدين، دعم للمناهج، وكورسات مستقلة.' : '1:1 tutoring, curriculum support, and independent courses.' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <a href="{{ route('public.instructors.index') }}" class="mc-track mc-track--featured"><span class="mc-track__icon"><i class="fas fa-user-graduate"></i></span><h3>{{ $isRtl ? 'دروس فردية 1:1' : '1:1 tutoring' }}</h3><p>{{ $isRtl ? 'اختر معلماً مناسباً للمادة والمرحلة واحجز موعدك أونلاين.' : 'Choose a tutor and book online.' }}</p><span class="mc-track__cta">{{ $isRtl ? 'ابحث عن معلم ←' : 'Find a tutor →' }}</span></a>
      <a href="{{ route('public.curricula') }}" class="mc-track"><span class="mc-track__icon"><i class="fas fa-book-open"></i></span><h3>{{ $isRtl ? 'المناهج' : 'Curricula' }}</h3><p>{{ $isRtl ? 'دعم دراسي حسب المادة والمرحلة ونوع المنهج.' : 'Support by subject, stage, and curriculum.' }}</p><span class="mc-track__cta">{{ $isRtl ? 'استكشف المناهج ←' : 'Browse curricula →' }}</span></a>
      <a href="{{ route('public.courses') }}" class="mc-track"><span class="mc-track__icon"><i class="fas fa-laptop-file"></i></span><h3>{{ $isRtl ? 'الكورسات' : 'Courses' }}</h3><p>{{ $isRtl ? 'تعلم مهارة أو استعد لاختبار من خلال كورس مستقل.' : 'Build a skill through an independent course.' }}</p><span class="mc-track__cta">{{ $isRtl ? 'استكشف الكورسات ←' : 'Browse courses →' }}</span></a>
    </div>
  </div>
</section>

@if(isset($services) && $services->isNotEmpty())
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head"><h2>{{ $isRtl ? 'خدمات إضافية منشورة' : 'Published services' }}</h2></div>
    <div class="mc-grid mc-grid--3">
      @foreach($services as $service)
        <a href="{{ route('public.services.show', $service) }}" class="mc-card">
          @if($service->publicImageUrl())<div class="mc-card__media"><img src="{{ $service->publicImageUrl() }}" alt="" loading="lazy"></div>@endif
          <div class="mc-card__body"><h3 class="mc-card__title">{{ $service->name }}</h3><p>{{ \Illuminate\Support\Str::limit(strip_tags($service->summary ?: $service->body), 140) }}</p><span class="mc-track__cta">{{ $isRtl ? 'اعرف المزيد ←' : 'Learn more →' }}</span></div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="mc-section">
  <div class="mc-container"><div class="mc-cta"><div><h2>{{ $isRtl ? 'لست متأكداً من المسار؟' : 'Not sure which path fits?' }}</h2><p>{{ $isRtl ? 'قارن الباقات أو تواصل معنا وسنساعدك في الخطوة التالية.' : 'Compare packages or contact us.' }}</p></div><div class="mc-cta__actions"><a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--secondary">{{ $isRtl ? 'عرض الأسعار' : 'View pricing' }}</a><a href="{{ route('public.contact') }}" class="mc-btn mc-btn--ghost-on-dark">{{ $isRtl ? 'تواصل معنا' : 'Contact us' }}</a></div></div></div>
</section>
@endsection
