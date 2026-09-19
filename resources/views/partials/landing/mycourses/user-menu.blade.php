@php
    $authUser = auth()->user();
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $avatarUrl = $authUser?->profile_image_url;
    $displayName = trim((string) ($authUser?->name ?? ''));
    $initials = collect(preg_split('/\s+/u', $displayName) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    if ($initials === '') {
        $initials = 'ح';
    }
    $menuId = $menuId ?? 'mc-user-menu-panel';
@endphp
@if($authUser)
<div class="mc-user-menu" data-mc-user-menu>
  <button
    type="button"
    class="mc-user-menu__btn"
    id="{{ $menuId }}-btn"
    aria-expanded="false"
    aria-haspopup="menu"
    aria-controls="{{ $menuId }}"
    aria-label="{{ __('landing.nav.account_menu') }}"
  >
    <span class="mc-user-menu__avatar" aria-hidden="true">
      @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="" width="40" height="40" decoding="async">
      @else
        <span class="mc-user-menu__initials">{{ $initials }}</span>
      @endif
    </span>
    <span class="mc-user-menu__chevron" aria-hidden="true">
      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
        <path d="M2.5 4.5L6 8l3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </span>
  </button>

  <div class="mc-user-menu__panel" id="{{ $menuId }}" role="menu" hidden>
    <div class="mc-user-menu__head">
      <span class="mc-user-menu__avatar mc-user-menu__avatar--lg" aria-hidden="true">
        @if($avatarUrl)
          <img src="{{ $avatarUrl }}" alt="" width="48" height="48" decoding="async">
        @else
          <span class="mc-user-menu__initials">{{ $initials }}</span>
        @endif
      </span>
      <div class="mc-user-menu__meta">
        <p class="mc-user-menu__name">{{ $displayName !== '' ? $displayName : __('landing.nav.brand') }}</p>
        @if(!empty($authUser->email))
          <p class="mc-user-menu__email" dir="ltr">{{ $authUser->email }}</p>
        @endif
      </div>
    </div>

    <div class="mc-user-menu__list">
      <a href="{{ route('dashboard') }}" class="mc-user-menu__item" role="menuitem">
        <span class="mc-user-menu__icon" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M4 13h7V4H4v9zm0 7h7v-5H4v5zm9 0h7V11h-7v9zm0-16v5h7V4h-7z" fill="currentColor"/>
          </svg>
        </span>
        <span>{{ __('landing.nav.dashboard') }}</span>
      </a>
      <form action="{{ route('logout') }}" method="POST" class="mc-user-menu__logout">
        @csrf
        <button type="submit" class="mc-user-menu__item mc-user-menu__item--danger" role="menuitem">
          <span class="mc-user-menu__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M10 17l5-5-5-5v3H3v4h7v3zm9-14H12v2h7v14h-7v2h9V3h-2z" fill="currentColor"/>
            </svg>
          </span>
          <span>{{ __('landing.nav.logout') }}</span>
        </button>
      </form>
    </div>
  </div>
</div>
@endif
