@extends('layouts.app')

@section('title', $item->title . ' — ' . __('instructor.lib_curriculum_title'))
@section('page_title', $item->title)

@section('content')
@php
    $locale = app()->getLocale();
    $hasSections = isset($sectionTree) && $sectionTree->isNotEmpty();
    $hasLegacyFiles = $item->files && $item->files->isNotEmpty();
    $hasHtmlContent = filled($item->content ?? null);
    $backHref = route('instructor.libraries.curriculum.index');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $item->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.lib_curriculum_title') }}</p>
            <h2 class="id-hero__title">{{ $item->title }}</h2>
            <p class="id-hero__meta">
                @if($item->category)
                    {{ $item->category->name }}
                @endif
                @if($item->subject)
                    @if($item->category) · @endif{{ $item->subject }}
                @endif
                @if($item->description)
                    @if($item->category || $item->subject) · @endif{{ \Illuminate\Support\Str::limit($item->description, 90) }}
                @endif
                @unless($item->category || $item->subject || $item->description)
                    {{ __('instructor.lib_curriculum_subtitle') }}
                @endunless
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    @if(session('error'))
        <div class="id-alert id-alert--err" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($hasSections)
        <div style="display:flex;flex-direction:column;gap:12px">
            @include('instructor.libraries.curriculum._section-node', ['sections' => $sectionTree, 'item' => $item, 'depth' => 0])
        </div>
    @endif

    @if($hasLegacyFiles)
        <section class="id-panel id-panel--wide" aria-label="{{ __('instructor.lib_curriculum_legacy_files') }}">
            <header class="id-panel__head">
                <h2>{{ __('instructor.lib_curriculum_legacy_files') }}</h2>
            </header>
            <div class="id-list">
                @foreach($item->files as $file)
                    <div class="id-list__row">
                        <span class="id-list__ico id-list__ico--teal" aria-hidden="true"><i class="fas fa-file"></i></span>
                        <div class="id-list__body">
                            <div class="id-list__title">{{ $file->label ?: $file->file_type }}</div>
                            <div class="id-list__meta">{{ strtoupper((string) $file->file_type) }}</div>
                        </div>
                        <div class="id-list__actions">
                            @if($file->file_type === 'html')
                                <a href="{{ route('curriculum-library.file.view', [$item, $file]) }}" target="_blank" rel="noopener" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.view') }}</a>
                            @elseif($file->file_type === 'presentation')
                                <a href="{{ route('curriculum-library.file.presentation', [$item, $file]) }}" target="_blank" rel="noopener" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.lib_curriculum_interactive_view') }}</a>
                            @elseif($file->file_type === 'pdf')
                                <a href="{{ route('curriculum-library.file.pdf', [$item, $file]) }}" target="_blank" rel="noopener" class="id-btn id-btn--outline" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('common.view') }}</a>
                                <a href="{{ route('curriculum-library.file.download', [$item, $file]) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.download') }}</a>
                            @else
                                <a href="{{ route('curriculum-library.file.download', [$item, $file]) }}" class="id-btn id-btn--navy" style="min-height:34px;padding:0 12px;font-size:12px">{{ __('instructor.download') }}</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($hasHtmlContent)
        <section class="id-panel" aria-label="{{ $item->title }}">
            <div class="id-prose" style="font-size:14px;line-height:1.8;color:#3A4A63">{!! $item->content !!}</div>
        </section>
    @endif

    @if(! $hasSections && ! $hasLegacyFiles && ! $hasHtmlContent)
        <div class="id-empty">
            <span class="id-empty__mark" aria-hidden="true"><i class="fas fa-inbox"></i></span>
            <p>{{ __('instructor.lib_curriculum_empty_content') }}</p>
        </div>
    @endif
</div>
@endsection
