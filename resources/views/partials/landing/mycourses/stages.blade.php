<section class="mc-section mc-section--muted" id="stages">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.stages.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.stages.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.stages.lead') }}</p>
      </div>
    </div>
    <div class="mc-stages">
      @foreach(__('landing.mc.stages.groups') as $i => $group)
        <details class="mc-stage" @if($i === 0) open @endif>
          <summary>
            <span class="mc-stage__n">{{ $i + 1 }}</span>
            <span class="mc-stage__title">{{ $group['title'] }}</span>
          </summary>
          <div class="mc-stage__body">
            <p>{{ $group['subjects'] }}</p>
            <a class="mc-btn mc-btn--md mc-btn--secondary" href="{{ route('public.instructors.index', ['q' => $group['title']]) }}">{{ __('landing.mc.hero.cta_primary') }}</a>
          </div>
        </details>
      @endforeach
    </div>
  </div>
</section>
