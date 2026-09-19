<section class="mc-section" id="best-selling">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ app()->getLocale() === 'ar' ? 'الأكثر طلبًا' : 'Best selling' }}</p>
        <h2 class="mc-title">{{ app()->getLocale() === 'ar' ? 'باقات الحصص الفردية' : 'Private lesson packages' }}</h2>
        <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'رصيد ساعات واضح، جاهز للحجز مع أي معلم معتمد.' : 'Clear hour credits, ready to book with any approved teacher.' }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.pricing') }}">{{ __('landing.mc.packages.more') }} →</a>
    </div>
    @php $packages = $homePackages ?? collect(); @endphp
    @if($packages->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد باقات متاحة حالياً.' : 'No packages available yet.' }}</div>
    @else
      <div class="mc-packages">
        @foreach($packages as $pkg)
          @php
            $recommended = (bool) $pkg->is_featured;
            $hoursLabel = $pkg->units_count
              ? (app()->getLocale() === 'ar'
                  ? $pkg->units_count.' حصة'
                  : $pkg->units_count.' sessions')
              : ($pkg->tagline ?: '');
            $perks = is_array($pkg->features) ? $pkg->features : [];
          @endphp
          <article class="mc-package {{ $recommended ? 'mc-package--recommended' : '' }}">
            @if(!empty($pkg->badge))
              <span class="mc-package__badge">{{ $pkg->badge }}</span>
            @endif
            <h3>{{ $pkg->name }}</h3>
            @if($hoursLabel !== '')
              <p class="mc-package__hours">{{ $hoursLabel }}</p>
            @endif
            <p class="mc-package__price">{{ $pkg->formattedPrice() }}</p>
            @if(!empty($pkg->tagline))
              <p class="mc-package__why">{{ $pkg->tagline }}</p>
            @elseif(!empty($pkg->description))
              <p class="mc-package__why">{{ \Illuminate\Support\Str::limit(strip_tags($pkg->description), 100) }}</p>
            @endif
            @if(count($perks) > 0)
              <ul>
                @foreach(array_slice($perks, 0, 4) as $perk)
                  <li>{{ is_string($perk) ? $perk : (string) $perk }}</li>
                @endforeach
              </ul>
            @endif
            <a href="{{ route('public.service-packages.checkout', $pkg) }}" class="mc-btn mc-btn--md {{ $recommended ? 'mc-btn--secondary' : 'mc-btn--soft' }}">{{ __('landing.mc.packages.cta') }}</a>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
