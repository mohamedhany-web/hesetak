@extends('layouts.app')

@section('title', __('instructor.lib_curriculum_title') . ': ' . $course->title)
@section('page_title', $course->title)

@section('content')
@php
    $locale = app()->getLocale();
    $sectionsCount = $course->sections->count();
    $itemsCount = $course->sections->sum(fn ($s) => $s->items->count());
    $lecturesCount = $course->lectures->count();
    $backHref = route('instructor.libraries.curriculum.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $course->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.lib_curriculum_courses_structure') }}</p>
            <h2 class="id-hero__title">{{ $course->title }}</h2>
            <p class="id-hero__meta">
                {{ $course->academicSubject?->academicYear?->name }}
                @if($course->academicSubject?->name)
                    · {{ $course->academicSubject->name }}
                @endif
                · {{ __('instructor.lib_curriculum_course_sub') }}
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))" aria-label="{{ __('instructor.lib_curriculum_course_sub') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-folder"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_curriculum_kpi_sections') }}</span>
                <span class="id-kpi__value">{{ number_format($sectionsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-list"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_curriculum_kpi_items') }}</span>
                <span class="id-kpi__value">{{ number_format($itemsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-chalkboard-teacher"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_curriculum_kpi_lectures') }}</span>
                <span class="id-kpi__value">{{ number_format($lecturesCount) }}</span>
            </span>
        </article>
    </section>

    @forelse($course->sections as $section)
        <section class="id-panel id-panel--wide">
            <header class="id-panel__head">
                <h2>{{ $section->title }}</h2>
                <span class="id-panel__badge">{{ $section->items->count() }}</span>
            </header>
            @if($section->description)
                <p class="id-field__hint" style="margin:-4px 0 14px">{{ $section->description }}</p>
            @endif

            <div class="id-list">
                @forelse($section->items as $item)
                    @php
                        $related = $item->item;
                        $label = $related->title
                            ?? $related->name
                            ?? (class_basename((string) $item->item_type).' #'.$item->item_id);
                    @endphp
                    <article class="id-list__row">
                        <span class="id-list__ico" aria-hidden="true"><i class="fas fa-puzzle-piece"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ $label }}</div>
                            <div class="id-list__meta">
                                <span class="id-chip id-chip--muted" style="margin-inline-end:6px;text-transform:uppercase">{{ class_basename((string) $item->item_type) }}</span>
                                {{ __('instructor.lib_curriculum_order', ['order' => $item->order]) }}
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="id-empty" style="border:0;background:transparent;padding:20px 8px">
                        <p>{{ __('instructor.lib_curriculum_no_section_items') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="id-empty">
            <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
            <p>{{ __('instructor.lib_curriculum_no_sections') }}</p>
        </div>
    @endforelse
</div>
@endsection
