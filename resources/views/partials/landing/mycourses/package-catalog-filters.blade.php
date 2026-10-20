{{-- سايدبار تصفية كتالوج الباقات: مرحلة + منهج + مادة --}}
@php
  $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
  $catalog = $packageCatalog ?? [];
  $years = $catalog['years'] ?? collect();
  $subjects = $catalog['subjects'] ?? collect();
  $tracks = $catalog['tracks'] ?? [];
  $selYear = $catalog['selected_year_id'] ?? null;
  $selSubject = $catalog['selected_subject_id'] ?? null;
  $selTrack = $catalog['selected_curriculum_type'] ?? null;
  $anchor = $anchor ?? 'mc-pricing-packages';
  $filterRoute = $filterRoute ?? 'public.pricing';

  $filterUrl = static function (array $overrides = []) use ($selYear, $selSubject, $selTrack, $anchor, $filterRoute): string {
      $year = array_key_exists('year', $overrides) ? $overrides['year'] : $selYear;
      $track = array_key_exists('curriculum_type', $overrides) ? $overrides['curriculum_type'] : $selTrack;
      $subject = array_key_exists('subject', $overrides) ? $overrides['subject'] : $selSubject;

      if (array_key_exists('year', $overrides) && (int) ($overrides['year'] ?? 0) !== (int) $selYear) {
          $subject = null;
      }

      $query = array_filter([
          'year' => $year ?: null,
          'curriculum_type' => $track ?: null,
          'subject' => $subject ?: null,
      ]);

      return route($filterRoute, $query).'#'.$anchor;
  };

  $hasFilters = $years->isNotEmpty() || count($tracks) > 0 || $subjects->isNotEmpty();
@endphp

@if($hasFilters)
<aside class="mc-pkg-filter" aria-label="{{ __('public.pricing_filter_title') }}">
  <details class="mc-pkg-filter__box" id="{{ $filterId ?? 'mcPkgFilter' }}" open>
    <summary class="mc-pkg-filter__summary">
      <span>{{ __('public.pricing_filter_title') }}</span>
      <i class="fas fa-sliders-h" aria-hidden="true"></i>
    </summary>

    <div class="mc-pkg-filter__body">
      @if($years->isNotEmpty())
        <div class="mc-pkg-filter__group">
          <h3>{{ __('public.pricing_filter_stage') }}</h3>
          <a class="mc-pkg-filter__opt {{ empty($selYear) ? 'is-on' : '' }}" href="{{ $filterUrl(['year' => null, 'subject' => null]) }}">
            <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
            <span>{{ __('public.pricing_filter_all_stages') }}</span>
          </a>
          @foreach($years as $year)
            <a class="mc-pkg-filter__opt {{ (int) $selYear === (int) $year->id ? 'is-on' : '' }}" href="{{ $filterUrl(['year' => $year->id]) }}">
              <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
              <span>{{ $year->name }}</span>
            </a>
          @endforeach
        </div>
      @endif

      @if(count($tracks) > 0)
        <div class="mc-pkg-filter__group">
          <h3>{{ __('public.pricing_filter_track') }}</h3>
          <a class="mc-pkg-filter__opt {{ empty($selTrack) ? 'is-on' : '' }}" href="{{ $filterUrl(['curriculum_type' => null]) }}">
            <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
            <span>{{ __('public.pricing_filter_all_tracks') }}</span>
          </a>
          @foreach($tracks as $key => $meta)
            <a class="mc-pkg-filter__opt {{ $selTrack === $key ? 'is-on' : '' }}" href="{{ $filterUrl(['curriculum_type' => $key]) }}">
              <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
              <span>{{ $meta['label'] }}</span>
            </a>
          @endforeach
        </div>
      @endif

      @if($subjects->isNotEmpty())
        <div class="mc-pkg-filter__group">
          <h3>{{ __('public.pricing_filter_subject') }}</h3>
          <a class="mc-pkg-filter__opt {{ empty($selSubject) ? 'is-on' : '' }}" href="{{ $filterUrl(['subject' => null]) }}">
            <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
            <span>{{ __('public.pricing_filter_all_subjects') }}</span>
          </a>
          @foreach($subjects as $subject)
            <a class="mc-pkg-filter__opt {{ (int) $selSubject === (int) $subject->id ? 'is-on' : '' }}" href="{{ $filterUrl(['subject' => $subject->id]) }}">
              <span class="mc-pkg-filter__radio" aria-hidden="true"></span>
              <span>{{ $subject->name }}</span>
            </a>
          @endforeach
        </div>
      @endif

      <p class="mc-pkg-filter__hint">{{ __('public.pricing_filter_hint') }}</p>
    </div>
  </details>
</aside>
@endif
