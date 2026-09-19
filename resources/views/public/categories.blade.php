@extends('layouts.mycourses-public')

@php
    $mcActive = 'courses';
    $pageTitle = (__('landing.nav.categories') ?? 'Categories').' — حصتك';
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('landing.nav.categories') }}</p>
    <h1>{{ __('landing.nav.categories') }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'مسارات واضحة تقودك للمحتوى المناسب — معلمون، مناهج، وكورسات.' : 'Clear paths to the right content — teachers, curricula, and courses.' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      @forelse($categories ?? [] as $category)
        <a class="mc-track" href="{{ $category['url'] }}">
          @if(!empty($category['thumb_url']))
            <img src="{{ $category['thumb_url'] }}" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:8px" loading="lazy">
          @endif
          <h3>{{ $category['name'] }}</h3>
          @if(!empty($category['desc']))
            <p>{{ $category['desc'] }}</p>
          @endif
          <span class="mc-track__cta">{{ $isRtl ? 'استكشف' : 'Explore' }} →</span>
        </a>
      @empty
        <p class="mc-lead">{{ $isRtl ? 'لا توجد تصنيفات معروضة حالياً.' : 'No categories to show yet.' }}</p>
      @endforelse
    </div>
  </div>
</section>
@endsection
