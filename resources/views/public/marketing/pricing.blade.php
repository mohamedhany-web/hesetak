@extends('layouts.mycourses-public')

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
    $brand = __('landing.nav.brand');
    $catalog = $packageCatalog ?? [];
    $stages = $pricingStages ?? ($catalog['years'] ?? collect());
    $subjects = $pricingSubjects ?? ($catalog['subjects'] ?? collect());
    $cards = $pricingCards ?? [];
    $selYear = $catalog['selected_year_id'] ?? null;
    $selSubject = $catalog['selected_subject_id'] ?? null;
    $selCurriculum = $catalog['selected_curriculum_type'] ?? null;
    $customUrl = $customBuilderUrl ?? route('public.service-packages.index', ['catalog' => 1]).'#private-builder';
    $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

    $stageUrl = static function (?int $yearId, ?int $subjectId = null, ?string $curriculum = null) use ($catalog): string {
        $query = array_filter([
            'year' => $yearId ?: null,
            'curriculum_type' => $curriculum ?: ($catalog['selected_curriculum_type'] ?? null),
            'subject' => $subjectId ?: null,
        ]);

        return route('public.pricing', $query).'#mc-pricing-storefront';
    };

    $stageIcon = static function ($year): string {
        $slug = mb_strtolower((string) ($year->slug ?? ''));
        $name = mb_strtolower((string) ($year->name ?? ''));

        return match (true) {
            str_contains($slug, 'kg') || str_contains($name, 'رياض') => 'fa-child',
            str_contains($slug, 'primary') || str_contains($name, 'ابتد') => 'fa-graduation-cap',
            str_contains($slug, 'middle') || str_contains($slug, 'almtost') || str_contains($name, 'متوسط') => 'fa-book',
            str_contains($slug, 'second') || str_contains($name, 'ثان') => 'fa-user-graduate',
            str_contains($name, 'دولي') || str_contains($slug, 'intl') => 'fa-globe',
            str_contains($name, 'جامع') || str_contains($slug, 'univ') => 'fa-university',
            str_contains($name, 'قياس') || str_contains($slug, 'qiyas') => 'fa-clipboard-check',
            str_contains($name, 'مهار') || str_contains($slug, 'skill') => 'fa-lightbulb',
            default => 'fa-layer-group',
        };
    };
@endphp

@php
    $selectedCurriculumMeta = $selCurriculum && isset($curriculumTypes[$selCurriculum])
        ? $curriculumTypes[$selCurriculum]
        : null;
    $selectedStageLabel = null;
    if ($selYear) {
        $selectedStageLabel = optional($stages->firstWhere('id', (int) $selYear))->name;
        $selectedStageLabel = $selectedStageLabel
            ? \App\Support\LocalizedCatalogLabel::text($selectedStageLabel)
            : null;
    }
@endphp

<section class="mc-pric-hero" aria-labelledby="mc-pricing-title">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ __('public.pricing_hero_kicker') }}</p>
    <h1 id="mc-pricing-title" class="mc-pric-hero__title">{{ __('public.pricing_storefront_title') }}</h1>
    <p class="mc-pric-hero__lead">{{ __('public.pricing_hero_sub') }}</p>
    <p class="mc-pric-hero__lead" style="opacity:.9;margin-top:.35rem">{{ __('public.pricing_hero_note') }}</p>
    <ol class="mc-pric-steps" aria-label="{{ $isRtl ? 'خطوات الاختيار' : 'Selection steps' }}">
      <li class="{{ $selCurriculum ? 'is-done' : 'is-on' }}"><span>1</span>{{ $isRtl ? 'المنهج' : 'Curriculum' }}</li>
      <li class="{{ $selYear ? 'is-done' : ($selCurriculum ? 'is-on' : '') }}"><span>2</span>{{ $isRtl ? 'المرحلة' : 'Stage' }}</li>
      <li class="{{ $selSubject ? 'is-done' : ($selYear ? 'is-on' : '') }}"><span>3</span>{{ $isRtl ? 'المادة' : 'Subject' }}</li>
      <li class="{{ $selSubject ? 'is-on' : '' }}"><span>4</span>{{ $isRtl ? 'عدد الحصص' : 'Sessions' }}</li>
    </ol>
  </div>
</section>

