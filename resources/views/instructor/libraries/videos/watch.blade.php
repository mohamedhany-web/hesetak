@extends('layouts.app')

@section('title', $libraryVideo->title)
@section('page_title', $libraryVideo->title)

@section('content')
@php
    $locale = app()->getLocale();
    $backHref = route('instructor.libraries.videos.index');
    $badge = $isOwn ? __('instructor.lib_videos_own_badge') : __('instructor.lib_videos_academy_badge');
@endphp

<div class="id-page">
    <section class="id-hero" aria-label="{{ $libraryVideo->title }}">
        <div class="id-hero__copy">
            <p class="id-hero__kicker">{{ __('instructor.lib_videos_title') }}</p>
            <h2 class="id-hero__title">{{ $libraryVideo->title }}</h2>
            <p class="id-hero__meta">{{ $badge }}</p>
        </div>
        <div class="id-hero__actions">
            @if($isOwn)
                <a href="{{ route('instructor.libraries.videos.edit', $libraryVideo) }}" class="id-btn id-btn--gold">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                    {{ __('common.edit') }}
                </a>
            @endif
            <a href="{{ $backHref }}" class="id-btn id-btn--ghost">
                <i class="fas fa-arrow-{{ $locale === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                {{ __('instructor.back') }}
            </a>
        </div>
    </section>

    <section class="id-panel id-panel--wide" style="padding:0;overflow:hidden" aria-label="{{ $libraryVideo->title }}">
        <div style="aspect-ratio:16/9;background:#000">
            @if($embedUrl)
                <iframe
                    src="{{ $embedUrl }}"
                    title="{{ $libraryVideo->title }}"
                    style="display:block;width:100%;height:100%;border:0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen; web-share"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            @elseif($directUrl)
                <video style="display:block;width:100%;height:100%" controls playsinline preload="metadata" @if($thumbnail) poster="{{ $thumbnail }}" @endif controlslist="nodownload">
                    <source src="{{ $directUrl }}" type="{{ $libraryVideo->mime_type ?: 'video/mp4' }}">
                </video>
            @else
                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:rgba(255,255,255,.8);font-size:13px;font-weight:700">
                    {{ __('instructor.lib_videos_cannot_play') }}
                </div>
            @endif
        </div>
        @if($libraryVideo->description)
            <p style="padding:14px 16px;margin:0;font-size:13px;font-weight:600;line-height:1.7;color:#6B7A93">{{ $libraryVideo->description }}</p>
        @endif
    </section>
</div>
@endsection
