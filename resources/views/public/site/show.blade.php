@extends('layouts.mycourses-public')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $children = $node['children'] ?? [];
  $parent = $node['parent'] ?? null;
  $sections = $content['sections'] ?? [];
  $ctaPrimary = route('public.instructors.index');
  $ctaPrimaryLabel = __('landing.mc.hero.cta_primary');
  if (str_starts_with($pageKey, 'account')) {
      $ctaPrimary = auth()->check() ? url('/dashboard') : route('login');
      $ctaPrimaryLabel = auth()->check() ? __('auth.dashboard') : __('site.cta.login');
  }
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    @if($parent)
      <p class="mc-eyebrow">
        <a href="{{ route($parent['route']) }}">{{ $parent['label'] }}</a>
        · {{ $node['label'] }}
      </p>
    @elseif(!empty($content['kicker']))
      <p class="mc-eyebrow">{{ $content['kicker'] }}</p>
    @endif
    <h1>{{ $content['title'] ?? $node['label'] }}</h1>
    @if(!empty($content['lead']))
      <p class="mc-lead">{{ $content['lead'] }}</p>
    @endif
    <div class="mc-hero__actions">
      <a href="{{ $ctaPrimary }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $ctaPrimaryLabel }}</a>
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ __('site.cta.contact') }}</a>
    </div>
    @if(!empty($content['auth_note']))
      <p class="mc-lead" style="margin-top:1rem">{{ $content['auth_note'] }}</p>
    @endif
  </div>
</section>

@if(count($children))
<section class="mc-section">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 class="mc-title">{{ $isRtl ? 'استكشف الأقسام' : 'Explore sections' }}</h2>
      </div>
    </div>
    <div class="mc-tracks">
      @foreach($children as $child)
        @php $childContent = __('site.pages.'.$child['key']); @endphp
        <a class="mc-track" href="{{ route($child['route']) }}">
          <h3>{{ $child['label'] }}</h3>
          <p>{{ is_array($childContent) ? ($childContent['lead'] ?? '') : '' }}</p>
          <span class="mc-track__cta">{{ __('site.cta.explore') }} →</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

@if(count($sections))
<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-features">
      @foreach($sections as $section)
        <article class="mc-feature">
          <h3>{{ $section['title'] ?? '' }}</h3>
          <p>{{ $section['body'] ?? '' }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ $isRtl ? 'الخطوة التالية' : 'Next step' }}</h2>
        <p>{{ $isRtl ? 'ابحث عن معلم، أو تصفّح المناهج والكورسات، أو تواصل معنا.' : 'Find a teacher, browse curricula and courses, or contact us.' }}</p>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:.75rem">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.hero.cta_primary') }}</a>
        <a href="{{ route('register') }}" class="mc-btn mc-btn--lg mc-btn--outline" style="border-color:#fff;color:#fff!important">{{ __('landing.nav.register') }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
