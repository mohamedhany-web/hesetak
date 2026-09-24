<section class="mc-section mc-section--compact" id="packages">
  <div class="mc-container">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ __('landing.mc.packages.eyebrow') }}</p>
        <h2 class="mc-title">{{ __('landing.mc.packages.title') }}</h2>
        <p class="mc-lead">{{ __('landing.mc.packages.lead') }}</p>
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
      'anchor' => 'packages',
    ])

    @php $packages = $homePackages ?? ($packageCatalog['packages'] ?? collect()); @endphp
    @if($packages->isEmpty())
      <div class="mc-empty">{{ app()->getLocale() === 'ar' ? 'لا توجد باقات متاحة حالياً.' : 'No packages available yet.' }}</div>
    @else
      <div class="mc-packages">
        @foreach($packages as $row)
          @php
            $pkg = is_array($row) ? ($row['model'] ?? null) : $row;
            $recommended = is_array($row) ? (bool) ($row['is_featured'] ?? false) : (bool) ($pkg->is_featured ?? false);
            $name = is_array($row) ? ($row['name'] ?? '') : ($pkg->name ?? '');
            $units = is_array($row) ? (int) ($row['units_count'] ?? 0) : (int) ($pkg->units_count ?? 0);
            $hoursLabel = $units
              ? (app()->getLocale() === 'ar' ? $units.' حصة' : $units.' sessions')
              : (is_array($row) ? ($row['tagline'] ?? '—') : ($pkg->tagline ?: '—'));
            $priceLabel = is_array($row)
              ? number_format((float) $row['display_price'], 2).' '.($row['currency'] ?? 'SAR')
              : $pkg->formattedPrice();
            $unitHint = is_array($row)
              ? (app()->getLocale() === 'ar'
                  ? 'سعر الحصة '.number_format((float) $row['display_unit'], 2).' '.$row['currency']
                  : number_format((float) $row['display_unit'], 2).' '.$row['currency'].' / session')
              : null;
            $perks = is_array($row) ? ($row['features'] ?? []) : (is_array($pkg->features ?? null) ? $pkg->features : []);
            $checkout = is_array($row) ? ($row['checkout_url'] ?? '#') : route('public.service-packages.checkout', $pkg);
            $giftUrl = is_array($row) ? ($row['gift_url'] ?? route('public.gift-package.show')) : route('public.gift-package.show', ['package' => $pkg->id]);
            $badge = is_array($row) ? ($row['badge'] ?? null) : ($pkg->badge ?? null);
            $tagline = is_array($row) ? ($row['tagline'] ?? null) : ($pkg->tagline ?? null);
            $description = is_array($row) ? ($row['description'] ?? null) : ($pkg->description ?? null);
          @endphp
          <article class="mc-package {{ $recommended ? 'mc-package--recommended' : '' }}">
            @if(!empty($badge))
              <span class="mc-package__badge">{{ $badge }}</span>
            @endif
            <h3>{{ $name }}</h3>
            <p class="mc-package__hours">{{ $hoursLabel }}</p>
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
                @foreach(array_slice($perks, 0, 5) as $perk)
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
