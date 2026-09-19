@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $img = $img ?? fn (string $file) => public_img_url('lasles/'.$file);
    $steps = [
        ['num' => '01', 'key' => 'diagnose'],
        ['num' => '02', 'key' => 'access'],
        ['num' => '03', 'key' => 'develop'],
        ['num' => '04', 'key' => 'measure'],
    ];
@endphp
<section class="lasles-path-home" id="path">
  <div class="lasles-container">
    <div class="lasles-path-home__head">
      <p class="lasles-path-kicker">{{ __('landing.path.kicker') }}</p>
      <h2 class="lasles-section-title lasles-section-title--center">{{ __('landing.path.home_title') }}</h2>
      <p class="lasles-section-lead lasles-section-lead--center">{{ __('landing.path.home_lead') }}</p>
    </div>
    <ol class="lasles-path-grid lasles-path-grid--home">
      @foreach ($steps as $step)
        <li class="lasles-path-card lasles-path-card--compact">
          <span class="lasles-path-card__num" aria-hidden="true">{{ $step['num'] }}</span>
          <h3 class="lasles-path-card__title">{{ __('landing.path.steps.'.$step['key'].'.title') }}</h3>
          <p class="lasles-path-card__body">{{ __('landing.path.steps.'.$step['key'].'.short') }}</p>
        </li>
      @endforeach
    </ol>
    <div class="lasles-path-home__actions">
      <a href="{{ route('public.path') }}" class="lasles-btn-outline">{{ __('landing.path.home_more') }}</a>
      <a href="{{ route('register') }}" class="lasles-btn-primary">{{ __('landing.path.cta_primary') }}</a>
    </div>
  </div>
</section>
