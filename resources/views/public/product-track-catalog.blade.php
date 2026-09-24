@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $courses = $courses ?? collect();
  $track = $track ?? 'recorded';
  $resultCount = method_exists($courses, 'total') ? (int) $courses->total() : $courses->count();
  $currency = __('public.currency_egp');
  $pageTitle = $pageTitle ?? ($heroTitle ?? 'حصتك');
  $pageDescription = $pageDescription ?? ($heroLead ?? '');
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'مسارات التعلّم' : 'Learning tracks' }}</p>
    <h1>{{ $heroTitle }}</h1>
    <p class="mc-lead">{{ $heroLead }}</p>
    <div style="display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1rem">
      <a href="{{ route('public.recorded-courses') }}" class="mc-btn mc-btn--md {{ $track === 'recorded' ? 'mc-btn--primary' : 'mc-btn--soft' }}">
        {{ $isRtl ? 'كورسات مسجّلة' : 'Recorded courses' }}
      </a>
      <a href="{{ route('public.books') }}" class="mc-btn mc-btn--md {{ $track === 'book' ? 'mc-btn--primary' : 'mc-btn--soft' }}">
        {{ $isRtl ? 'كتب للقراءة' : 'Books' }}
      </a>
      <a href="{{ $upsellUrl }}" class="mc-btn mc-btn--md mc-btn--outline">
        {{ $isRtl ? 'الباقات والأسعار' : 'Packages & pricing' }}
      </a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <p style="margin:0 0 1.25rem;color:var(--mc-muted)">
      <strong>{{ number_format($resultCount) }}</strong>
      {{ $isRtl ? 'عنصر في هذا المسار' : 'items in this track' }}
    </p>

    @if($courses->isNotEmpty())
      <div class="mc-catalog-grid">
        @foreach($courses as $course)
          @php
            $price = (float) ($course->price_egp_after_discount ?? $course->price_egp ?? $course->price ?? 0);
            $showUrl = route('public.course.show', $course->id);
          @endphp
          <article class="mc-card">
            <div class="mc-card__body">
              <p class="mc-eyebrow" style="margin-bottom:.35rem">{{ $course->productTrackLabel() }}</p>
              <h3 class="mc-card__title"><a href="{{ $showUrl }}">{{ $course->title }}</a></h3>
              @if($course->instructor)
                <p style="margin:.35rem 0;color:var(--mc-muted);font-size:.9rem">{{ $course->instructor->name }}</p>
              @endif
              <p style="margin:.5rem 0 0;font-weight:700">
                @if($price > 0)
                  {{ number_format($price) }} {{ $currency }}
                @else
                  {{ $isRtl ? 'ضمن الباقة / تواصل معنا' : 'Included / contact us' }}
                @endif
              </p>
              <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem">
                <a href="{{ $showUrl }}" class="mc-btn mc-btn--sm mc-btn--primary">{{ $isRtl ? 'التفاصيل' : 'Details' }}</a>
                <a href="{{ $upsellUrl }}" class="mc-btn mc-btn--sm mc-btn--outline">{{ $isRtl ? 'فعّل باقة' : 'Unlock package' }}</a>
              </div>
            </div>
          </article>
        @endforeach
      </div>

      @if(method_exists($courses, 'links'))
        <div style="margin-top:1.5rem">{{ $courses->links() }}</div>
      @endif
    @else
      <div class="mc-empty" style="padding:2rem;text-align:center">
        <p>{{ $isRtl ? 'لا توجد عناصر منشورة في هذا المسار بعد.' : 'No published items in this track yet.' }}</p>
        <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.65rem;justify-content:center">
          <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'كل الكورسات' : 'All courses' }}</a>
          <a href="{{ $libraryUrl }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $isRtl ? 'مكتبة المناهج' : 'Curriculum library' }}</a>
          <a href="{{ $upsellUrl }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'الباقات' : 'Packages' }}</a>
        </div>
      </div>
    @endif
  </div>
</section>
@endsection
