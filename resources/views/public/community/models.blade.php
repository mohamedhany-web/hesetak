@extends('layouts.public')

@section('title', 'نماذج المجتمع - حصتك')

@section('content')
<section class="mc-dir-head">
  <div class="mc-container">
    <div class="mc-dir-head__top"><div class="mc-dir-head__copy"><p class="mc-eyebrow">موارد المجتمع</p><h1>نماذج منشورة</h1><p>تصفح الملفات والمنهجيات التي شاركها مساهمو مجتمع حصتك.</p></div></div>
    <form action="{{ route('community.models.index') }}" method="GET" class="mc-card" style="padding:1rem;display:flex;gap:.65rem;flex-wrap:wrap">
      <input type="search" name="q" value="{{ request('q', $currentSearch ?? '') }}" class="mc-input" style="flex:1" placeholder="ابحث في النماذج">
      <button type="submit" class="mc-btn mc-btn--md mc-btn--primary">بحث</button>
    </form>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if($models->isNotEmpty())
      <div class="mc-grid mc-grid--4">
        @foreach($models as $model)
          <a href="{{ route('community.models.show', $model) }}" class="mc-card">
            <div class="mc-card__media"><span class="mc-track__icon" style="position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%)"><i class="fas fa-cubes"></i></span></div>
            <div class="mc-card__body"><h2 class="mc-card__title">{{ $model->title }}</h2><div class="mc-card__meta">@if($model->creator)<span>{{ $model->creator->name }}</span>@endif @if($model->license)<span>{{ $model->license }}</span>@endif @if($model->file_size)<span>{{ $model->file_size }}</span>@endif</div><div class="mc-card__foot"><span><i class="fas fa-download"></i> {{ number_format($model->downloads_count) }}</span><span class="mc-track__cta">عرض ←</span></div></div>
          </a>
        @endforeach
      </div>
      @if($models->hasPages())<div style="margin-top:1.5rem">{{ $models->withQueryString()->links() }}</div>@endif
    @else
      <div class="mc-empty"><p>{{ request('q') ? 'لا توجد نتائج مطابقة.' : 'لا توجد نماذج منشورة حالياً.' }}</p><a href="{{ route('community.models.index') }}" class="mc-btn mc-btn--md mc-btn--outline">عرض الكل</a></div>
    @endif
  </div>
</section>
@endsection
