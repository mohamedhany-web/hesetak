@php
    $trackRoutes = [
        'instructors' => route('public.instructors.index'),
        'curricula' => route('public.curricula'),
        'courses' => route('public.courses'),
    ];
    $trackIcons = ['①', '②', '③'];
@endphp
<section class="mc-section mc-section--compact" id="tracks">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.tracks.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.tracks.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.tracks.lead') }}</p>
      </div>
    </div>
    <div class="mc-tracks">
      @foreach(__('landing.mc.tracks.items') as $i => $item)
        @php
          $href = $trackRoutes[$item['href']] ?? route('home');
          $featured = !empty($item['featured']);
        @endphp
        <a class="mc-track {{ $featured ? 'mc-track--featured' : '' }}" href="{{ $href }}">
          @if($featured && !empty($item['badge']))
            <span class="mc-track__badge">{{ $item['badge'] }}</span>
          @endif
          <span class="mc-track__icon" aria-hidden="true">{{ $trackIcons[$i] ?? '◆' }}</span>
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
          <span class="mc-track__cta">{{ $item['cta'] }} →</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
