@extends('layouts.app')

@section('title', $folder->displayName())
@section('page_title', $folder->displayName())

@section('content')
@php
    $locale = app()->getLocale();
    $themeLocale = $locale === 'ar' ? 'ar' : 'en';
    $canManageFolder = $canManage ?? true;
    $materialsCount = $folder->materials->count();
    $visibleCount = $folder->materials->where('is_visible_to_student', true)->count();
    $backHref = route('instructor.libraries.materials.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $folder->displayName() }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.lib_materials_title') }}</p>
            <h2 class="id-hero__title">{{ $folder->displayName() }}</h2>
            <p class="id-hero__meta">
                {{ $folder->academicYear?->name ?? __('instructor.lib_materials_general_year') }}
                · {{ __('instructor.lib_materials_files_count', ['count' => $materialsCount]) }}
            </p>
        </div>
        <div class="id-hero__actions">
            @unless($canManageFolder)
                <span class="id-chip id-chip--warn">{{ __('instructor.lib_materials_admin_folder') }}</span>
            @endunless
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-kpis" style="grid-template-columns:repeat(2,minmax(0,1fr))" aria-label="{{ __('instructor.lib_materials_col_file') }}">
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_materials_col_file') }}</span>
                <span class="id-kpi__value">{{ number_format($materialsCount) }}</span>
            </span>
        </article>
        <article class="id-kpi" style="cursor:default">
            <span class="id-kpi__icon id-kpi__icon--teal" aria-hidden="true"><i class="fas fa-eye"></i></span>
            <span class="id-kpi__body">
                <span class="id-kpi__label">{{ __('instructor.lib_materials_visible') }}</span>
                <span class="id-kpi__value">{{ number_format($visibleCount) }}</span>
            </span>
        </article>
    </section>

    @if($errors->any())
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    @if($canManageFolder)
        <section class="id-panel" aria-label="{{ __('instructor.lib_materials_upload_title') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.lib_materials_upload_title') }}</h2>
            </header>

            <form method="POST" action="{{ route('instructor.libraries.materials.upload', $folder) }}" enctype="multipart/form-data" class="id-form">
                @csrf
                <div class="id-form-grid">
                    <div class="id-field">
                        <label for="title">{{ __('instructor.lib_materials_title_ph') }}</label>
                        <input type="text" name="title" id="title" class="id-input"
                               placeholder="{{ __('instructor.lib_materials_title_ph') }}" value="{{ old('title') }}">
                    </div>
                    <div class="id-field">
                        <label for="description">{{ __('instructor.lib_materials_desc_ph') }}</label>
                        <input type="text" name="description" id="description" class="id-input"
                               placeholder="{{ __('instructor.lib_materials_desc_ph') }}" value="{{ old('description') }}">
                    </div>
                    <div class="id-field">
                        <label for="content_theme">{{ __('instructor.lib_videos_theme') }}</label>
                        <select name="content_theme" id="content_theme" class="id-select">
                            @foreach(\App\Support\FamilyLibraryThemes::labels($themeLocale) as $key => $themeLabel)
                                <option value="{{ $key }}" @selected(old('content_theme', $folder->content_theme ?: 'general') === $key)>{{ $themeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="id-field">
                        <label for="experience_mode">{{ __('instructor.lib_materials_col_theme') }}</label>
                        <select name="experience_mode" id="experience_mode" class="id-select">
                            <option value="download" @selected(old('experience_mode') === 'download')>{{ __('instructor.lib_materials_mode_download') }}</option>
                            <option value="view" @selected(old('experience_mode') === 'view')>{{ __('instructor.lib_materials_mode_view') }}</option>
                            <option value="play" @selected(old('experience_mode') === 'play')>{{ __('instructor.lib_materials_mode_play') }}</option>
                        </select>
                    </div>
                    <div class="id-field id-field--span2">
                        <label for="file">{{ __('instructor.lib_materials_col_file') }}</label>
                        <input type="file" name="file" id="file" required
                               accept="{{ \App\Support\FamilyLibraryThemes::materialAcceptAttr() }}"
                               class="id-input" style="padding-top:10px;padding-bottom:10px">
                    </div>
                    <div class="id-field id-field--span2" style="justify-content:center">
                        <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#3A4A63;cursor:pointer">
                            <input type="checkbox" name="is_visible_to_student" value="1" @checked(old('is_visible_to_student', true))>
                            {{ __('instructor.lib_materials_visible_student') }}
                        </label>
                    </div>
                </div>
                <div>
                    <button type="submit" class="id-btn id-btn--navy">
                        <i class="fas fa-upload" aria-hidden="true"></i>
                        {{ __('instructor.lib_materials_upload') }}
                    </button>
                </div>
            </form>
        </section>
    @else
        <div class="id-alert id-alert--info" role="note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <span>{{ __('instructor.lib_materials_admin_readonly') }}</span>
        </div>
    @endif

    <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_materials_col_file') }}">
        <header class="id-panel__head">
            <h2>{{ __('instructor.lib_materials_col_file') }}</h2>
            @if($materialsCount > 0)
                <span class="id-panel__badge">{{ number_format($materialsCount) }}</span>
            @endif
        </header>

        <div class="id-table-wrap">
            <table class="id-table">
                <thead>
                    <tr>
                        <th>{{ __('instructor.lib_materials_col_file') }}</th>
                        <th>{{ __('instructor.lib_materials_col_theme') }}</th>
                        <th>{{ __('instructor.lib_materials_col_visibility') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folder->materials as $m)
                        <tr>
                            <td>
                                <strong>{{ $m->title ?: $m->file_name }}</strong>
                                <div class="muted" style="font-size:12px;margin-top:2px">{{ $m->file_name }}</div>
                            </td>
                            <td>
                                <span class="muted" style="font-size:12px">
                                    {{ $m->themeLabel($themeLocale) }} · {{ $m->experience_mode ?: 'download' }}
                                </span>
                            </td>
                            <td>
                                <span class="id-chip {{ $m->is_visible_to_student ? 'id-chip--ok' : 'id-chip--muted' }}">
                                    {{ $m->is_visible_to_student ? __('instructor.lib_materials_visible') : __('instructor.lib_materials_hidden') }}
                                </span>
                            </td>
                            <td class="id-table__end">
                                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px">
                                    @if($m->file_path)
                                        @php
                                            $mode = $m->experience_mode ?: \App\Support\FamilyLibraryThemes::detectExperienceMode($m->file_name, $m->content_theme);
                                            $canPlay = \App\Support\FamilyLibraryThemes::isPlayableInPlatform($m->file_name, $mode);
                                        @endphp
                                        @if($canPlay)
                                            <a href="{{ route('instructor.libraries.materials.experience', [$folder, $m]) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.view') }}</a>
                                        @endif
                                        <a href="{{ route('instructor.libraries.materials.download', [$folder, $m]) }}" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.download') }}</a>
                                    @endif
                                    @if($canManageFolder)
                                        <form method="POST" action="{{ route('instructor.libraries.materials.destroy', [$folder, $m]) }}" onsubmit="return confirm(@json(__('instructor.lib_materials_confirm_delete')))" style="margin:0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="id-btn id-btn--danger" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="id-empty" style="border:0;background:transparent;padding:28px 8px">
                                    <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-file"></i></span>
                                    <p>{{ __('instructor.lib_materials_empty_files') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
