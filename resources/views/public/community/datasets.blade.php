@extends('layouts.public')

@section('title', 'مجموعات البيانات - حصتك')

@section('content')
<section class="mc-dir-head">
  <div class="mc-container">
    <div class="mc-dir-head__top"><div class="mc-dir-head__copy"><p class="mc-eyebrow">موارد المجتمع</p><h1>مجموعات البيانات</h1><p>استكشف موارد منشورة للتعلم والبحث والتجربة.</p></div></div>
    <form action="{{ route('community.data.index') }}" method="GET" class="mc-card" style="padding:1rem">
      <div style="display:flex;gap:.65rem;flex-wrap:wrap"><input type="search" name="q" value="{{ request('q', $currentSearch ?? '') }}" class="mc-input" style="flex:1" placeholder="ابحث في مجموعات البيانات"><button type="submit" class="mc-btn mc-btn--md mc-btn--primary">بحث</button></div>
      <div class="mc-filters" style="margin:1rem 0 0">
        <a href="{{ route('community.data.index', ['q' => request('q')]) }}" class="mc-chip {{ empty($currentCategory) ? 'is-on' : '' }}">الكل</a>
        @foreach($categoriesWithCount ?? [] as $category)<a href="{{ route('community.data.index', ['category' => $category['key'], 'q' => request('q')]) }}" class="mc-chip {{ ($currentCategory ?? '') === $category['key'] ? 'is-on' : '' }}">{{ $category['label'] }} ({{ $category['count'] }})</a>@endforeach
      </div>
    </form>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    @if($datasets->isNotEmpty())
      <div class="mc-grid mc-grid--4">
        @foreach($datasets as $dataset)
          <a href="{{ route('community.data.show', $dataset) }}" class="mc-card">
            <div class="mc-card__media"><span class="mc-track__icon" style="position:absolute;inset:50% auto auto 50%;transform:translate(-50%,-50%)"><i class="fas fa-database"></i></span></div>
            <div class="mc-card__body"><h2 class="mc-card__title">{{ $dataset->title }}</h2><div class="mc-card__meta">@if($dataset->creator)<span>{{ $dataset->creator->name }}</span>@endif @if($dataset->category)<span>{{ $dataset->category_label }}</span>@endif @if($dataset->file_size)<span>{{ $dataset->file_size }}</span>@endif</div><div class="mc-card__foot"><span><i class="fas fa-download"></i> {{ number_format($dataset->downloads_count) }}</span><span class="mc-track__cta">عرض ←</span></div></div>
          </a>
        @endforeach
      </div>
      @if($datasets->hasPages())<div style="margin-top:1.5rem">{{ $datasets->withQueryString()->links() }}</div>@endif
    @else
      <div class="mc-empty"><p>{{ request('q') || request('category') ? 'لا توجد نتائج مطابقة.' : 'لا توجد مجموعات بيانات منشورة حالياً.' }}</p><a href="{{ route('community.data.index') }}" class="mc-btn mc-btn--md mc-btn--outline">عرض الكل</a></div>
    @endif
  </div>
</section>
@endsection
