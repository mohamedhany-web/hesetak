<section class="mc-section mc-section--compact">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ __('landing.mc.cta.title') }}</h2>
        <p>{{ __('landing.mc.cta.lead') }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.cta.primary') }}</a>
        <a href="{{ route('public.tutor.apply') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('landing.mc.cta.secondary') }}</a>
      </div>
    </div>
  </div>
</section>
