@php $packages = $homePackages ?? collect(); @endphp
@if($packages->isNotEmpty())
<section class="mc-section" id="best-selling">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ app()->getLocale() === 'ar' ? 'الأكثر طلبًا' : 'Best selling' }}</p>
        <h2 class="mc-title">{{ app()->getLocale() === 'ar' ? 'الباقات المميزة للحصص الفردية' : 'Featured private lesson packages' }}</h2>
        <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'رصيد ساعات مرن واحجزوا منه مع أي معلم معتمد.' : 'Flexible hour credits — book with any accredited teacher.' }}</p>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
        <a class="mc-btn mc-btn--sm mc-btn--outline" href="{{ route('public.gift-package.show') }}">
          <i class="fas fa-gift" aria-hidden="true"></i>
          {{ app()->getLocale() === 'ar' ? 'إهداء باقة' : 'Gift a package' }}
        </a>
        <a class="mc-btn mc-btn--sm mc-btn--secondary" href="{{ route('public.pricing') }}#mc-pricing-storefront">
          {{ app()->getLocale() === 'ar' ? 'عرض كل باقات الحصص' : 'See all lesson packages' }}
        </a>
      </div>
    </div>

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
            $pkgId = $isArr ? (int) ($row['id'] ?? ($pkg->id ?? 0)) : (int) ($pkg->id ?? 0);
            $checkout = ($pkgId > 0 && \Illuminate\Support\Facades\Route::has('public.package-booking.teachers'))
              ? route('public.package-booking.teachers', $pkgId)
              : ($isArr ? ($row['checkout_url'] ?? '#') : route('public.service-packages.checkout', $pkg));
            $coverUrl = $isArr
              ? ($row['cover_image_url'] ?? ($pkg?->coverImageUrl() ?? null))
              : ($pkg?->coverImageUrl() ?? null);
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
            @if(!empty($coverUrl))
              <a href="{{ $checkout }}" style="display:block;margin:0 0 .75rem;border-radius:.85rem;overflow:hidden;aspect-ratio:16/10;background:#0b1b33">
                <img src="{{ $coverUrl }}" alt="{{ $name }}" style="display:block;width:100%;height:100%;object-fit:cover">
              </a>
            @endif
            @php
              $tidyName = trim(preg_replace('/\s+/u', ' ', (string) $name) ?? (string) $name);
              if (preg_match('/^(\d+)\s*أشهر?\s*-?\s*(.+)$/u', $tidyName, $m)) {
                $tidyName = $m[1].' أشهر · '.trim($m[2]);
              } else {
                $tidyName = str_replace(' - ', ' · ', $tidyName);
              }
            @endphp
            <h3>{{ $tidyName }}</h3>
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
  </div>
</section>
@endif
