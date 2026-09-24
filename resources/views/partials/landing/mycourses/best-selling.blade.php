<section class="mc-section" id="best-selling">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ app()->getLocale() === 'ar' ? 'الأكثر طلبًا' : 'Best selling' }}</p>
        <h2 class="mc-title">{{ app()->getLocale() === 'ar' ? 'باقات الحصص الفردية' : 'Private lesson packages' }}</h2>
        <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'رصيد ساعات واضح، جاهز للحجز مع أي معلم معتمد.' : 'Clear hour credits, ready to book with any approved teacher.' }}</p>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
        <a class="mc-btn mc-btn--sm mc-btn--outline" href="{{ route('public.gift-package.show') }}">
          <i class="fas fa-gift" aria-hidden="true"></i>
          {{ app()->getLocale() === 'ar' ? 'إهداء باقة' : 'Gift a package' }}
        </a>
        <a class="mc-link-more" href="{{ route('public.pricing') }}">{{ __('landing.mc.packages.more') }} →</a>
      </div>
    </div>

    @include('partials.landing.mycourses.package-catalog-filters', [
      'packageCatalog' => $packageCatalog ?? [],
      'anchor' => 'best-selling',
    ])

    @php $packages = $homePackages ?? ($packageCatalog['packages'] ?? collect()); @endphp
    @if($packages->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد باقات متاحة حالياً.' : 'No packages available yet.' }}</div>
    @else
      <div class="mc-packages">
        @foreach($packages as $row)
          @php
            $isArr = is_array($row);
            $pkg = $isArr ? ($row['model'] ?? null) : $row;
            $recommended = $isArr ? (bool) ($row['is_featured'] ?? false) : (bool) ($pkg->is_featured ?? false);
            $name = $isArr ? ($row['name'] ?? '') : ($pkg->name ?? '');
            $units = $isArr ? (int) ($row['units_count'] ?? 0) : (int) ($pkg->units_count ?? 0);
            $hoursLabel = $units
              ? (app()->getLocale() === 'ar' ? $units.' حصة' : $units.' sessions')
              : ($isArr ? ($row['tagline'] ?? '') : ($pkg->tagline ?: ''));
            $priceLabel = $isArr
              ? number_format((float) $row['display_price'], 2).' '.($row['currency'] ?? 'SAR')
              : $pkg->formattedPrice();
            $perks = $isArr ? ($row['features'] ?? []) : (is_array($pkg->features ?? null) ? $pkg->features : []);
            $checkout = $isArr ? ($row['checkout_url'] ?? '#') : route('public.service-packages.checkout', $pkg);
            $giftUrl = $isArr ? ($row['gift_url'] ?? route('public.gift-package.show')) : route('public.gift-package.show', ['package' => $pkg->id]);
            $badge = $isArr ? ($row['badge'] ?? null) : ($pkg->badge ?? null);
            $tagline = $isArr ? ($row['tagline'] ?? null) : ($pkg->tagline ?? null);
            $description = $isArr ? ($row['description'] ?? null) : ($pkg->description ?? null);
            $unitHint = $isArr
              ? (app()->getLocale() === 'ar'
                  ? 'سعر الحصة '.number_format((float) $row['display_unit'], 2).' '.$row['currency']
                  : number_format((float) $row['display_unit'], 2).' '.$row['currency'].' / session')
              : null;
          @endphp
          <article class="mc-package {{ $recommended ? 'mc-package--recommended' : '' }}">
            @if(!empty($badge))
              <span class="mc-package__badge">{{ $badge }}</span>
            @endif
            <h3>{{ $name }}</h3>
            @if($hoursLabel !== '')
              <p class="mc-package__hours">{{ $hoursLabel }}</p>
            @endif
            <p class="mc-package__price">{{ $priceLabel }}</p>
            @if($unitHint)
              <p class="mc-package__why" style="opacity:.85">{{ $unitHint }}</p>
            @endif
            @if(!empty($tagline))
              <p class="mc-package__why">{{ $tagline }}</p>
            @elseif(!empty($description))
              <p class="mc-package__why">{{ \Illuminate\Support\Str::limit(strip_tags($description), 100) }}</p>
            @endif
            @if(count($perks) > 0)
              <ul>
                @foreach(array_slice($perks, 0, 4) as $perk)
                  <li>{{ is_string($perk) ? $perk : (string) $perk }}</li>
                @endforeach
              </ul>
            @endif
            <div style="display:flex;flex-wrap:wrap;gap:.4rem">
              <a href="{{ $checkout }}" class="mc-btn mc-btn--md {{ $recommended ? 'mc-btn--secondary' : 'mc-btn--soft' }}">{{ __('landing.mc.packages.cta') }}</a>
              <a href="{{ $giftUrl }}" class="mc-btn mc-btn--md mc-btn--outline">{{ app()->getLocale() === 'ar' ? 'إهداء' : 'Gift' }}</a>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </div>
</section>