<section class="mc-pric-storefront" id="mc-pricing-storefront">
  <div class="mc-container">
    <p class="mc-pric-path-tip">
      {{ __('public.pricing_path_tip') }}
      <a href="{{ route('public.courses') }}">{{ __('public.pricing_path_tip_courses') }}</a>
    </p>

    @if($selectedCurriculumMeta)
      <div class="mc-pric-context" role="status">
        <div class="mc-pric-context__copy">
          <p class="mc-pric-context__eyebrow">{{ __('public.pricing_context_eyebrow') }}</p>
          <h2 class="mc-pric-context__title">{{ $selectedCurriculumMeta['label'] ?? $selCurriculum }}</h2>
          <p class="mc-pric-context__lead">{{ __('public.pricing_context_lead') }}</p>
          @if($selectedStageLabel)
            <p class="mc-pric-context__meta">
              <span>{{ __('public.pricing_filter_stage') }}: {{ $selectedStageLabel }}</span>
              <span>·</span>
              <span>{{ __('public.pricing_filter_subject') }}: {{ $selSubject ? __('public.pricing_context_subject_on') : __('public.pricing_context_subject_pick') }}</span>
            </p>
          @endif
        </div>
        <a href="{{ $stageUrl(null, null, null) }}" class="mc-btn mc-btn--sm mc-btn--soft">{{ __('public.pricing_context_clear') }}</a>
      </div>
    @endif

    @if(!empty($curriculumTypes))
      <div class="mc-pric-filter" aria-labelledby="mc-pric-track-label">
        <h2 id="mc-pric-track-label" class="mc-pric-filter__label">1. {{ __('public.pricing_filter_track') }}</h2>
        <p class="mc-pric-filter__hint">{{ __('public.pricing_filter_track_hint') }}</p>
        <div class="mc-pric-subjects" role="list">
          @foreach($curriculumTypes as $key => $meta)
            @php $on = (string) $selCurriculum === (string) $key; @endphp
            <a href="{{ $stageUrl($selYear ? (int) $selYear : null, null, $on ? null : $key) }}" class="mc-pric-chip {{ $on ? 'is-on' : '' }}" role="listitem">{{ $meta['label'] ?? $key }}</a>
          @endforeach
        </div>
      </div>
    @endif

    @if($selYear || $selSubject || $selCurriculum)
      <p class="mc-pric-back" style="margin:0 0 1rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
        <a href="{{ route('public.pricing') }}#mc-pricing-storefront" class="mc-btn mc-btn--sm mc-btn--soft">
          <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
          {{ $isRtl ? 'كل الباقات' : 'All packages' }}
        </a>
        <a href="{{ $stageUrl(null, null, $selCurriculum ?: null) }}" class="mc-btn mc-btn--sm mc-btn--outline">
          {{ $isRtl ? 'العودة لكل المراحل' : 'Back to all stages' }}
        </a>
        @if($selSubject)
          <a href="{{ $stageUrl($selYear ? (int) $selYear : null, null) }}" class="mc-btn mc-btn--sm mc-btn--outline">
            {{ $isRtl ? 'كل مواد المرحلة' : 'All subjects in stage' }}
          </a>
        @endif
      </p>
    @endif

    <div class="mc-pric-filter" aria-labelledby="mc-pric-stage-label">
      <h2 id="mc-pric-stage-label" class="mc-pric-filter__label">2. {{ __('public.pricing_filter_stage') }}</h2>
      <p class="mc-pric-filter__hint">{{ __('public.pricing_filter_stage_hint') }}</p>
      <div class="mc-pric-stages" role="tablist" aria-label="{{ __('public.pricing_filter_stage') }}">
        <div class="mc-pric-stages__bar">
          <div class="mc-pric-stages__scroll">
            @forelse($stages as $year)
              <a
                href="{{ $stageUrl((int) $year->id) }}"
                class="mc-pric-stage {{ (int) $selYear === (int) $year->id ? 'is-on' : '' }}"
                role="tab"
                aria-selected="{{ (int) $selYear === (int) $year->id ? 'true' : 'false' }}"
              >
                <i class="fas {{ $stageIcon($year) }}" aria-hidden="true"></i>
                <span>{{ \App\Support\LocalizedCatalogLabel::text($year->name) }}</span>
              </a>
            @empty
              <span class="mc-pric-stage is-on"><i class="fas fa-graduation-cap" aria-hidden="true"></i><span>{{ __('public.pricing_filter_all_stages') }}</span></span>
            @endforelse
          </div>
          <a href="{{ $customUrl }}" class="mc-pric-diverse">
            <i class="fas fa-layer-group" aria-hidden="true"></i>
            <span>{{ __('public.pricing_diverse_cta') }}</span>
          </a>
        </div>
      </div>
    </div>

    <div class="mc-pric-filter" aria-labelledby="mc-pric-subject-label">
      <h2 id="mc-pric-subject-label" class="mc-pric-filter__label">3. {{ __('public.pricing_filter_subject') }}</h2>
      <p class="mc-pric-filter__hint">{{ __('public.pricing_filter_subject_hint') }}</p>
      @if($subjects->isNotEmpty())
        <div class="mc-pric-subjects" role="list">
          @foreach($subjects as $subject)
            @php
              $isSubjectOn = (int) $selSubject === (int) $subject->id;
              $subjectHref = $isSubjectOn
                ? $stageUrl($selYear ? (int) $selYear : null, null)
                : $stageUrl($selYear ? (int) $selYear : null, (int) $subject->id);
            @endphp
            <a href="{{ $subjectHref }}" class="mc-pric-chip {{ $isSubjectOn ? 'is-on' : '' }}" role="listitem">{{ \App\Support\LocalizedCatalogLabel::text($subject->name) }}</a>
          @endforeach
        </div>
      @else
        <div class="mc-empty mc-empty--soft">{{ __('public.pricing_subjects_empty') }}</div>
      @endif
    </div>

    <div class="mc-pric-filter" aria-labelledby="mc-pric-packages-label">
      <h2 id="mc-pric-packages-label" class="mc-pric-filter__label">4. {{ __('public.pricing_packages_title') }}</h2>
      <p class="mc-pric-filter__hint">{{ __('public.pricing_packages_sub') }}</p>
      @if(empty($cards))
        <div class="mc-empty">{{ __('public.pricing_no_packages') }}</div>
      @else
        <div class="mc-pric-grid">
          @foreach($cards as $card)
            <article class="mc-pric-card tone-{{ $card['tone'] ?? 0 }}">
              <div class="mc-pric-card__cover" aria-hidden="true">
                <i class="fas {{ $card['icon'] ?? 'fa-graduation-cap' }}"></i>
                <div class="mc-pric-card__devices">
                  @foreach(($card['devices'] ?? []) as $device)
                    <span>{{ $device }}</span>
                  @endforeach
                </div>
              </div>
              <div class="mc-pric-card__body">
                @if(!empty($card['badge']))
                  <span class="mc-pric-card__badge">{{ $card['badge'] }}</span>
                @endif
                <h3 class="mc-pric-card__title">{{ \App\Support\LocalizedCatalogLabel::text($card['title']) }}</h3>
                @if(!empty($card['description']))
                  <p class="mc-pric-card__desc">{{ \App\Support\LocalizedCatalogLabel::text($card['description']) }}</p>
                @endif
                @if(!empty($card['features']))
                  <ul class="mc-pric-card__perks">
                    @foreach($card['features'] as $feature)
                      <li><i class="fas fa-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                    @endforeach
                  </ul>
                @endif
                <div class="mc-pric-card__prices">
                  @foreach(($card['price_options'] ?? []) as $opt)
                    <a href="{{ $opt['href'] }}" class="mc-pric-price {{ !empty($opt['is_urgent']) ? 'is-urgent' : '' }}">
                      <span class="mc-pric-price__label">
                        @if(!empty($opt['is_urgent']))
                          <i class="fas fa-bolt" aria-hidden="true"></i>
                        @endif
                        {{ $opt['label'] }}
                      </span>
                      <span class="mc-pric-price__unit">{{ $opt['unit_label'] }} · {{ format_money((float) ($opt['total'] ?? 0), 0) }}</span>
                    </a>
                  @endforeach
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

@include('partials.landing.mycourses.path-guide', ['pathGuideActive' => 'packages'])

<section class="mc-section mc-section--compact">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ __('public.pricing_footer_cta_title') }}</h2>
        <p>{{ __('public.pricing_footer_cta_sub') }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ __('landing.mc.hero.cta_primary') }}</a>
        <a href="{{ $customUrl }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ __('public.pricing_diverse_cta') }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
