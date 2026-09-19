@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.curricula');
    $d = $p['detail'] ?? [];
    $brand = __('landing.nav.brand');
    $year = $year;
    $subjects = $subjects ?? collect();
    $teachers = $teachers ?? collect();
    $siblings = $siblings ?? collect();
    $isRtl = app()->getLocale() === 'ar';
@endphp

<section class="mc-dir-head mc-curr-head" aria-labelledby="mc-curr-detail-title">
  <div class="mc-container">
    <nav class="mc-tp-crumb" aria-label="{{ $isRtl ? 'مسار التنقل' : 'Breadcrumb' }}">
      <a href="{{ route('home') }}">{{ $isRtl ? 'الرئيسية' : 'Home' }}</a>
      <span aria-hidden="true">/</span>
      <a href="{{ route('public.curricula') }}">{{ $p['eyebrow'] }}</a>
      <span aria-hidden="true">/</span>
      <span>{{ $year->name }}</span>
    </nav>

    <div class="mc-dir-head__top" style="margin-top:1rem">
      <div class="mc-dir-head__copy">
        <p class="mc-eyebrow">{{ $d['eyebrow'] ?? $p['eyebrow'] }}</p>
        <h1 id="mc-curr-detail-title">{{ $year->name }}</h1>
        @if(filled($year->tagline))
          <p class="mc-curr-head__lead">{{ $year->tagline }}</p>
        @elseif(filled($year->description))
          <p class="mc-curr-head__lead">{{ \Illuminate\Support\Str::limit(strip_tags($year->description), 180) }}</p>
        @endif
      </div>
      <p class="mc-dir-head__count">
        <strong>{{ number_format($subjects->count()) }}</strong>
        <span>{{ $p['subjects_count'] }}</span>
      </p>
    </div>

    <div class="mc-curr-actions">
      <a
        href="{{ route('public.instructors.index', ['stage' => $year->slug]) }}"
        class="mc-btn mc-btn--md mc-btn--primary"
      >{{ $d['cta_match'] ?? $p['cta_teachers'] }}</a>
      <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $p['cta_packages'] }}</a>
      <a href="{{ route('public.curricula') }}" class="mc-btn mc-btn--md mc-btn--soft">{{ $d['cta_back'] ?? ($isRtl ? 'كل المناهج' : 'All curricula') }}</a>
    </div>
  </div>
</section>

@if(filled($year->description))
<section class="mc-section mc-section--compact mc-curr-section">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 class="mc-title">{{ $d['about_title'] ?? ($isRtl ? 'عن هذه المرحلة' : 'About this stage') }}</h2>
      </div>
    </div>
    <div class="mc-curr-note" style="margin:0; white-space:pre-line">{{ strip_tags($year->description) }}</div>
  </div>
</section>
@endif

<section class="mc-section mc-section--compact mc-curr-section" aria-labelledby="mc-curr-subjects">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-subjects" class="mc-title">{{ $d['subjects_title'] ?? $p['subjects_title'] }}</h2>
        <p class="mc-lead">{{ $d['subjects_lead'] ?? ($isRtl ? 'المواد كما يصفها الأدمن داخل المنصة. اختر مادة لفتح دليل المعلمين.' : 'Subjects as configured by admin. Pick one to open the teacher directory.') }}</p>
      </div>
    </div>

    @if($subjects->isEmpty())
      <div class="mc-empty">{{ $d['subjects_empty'] ?? ($isRtl ? 'لا مواد منشورة لهذه المرحلة بعد.' : 'No subjects published for this stage yet.') }}</div>
    @else
      <div class="mc-curricula-grid">
        @foreach($subjects as $subject)
          <a
            class="mc-curricula-card mc-curricula-card--link"
            href="{{ route('public.instructors.index', ['stage' => $year->slug, 'skill' => $subject->name]) }}"
          >
            <h3>{{ $subject->name }}</h3>
            <p>
              @if(filled($subject->description))
                {{ \Illuminate\Support\Str::limit(strip_tags($subject->description), 100) }}
              @else
                {{ $d['subject_cta'] ?? ($isRtl ? 'ابحث عن معلم لهذه المادة' : 'Find a teacher for this subject') }}
              @endif
            </p>
            <span class="mc-curricula-card__cta">{{ $p['cta_teachers'] }} →</span>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>

@if($teachers->isNotEmpty())
<section class="mc-section mc-section--muted mc-section--compact" aria-labelledby="mc-curr-teachers">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <h2 id="mc-curr-teachers" class="mc-title">{{ $d['teachers_title'] ?? ($isRtl ? 'معلمون مرتبطون' : 'Matched teachers') }}</h2>
        <p class="mc-lead">{{ $d['teachers_lead'] ?? ($isRtl ? 'ابدأ من هنا أو وسّع البحث بالفلاتر.' : 'Start here or widen the search with filters.') }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.instructors.index', ['stage' => $year->slug]) }}">{{ $p['cta_teachers'] }} →</a>
    </div>
    <div class="mc-teachers mc-teachers--dense">
      @foreach($teachers->take(8) as $profile)
        @php
          $user = $profile->user;
          $name = $user->name ?? '';
          $url = $user ? route('public.instructors.show', $user) : route('public.instructors.index');
        @endphp
        <article class="mc-teacher mc-teacher--sm">
          <a href="{{ $url }}" class="mc-teacher__media">
            @if($profile->photo_url)
              <img src="{{ $profile->photo_url }}" alt="{{ $name }}" loading="lazy" width="160" height="160">
            @else
              <span class="mc-teacher__fallback" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
            @endif
          </a>
          <div class="mc-teacher__body">
            <h3><a href="{{ $url }}">{{ $name }}</a></h3>
            <p>{{ $profile->headline_clean ?: __('public.instructors_verified') }}</p>
            <a href="{{ $url }}" class="mc-btn mc-btn--sm mc-btn--secondary">{{ __('public.instructors_book') }}</a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
@endif

@if($siblings->isNotEmpty())
<section class="mc-section mc-section--compact">
  <div class="mc-container">
    <h2 class="mc-title" style="margin-bottom:1rem">{{ $d['siblings_title'] ?? ($isRtl ? 'مراحل أخرى' : 'Other stages') }}</h2>
    <div class="mc-dir-chips">
      @foreach($siblings as $sib)
        <a class="mc-dir-chip" href="{{ route('public.curricula.show', $sib) }}"><em>{{ $sib->name }}</em></a>
      @endforeach
    </div>
  </div>
</section>
@endif

<section class="mc-section mc-section--compact">
  <div class="mc-container">
    <div class="mc-cta">
      <div>
        <h2>{{ $p['parent_cta_title'] }}</h2>
        <p>{{ $p['parent_cta_desc'] }}</p>
      </div>
      <div class="mc-cta__actions">
        <a href="{{ route('public.instructors.index', ['stage' => $year->slug]) }}" class="mc-btn mc-btn--lg mc-btn--secondary">{{ $p['cta_teachers'] }}</a>
        <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--ghost-on-dark">{{ $p['cta_packages'] }}</a>
      </div>
    </div>
  </div>
</section>
@endsection
