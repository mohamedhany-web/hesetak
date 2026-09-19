@extends('layouts.app')

@section('title', $material->title ?: $material->file_name)
@section('page_title', $material->title ?: $material->file_name)

@section('content')
@php
    $locale = app()->getLocale();
    $title = $material->title ?: $material->file_name;
    $backHref = route('instructor.libraries.materials.show', $folder);
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ $folder->displayName() }}</p>
            <h2 class="id-hero__title">{{ $title }}</h2>
            <p class="id-hero__meta">
                {{ $isGame ? __('instructor.lib_materials_play_in') : __('instructor.lib_materials_view_in') }}
            </p>
        </div>
        <div class="id-hero__actions">
            <a href="{{ route('instructor.libraries.materials.download', [$folder, $material]) }}" class="id-btn id-btn--gold">
                <i class="fas fa-download" aria-hidden="true"></i>
                {{ __('instructor.download') }}
            </a>
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel id-panel--wide" style="padding:0;overflow:hidden" aria-label="{{ $title }}">
        <iframe
            src="{{ $frameUrl }}"
            title="{{ $title }}"
            style="display:block;width:100%;height:75vh;border:0;background:#fff"
            sandbox="allow-scripts allow-same-origin allow-forms"
        ></iframe>
    </section>
</div>
@endsection
