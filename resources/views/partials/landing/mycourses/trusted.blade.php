<section class="mc-section mc-trusted" id="trusted">
  <div class="mc-container">
    <div class="mc-section-head mc-section-head--center">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.trusted.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.trusted.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.trusted.lead') }}</p>
      </div>
    </div>
    <ul class="mc-trusted__pills">
      @foreach(__('landing.mc.trusted.pills') as $pill)
        <li>{{ $pill }}</li>
      @endforeach
    </ul>
  </div>
</section>
