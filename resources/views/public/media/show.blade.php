@extends('layouts.public')

@section('title', ($media->title ?? 'مقال') . ' - حصتك')
@section('meta_description', Str::limit(strip_tags($media->excerpt ?? $media->content ?? ''), 160))
@section('canonical_url', route('public.media.show', $media))

@section('content')
<!-- Hero Section -->
<section class="hero-gradient min-h-[40vh] flex items-center relative overflow-hidden pt-28">
    <div class="container mx-auto px-4 text-center relative z-10">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight mb-4 animate-fade-in">
            {{ $media->title }}
        </h1>
    </div>
</section>

<!-- Media Content -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <a href="{{ route('public.media.index') }}" class="inline-flex items-center text-sky-600 hover:text-sky-700 mb-6 btn-outline">
                <i class="fas fa-arrow-right ml-2"></i>
                العودة للمعرض
            </a>

            <!-- Media Content -->
            <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-200 card-hover">
                @if($media->description)
                <p class="text-gray-600 mb-6 text-lg">{{ $media->description }}</p>
                @endif

                @if($media->type == 'image')
                <div class="mb-6">
                    <img src="{{ storage_asset($media->file_path) }}" alt="{{ $media->title }}" class="w-full rounded-lg shadow-lg">
                </div>
                @elseif($media->type == 'video')
                <div class="mb-6">
                    <video controls class="w-full rounded-lg shadow-lg">
                        <source src="{{ storage_asset($media->file_path) }}" type="{{ $media->mime_type }}">
                        متصفحك لا يدعم تشغيل الفيديو
                    </video>
                </div>
                @else
                <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg p-12 text-center mb-6 card-hover">
                    <i class="fas fa-file-alt text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-700 mb-4 font-semibold">{{ $media->file_name }}</p>
                    <a href="{{ storage_asset($media->file_path) }}" download class="btn-primary">
                        <i class="fas fa-download ml-2"></i>
                        تحميل الملف
                    </a>
                </div>
                @endif

                <div class="flex items-center justify-between text-sm text-gray-600 border-t border-gray-200 pt-4 flex-wrap gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-eye ml-2"></i>
                        <span>{{ $media->views_count }} مشاهدة</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-file ml-2"></i>
                        <span>{{ $media->file_size_formatted }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-calendar ml-2"></i>
                        <span>{{ $media->created_at->format('Y-m-d') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


