@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.curricula');
    $years = $years ?? collect();
    $curriculumTypes = $curriculumTypes ?? [];
    $matchYears = $years->map(function ($year) {
        return [
            'id' => (int) $year->id,
            'slug' => (string) ($year->slug ?: $year->id),
            'name' => \App\Support\LocalizedCatalogLabel::text($year->name),
            'subjects' => $year->subjects->map(function ($subject) {
                return [
                    'id' => (int) $subject->id,
                    'slug' => (string) ($subject->slug ?: $subject->id),
                    'name' => \App\Support\LocalizedCatalogLabel::text($subject->name),
                ];
            })->values(),
        ];
    })->values();
@endphp

<section class="mc-section" aria-labelledby="mc-request-title">
  <div class="mc-container" style="max-width:46rem;padding-block:clamp(1.5rem,4vw,2.75rem) 3rem">
    @include('public.partials.request-match', [
        'p' => $p,
        'matchYears' => $matchYears,
        'curriculumTypes' => $curriculumTypes,
        'matchDom' => 'mc-request',
        'matchHeading' => 'h1',
    ])
  </div>
</section>
@endsection
