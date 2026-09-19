<section class="mc-section mc-section--muted mc-section--compact" id="features">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.features.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.features.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.features.lead') }}</p>
      </div>
    </div>
    <div class="mc-features">
      @foreach(__('landing.mc.features.items') as $item)
        <article class="mc-feature">
          <div class="mc-feature__icon" aria-hidden="true">{{ $item['icon'] }}</div>
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>
