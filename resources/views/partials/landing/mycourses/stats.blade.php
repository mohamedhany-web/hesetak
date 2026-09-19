<section class="mc-trust" aria-label="{{ app()->getLocale() === 'ar' ? 'ثقة المنصة' : 'Trust signals' }}">
  <div class="mc-container">
    @php $trustStats = $homeTrustStats ?? []; @endphp
    @if(!empty($trustStats))
      <ul class="mc-trust__list">
        @foreach($trustStats as $stat)
          <li class="mc-trust__item">
            <span class="mc-trust__num">
              @if(!empty($stat['suffix']))
                <span class="mc-trust__star" aria-hidden="true">{{ $stat['suffix'] }}</span>
              @endif
              {{ $stat['num'] }}
            </span>
            <span class="mc-trust__label">{{ $stat['label'] }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </div>
</section>
