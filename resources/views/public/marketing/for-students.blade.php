@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.for_students');
@endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $p['eyebrow'] }}</p>
    <h1>{{ $p['title'] }}</h1>
    <p class="mc-lead">{{ $p['lead'] }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $p['cta_primary'] }}</a>
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $p['cta_secondary'] }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 class="mc-title">{{ $p['benefits_title'] }}</h2>
      </div>
    </div>
    <div class="mc-features">
      @foreach($p['benefits'] as $item)
        <article class="mc-feature">
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

@include('partials.landing.mycourses.stages')
@include('partials.landing.mycourses.packages')

<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 class="mc-title">{{ $p['faq_title'] }}</h2>
      </div>
    </div>
    <div class="mc-faq-list">
      @foreach($p['faqs'] as $faq)
        <details class="mc-faq-item">
          <summary>{{ $faq['q'] }}</summary>
          <p>{{ $faq['a'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

@include('partials.landing.mycourses.cta')
@endsection
