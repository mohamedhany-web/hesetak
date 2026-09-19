@extends('layouts.public')

@section('title', $media->title . ' - حصتك')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($media->description ?? ''), 160))
@section('canonical_url', route('public.media.show', $media))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">معرض حصتك</p>
    <h1>{{ $media->title }}</h1>
    @if($media->description)<p class="mc-lead">{{ $media->description }}</p>@endif
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-detail">
      <article class="mc-detail__panel">
        @if($media->type === 'image')
          <img src="{{ storage_asset($media->file_path) }}" alt="{{ $media->title }}" style="width:100%;display:block">
        @elseif($media->type === 'video')
          <video controls style="width:100%;display:block">
            <source src="{{ storage_asset($media->file_path) }}" type="{{ $media->mime_type }}">
            متصفحك لا يدعم تشغيل الفيديو.
          </video>
        @else
          <div class="mc-empty">
            <p><i class="fas fa-file-lines"></i> {{ $media->file_name }}</p>
            <a href="{{ storage_asset($media->file_path) }}" download class="mc-btn mc-btn--md mc-btn--primary">تحميل الملف</a>
          </div>
        @endif
      </article>
      <aside class="mc-detail__panel" style="padding:1.25rem">
        <h2>تفاصيل الملف</h2>
        <div class="mc-card__meta" style="display:grid">
          <span><i class="fas fa-eye"></i> {{ $media->views_count }} مشاهدة</span>
          <span><i class="fas fa-file"></i> {{ $media->file_size_formatted }}</span>
          <span><i class="fas fa-calendar"></i> {{ $media->created_at->format('Y-m-d') }}</span>
        </div>
        <a href="{{ route('public.media.index') }}" class="mc-btn mc-btn--md mc-btn--outline" style="margin-top:1rem">العودة للمعرض</a>
      </aside>
    </div>
  </div>
</section>
@endsection
