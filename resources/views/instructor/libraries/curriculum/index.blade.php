@extends('layouts.app')

@section('title', __('instructor.lib_curriculum_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.lib_curriculum_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $itemsTotal = method_exists($items, 'total') ? $items->total() : $items->count();
    $coursesTotal = ($teachingCourses ?? collect())->count();
    $materialsHref = Route::has('instructor.libraries.materials.index')
        ? route('instructor.libraries.materials.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.lib_curriculum_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.curriculum_library') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.lib_curriculum_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.lib_curriculum_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($materialsHref)
                <a href="{{ $materialsHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    {{ __('instructor.materials_library') }}
                </a>
            @endif
            <span class="id-chip" style="background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.35)">
                {{ __('instructor.lib_curriculum_badge') }}
            </span>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.lib_curriculum_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-sitemap"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_curriculum_title') }}</span>
                <span class="id-kpi__value">{{ number_format($itemsTotal) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_curriculum_courses_structure') }}</span>
                <span class="id-kpi__value">{{ number_format($coursesTotal) }}</span>
            </span>
        </article>
    </section>

    <div class="id-alert id-alert--info" role="note">
        <i class="fas fa-info-circle" aria-hidden="true"></i>
        <span>{{ __('instructor.lib_curriculum_info') }}</span>
    </div>

    <section class="id-panel" aria-label="{{ __('instructor.lib_curriculum_filter') }}">
        <form method="GET" class="id-form" style="gap:12px">
            <div class="id-form-grid" style="align-items:end">
                <div class="id-field id-field--span2">
                    <label for="lib-curr-q">{{ __('common.search') }}</label>
                    <input type="search" name="q" id="lib-curr-q" value="{{ request('q') }}"
                           placeholder="{{ __('instructor.lib_curriculum_search_ph') }}"
                           class="id-input">
                </div>
                <div class="id-field">
                    <label for="lib-curr-cat">{{ __('instructor.lib_curriculum_all_categories') }}</label>
                    <select name="category_id" id="lib-curr-cat" class="id-select">
                        <option value="">{{ __('instructor.lib_curriculum_all_categories') }}</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string) request('category_id') === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="lib-curr-lang">{{ __('instructor.lib_curriculum_all_languages') }}</label>
                    <select name="language" id="lib-curr-lang" class="id-select">
                        <option value="">{{ __('instructor.lib_curriculum_all_languages') }}</option>
                        <option value="ar" @selected(request('language') === 'ar')>العربية</option>
                        <option value="en" @selected(request('language') === 'en')>English</option>
                        <option value="fr" @selected(request('language') === 'fr')>Français</option>
                    </select>
                </div>
            </div>
            <div>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                    {{ __('instructor.lib_curriculum_filter') }}
                </button>
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_curriculum_title') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_curriculum_title') }}</h2>
            @if($itemsTotal > 0)
                <span class="id-panel__badge">{{ number_format($itemsTotal) }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($items as $item)
                <a href="{{ route('instructor.libraries.curriculum.show', $item) }}" class="id-list__row" style="text-decoration:none;color:inherit">
                    <span class="id-list__ico" aria-hidden="true"><i class="fas fa-book-open"></i></span>
                    <div class="id-list__body">
                        <div class="id-list__title">{{ $item->title }}</div>
                        <div class="id-list__meta">
                            @if($item->category)
                                <span class="id-chip id-chip--muted" style="margin-inline-end:6px">{{ $item->category->name }}</span>
                            @endif
                            @if($item->subject)
                                {{ $item->subject }}
                            @endif
                            @if($item->description)
                                · {{ \Illuminate\Support\Str::limit($item->description, 80) }}
                            @endif
                        </div>
                    </div>
                    <span class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px;pointer-events:none">
                        {{ __('instructor.lib_curriculum_open') }}
                    </span>
                    <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                </a>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-sitemap"></i></span>
                    <p>{{ __('instructor.lib_curriculum_empty') }}</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($items, 'hasPages') && $items->hasPages())
            <div class="id-pager">{{ $items->links() }}</div>
        @endif
    </section>

    @if(($teachingCourses ?? collect())->isNotEmpty())
        <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_curriculum_courses_structure') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.lib_curriculum_courses_structure') }}</h2>
            </header>
            <p class="id-field__hint" style="margin:-4px 0 14px">{{ __('instructor.lib_curriculum_courses_structure_sub') }}</p>

            <div class="id-list">
                @foreach($teachingCourses as $course)
                    <div class="id-list__row">
                        <span class="id-list__ico id-list__ico--gold" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ $course->title }}</div>
                            <div class="id-list__meta">
                                {{ $course->academicSubject?->academicYear?->name ?? '—' }}
                                · {{ $course->academicSubject?->name ?? '—' }}
                                · {{ __('instructor.lib_curriculum_sections_count', ['count' => $course->sections_count]) }}
                            </div>
                        </div>
                        <div class="id-list__actions">
                            <a href="{{ route('instructor.libraries.curriculum.course', $course) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                {{ __('instructor.lib_curriculum_view_structure') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
