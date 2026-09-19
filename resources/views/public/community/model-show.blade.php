@extends('layouts.public')

@section('title', $model->title . ' - نماذج المجتمع')

@section('content')
@php $filesList = $model->files_list ?? []; @endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">نماذج المجتمع</p>
    <h1>{{ $model->title }}</h1>
    @if($model->description)<p class="mc-lead">{{ $model->description }}</p>@endif
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-detail">
      <div>
        <article class="mc-detail__panel" style="padding:1.25rem;margin-bottom:1rem"><h2>المنهجية</h2><p style="white-space:pre-line;line-height:1.8">{{ $model->methodology_steps ?: 'لم تُضف منهجية بعد.' }}</p></article>
        @if($model->performance_metrics && is_array($model->performance_metrics))
          <article class="mc-detail__panel" style="padding:1.25rem;margin-bottom:1rem"><h2>مقاييس الأداء</h2><div class="mc-filters">@foreach($model->performance_metrics as $key => $value)<span class="mc-chip">{{ $key }}: {{ is_numeric($value) ? number_format((float)$value, 4) : $value }}</span>@endforeach</div></article>
        @endif
        @if($model->usage_instructions)<article class="mc-detail__panel" style="padding:1.25rem"><h2>طريقة الاستخدام</h2><pre style="white-space:pre-wrap;overflow:auto;background:var(--mc-ink);color:#fff;padding:1rem;border-radius:12px">{{ $model->usage_instructions }}</pre></article>@endif
      </div>
      <aside class="mc-detail__panel" style="padding:1.25rem">
        <h2>الملفات ({{ count($filesList) }})</h2>
        @forelse($filesList as $index => $file)
          @php $name = is_array($file) ? ($file['original_name'] ?? basename($file['path'] ?? '')) : basename($file); @endphp
          <div class="mc-card__foot" style="padding:.7rem 0;border-bottom:1px solid var(--mc-line)"><span>{{ $name }}</span><a href="{{ route('community.models.download-file', [$model, $index]) }}" class="mc-btn mc-btn--sm mc-btn--outline">تحميل</a></div>
        @empty
          <p style="color:var(--mc-muted)">لا توجد ملفات.</p>
        @endforelse
        @if($model->dataset)<a href="{{ route('community.data.show', $model->dataset) }}" class="mc-btn mc-btn--md mc-btn--soft" style="margin-top:1rem">مجموعة البيانات المرتبطة</a>@endif
      </aside>
    </div>
    <a href="{{ route('community.models.index') }}" class="mc-btn mc-btn--md mc-btn--ghost" style="margin-top:1rem">العودة للنماذج</a>
  </div>
</section>
@endsection
