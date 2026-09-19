{{-- Unused when route public.path redirects to public.how; kept as safe fallback. --}}
@extends('layouts.mycourses-public')

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('landing.mc.path.eyebrow') }}</p>
    <h1>{{ __('landing.mc.path.title') }}</h1>
    <p class="mc-lead">{{ __('landing.mc.path.lead') }}</p>
    <div class="mc-hero__actions">
      <a href="{{ route('public.how') }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ __('landing.mc.path.more') }}</a>
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ __('landing.mc.hero.cta_primary') }}</a>
    </div>
  </div>
</section>
@endsection
