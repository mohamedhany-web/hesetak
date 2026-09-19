@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.for_teachers');
@endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $p['eyebrow'] }}</p>
    <h1>{{ $p['title'] }}</h1>
    <p class="mc-lead">{{ $p['lead'] }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.tutor.apply') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $p['cta_primary'] }}</a>
      <a href="{{ route('public.how') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $p['cta_secondary'] }}</a>
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

<section class="mc-section mc-section--muted">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 class="mc-title">{{ $p['steps_title'] }}</h2>
      </div>
    </div>
    <div class="mc-steps">
      @foreach($p['steps'] as $i => $step)
        <div class="mc-step">
          <div class="mc-step__n">{{ $i + 1 }}</div>
          <h3>{{ $step['title'] }}</h3>
          <p>{{ $step['body'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="mc-section">
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

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ $p['title'] }}</h2>
        <p>{{ $p['lead'] }}</p>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:.75rem">
        <a href="{{ route('public.tutor.apply') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $p['cta_primary'] }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
