@extends('layouts.admin')

@section('title', $video->title)
@section('page_title', $video->title)

@section('content')
<div class="space-y-5">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-muted"><a href="{{ route('admin.libraries.videos.index') }}" class="hover:text-accent">مكتبة الفيديوهات</a> · معاينة</p>
            <h2 class="mt-1 text-2xl font-semibold text-ink">{{ $video->title }}</h2>
            <p class="mt-1 text-sm text-muted">
                {{ $video->audienceLabel() }}
                · {{ $video->folder?->displayName() ?: 'بدون مجلد' }}
                · {{ $video->is_published ? 'منشور' : 'مسودة' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.libraries.videos.edit', $video) }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl border border-line px-4 text-sm">تعديل</a>
            <a href="{{ route('admin.libraries.videos.index') }}" class="btn-press inline-flex h-9 items-center gap-2 rounded-xl bg-ink px-4 text-sm font-medium text-white">رجوع</a>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-line bg-surface shadow-soft">
        <div style="aspect-ratio:16/9;background:#000">
            @if($embedUrl)
                <iframe
                    src="{{ $embedUrl }}"
                    title="{{ $video->title }}"
                    style="display:block;width:100%;height:100%;border:0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen; web-share"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            @elseif($directUrl)
                <video style="display:block;width:100%;height:100%" controls playsinline preload="metadata" controlslist="nodownload">
                    <source src="{{ $directUrl }}" type="{{ $video->mime_type ?: 'video/mp4' }}">
                </video>
            @else
                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:rgba(255,255,255,.85);font-weight:700">
                    تعذّر تشغيل الفيديو — تحقق من الرابط أو من ملف Cloudflare.
                </div>
            @endif
        </div>
        @if($video->description)
            <p class="px-4 py-3 text-sm text-muted">{{ $video->description }}</p>
        @endif
    </section>
</div>
@endsection
