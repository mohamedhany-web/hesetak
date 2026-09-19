@extends('layouts.public')

@section('title', $dataset->title . ' - مجموعات البيانات')

@section('content')
@php $filesList = $dataset->files_list ?? []; @endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">مجموعات البيانات</p>
    <h1>{{ $dataset->title }}</h1>
    <div class="mc-card__meta">
      @if($dataset->category)<span>{{ $dataset->category_label }}</span>@endif
      @if($dataset->creator)<span>{{ $dataset->creator->name }}</span>@endif
      @if($dataset->file_size)<span>{{ $dataset->file_size }}</span>@endif
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-detail">
      <article class="mc-detail__panel" style="padding:1.25rem">
        <h2>وصف المورد</h2>
        <p style="white-space:pre-line;line-height:1.9">{{ $dataset->description ?: 'لم يُضف وصف بعد.' }}</p>
        @if($dataset->file_url)<a href="{{ $dataset->file_url }}" target="_blank" rel="noopener" class="mc-btn mc-btn--md mc-btn--primary">فتح رابط التحميل</a>@endif
      </article>
      <aside class="mc-detail__panel" style="padding:1.25rem">
        <h2>الملفات ({{ count($filesList) }})</h2>
        @if(count($filesList) > 1)<a href="{{ route('community.data.download-all', $dataset) }}" class="mc-btn mc-btn--md mc-btn--soft" style="margin-bottom:1rem">تحميل الكل ZIP</a>@endif
        @forelse($filesList as $index => $file)
          @php $name = $file['original_name'] ?? basename($file['path'] ?? ''); @endphp
          <div class="mc-card__foot" style="padding:.7rem 0;border-bottom:1px solid var(--mc-line)"><span>{{ $name }} @if(!empty($file['size']))<small>{{ $file['size'] }}</small>@endif</span><a href="{{ route('community.data.download-file', [$dataset, $index]) }}" class="mc-btn mc-btn--sm mc-btn--outline">تحميل</a></div>
        @empty
          <p style="color:var(--mc-muted)">لا توجد ملفات مرفقة.</p>
        @endforelse
      </aside>
    </div>
    <a href="{{ route('community.data.index') }}" class="mc-btn mc-btn--md mc-btn--ghost" style="margin-top:1rem">العودة لمجموعات البيانات</a>
  </div>
</section>
@endsection
