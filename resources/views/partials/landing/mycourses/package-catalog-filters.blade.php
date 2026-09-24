{{-- فلترة كتالوج الباقات: مرحلة + منهج + مادة --}}
@php
  $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
  $catalog = $packageCatalog ?? [];
  $years = $catalog['years'] ?? collect();
  $subjects = $catalog['subjects'] ?? collect();
  $tracks = $catalog['tracks'] ?? [];
  $selYear = $catalog['selected_year_id'] ?? null;
  $selSubject = $catalog['selected_subject_id'] ?? null;
  $selTrack = $catalog['selected_curriculum_type'] ?? 'saudi';
  $filterId = $filterId ?? 'mcPkgFilter';
@endphp

@if($years->isNotEmpty() || count($tracks) > 0)
<nav class="mc-curr-actions" id="{{ $filterId }}" style="flex-wrap:wrap;gap:.5rem;margin-bottom:1.25rem" aria-label="{{ $isRtl ? 'تصفية الباقات' : 'Filter packages' }}">
  @foreach($years as $y)
    <a href="?year={{ $y->id }}&curriculum_type={{ urlencode($selTrack) }}{{ $selSubject ? '&subject='.$selSubject : '' }}#{{ $anchor ?? 'packages' }}"
       class="mc-btn mc-btn--sm {{ (int)$selYear === (int)$y->id ? 'mc-btn--primary' : 'mc-btn--outline' }}">{{ $y->name }}</a>
  @endforeach
  @foreach($tracks as $key => $meta)
    <a href="?year={{ $selYear }}&curriculum_type={{ urlencode($key) }}{{ $selSubject ? '&subject='.$selSubject : '' }}#{{ $anchor ?? 'packages' }}"
       class="mc-btn mc-btn--sm {{ $selTrack === $key ? 'mc-btn--secondary' : 'mc-btn--soft' }}">{{ $meta['label'] }}</a>
  @endforeach
</nav>
@if($subjects->isNotEmpty())
<nav class="mc-curr-actions" style="flex-wrap:wrap;gap:.4rem;margin-bottom:1rem" aria-label="{{ $isRtl ? 'المواد' : 'Subjects' }}">
  <a href="?year={{ $selYear }}&curriculum_type={{ urlencode($selTrack) }}#{{ $anchor ?? 'packages' }}"
     class="mc-btn mc-btn--sm {{ !$selSubject ? 'mc-btn--primary' : 'mc-btn--outline' }}">{{ $isRtl ? 'كل المواد' : 'All subjects' }}</a>
  @foreach($subjects as $s)
    <a href="?year={{ $selYear }}&subject={{ $s->id }}&curriculum_type={{ urlencode($selTrack) }}#{{ $anchor ?? 'packages' }}"
       class="mc-btn mc-btn--sm {{ (int)$selSubject === (int)$s->id ? 'mc-btn--primary' : 'mc-btn--outline' }}">{{ $s->name }}</a>
  @endforeach
</nav>
@endif
@endif
