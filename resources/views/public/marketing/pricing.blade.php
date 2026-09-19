@extends('layouts.mycourses-public')

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $brand = __('landing.nav.brand');
    $packages = $packages ?? collect();
@endphp

<section class="mc-dir-head mc-curr-head" aria-labelledby="mc-pricing-title">
  <div class="mc-container">
    <div class="mc-dir-head__top">
      <div class="mc-dir-head__copy">
        <p class="mc-eyebrow">{{ __('public.pricing_hero_kicker') }}</p>
        <h1 id="mc-pricing-title">
          {{ __('public.pricing_hero_title') }}
          <strong class="mc-brand-word">{{ $brand }}</strong>
        </h1>
        <p class="mc-curr-head__lead">{{ __('public.pricing_hero_sub') }}</p>
      </div>
      @if($packages->isNotEmpty())
        <p class="mc-dir-head__count">
          <strong>{{ number_format($packages->count()) }}</strong>
          <span>{{ $isRtl ? 'باقة متاحة' : 'packages' }}</span>
        </p>
      @endif
    </div>
    <div class="mc-curr-actions">
      <a href="#mc-pricing-packages" class="mc-btn mc-btn--md mc-btn--primary">{{ __('public.pricing_packages_title') }}</a>
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ __('landing.mc.hero.cta_primary') }}</a>
    </div>
    <p class="mc-curr-note" style="margin-top:1rem">{{ __('public.pricing_hero_note') }}</p>
  </div>
</section>

<section class="mc-section mc-section--compact" id="mc-pricing-packages">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('public.pricing_packages_badge') }}</p>
        <h2 class="mc-title">{{ __('public.pricing_packages_title') }}</h2>
        <p class="mc-lead">{{ __('public.pricing_packages_sub') }}</p>
      </div>
    </div>

    @if($packages->isEmpty())
      <div class="mc-empty">{{ __('public.pricing_no_packages') }}</div>
    @else
      <div class="mc-packages">
        @foreach($packages as $pkg)
          @php
            $recommended = (bool) ($pkg->is_featured || $pkg->is_popular);
            $hoursLabel = $pkg->units_count
              ? ($isRtl ? $pkg->units_count.' حصة' : $pkg->units_count.' sessions')
              : ($pkg->tagline ?: '');
            $perks = is_array($pkg->features) ? $pkg->features : [];
          @endphp
          <article class="mc-package {{ $recommended ? 'mc-package--recommended' : '' }}">
            @if(!empty($pkg->badge))
              <span class="mc-package__badge">{{ $pkg->badge }}</span>
            @elseif($recommended)
              <span class="mc-package__badge">{{ __('public.pricing_package_popular') }}</span>
            @endif
            <h3>{{ $pkg->name }}</h3>
            @if($hoursLabel !== '')
              <p class="mc-package__hours">{{ $hoursLabel }}</p>
            @endif
            <p class="mc-package__price">{{ $pkg->formattedPrice() }}</p>
            @if(!empty($pkg->tagline))
              <p class="mc-package__why">{{ $pkg->tagline }}</p>
            @elseif(!empty($pkg->description))
              <p class="mc-package__why">{{ \Illuminate\Support\Str::limit(strip_tags($pkg->description), 110) }}</p>
            @endif
            @if(count($perks) > 0)
              <ul>
                @foreach(array_slice($perks, 0, 5) as $perk)
                  <li>{{ is_string($perk) ? $perk : (string) $perk }}</li>
                @endforeach
              </ul>
            @endif
            <a
              href="{{ route('public.service-packages.checkout', $pkg) }}"
              class="mc-btn mc-btn--md {{ $recommended ? 'mc-btn--secondary' : 'mc-btn--soft' }}"
            >{{ __('public.pricing_package_buy') }}</a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>

<section class="mc-section mc-section--compact">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ __('public.pricing_footer_cta_title') }}</h2>
        <p>{{ __('public.pricing_footer_cta_sub') }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.hero.cta_primary') }}</a>
        <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('public.pricing_footer_contact') }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
