@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $packages = $packages ?? collect();
    $tutoringGroups = $tutoringGroups ?? collect();
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
    $mcCss = public_path('css/landing/mycourses.css');
    $mcVer = is_file($mcCss) ? (string) filemtime($mcCss) : (string) time();
    $mcActive = 'pricing';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ request()->boolean('figma') ? 'ltr' : ($isRtl ? 'rtl' : 'ltr') }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>{{ __('public.pricing_page_title') }} — {{ $brand }}</title>
  <meta name="description" content="{{ __('public.pricing_meta_description') }}">
  <link rel="canonical" href="{{ route('public.pricing') }}">
  @include('partials.favicon-links')
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <meta name="theme-color" content="#00C2A8">
  <link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'mycourses']) }}?v={{ $mcVer }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .mc-prx-actions { display: flex; flex-wrap: wrap; gap: .65rem; margin-top: 1.15rem; }
    .mc-prx-note { margin: .75rem 0 0; font-size: .85rem; color: var(--mc-muted); }
    .mc-prx-card.is-popular {
      border-color: var(--mc-primary-70);
      box-shadow: 0 16px 36px -18px rgba(0, 194, 168, .35);
    }
    .mc-prx-card .mc-card__body { gap: .75rem; }
    .mc-prx-head {
      display: flex; gap: .85rem; align-items: flex-start;
    }
    .mc-prx-thumb {
      width: 56px; height: 56px; border-radius: 14px; overflow: hidden; flex-shrink: 0;
      background: rgba(0, 194, 168, .12); display: flex; align-items: center; justify-content: center;
      color: var(--mc-primary-100);
    }
    .mc-prx-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .mc-prx-list {
      list-style: none; margin: 0; padding: 0; display: grid; gap: .4rem; flex: 1;
    }
    .mc-prx-list li {
      display: flex; gap: .45rem; align-items: flex-start;
      font-size: .8rem; color: var(--mc-ink-soft);
    }
    .mc-prx-list i { color: var(--mc-primary-70); margin-top: .2rem; font-size: .7rem; }
    .mc-prx-desc {
      margin: 0; font-size: .84rem; line-height: 1.7; color: var(--mc-muted);
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .mc-prx-old {
      margin: 0; font-size: .8rem; color: var(--mc-muted); text-decoration: line-through;
    }
  </style>
  @include('partials.figma-capture-head')
</head>
<body class="mc-body">
@include('partials.landing.mycourses.nav')

<main>
  <section class="mc-page-hero">
    <div class="mc-container">
      <p class="mc-eyebrow">{{ __('public.pricing_hero_kicker') }}</p>
      <h1>
        {{ __('public.pricing_hero_title') }}
        <span style="color:var(--mc-primary-100)">{{ __('public.pricing_hero_accent') }}</span>
      </h1>
      <p>{{ __('public.pricing_hero_sub') }}</p>
      <p class="mc-prx-note">{{ __('public.pricing_hero_note') }}</p>
      <div class="mc-prx-actions">
        <a href="#packages" class="mc-btn mc-btn--md mc-btn--primary">{{ __('public.pricing_packages_title') }}</a>
        <a href="#tutoring-groups" class="mc-btn mc-btn--md mc-btn--outline">{{ __('public.pricing_groups_title') }}</a>
      </div>
    </div>
  </section>

  <section class="mc-section" id="packages">
    <div class="mc-container">
      <div class="mc-section-head" style="margin-bottom:1.75rem">
        <div>
          <p class="mc-eyebrow">{{ __('public.pricing_packages_badge') }}</p>
          <h2 class="mc-title">{{ __('public.pricing_packages_title') }}</h2>
          <p class="mc-lead">{{ __('public.pricing_packages_sub') }}</p>
        </div>
      </div>

      @if($packages->isNotEmpty())
        <div class="mc-grid mc-grid--3">
          @foreach($packages as $package)
            @php
              $cardBody = trim((string) ($package->card_summary ?? '')) !== ''
                  ? $package->card_summary
                  : ($package->description ?? '');
              $cardFeatures = collect($package->features ?? [])->map(fn ($f) => trim((string) $f))->filter()->values();
              $isPopular = (bool) $package->is_popular;
            @endphp
            <article @class(['mc-card mc-prx-card', 'is-popular' => $isPopular])>
              <div class="mc-card__body">
                @if($isPopular)
                  <span class="mc-card__badge" style="position:static;align-self:flex-start">{{ __('public.pricing_package_popular') }}</span>
                @endif
                <div class="mc-prx-head">
                  <div class="mc-prx-thumb">
                    @if($package->thumbnail)
                      <img src="{{ storage_asset($package->thumbnail) }}" alt="" loading="lazy">
                    @else
                      <i class="fas fa-graduation-cap"></i>
                    @endif
                  </div>
                  <div>
                    <h3 class="mc-card__title">{{ $package->name }}</h3>
                    @if(($package->courses_count ?? 0) > 0)
                      <div class="mc-card__meta">
                        <span><i class="fas fa-book-open"></i> {{ __('public.path_courses_count', ['count' => $package->courses_count]) }}</span>
                      </div>
                    @endif
                  </div>
                </div>

                <div class="mc-card__foot" style="border:0;padding:0;margin:0">
                  <div>
                    @if($package->original_price && $package->original_price > $package->price)
                      <p class="mc-prx-old">{{ format_money((float) $package->original_price, 0) }}</p>
                    @endif
                    <span class="mc-card__price">
                      @if($package->price > 0)
                        {{ format_money((float) $package->price, 0) }}
                      @else
                        {{ __('public.free_price') }}
                      @endif
                    </span>
                  </div>
                </div>

                @if($cardBody !== '')
                  <p class="mc-prx-desc">{{ $cardBody }}</p>
                @endif

                @if($cardFeatures->isNotEmpty())
                  <ul class="mc-prx-list">
                    @foreach($cardFeatures->take(5) as $feature)
                      <li><i class="fas fa-check"></i><span>{{ $feature }}</span></li>
                    @endforeach
                  </ul>
                @endif

                <a href="{{ route('public.package.show', $package->slug) }}"
                   class="mc-btn mc-btn--md {{ $isPopular ? 'mc-btn--primary' : 'mc-btn--outline' }}"
                   style="margin-top:auto;justify-content:center;width:100%">
                  <i class="fas fa-{{ $package->price > 0 ? 'shopping-cart' : 'eye' }}"></i>
                  {{ $package->price > 0 ? __('public.pricing_package_buy') : __('public.view_details') }}
                </a>
              </div>
            </article>
          @endforeach
        </div>
      @else
        <div class="mc-empty">{{ __('public.pricing_no_packages') }}</div>
      @endif
    </div>
  </section>

  <section class="mc-section mc-section--muted" id="tutoring-groups">
    <div class="mc-container">
      <div class="mc-section-head" style="margin-bottom:1.75rem">
        <div>
          <p class="mc-eyebrow">{{ __('public.pricing_groups_badge') }}</p>
          <h2 class="mc-title">{{ __('public.pricing_groups_title') }}</h2>
          <p class="mc-lead">{{ __('public.pricing_groups_sub') }}</p>
        </div>
      </div>

      @if($tutoringGroups->isNotEmpty())
        <div class="mc-grid mc-grid--3">
          @foreach($tutoringGroups as $group)
            @php
              $img = $group->imageUrl();
              $isFeatured = (bool) $group->is_featured;
            @endphp
            <article @class(['mc-card mc-prx-card', 'is-popular' => $isFeatured])>
              <div class="mc-card__body">
                @if($isFeatured)
                  <span class="mc-card__badge" style="position:static;align-self:flex-start">{{ __('public.pricing_package_popular') }}</span>
                @endif
                <div class="mc-prx-head">
                  <div class="mc-prx-thumb">
                    @if($img)
                      <img src="{{ $img }}" alt="" loading="lazy">
                    @else
                      <i class="fas fa-{{ $group->isIndividual() ? 'user' : 'users' }}"></i>
                    @endif
                  </div>
                  <div>
                    <h3 class="mc-card__title">{{ $group->title }}</h3>
                    <div class="mc-card__meta">
                      <span>
                        {{ $group->typeLabel() }}
                        @if($group->instructor)
                          · {{ $group->instructor->name }}
                        @endif
                      </span>
                    </div>
                  </div>
                </div>

                <div class="mc-card__foot" style="border:0;padding:0;margin:0">
                  <span class="mc-card__price">
                    @if($group->price !== null && (float) $group->price > 0)
                      {{ format_money((float) $group->price, 0) }}
                    @else
                      {{ __('public.pricing_groups_price_contact') }}
                    @endif
                  </span>
                </div>

                @if(filled($group->description))
                  <p class="mc-prx-desc">{{ $group->description }}</p>
                @endif

                <ul class="mc-prx-list">
                  @if($group->duration_minutes)
                    <li><i class="fas fa-clock"></i><span>{{ __('public.pricing_groups_duration', ['minutes' => $group->duration_minutes]) }}</span></li>
                  @endif
                  @if($group->capacity)
                    <li><i class="fas fa-user-group"></i><span>{{ __('public.pricing_groups_capacity', ['count' => $group->capacity]) }}</span></li>
                  @endif
                </ul>

                <a href="{{ route('public.groups.show', $group->slug) }}"
                   class="mc-btn mc-btn--md {{ $isFeatured ? 'mc-btn--primary' : 'mc-btn--outline' }}"
                   style="margin-top:auto;justify-content:center;width:100%">
                  {{ __('public.pricing_groups_cta') }}
                </a>
              </div>
            </article>
          @endforeach
        </div>
      @else
        <div class="mc-empty">{{ __('public.pricing_no_groups') }}</div>
      @endif
    </div>
  </section>

  <section class="mc-section">
    <div class="mc-container">
      <div class="mc-cta">
        <div>
          <h2>{{ __('public.pricing_footer_cta_title') }}</h2>
          <p>{{ __('public.pricing_footer_cta_sub') }}</p>
        </div>
        <div class="mc-prx-actions" style="margin:0">
          <a href="{{ route('register') }}" class="mc-btn mc-btn--md mc-btn--secondary">{{ __('public.register_free') }}</a>
          <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--md mc-btn--ghost" style="background:rgba(255,255,255,.15);color:#fff;border-color:transparent">{{ __('public.pricing_footer_contact') }}</a>
          <a href="{{ $waUrl }}" class="mc-btn mc-btn--md mc-btn--ghost" style="background:rgba(255,255,255,.15);color:#fff;border-color:transparent" target="_blank" rel="noopener">WhatsApp</a>
        </div>
      </div>
    </div>
  </section>
</main>

@include('partials.landing.mycourses.footer')
</body>
</html>
