@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.curricula');
    $brand = __('landing.nav.brand');
    $teacherCount = (int) ($teacherCount ?? 0);
    $years = $years ?? collect();
    $presence = $p['presence'] ?? [];
    $how = $p['how'] ?? [];
    $benefits = $p['benefits'] ?? [];
    $curriculumTypes = $curriculumTypes ?? [];
    $featuredTypes = collect($curriculumTypes)->only(['saudi', 'egyptian', 'us_intl'])->values();
@endphp

<section class="mc-dir-head mc-curr-head" aria-labelledby="mc-curr-title">
  <div class="mc-container">
    <div class="mc-dir-head__top">
      <div class="mc-dir-head__copy">
        <p class="mc-eyebrow">{{ $p['eyebrow'] }}</p>
        <h1 id="mc-curr-title">{!! __('hesetak_pages.curricula.title_html', ['brand' => e($brand)]) !!}</h1>
        <p class="mc-curr-head__lead">{{ $p['lead'] }}</p>
      </div>
      @if($teacherCount > 0)
        <p class="mc-dir-head__count">
          <strong>{{ number_format($teacherCount) }}</strong>
          <span>{{ $p['count_label'] }}</span>
        </p>
      @endif
    </div>

    <div class="mc-curr-actions">
      <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $p['cta_teachers'] }}</a>
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $p['cta_packages'] }}</a>
      <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $p['cta_courses'] }}</a>
    </div>
  </div>
</section>

@if(count($presence) > 0)
<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-presence">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-presence" class="mc-title">{{ $p['presence_title'] }}</h2>
        <p class="mc-lead">{{ $p['presence_lead'] }}</p>
      </div>
    </div>
    <div class="mc-curr-presence">
      @foreach($presence as $item)
        <article class="mc-curr-presence__item">
          <h3>{{ $item['title'] }}</h3>
          <p>{{ $item['body'] }}</p>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-catalog">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-catalog" class="mc-title">{{ $p['catalog_title'] }}</h2>
        <p class="mc-lead">{{ $p['catalog_lead'] }}</p>
      </div>
    </div>

    @if($years->isEmpty())
      <div class="mc-empty">{{ $p['catalog_empty'] }}</div>
    @else
      <div class="mc-curr-stages">
        @foreach($years as $year)
          <a class="mc-curr-stage" href="{{ route('public.curricula.show', $year) }}">
            <h3>{{ $year->name }}</h3>
            <p>
              @if(filled($year->tagline))
                {{ $year->tagline }}
              @elseif(filled($year->description))
                {{ \Illuminate\Support\Str::limit(strip_tags($year->description), 90) }}
              @else
                {{ $p['catalog_open'] }}
              @endif
            </p>
            <span class="mc-curr-stage__meta">
              {{ (int) ($year->subjects_count ?? $year->subjects->count()) }} {{ $p['subjects_count'] }}
            </span>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>

<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-how">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-how" class="mc-title">{{ $p['how_title'] }}</h2>
        <p class="mc-lead">{{ $p['how_lead'] }}</p>
      </div>
    </div>
    <div class="mc-steps">
      @foreach($how as $step)
        <article class="mc-step">
          <div class="mc-step__n" aria-hidden="true">{{ $step['n'] }}</div>
          <h3>{{ $step['title'] }}</h3>
          <p>{{ $step['body'] }}</p>
        </article>
      @endforeach
    </div>

    @if(count($benefits) > 0)
      <h3 class="mc-curr-benefits__title">{{ $p['benefits_title'] }}</h3>
      <ul class="mc-curr-benefits">
        @foreach($benefits as $benefit)
          <li>{{ $benefit }}</li>
        @endforeach
      </ul>
    @endif
  </div>
</section>

@if($featuredTypes->isNotEmpty())
<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-types">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-types" class="mc-title">{{ $p['types_title'] }}</h2>
        <p class="mc-lead">{{ $p['types_lead'] }}</p>
      </div>
    </div>
    <div class="mc-curricula-grid">
      @foreach($featuredTypes as $type)
        <a
          class="mc-curricula-card mc-curricula-card--link"
          href="{{ route('public.instructors.index', ['curriculum' => $type['key']]) }}"
        >
          <h3>{{ $type['label'] }}</h3>
          <p>{{ $p['types_card_hint'] }}</p>
          <span class="mc-curricula-card__cta">{{ $p['cta_teachers'] }} →</span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-split">
  <div class="mc-container">
    <h2 id="mc-curr-split" class="mc-title mc-curr-split__title">{{ $p['split_title'] }}</h2>
    <div class="mc-curr-split">
      <article class="mc-curr-split__card is-active">
        <h3>{{ $p['split_curricula_title'] }}</h3>
        <p>{{ $p['split_curricula'] }}</p>
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $p['cta_teachers'] }}</a>
      </article>
      <article class="mc-curr-split__card">
        <h3>{{ $p['split_courses_title'] }}</h3>
        <p>{{ $p['split_courses'] }}</p>
        <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $p['cta_courses'] }}</a>
      </article>
    </div>
    <p class="mc-curr-note">{{ $p['note'] }}</p>
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
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $p['cta_teachers'] }}</a>
        <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $p['cta_packages'] }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
