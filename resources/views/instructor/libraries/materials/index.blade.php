@extends('layouts.app')

@section('title', __('instructor.lib_materials_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.lib_materials_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $themeLocale = $locale === 'ar' ? 'ar' : 'en';
    $foldersTotal = method_exists($folders, 'count') ? $folders->count() : count($folders);
    $filesTotal = collect($folders)->sum(fn ($f) => (int) ($f->materials_count ?? 0));
    $curriculumHref = Route::has('instructor.libraries.curriculum.index')
        ? route('instructor.libraries.curriculum.index')
        : null;
    $videosHref = Route::has('instructor.libraries.videos.index')
        ? route('instructor.libraries.videos.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.lib_materials_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.materials_library') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.lib_materials_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.lib_materials_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            @if($curriculumHref)
                <a href="{{ $curriculumHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-sitemap" aria-hidden="true"></i>
                    {{ __('instructor.curriculum_library') }}
                </a>
            @endif
            @if($videosHref)
                <a href="{{ $videosHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-video" aria-hidden="true"></i>
                    {{ __('instructor.videos_for_students') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.lib_materials_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_videos_col_folder') }}</span>
                <span class="id-kpi__value">{{ number_format($foldersTotal) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_materials_col_file') }}</span>
                <span class="id-kpi__value">{{ number_format($filesTotal) }}</span>
            </span>
        </article>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="id-panel" aria-label="{{ __('instructor.lib_materials_create_folder') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_materials_create_folder') }}</h2>
        </header>

        <form method="POST" action="{{ route('instructor.libraries.materials.folders.store') }}" class="id-form">
            @csrf
            <div class="id-form-grid">
                <div class="id-field">
                    <label for="name_ar">{{ __('instructor.lib_materials_name_ar') }}</label>
                    <input type="text" name="name_ar" id="name_ar" required class="id-input"
                           placeholder="{{ __('instructor.lib_materials_name_ar') }}" value="{{ old('name_ar') }}">
                </div>
                <div class="id-field">
                    <label for="name_en">{{ __('instructor.lib_materials_name_en') }}</label>
                    <input type="text" name="name_en" id="name_en" class="id-input"
                           placeholder="{{ __('instructor.lib_materials_name_en') }}" value="{{ old('name_en') }}">
                </div>
                <div class="id-field">
                    <label for="academic_year_id">{{ __('instructor.lib_materials_year_required') }}</label>
                    <select name="academic_year_id" id="academic_year_id" required class="id-select">
                        <option value="">{{ __('instructor.lib_materials_year_required') }}</option>
                        @foreach($years as $y)
                            <option value="{{ $y->id }}" @selected((string) old('academic_year_id') === (string) $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="content_theme">{{ __('instructor.lib_videos_theme') }}</label>
                    <select name="content_theme" id="content_theme" class="id-select">
                        @foreach(\App\Support\FamilyLibraryThemes::labels($themeLocale) as $key => $themeLabel)
                            <option value="{{ $key }}" @selected(old('content_theme', 'general') === $key)>{{ $themeLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('instructor.lib_materials_create') }}
                </button>
            </div>
        </form>
    </section>

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_materials_title') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_materials_title') }}</h2>
            @if($foldersTotal > 0)
                <span class="id-panel__badge">{{ number_format($foldersTotal) }}</span>
            @endif
        </header>

        <div class="id-list">
            @forelse($folders as $folder)
                <a href="{{ route('instructor.libraries.materials.show', $folder) }}" class="id-list__row" style="text-decoration:none;color:inherit">
                    <span class="id-list__ico {{ $folder->instructor_id ? '' : 'id-list__ico--gold' }}" aria-hidden="true">
                        <i class="fas fa-folder{{ $folder->instructor_id ? '' : '-open' }}"></i>
                    </span>
                    <div class="id-list__body">
                        <div class="id-list__title">{{ $folder->displayName() }}</div>
                        <div class="id-list__meta">
                            {{ $folder->academicYear->name ?? __('instructor.lib_materials_general_year') }}
                            · {{ __('instructor.lib_materials_files_count', ['count' => (int) $folder->materials_count]) }}
                            @if(! $folder->instructor_id)
                                · <span class="id-chip id-chip--warn">{{ __('instructor.lib_materials_admin_folder') }}</span>
                            @endif
                        </div>
                    </div>
                    <i class="fas fa-chevron-{{ $locale === 'ar' ? 'left' : 'right' }} id-act__chev" aria-hidden="true"></i>
                </a>
            @empty
                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-folder-open"></i></span>
                    <p>{{ __('instructor.lib_materials_empty') }}</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
