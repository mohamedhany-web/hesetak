@extends('layouts.mycourses-public')

@section('content')
@php
  $brand = __('landing.nav.brand');
  $isRtl = app()->getLocale() === 'ar';
  $icons = ['check-circle', 'chalkboard-teacher', 'user-lock', 'wallet', 'copyright', 'ban', 'balance-scale', 'file-signature'];
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('public.terms_page_title') }}</p>
    <h1>
      {{ $isRtl ? 'شروط وأحكام' : 'Terms of' }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    <p class="mc-lead">{{ __('public.legal_terms_hero_sub') }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ __('public.contact_page_title') }}</a>
      <a href="{{ route('public.privacy') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ __('public.privacy_page_title') }}</a>
    </div>
  </div>
</section>

<section class="mc-section mc-section--tight">
  <div class="mc-container">
    <div class="mc-legal-intro">
      <span class="mc-legal-intro__icon" aria-hidden="true"><i class="fas fa-file-contract"></i></span>
      <p>{!! nl2br(e(__('public.legal_terms_intro', ['brand' => $brand]))) !!}</p>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-legal-grid">
      @foreach(range(1, 8) as $i)
        <article class="mc-legal-card {{ $i === 8 ? 'mc-legal-card--wide' : '' }}">
          <div class="mc-legal-card__head">
            <span class="mc-legal-card__icon" aria-hidden="true"><i class="fas fa-{{ $icons[$i - 1] }}"></i></span>
            <h2>{{ __('public.legal_terms_s'.$i.'_title') }}</h2>
          </div>
          <p>{!! nl2br(e(__('public.legal_terms_s'.$i.'_body', ['brand' => $brand]))) !!}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ __('public.legal_cta_title') }}</h2>
        <p>{{ __('public.legal_cta_desc') }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('public.contact_page_title') }}</a>
        <a href="{{ route('home') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
