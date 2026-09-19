<section class="mc-section mc-section--tight" id="categories">
  <div class="mc-container">
    <div class="mc-section-head mc-section-head--tight">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.cats.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.cats.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.cats.lead') }}</p>
      </div>
    </div>
    @php $cats = $homeCategories ?? collect(); @endphp
    @if($cats->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد مواد معروضة حالياً.' : 'No subjects to show yet.' }}</div>
    @else
      <div class="mc-cats">
        @foreach($cats as $item)
          <a class="mc-cat" href="{{ $item['url'] }}">
            <span class="mc-cat__dot" aria-hidden="true"></span>
            {{ $item['name'] }}
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
