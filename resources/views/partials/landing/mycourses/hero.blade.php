@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $slides = __('landing.mc.hero.slides');
    if (! is_array($slides) || count($slides) === 0) {
        $slides = [[
            'tab' => __('landing.mc.hero.cta_primary'),
            'eyebrow' => __('landing.mc.hero.eyebrow'),
            'title_html' => __('landing.mc.hero.title_html'),
            'lead' => __('landing.mc.hero.lead'),
            'cta_primary' => __('landing.mc.hero.cta_primary'),
            'cta_secondary' => __('landing.mc.hero.cta_secondary'),
            'cta_primary_route' => 'instructors',
            'cta_secondary_route' => 'curricula',
            'image' => 'hero-tutor-student.png',
            'image_alt' => __('landing.mc.hero.card_sub'),
        ]];
    }
    $routeMap = [
        'instructors' => route('public.instructors.index'),
        'curricula' => route('public.curricula'),
        'courses' => route('public.courses'),
        'packages' => route('public.pricing'),
        'path' => url('/#path'),
    ];
@endphp
<section class="mc-hero mc-hero-slider" data-mc-hero-slider aria-roledescription="carousel" aria-label="{{ __('landing.mc.hero.slider_aria') }}">
  <div class="mc-container">
    <div class="mc-hero-slider__tabs" role="tablist" aria-label="{{ __('landing.mc.hero.slider_aria') }}">
      @foreach($slides as $i => $slide)
        <button
          type="button"
          class="mc-hero-slider__tab {{ $i === 0 ? 'is-active' : '' }}"
          role="tab"
          id="mc-hero-tab-{{ $i }}"
          aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
          aria-controls="mc-hero-slide-{{ $i }}"
          data-mc-hero-tab="{{ $i }}"
        >{{ $slide['tab'] }}</button>
      @endforeach
    </div>

    <div class="mc-hero-slider__stage">
      @foreach($slides as $i => $slide)
        @php
          $imgFile = $slide['image'] ?? 'hero-tutor-student.png';
          $imgUrl = public_img_url('mycourses/'.$imgFile);
          $primaryHref = $routeMap[$slide['cta_primary_route'] ?? 'instructors'] ?? route('public.instructors.index');
          $secondaryHref = $routeMap[$slide['cta_secondary_route'] ?? 'curricula'] ?? route('public.curricula');
        @endphp
        <article
          class="mc-hero-slider__slide {{ $i === 0 ? 'is-active' : '' }}"
          id="mc-hero-slide-{{ $i }}"
          role="tabpanel"
          aria-labelledby="mc-hero-tab-{{ $i }}"
          data-mc-hero-slide="{{ $i }}"
          @if($i !== 0) hidden @endif
        >
          <div class="mc-hero__grid">
            <div class="mc-hero__copy">
              <p class="mc-eyebrow">{{ $slide['eyebrow'] }}</p>
              @if($i === 0)
                <h1 class="mc-hero__title">{!! $slide['title_html'] !!}</h1>
              @else
                <p class="mc-hero__title" role="heading" aria-level="2">{!! $slide['title_html'] !!}</p>
              @endif
              <p class="mc-hero__lead">{{ $slide['lead'] }}</p>
              <div class="mc-hero__actions">
                <a href="{{ $primaryHref }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $slide['cta_primary'] }}</a>
                <a href="{{ $secondaryHref }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $slide['cta_secondary'] }}</a>
              </div>
            </div>
            <figure class="mc-hero__media">
              <img
                src="{{ $imgUrl }}"
                width="1152"
                height="864"
                alt="{{ $slide['image_alt'] ?? '' }}"
                @if($i === 0) loading="eager" @else loading="lazy" @endif
                decoding="async"
              >
            </figure>
          </div>
        </article>
      @endforeach
    </div>

    <div class="mc-hero-slider__footer">
      <div class="mc-hero-slider__controls">
        <button type="button" class="mc-hero-slider__nav" data-mc-hero-prev aria-label="{{ __('landing.mc.hero.prev') }}">
          <span aria-hidden="true">{{ $isRtl ? '›' : '‹' }}</span>
        </button>
        <div class="mc-hero-slider__progress" aria-hidden="true">
          <span class="mc-hero-slider__bar" data-mc-hero-bar></span>
        </div>
        <button type="button" class="mc-hero-slider__nav" data-mc-hero-next aria-label="{{ __('landing.mc.hero.next') }}">
          <span aria-hidden="true">{{ $isRtl ? '‹' : '›' }}</span>
        </button>
      </div>

      <div class="mc-search-block mc-hero-slider__search">
        <label class="mc-search-block__label" for="mc-hero-search">{{ __('landing.mc.hero.search_label') }}</label>
        <form class="mc-search" action="{{ route('public.instructors.index') }}" method="get" role="search">
          <input id="mc-hero-search" type="search" name="q" placeholder="{{ __('landing.mc.hero.search_placeholder') }}" aria-label="{{ __('landing.mc.hero.search_placeholder') }}">
          <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">{{ __('landing.mc.hero.search_btn') }}</button>
        </form>
      </div>
    </div>
  </div>
</section>
<script>
(function () {
  var root = document.querySelector('[data-mc-hero-slider]');
  if (!root) return;
  var slides = Array.prototype.slice.call(root.querySelectorAll('[data-mc-hero-slide]'));
  var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-mc-hero-tab]'));
  var bar = root.querySelector('[data-mc-hero-bar]');
  var prevBtn = root.querySelector('[data-mc-hero-prev]');
  var nextBtn = root.querySelector('[data-mc-hero-next]');
  if (slides.length < 2) return;

  var index = 0;
  var timer = null;
  var DURATION = 6500;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function go(to) {
    index = (to + slides.length) % slides.length;
    slides.forEach(function (slide, i) {
      var on = i === index;
      slide.classList.toggle('is-active', on);
      slide.hidden = !on;
    });
    tabs.forEach(function (tab, i) {
      var on = i === index;
      tab.classList.toggle('is-active', on);
      tab.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    restart();
  }

  function restart() {
    if (bar) {
      bar.style.transition = 'none';
      bar.style.width = '0%';
      void bar.offsetWidth;
      if (!reduceMotion) {
        bar.style.transition = 'width ' + DURATION + 'ms linear';
        bar.style.width = '100%';
      } else {
        bar.style.width = '100%';
      }
    }
    if (timer) clearInterval(timer);
    if (!reduceMotion) {
      timer = setInterval(function () { go(index + 1); }, DURATION);
    }
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      go(parseInt(tab.getAttribute('data-mc-hero-tab'), 10) || 0);
    });
  });
  if (prevBtn) prevBtn.addEventListener('click', function () { go(index - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { go(index + 1); });

  root.addEventListener('mouseenter', function () { if (timer) clearInterval(timer); });
  root.addEventListener('mouseleave', restart);
  root.addEventListener('focusin', function () { if (timer) clearInterval(timer); });
  root.addEventListener('focusout', function (e) {
    if (!root.contains(e.relatedTarget)) restart();
  });

  restart();
})();
</script>
