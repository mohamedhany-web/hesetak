@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $topbar = \App\Services\PublicFooterSettings::payload();
    $topbarEmail = trim((string) ($topbar['email'] ?? ''));
    $topbarPhone = trim((string) ($topbar['phone'] ?? ''));
    $topbarWhatsapp = trim((string) ($topbar['whatsapp_url'] ?? ''));
    $topbarSocials = $topbar['socials'] ?? [];
@endphp
<div class="mc-topbar" role="region" aria-label="{{ __('landing.mc.topbar.aria') }}">
  <div class="mc-container mc-topbar__inner">
    <div class="mc-topbar__contact">
      @if($topbarEmail !== '')
        <a class="mc-topbar__item" href="mailto:{{ $topbarEmail }}">
          <span class="mc-topbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
          </span>
          <span>{{ $topbarEmail }}</span>
        </a>
      @endif
      @if($topbarPhone !== '')
        <a class="mc-topbar__item" href="tel:{{ preg_replace('/\s+/', '', $topbarPhone) }}">
          <span class="mc-topbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h2a2 2 0 0 1 2 1.7c.1.8.3 1.6.6 2.3a2 2 0 0 1-.5 2.1L7.1 9.1a16 16 0 0 0 6 6l1.9-1.1a2 2 0 0 1 2.1-.4c.7.3 1.5.5 2.3.6a2 2 0 0 1 1.7 2z"/></svg>
          </span>
          <span dir="ltr">{{ $topbarPhone }}</span>
        </a>
      @endif
      @if($topbarWhatsapp !== '')
        <a class="mc-topbar__item mc-topbar__item--wa" href="{{ $topbarWhatsapp }}" target="_blank" rel="noopener noreferrer">
          <span class="mc-topbar__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20 3.5A10 10 0 0 0 3.4 17.6L2 22l4.5-1.2A10 10 0 1 0 20 3.5zm-8 16.1a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-2.6.7.7-2.5-.2-.3a8.2 8.2 0 1 1 6.6 3.5zm4.5-6.1c-.2-.1-1.4-.7-1.6-.8s-.4-.1-.5.1-.6.8-.7.9-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.4 7.4 0 0 1-1.4-1.7c-.1-.3 0-.4.1-.5l.4-.4.2-.3.1-.4-.1-.4c0-.1-.5-1.3-.7-1.7s-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3a2 2 0 0 0-.6 1.5 3.5 3.5 0 0 0 .7 1.8 8 8 0 0 0 3.1 3 10.7 10.7 0 0 0 2 .8 2.4 2.4 0 0 0 1.6.1 2 2 0 0 0 1.3-1 1.6 1.6 0 0 0 .1-1c0-.1-.2-.2-.4-.3z"/></svg>
          </span>
          <span>{{ __('landing.mc.topbar.whatsapp') }}</span>
        </a>
      @endif
      <span class="mc-topbar__pill">{{ __('landing.mc.topbar.hours') }}</span>
    </div>

    <div class="mc-topbar__meta">
      <span class="mc-topbar__note">{{ __('landing.mc.topbar.note') }}</span>
      <div class="mc-topbar__socials" aria-label="{{ __('landing.mc.topbar.socials_aria') }}">
        @forelse($topbarSocials as $social)
          <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}" title="{{ $social['label'] }}">
            @if(str_contains($social['icon'], 'facebook'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v7h3v-7h2.5l.5-3H14V9z"/></svg>
            @elseif(str_contains($social['icon'], 'instagram'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm-5 3.5A3.5 3.5 0 1 1 8.5 12 3.5 3.5 0 0 1 12 8.5zm0 2A1.5 1.5 0 1 0 13.5 12 1.5 1.5 0 0 0 12 10.5zM17.2 7.3a.9.9 0 1 1-.9.9.9.9 0 0 1 .9-.9z"/></svg>
            @elseif(str_contains($social['icon'], 'youtube'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7a3 3 0 0 0-2.1-2.1C18.9 4.7 12 4.7 12 4.7s-6.9 0-8.5.5A3 3 0 0 0 1.4 7.3C1 8.8 1 12 1 12s0 3.2.4 4.7a3 3 0 0 0 2.1 2.1c1.6.5 8.5.5 8.5.5s6.9 0 8.5-.5a3 3 0 0 0 2.1-2.1c.4-1.5.4-4.7.4-4.7zM9.8 15.5v-7l6 3.5-6 3.5z"/></svg>
            @elseif(str_contains($social['icon'], 'linkedin'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M6.5 9H3.7v11h2.8V9zM5.1 3.5A1.6 1.6 0 1 0 5.1 6.7 1.6 1.6 0 0 0 5.1 3.5zM20.3 9c-1.5 0-2.5.8-3 1.5V9h-2.8c0 .5-.1 11 0 11h2.8v-6.1c0-.3.1-.7.3-.9.4-.7 1-1.3 2.1-1.3 1.5 0 2.1 1.1 2.1 2.8V20h2.8v-6.4C24.6 10.4 22.7 9 20.3 9z"/></svg>
            @elseif(str_contains($social['icon'], 'tiktok'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M19.6 8.4a6.5 6.5 0 0 1-3.8-1.2v7.1a5.6 5.6 0 1 1-4.8-5.5v2.5a3.1 3.1 0 1 0 2.2 3v-11h2.5a4.1 4.1 0 0 0 3.9 3.8v2.3z"/></svg>
            @elseif(str_contains($social['icon'], 'telegram'))
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M21.7 4.3 2.9 11.5c-1.3.5-1.3 1.2-.2 1.5l4.8 1.5 1.8 5.6c.2.7.4 1 1 .7l2.7-2.3 5.6 4.1c1 .6 1.8.3 2.1-.9L22.9 5.5c.3-1.3-.5-1.9-1.2-1.2z"/></svg>
            @else
              <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true"><path d="M18 3h-3a5 5 0 0 0-5 5v2H7v3h3v8h3v-8h3l1-3h-4V8a1 1 0 0 1 1-1h3z"/></svg>
            @endif
          </a>
        @empty
          <a href="{{ route('public.contact') }}" aria-label="{{ __('landing.mc.nav.contact') }}" title="{{ __('landing.mc.nav.contact') }}">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/></svg>
          </a>
        @endforelse
      </div>
    </div>
  </div>
</div>
