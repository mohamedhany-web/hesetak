@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = __('landing.nav.brand');
    $p = __('hesetak_pages.courses');
    $courses = $courses ?? collect();
    $categories = $categories ?? collect();
    $filters = $filters ?? ['q' => '', 'category' => null, 'sort' => 'featured'];
    $totalCourses = (int) ($totalCourses ?? $courses->count());
    $q = (string) ($filters['q'] ?? '');
    $categoryId = (int) ($filters['category'] ?? 0);
    $sort = (string) ($filters['sort'] ?? 'featured');
    $resultCount = method_exists($courses, 'total') ? (int) $courses->total() : $courses->count();
    $hasActiveFilters = $q !== '' || $categoryId > 0 || $sort !== 'featured';
    $currency = __('public.currency_egp');
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $mcActive = 'courses';
    $filterQuery = fn (array $extra = []) => array_filter(array_merge([
        'q' => $q !== '' ? $q : null,
        'category' => $categoryId > 0 ? $categoryId : null,
        'sort' => $sort !== 'featured' ? $sort : null,
    ], $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ request()->boolean('figma') ? 'ltr' : ($isRtl ? 'rtl' : 'ltr') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ $p['meta_title'] }}</title>
  <meta name="description" content="{{ $p['meta_description'] }}">
  <meta name="theme-color" content="#1E4E8C">
  <link rel="canonical" href="{{ route('public.courses') }}">
  <link rel="alternate" hreflang="ar" href="{{ url('/courses') }}?lang=ar">
  <link rel="alternate" hreflang="en" href="{{ url('/courses') }}?lang=en">
  @include('partials.favicon-links')
  @include('partials.seo-jsonld', ['jsonldType' => 'website'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--dir">
@include('partials.landing.mycourses.nav')

<main class="mc-dir mc-catalog">
  <section class="mc-dir-head mc-catalog-head" aria-labelledby="mc-cat-title">
    <div class="mc-container">
      <div class="mc-dir-head__top">
        <div class="mc-dir-head__copy">
          <p class="mc-eyebrow">{{ $p['eyebrow'] }}</p>
          <h1 id="mc-cat-title">{!! __('hesetak_pages.courses.title_html', ['brand' => e($brand)]) !!}</h1>
          <p class="mc-catalog-head__lead">{{ $p['lead'] }}</p>
        </div>
        <p class="mc-dir-head__count">
          <strong>{{ number_format($resultCount) }}</strong>
          <span>{{ $p['count_label'] }}</span>
          @if($hasActiveFilters && $resultCount !== $totalCourses)
            <em>{{ __('hesetak_pages.courses.results_of', ['total' => number_format($totalCourses)]) }}</em>
          @endif
        </p>
      </div>

      <form class="mc-dir-bar" action="{{ route('public.courses') }}" method="get" role="search">
        <label class="mc-dir-bar__field mc-dir-bar__field--grow">
          <span class="mc-dir-bar__label">{{ $p['search_label'] }}</span>
          <input
            class="mc-input"
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="{{ $p['search_placeholder'] }}"
            autocomplete="off"
          >
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ $p['category_label'] }}</span>
          <select class="mc-select" name="category">
            <option value="">{{ $p['category_all'] }}</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" @selected($categoryId === (int) $category->id)>
                {{ $category->name }} ({{ (int) $category->courses_count }})
              </option>
            @endforeach
          </select>
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ $p['sort_label'] }}</span>
          <select class="mc-select" name="sort">
            <option value="featured" @selected($sort === 'featured')>{{ $p['sort_featured'] }}</option>
            <option value="newest" @selected($sort === 'newest')>{{ $p['sort_newest'] }}</option>
            <option value="price_asc" @selected($sort === 'price_asc')>{{ $p['sort_price_asc'] }}</option>
            <option value="price_desc" @selected($sort === 'price_desc')>{{ $p['sort_price_desc'] }}</option>
          </select>
        </label>

        <div class="mc-dir-bar__actions">
          <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">{{ $p['apply'] }}</button>
          @if($hasActiveFilters)
            <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $p['clear'] }}</a>
          @endif
        </div>
      </form>

      @if($categories->isNotEmpty())
        <div class="mc-dir-chips" role="list" aria-label="{{ $p['category_label'] }}">
          <a
            role="listitem"
            class="mc-dir-chip {{ $categoryId === 0 ? 'is-on' : '' }}"
            href="{{ route('public.courses', $filterQuery(['category' => null])) }}"
          >{{ $p['category_all'] }}</a>
          @foreach($categories as $category)
            <a
              role="listitem"
              class="mc-dir-chip {{ $categoryId === (int) $category->id ? 'is-on' : '' }}"
              href="{{ route('public.courses', $filterQuery(['category' => $category->id])) }}"
            >{{ $category->name }} <em>{{ (int) $category->courses_count }}</em></a>
          @endforeach
        </div>
      @endif

      @if($activeCategory)
        <p class="mc-dir-meta__filter mc-dir-head__active">
          <span class="mc-dir-meta__tag">{{ $activeCategory->name }}</span>
          <a href="{{ route('public.courses', $filterQuery(['category' => null])) }}">{{ $p['clear'] }}</a>
        </p>
      @endif
    </div>
  </section>

  <section class="mc-section mc-section--tight mc-dir-results">
    <div class="mc-container">
      @if($courses->isNotEmpty())
        <div class="mc-catalog-grid">
          @foreach($courses as $course)
            @php
              $url = route('public.course.show', $course->id);
              $title = $course->title ?? ($isRtl ? 'كورس' : 'Course');
              $cover = $course->thumbnail_url;
              $instructorName = $course->instructor->name ?? '';
              $categoryName = $course->courseCategory->name ?? '';
              $lessonsCount = (int) ($course->lessons_count ?? 0);
              $hours = (int) ($course->duration_hours ?? 0);
              $listPrice = (float) ($course->price ?? 0);
              $payPrice = method_exists($course, 'effectiveCheckoutPrice')
                  ? (float) $course->effectiveCheckoutPrice()
                  : (float) ($course->price_after_discount ?? $listPrice);
              $isFree = (bool) ($course->is_free ?? false) || ($payPrice <= 0 && $listPrice <= 0);
              $hasPromo = ! $isFree && $listPrice > 0 && $payPrice > 0 && $payPrice < $listPrice;
            @endphp
            <article class="mc-catalog-card">
              <a href="{{ $url }}" class="mc-catalog-card__media" tabindex="-1" aria-hidden="true">
                @if($cover)
                  <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" width="480" height="300">
                @else
                  <span class="mc-catalog-card__media-fallback" aria-hidden="true"></span>
                @endif
                @if(!empty($course->is_featured))
                  <span class="mc-catalog-card__badge">{{ $p['featured'] }}</span>
                @elseif($isFree)
                  <span class="mc-catalog-card__badge mc-catalog-card__badge--soft">{{ $p['free'] }}</span>
                @endif
              </a>
              <div class="mc-catalog-card__body">
                @if($categoryName !== '')
                  <p class="mc-catalog-card__cat">{{ $categoryName }}</p>
                @endif
                <h3><a href="{{ $url }}">{{ $title }}</a></h3>
                @if($instructorName !== '')
                  <p class="mc-catalog-card__teacher">{{ $instructorName }}</p>
                @endif
                <div class="mc-catalog-card__meta">
                  @if($lessonsCount > 0)
                    <span>
                      {{ $lessonsCount }}
                      {{ $lessonsCount === 1 ? $p['lessons_one'] : $p['lessons_many'] }}
                    </span>
                  @endif
                  @if($hours > 0)
                    <span>{{ __('hesetak_pages.courses.hours', ['n' => $hours]) }}</span>
                  @endif
                </div>
                <div class="mc-catalog-card__foot">
                  <p class="mc-catalog-card__price">
                    @if($isFree)
                      {{ $p['free'] }}
                    @else
                      <strong>{{ number_format($payPrice, 0) }} {{ $currency }}</strong>
                      @if($hasPromo)
                        <s>{{ number_format($listPrice, 0) }} {{ $currency }}</s>
                      @endif
                    @endif
                  </p>
                  <a href="{{ $url }}" class="mc-btn mc-btn--sm mc-btn--secondary">{{ $p['view'] }}</a>
                </div>
              </div>
            </article>
          @endforeach
        </div>

        @if(method_exists($courses, 'hasPages') && $courses->hasPages())
          <div class="mc-pager">{{ $courses->links() }}</div>
        @endif
      @else
        <div class="mc-empty mc-dir-empty">
          <p>{{ $hasActiveFilters ? $p['empty'] : $p['empty_hint'] }}</p>
          @if($hasActiveFilters)
            <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $p['show_all'] }}</a>
          @endif
        </div>
      @endif
    </div>
  </section>

  <section class="mc-section mc-section--compact" aria-labelledby="mc-cat-split">
    <div class="mc-container">
      <h2 id="mc-cat-split" class="mc-title mc-curr-split__title">{{ $p['split_title'] }}</h2>
      <div class="mc-curr-split mc-catalog-split">
        <article class="mc-curr-split__card is-active">
          <h3>{{ $p['split_courses_title'] }}</h3>
          <p>{{ $p['split_courses'] }}</p>
        </article>
        <article class="mc-curr-split__card">
          <h3>{{ $p['split_curricula_title'] }}</h3>
          <p>{{ $p['split_curricula'] }}</p>
          <a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $p['cta_curricula'] }}</a>
        </article>
        <article class="mc-curr-split__card">
          <h3>{{ $p['split_teachers_title'] }}</h3>
          <p>{{ $p['split_teachers'] }}</p>
          <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $p['cta_teachers'] }}</a>
        </article>
      </div>
    </div>
  </section>

  <section class="mc-section mc-section--compact">
    <div class="mc-container">
      <div class="mc-cta">
        <div>
          <h2>{{ $p['parent_cta_title'] }}</h2>
          <p>{{ $p['parent_cta_desc'] }}</p>
        </div>
        <div class="mc-cta__actions">
          <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $p['cta_packages'] }}</a>
          <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $p['cta_teachers'] }}</a>
        </div>
      </div>
    </div>
  </section>
</main>

@include('partials.landing.mycourses.footer')
</body>
</html>
