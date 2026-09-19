@extends('layouts.mycourses-public')

@section('content')
@php
  $brand = __('landing.nav.brand');
  $isRtl = app()->getLocale() === 'ar';
@endphp

<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('public.help_hero_badge') }}</p>
    <h1>
      {{ __('public.help_page_title') }}
      <span class="mc-contact-hero__accent">{{ $brand }}</span>
    </h1>
    <p class="mc-lead">{{ __('public.help_hero_sub') }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.faq') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ __('public.faq_page_title') }}</a>
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ __('public.contact_page_title') }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tracks">
      <a class="mc-track" href="{{ route('public.faq') }}">
        <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-circle-question"></i></span>
        <h3>{{ __('public.help_card_faq_title') }}</h3>
        <p>{{ __('public.help_card_faq_desc') }}</p>
        <span class="mc-track__cta">{{ $isRtl ? 'افتح الأسئلة ←' : 'Open FAQ →' }}</span>
      </a>
      <a class="mc-track mc-track--featured" href="{{ route('public.contact') }}">
        <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
        <h3>{{ __('public.help_card_contact_title') }}</h3>
        <p>{{ __('public.help_card_contact_desc') }}</p>
        <span class="mc-track__cta">{{ $isRtl ? 'تواصل الآن ←' : 'Contact now →' }}</span>
      </a>
      <a class="mc-track" href="{{ route('public.instructors.index') }}">
        <span class="mc-track__icon" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></span>
        <h3>{{ $isRtl ? 'دليل المعلمين' : 'Teacher directory' }}</h3>
        <p>{{ $isRtl ? 'ابحث عن معلم معتمد واحجز حصة فردية.' : 'Find an approved teacher and book a 1:1 session.' }}</p>
        <span class="mc-track__cta">{{ __('landing.mc.hero.cta_primary') }} ←</span>
      </a>
    </div>
  </div>
</section>
@endsection
