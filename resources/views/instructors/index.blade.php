@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = __('landing.nav.brand');
    $profiles = $profiles ?? collect();
    $skillFacets = $skillFacets ?? [];
    $stages = $stages ?? collect();
    $subjectOptions = $subjectOptions ?? collect();
    $curriculumTypes = $curriculumTypes ?? [];
    $filters = $filters ?? ['q' => '', 'skill' => '', 'stage' => '', 'curriculum' => '', 'sort' => 'newest'];
    $totalTeachers = (int) ($totalTeachers ?? $profiles->count());
    $q = (string) ($filters['q'] ?? '');
    $skill = (string) ($filters['skill'] ?? '');
    $stage = (string) ($filters['stage'] ?? '');
    $curriculum = (string) ($filters['curriculum'] ?? '');
    $sort = (string) ($filters['sort'] ?? 'newest');
    $resultCount = $profiles->count();
    $hasActiveFilters = $q !== '' || $skill !== '' || $stage !== '' || $curriculum !== '' || $sort !== 'newest';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $mcActive = 'instructors';
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $skillChoices = $subjectOptions->isNotEmpty()
        ? $subjectOptions->map(fn ($s) => [
            'label' => $s->name,
            'value' => $s->slug ?: (string) $s->id,
            'count' => null,
        ])->values()
        : collect($skillFacets)->take(12)->map(function ($facet) {
            if (! isset($facet['value'])) {
                $facet['value'] = $facet['label'] ?? '';
            }

            return $facet;
        })->values();
    $popularSkills = collect($skillFacets)->take(8)->values();
    $filterQuery = fn (array $extra = []) => array_filter(array_merge([
        'q' => $q !== '' ? $q : null,
        'skill' => $skill !== '' ? $skill : null,
        'stage' => $stage !== '' ? $stage : null,
        'curriculum' => $curriculum !== '' ? $curriculum : null,
        'sort' => $sort !== 'newest' ? $sort : null,
    ], $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ request()->boolean('figma') ? 'ltr' : ($isRtl ? 'rtl' : 'ltr') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __('public.instructors_page_title') }} | {{ $brand }}</title>
  <meta name="description" content="{{ __('public.instructors_subtitle') }}">
  <meta name="theme-color" content="#1E4E8C">
  <link rel="canonical" href="{{ route('public.instructors.index') }}">
  @include('partials.favicon-links')
  @include('partials.seo-jsonld', ['jsonldType' => 'website'])
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Lato:wght@400;700;900&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  @include('partials.figma-capture-head')
</head>
<body class="mc-body mc-body--dir">
@include('partials.landing.mycourses.nav')

