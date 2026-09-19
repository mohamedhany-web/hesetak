<section class="mc-section mc-section--compact" id="path">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.path.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.path.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.path.lead') }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.how') }}">{{ __('landing.mc.path.more') }} →</a>
    </div>
    <div class="mc-steps">
      @foreach(__('landing.mc.path.steps') as $i => $step)
        <div class="mc-step">
          <div class="mc-step__n">{{ $i + 1 }}</div>
          <h3>{{ $step['title'] }}</h3>
          <p>{{ $step['body'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>
