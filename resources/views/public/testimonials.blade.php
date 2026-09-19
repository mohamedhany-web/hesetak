@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $brand = __('landing.nav.brand');
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'تجارب المتعلمين' : 'Learner stories' }}</p>
    <h1>{{ $isRtl ? 'ماذا يقول طلاب وأولياء أمور' : 'What students and parents say about' }} {{ $brand }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'آراء منشورة تساعدك على فهم تجربة الحجز والتعلم والمتابعة.' : 'Published stories about booking, learning, and progress.' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if($testimonials->isEmpty())
      <div class="mc-empty">
        <p>{{ $isRtl ? 'لا توجد آراء منشورة حالياً.' : 'No testimonials are published yet.' }}</p>
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</a>
      </div>
    @else
      <div class="mc-quotes">
        @foreach($testimonials as $testimonial)
          @php
            $body = trim(strip_tags((string) ($testimonial->body ?? '')));
            $initial = mb_substr($testimonial->author_name ?: ($isRtl ? 'ط' : 'S'), 0, 1, 'UTF-8');
          @endphp
          <blockquote class="mc-quote">
            @if($testimonial->isImageType() && $testimonial->publicImageUrl())
              <img src="{{ $testimonial->publicImageUrl() }}" alt="" style="width:100%;border-radius:12px" loading="lazy">
            @endif
            <div class="mc-quote__stars" aria-hidden="true">★★★★★</div>
            @if($body !== '')<p>«{{ \Illuminate\Support\Str::limit($body, 260) }}»</p>@endif
            @if($testimonial->author_name || $testimonial->role_label)
              <footer class="mc-quote__who">
                <span class="mc-quote__avatar">{{ $initial }}</span>
                <span>
                  @if($testimonial->author_name)<strong>{{ $testimonial->author_name }}</strong>@endif
                  @if($testimonial->role_label)<span>{{ $testimonial->role_label }}</span>@endif
                </span>
              </footer>
            @endif
          </blockquote>
        @endforeach
      </div>
    @endif
  </div>
</section>

<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-cta">
      <div><h2>{{ $isRtl ? 'ابدأ تجربتك مع حصتك' : 'Start with Hesetak' }}</h2><p>{{ $isRtl ? 'اختر معلماً معتمداً واحجز حصة فردية أونلاين.' : 'Choose an approved tutor and book a 1:1 online lesson.' }}</p></div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--secondary">{{ $isRtl ? 'ابحث عن معلم' : 'Find a teacher' }}</a>
        <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--ghost-on-dark">{{ $isRtl ? 'استكشف الكورسات' : 'Browse courses' }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
