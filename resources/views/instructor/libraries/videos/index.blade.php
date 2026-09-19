@extends('layouts.app')

@section('title', __('instructor.lib_videos_title') . ' - ' . config('app.name'))
@section('page_title', __('instructor.lib_videos_title'))

@section('content')
@php
    $locale = app()->getLocale();
    $themeLocale = $locale === 'ar' ? 'ar' : 'en';
    $ownTotal = method_exists($videos, 'total') ? $videos->total() : $videos->count();
    $academyTotal = ($academyVideos ?? collect())->count();
    $foldersTotal = ($folders ?? collect())->count();
    $materialsHref = Route::has('instructor.libraries.materials.index')
        ? route('instructor.libraries.materials.index')
        : null;
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ __('instructor.lib_videos_title') }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.videos_for_students') }}</p>
            <h2 class="id-hero__title">{{ __('instructor.lib_videos_title') }}</h2>
            <p class="id-hero__meta">{{ __('instructor.lib_videos_subtitle') }}</p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.libraries.videos.create') }}" class="id-btn id-btn--gold">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ __('instructor.lib_videos_add') }}
            </a>
            @if($materialsHref)
                <a href="{{ $materialsHref }}" class="id-btn id-btn--ghost">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    {{ __('instructor.materials_library') }}
                </a>
            @endif
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(3,minmax(0,1fr))" aria-label="{{ __('instructor.lib_videos_title') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-video"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_videos_yours') }}</span>
                <span class="id-kpi__value">{{ number_format($ownTotal) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--gold" aria-hidden="true"><i class="fas fa-university"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_videos_academy') }}</span>
                <span class="id-kpi__value">{{ number_format($academyTotal) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-folder"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_videos_col_folder') }}</span>
                <span class="id-kpi__value">{{ number_format($foldersTotal) }}</span>
            </span>
        </article>
    </section>

    @if(session('error'))
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <section class="id-panel" aria-label="{{ __('instructor.lib_videos_new_folder') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_videos_new_folder') }}</h2>
        </header>

        <form method="POST" action="{{ route('instructor.libraries.videos.folders.store') }}" class="id-form">
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
                    <label for="academic_year_id">{{ __('instructor.year') }}</label>
                    <select name="academic_year_id" id="academic_year_id" class="id-select">
                        <option value="">{{ __('instructor.lib_videos_no_year') }}</option>
                        @foreach($years as $y)
                            <option value="{{ $y->id }}" @selected((string) old('academic_year_id') === (string) $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="id-field">
                    <label for="content_theme">{{ __('instructor.lib_videos_theme') }}</label>
                    <select name="content_theme" id="content_theme" class="id-select">
                        @foreach(\App\Support\FamilyLibraryThemes::labels($themeLocale) as $key => $themeLabel)
                            <option value="{{ $key }}" @selected(old('content_theme', 'kids') === $key)>{{ $themeLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <button type="submit" class="id-btn id-btn--navy">
                    <i class="fas fa-folder-plus" aria-hidden="true"></i>
                    {{ __('instructor.lib_videos_create_folder') }}
                </button>
            </div>
        </form>

        @if(($folders ?? collect())->isNotEmpty())
            <div style="margin-top:14px;display:flex;flex-wrap:wrap;gap:8px">
                @foreach($folders as $folder)
                    <span class="id-chip id-chip--muted">
                        {{ $folder->displayName() }}
                        <em style="font-style:normal;opacity:.7;margin-inline-start:4px">{{ (int) $folder->library_videos_count }}</em>
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    @if(($academyVideos ?? collect())->isNotEmpty())
        <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_videos_academy') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.lib_videos_academy') }}</h2>
                <span class="id-panel__badge">{{ number_format($academyTotal) }}</span>
            </header>
            <p class="id-field__hint" style="margin:-4px 0 14px">{{ __('instructor.lib_videos_academy_sub') }}</p>

            <div class="id-table-wrap">
                <table class="id-table">
                    <thead>
                        <tr>
                            <th>{{ __('instructor.lib_videos_col_title') }}</th>
                            <th>{{ __('instructor.lib_videos_col_folder') }}</th>
                            <th>{{ __('instructor.lib_videos_col_source') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($academyVideos as $video)
                            <tr>
                                <td><strong>{{ $video->title }}</strong></td>
                                <td><span class="muted">{{ $video->folder?->displayName() ?: '—' }}</span></td>
                                <td>{{ $video->sourceLabel() }}</td>
                                <td class="id-table__end">
                                    <a href="{{ route('instructor.libraries.videos.watch', $video) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">
                                        {{ __('instructor.lib_videos_watch') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_videos_yours') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_videos_yours') }}</h2>
            @if($ownTotal > 0)
                <span class="id-panel__badge">{{ number_format($ownTotal) }}</span>
            @endif
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.lib_videos_col_title') }}</th>
                        <th>{{ __('instructor.lib_videos_col_folder') }}</th>
                        <th>{{ __('instructor.lib_videos_col_source') }}</th>
                        <th>{{ __('instructor.lib_videos_col_publish') }}</th>
                        <th>{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($videos as $video)
                        <tr>
                            <td><strong>{{ $video->title }}</strong></td>
                            <td><span class="muted">{{ $video->folder?->displayName() ?: '—' }}</span></td>
                            <td>{{ $video->sourceLabel() }}</td>
                            <td>
                                <form method="POST" action="{{ route('instructor.libraries.videos.toggle', $video) }}" style="margin:0">
                                    @csrf
                                    <button type="submit" class="id-chip {{ $video->is_published ? 'id-chip--ok' : 'id-chip--muted' }}" style="cursor:pointer;border:0">
                                        {{ $video->is_published ? __('instructor.lib_videos_published') : __('instructor.lib_videos_draft') }}
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div style="display:flex;flex-wrap:wrap;gap:8px">
                                    <a href="{{ route('instructor.libraries.videos.watch', $video) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.lib_videos_watch') }}</a>
                                    <a href="{{ route('instructor.libraries.videos.edit', $video) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.edit') }}</a>
                                    <form method="POST" action="{{ route('instructor.libraries.videos.destroy', $video) }}" onsubmit="return confirm(@json(__('instructor.lib_videos_confirm_delete')))" style="margin:0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="id-btn id-btn--danger" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-video"></i></span>
                                    <p>{{ __('instructor.lib_videos_empty') }}</p>
                                    <div class="id-empty__actions">
                                        <a href="{{ route('instructor.libraries.videos.create') }}" class="id-btn id-btn--navy">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                            {{ __('instructor.lib_videos_add') }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($videos, 'hasPages') && $videos->hasPages())
            <div class="id-pager">{{ $videos->links() }}</div>
        @endif
    </section>
</div>
@endsection