<main class="mc-dir">
  <section class="mc-dir-head" id="teachers-list" aria-labelledby="mc-dir-title">
    <div class="mc-container">
      <div class="mc-dir-head__top">
        <div class="mc-dir-head__copy">
          <p class="mc-eyebrow">{{ __('public.instructors_eyebrow') }}</p>
          <h1 id="mc-dir-title">{!! __('public.instructors_title_html', ['brand' => e($brand)]) !!}</h1>
        </div>
        <p class="mc-dir-head__count">
          <strong>{{ number_format($resultCount) }}</strong>
          <span>{{ $isRtl ? 'معلم معتمد' : 'approved' }}</span>
          @if($hasActiveFilters && $resultCount !== $totalTeachers)
            <em>{{ __('public.instructors_results_of', ['total' => number_format($totalTeachers)]) }}</em>
          @endif
        </p>
      </div>

      <form class="mc-dir-bar mc-dir-bar--triple" action="{{ route('public.instructors.index') }}" method="get" role="search">
        <label class="mc-dir-bar__field mc-dir-bar__field--grow">
          <span class="mc-dir-bar__label">{{ __('public.instructors_search_label') }}</span>
          <input
            class="mc-input"
            id="mc-inst-search"
            type="search"
            name="q"
            value="{{ $q }}"
            placeholder="{{ __('public.instructors_search_placeholder') }}"
            autocomplete="off"
          >
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ __('public.instructors_stage_label') }}</span>
          <select class="mc-select" name="stage">
            <option value="">{{ __('public.instructors_stage_all') }}</option>
            @foreach($stages as $year)
              <option value="{{ $year->slug }}" @selected($stage === $year->slug)>{{ $year->name }}</option>
            @endforeach
          </select>
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ __('public.instructors_skill_label') }}</span>
          <select class="mc-select" name="skill">
            <option value="">{{ __('public.instructors_skill_all') }}</option>
            @foreach($skillChoices as $facet)
              @php $skillValue = $facet['value'] ?? $facet['label'] ?? ''; @endphp
              <option value="{{ $skillValue }}" @selected($skill === $skillValue || $skill === ($facet['label'] ?? ''))>
                {{ $facet['label'] }}@if(!empty($facet['count'])) ({{ $facet['count'] }})@endif
              </option>
            @endforeach
          </select>
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ __('public.instructors_curriculum_label') }}</span>
          <select class="mc-select" name="curriculum">
            <option value="">{{ __('public.instructors_curriculum_all') }}</option>
            @foreach($curriculumTypes as $type)
              <option value="{{ $type['key'] }}" @selected($curriculum === $type['key'])>{{ $type['label'] }}</option>
            @endforeach
          </select>
        </label>

        <label class="mc-dir-bar__field">
          <span class="mc-dir-bar__label">{{ __('public.instructors_sort_label') }}</span>
          <select class="mc-select" name="sort">
            <option value="newest" @selected($sort === 'newest')>{{ __('public.instructors_sort_newest') }}</option>
            <option value="name" @selected($sort === 'name')>{{ __('public.instructors_sort_name') }}</option>
            <option value="courses" @selected($sort === 'courses')>{{ __('public.instructors_sort_courses') }}</option>
          </select>
        </label>

        <div class="mc-dir-bar__actions">
          <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">{{ __('public.instructors_apply') }}</button>
          @if($hasActiveFilters)
            <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ __('public.instructors_clear') }}</a>
          @endif
        </div>
      </form>

      @if($popularSkills->isNotEmpty())
        <div class="mc-dir-chips" role="list" aria-label="{{ __('public.instructors_popular') }}">
          <a
            role="listitem"
            class="mc-dir-chip {{ $skill === '' ? 'is-on' : '' }}"
            href="{{ route('public.instructors.index', $filterQuery(['skill' => null])) }}"
          >{{ __('public.instructors_skill_all') }}</a>
          @foreach($popularSkills as $facet)
            <a
              role="listitem"
              class="mc-dir-chip {{ $skill === $facet['label'] ? 'is-on' : '' }}"
              href="{{ route('public.instructors.index', $filterQuery(['skill' => $facet['label']])) }}"
            >{{ $facet['label'] }} <em>{{ $facet['count'] }}</em></a>
          @endforeach
        </div>
      @endif

      @if($skill !== '')
        <p class="mc-dir-meta__filter mc-dir-head__active">
          <span class="mc-dir-meta__tag">{{ $skill }}</span>
          <a href="{{ route('public.instructors.index', $filterQuery(['skill' => null])) }}">{{ __('public.instructors_clear') }}</a>
        </p>
      @endif
    </div>
  </section>

  <section class="mc-section mc-section--tight mc-dir-results">
    <div class="mc-container">
      @if($profiles->isNotEmpty())
        <div class="mc-teachers mc-teachers--dir">
          @foreach($profiles as $p)
            @php
              $user = $p->user;
              $url = route('public.instructors.show', $user);
              $name = $user->name ?? __('public.instructor_fallback');
              $headline = $p->headline_clean ?: '';
              $skills = array_slice($p->skills_list ?? [], 0, 2);
              $photoFallbacks = [
                  public_img_url('lasles/avatar-1.png'),
                  public_img_url('lasles/avatar-2.png'),
                  public_img_url('lasles/avatar-3.png'),
                  public_img_url('mycourses/hero-saudi-student.png'),
              ];
              $photo = $p->photo_url ?: $photoFallbacks[$loop->index % count($photoFallbacks)];
              $coursesCount = (int) ($p->courses_count ?? 0);
            @endphp
            <article class="mc-teacher mc-teacher--dir">
              <a href="{{ $url }}" class="mc-teacher__photo" tabindex="-1" aria-hidden="true">
                <img src="{{ $photo }}" width="320" height="320" alt="" loading="{{ request()->boolean('figma') ? 'eager' : 'lazy' }}" decoding="async">
                <span class="mc-teacher__badge">{{ __('public.instructors_verified') }}</span>
              </a>
              <div class="mc-teacher__body">
                <h3><a href="{{ $url }}">{{ $name }}</a></h3>
                @if(count($skills) > 0)
                  <p class="mc-teacher__subject">{{ implode($isRtl ? '، ' : ', ', $skills) }}</p>
                @elseif($headline !== '')
                  <p class="mc-teacher__specialty">{{ \Illuminate\Support\Str::limit($headline, 56) }}</p>
                @endif
                @if($coursesCount > 0)
                  <p class="mc-teacher__meta">
                    {{ $coursesCount }}
                    {{ $coursesCount === 1 ? __('public.instructors_course_one') : __('public.instructors_course_many') }}
                  </p>
                @endif
                <div class="mc-teacher__footer">
                  <a href="{{ $url }}" class="mc-btn mc-btn--sm mc-btn--secondary mc-teacher__cta">{{ __('public.instructors_book') }}</a>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @else
        <div class="mc-empty mc-dir-empty">
          <p>{{ $hasActiveFilters ? __('public.instructors_empty_filtered') : __('public.instructors_empty_hint') }}</p>
          @if($hasActiveFilters)
            <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ __('public.instructors_show_all') }}</a>
          @endif
        </div>
      @endif
    </div>
  </section>

  <section class="mc-section mc-section--compact">
    <div class="mc-container">
      <div class="mc-cta mc-cta--parent">
        <div>
          <h2>{{ __('public.instructors_parent_cta_title') }}</h2>
          <p>{{ __('public.instructors_parent_cta_desc') }}</p>
        </div>
        <div class="mc-cta__actions">
          <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('public.instructors_parent_cta_packages') }}</a>
          <a href="{{ $waUrl }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark" target="_blank" rel="noopener">{{ __('landing.mc.topbar.whatsapp') }}</a>
        </div>
      </div>
    </div>
  </section>

  <section class="mc-section mc-section--tight mc-dir-teacher-join">
    <div class="mc-container mc-dir-teacher-join__inner">
      <div>
        <p class="mc-eyebrow">{{ __('public.instructors_cta_badge') }}</p>
        <h2 class="mc-title">{{ __('public.instructors_cta_title') }}</h2>
        <p class="mc-lead">{{ __('public.instructors_cta_desc') }}</p>
      </div>
      <a href="{{ route('public.tutor.apply') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ __('public.instructors_cta_register') }}</a>
    </div>
  </section>
</main>

@include('partials.landing.mycourses.footer')
@if(request('focus') === 'private')
<script>
document.getElementById('teachers-list')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
</script>
@endif
</body>
</html>
