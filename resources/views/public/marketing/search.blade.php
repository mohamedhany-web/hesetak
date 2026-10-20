@extends('layouts.mycourses-public')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $q = $q ?? '';
  $hasQuery = (bool) ($hasQuery ?? false);
@endphp

<section class="mc-dir-head" aria-labelledby="mc-search-title">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'بحث حصتك' : 'Hesetak search' }}</p>
    <h1 id="mc-search-title">{{ $isRtl ? 'ابحث عما تحتاجه' : 'Find what you need' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'معلمون، مراحل، مواد، باقات حصص، أو كورسات مستقلة.' : 'Teachers, stages, subjects, lesson packages, or independent courses.' }}</p>

    <form method="GET" action="{{ route('public.search') }}" class="mc-search-form" style="margin-top:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap;max-width:40rem">
      <label class="visually-hidden" for="mc-search-q">{{ $isRtl ? 'كلمة البحث' : 'Search' }}</label>
      <input
        type="search"
        name="q"
        id="mc-search-q"
        value="{{ $q }}"
        required
        minlength="2"
        autofocus
        placeholder="{{ $isRtl ? 'مثال: رياضيات، ابتدائي، إنجليزي…' : 'e.g. math, primary, English…' }}"
        class="lasles-auth-input"
        style="flex:1;min-width:12rem"
      >
      <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">
        <i class="fas fa-search" aria-hidden="true"></i>
        {{ $isRtl ? 'بحث' : 'Search' }}
      </button>
    </form>
  </div>
</section>

<section class="mc-section mc-section--compact">
  <div class="mc-container">
    @if(! $hasQuery)
      <div class="mc-empty">{{ $isRtl ? 'اكتب كلمتين على الأقل للبحث.' : 'Type at least two characters to search.' }}</div>
    @else
      @php
        $emptyAll = $instructors->isEmpty() && $packages->isEmpty() && $courses->isEmpty() && $subjects->isEmpty() && $years->isEmpty();
      @endphp
      @if($emptyAll)
        <div class="mc-empty">
          {{ $isRtl ? 'لا نتائج مطابقة. جرّب مرحلة أو مادة من الباقات.' : 'No matches. Try a stage or subject from packages.' }}
          <p style="margin-top:.75rem">
            <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'تصفح الباقات' : 'Browse packages' }}</a>
          </p>
        </div>
      @endif

      @if($years->isNotEmpty())
        <h2 class="mc-title" style="margin-bottom:.75rem">{{ $isRtl ? 'المراحل' : 'Stages' }}</h2>
        <div class="mc-pric-subjects" style="margin-bottom:1.5rem">
          @foreach($years as $year)
            <a class="mc-pric-chip" href="{{ route('public.pricing', ['year' => $year->id]) }}#mc-pricing-storefront">{{ \App\Support\LocalizedCatalogLabel::text($year->name) }}</a>
          @endforeach
        </div>
      @endif

      @if($subjects->isNotEmpty())
        <h2 class="mc-title" style="margin-bottom:.75rem">{{ $isRtl ? 'المواد' : 'Subjects' }}</h2>
        <div class="mc-pric-subjects" style="margin-bottom:1.5rem">
          @foreach($subjects as $subject)
            <a class="mc-pric-chip" href="{{ route('public.pricing', array_filter(['year' => $subject->academic_year_id, 'subject' => $subject->id])) }}#mc-pricing-storefront">
              {{ \App\Support\LocalizedCatalogLabel::text($subject->name) }}
              @if($subject->academicYear)
                · {{ \App\Support\LocalizedCatalogLabel::text($subject->academicYear->name) }}
              @endif
            </a>
          @endforeach
        </div>
      @endif

      @if($packages->isNotEmpty())
        <h2 class="mc-title" style="margin-bottom:.75rem">{{ $isRtl ? 'الباقات' : 'Packages' }}</h2>
        <ul class="mc-list" style="margin-bottom:1.5rem;list-style:none;padding:0;display:grid;gap:.75rem">
          @foreach($packages as $package)
            <li>
              <a href="{{ route('public.service-packages.checkout', $package) }}?year={{ $package->academic_year_id }}&subject={{ $package->academic_subject_id }}" class="mc-btn mc-btn--md mc-btn--soft" style="justify-content:space-between;width:100%">
                <span>{{ $package->name }}</span>
                <span>{{ (int) $package->units_count }} {{ $isRtl ? 'حصص' : 'sessions' }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif

      @if($courses->isNotEmpty())
        <h2 class="mc-title" style="margin-bottom:.75rem">{{ $isRtl ? 'الكورسات المستقلة' : 'Independent courses' }}</h2>
        <ul style="margin-bottom:1.5rem;list-style:none;padding:0;display:grid;gap:.75rem">
          @foreach($courses as $course)
            <li>
              <a href="{{ route('public.course.show', $course->id) }}" class="mc-btn mc-btn--md mc-btn--outline" style="justify-content:space-between;width:100%">
                <span><i class="fas fa-language" aria-hidden="true"></i> {{ $course->title }}</span>
                <span>{{ $course->level ?: ($course->category ?: '') }}</span>
              </a>
            </li>
          @endforeach
        </ul>
      @endif

      @if($instructors->isNotEmpty())
        <h2 class="mc-title" style="margin-bottom:.75rem">{{ $isRtl ? 'المعلمون' : 'Teachers' }}</h2>
        <ul style="list-style:none;padding:0;display:grid;gap:.75rem">
          @foreach($instructors as $profile)
            @php $user = $profile->user; @endphp
            @if($user)
              <li>
                <a href="{{ route('public.instructors.show', $user) }}" class="mc-btn mc-btn--md mc-btn--soft" style="width:100%;justify-content:flex-start;gap:.75rem">
                  <i class="fas fa-chalkboard-user" aria-hidden="true"></i>
                  <span>{{ $user->name }}@if($profile->headline) — {{ \Illuminate\Support\Str::limit($profile->headline, 48) }}@endif</span>
                </a>
              </li>
            @endif
          @endforeach
        </ul>
      @endif
    @endif
  </div>
</section>
@endsection
