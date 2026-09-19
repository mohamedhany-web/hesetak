@extends('layouts.public')

@section('title', 'معرض حصتك')
@section('meta_description', 'صور وفيديوهات وملفات من تجربة التعلم أونلاين على حصتك.')
@section('canonical_url', url('/media'))

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">المعرض</p>
    <h1>من تجربة التعلم على حصتك</h1>
    <p class="mc-lead">استكشف مواد مرئية وملفات مرتبطة بالدروس الفردية والمناهج والكورسات.</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <form method="GET" class="mc-card" style="padding:1.25rem;margin-bottom:1.5rem">
      <div class="mc-grid mc-grid--3">
        <div class="mc-field">
          <label for="media-type">النوع</label>
          <select id="media-type" name="type" class="mc-select">
            <option value="">الكل</option>
            <option value="image" @selected(request('type') === 'image')>صور</option>
            <option value="video" @selected(request('type') === 'video')>فيديوهات</option>
            <option value="document" @selected(request('type') === 'document')>ملفات</option>
          </select>
        </div>
        <div class="mc-field">
          <label for="media-category">الفئة</label>
          <select id="media-category" name="category" class="mc-select">
            <option value="">الكل</option>
            @foreach($categories as $category)
              <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
            @endforeach
          </select>
        </div>
        <div class="mc-field">
          <label for="media-search">البحث</label>
          <input id="media-search" type="search" name="search" value="{{ request('search') }}" class="mc-input" placeholder="ابحث في المعرض">
        </div>
      </div>
      <button type="submit" class="mc-btn mc-btn--md mc-btn--primary"><i class="fas fa-search"></i> بحث</button>
    </form>

    <div class="mc-grid mc-grid--4">
      @forelse($media as $item)
        <a href="{{ route('public.media.show', $item) }}" class="mc-card">
          <div class="mc-card__media">
            @if($item->type === 'image')
              <img src="{{ storage_asset($item->file_path) }}" alt="{{ $item->title }}" loading="lazy">
            @elseif($item->type === 'video' && $item->thumbnail_path)
              <img src="{{ storage_asset($item->thumbnail_path) }}" alt="{{ $item->title }}" loading="lazy">
            @else
              <span class="mc-track__icon" style="position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%)"><i class="fas {{ $item->type === 'video' ? 'fa-circle-play' : 'fa-file-lines' }}"></i></span>
            @endif
          </div>
          <div class="mc-card__body">
            <h2 class="mc-card__title">{{ $item->title }}</h2>
            <div class="mc-card__meta"><span><i class="fas fa-eye"></i> {{ $item->views_count }}</span></div>
          </div>
        </a>
      @empty
        <div class="mc-empty" style="grid-column:1/-1">
          <p>لا توجد مواد متاحة بهذه الفلاتر حالياً.</p>
          <a href="{{ route('public.courses') }}" class="mc-btn mc-btn--md mc-btn--primary">استكشف الكورسات</a>
        </div>
      @endforelse
    </div>
    <div style="margin-top:1.5rem">{{ $media->withQueryString()->links() }}</div>
  </div>
</section>
@endsection
