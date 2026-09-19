@extends('layouts.auth-landing')

@section('title', __('auth.register'))
@section('main_class', 'lasles-auth-main--top')

@section('body_attrs', 'x-data="{ showPassword: false, showPasswordConfirm: false }"')

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $phoneCountries = $phoneCountries ?? config('phone_countries.countries', []);
  $defaultCountry = $defaultCountry ?? collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'));
  $oldDial = old('country_code', $defaultCountry['dial_code'] ?? '+966');
  $oldIso = old('country_iso', $defaultCountry['code'] ?? 'SA');
  $selectedCountry = collect($phoneCountries)->firstWhere('code', $oldIso)
      ?? collect($phoneCountries)->firstWhere('dial_code', $oldDial)
      ?? $defaultCountry;
  $phoneCountriesUi = collect($phoneCountries)->map(fn ($c) => [
      'code' => $c['code'] ?? '',
      'dial_code' => $c['dial_code'] ?? '',
      'name_ar' => $c['name_ar'] ?? '',
      'name_en' => $c['name_en'] ?? ($c['name_ar'] ?? ''),
      'placeholder' => $c['placeholder'] ?? '',
  ])->values()->all();
@endphp
<div class="lasles-auth-card lasles-auth-card--wide">
  <p class="lasles-auth-eyebrow">{{ __('auth.register_eyebrow') }}</p>
  <h1 class="lasles-auth-title">{!! __('auth.register_title_html') !!}</h1>
  <p class="lasles-auth-lead">{{ __('auth.register_subtitle') }}</p>

  <div class="lasles-auth-alert lasles-auth-alert--info">{{ __('auth.register_portal_note') }}</div>

  @if (! empty($pendingReferralCode))
    <div class="lasles-auth-alert lasles-auth-alert--ok">
      <strong>{{ __('auth.referral_invite_title') }}</strong><br>
      {{ __('auth.referral_invite_body', ['code' => $pendingReferralCode]) }}
    </div>
  @endif

  @if ($errors->any())
    <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
  @endif

  <form action="{{ route('register') }}" method="POST" novalidate>
    @csrf
    <input type="hidden" name="referral_code" value="{{ old('referral_code', $pendingReferralCode ?? '') }}">

    <div class="lasles-auth-field">
      <label for="name">{{ __('auth.full_name') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
        <input
          type="text"
          name="name"
          id="name"
          value="{{ old('name') }}"
          required
          autocomplete="name"
          placeholder="{{ __('auth.enter_full_name') }}"
          class="lasles-auth-input has-icon @error('name') has-error @enderror"
        >
      </div>
      @error('name')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label>{{ __('auth.phone_number') }}</label>
      <div
        class="lasles-auth-phone @error('phone') has-error @enderror"
        x-data="{
          open: false,
          q: '',
          dial: @js($selectedCountry['dial_code'] ?? '+966'),
          iso: @js($selectedCountry['code'] ?? 'SA'),
          name: @js($isRtl ? ($selectedCountry['name_ar'] ?? '') : ($selectedCountry['name_en'] ?? $selectedCountry['name_ar'] ?? '')),
          placeholder: @js($selectedCountry['placeholder'] ?? '5xxxxxxxx'),
          countries: @js($phoneCountriesUi),
          localeRtl: @js($isRtl),
          get filtered() {
            const term = (this.q || '').trim().toLowerCase();
            if (!term) return this.countries;
            return this.countries.filter((c) => {
              const hay = [c.dial_code, c.code, c.name_ar, c.name_en].join(' ').toLowerCase();
              return hay.includes(term);
            });
          },
          labelOf(c) {
            return this.localeRtl ? (c.name_ar || c.name_en) : (c.name_en || c.name_ar);
          },
          select(c) {
            this.dial = c.dial_code;
            this.iso = c.code;
            this.name = this.labelOf(c);
            this.placeholder = c.placeholder || '';
            this.open = false;
            this.q = '';
          },
          toggle() {
            this.open = !this.open;
            if (this.open) {
              this.$nextTick(() => this.$refs.ccSearch && this.$refs.ccSearch.focus());
            }
          }
        }"
        @keydown.escape.window="open = false"
      >
        <input type="hidden" name="country_code" :value="dial" required>
        <input type="hidden" name="country_iso" :value="iso">
        <div class="lasles-auth-cc" @click.outside="open = false">
          <button
            type="button"
            class="lasles-auth-cc-btn"
            dir="ltr"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox"
            aria-label="{{ __('auth.country_code_aria') }}"
          >
            <span class="lasles-auth-cc-dial" x-text="dial"></span>
            <span class="lasles-auth-cc-name" x-text="name"></span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
          </button>
          <div class="lasles-auth-cc-panel" x-show="open" x-cloak x-transition.opacity.duration.150ms role="listbox">
            <div class="lasles-auth-cc-search">
              <i class="fas fa-search" aria-hidden="true"></i>
              <input
                type="search"
                x-model="q"
                x-ref="ccSearch"
                @keydown.escape.stop="open = false"
                placeholder="{{ __('auth.search_country') }}"
                autocomplete="off"
                dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
              >
            </div>
            <ul class="lasles-auth-cc-list">
              <template x-for="c in filtered" :key="c.code">
                <li>
                  <button
                    type="button"
                    class="lasles-auth-cc-option"
                    :class="{ 'is-active': c.code === iso }"
                    @click="select(c)"
                    dir="ltr"
                  >
                    <span class="lasles-auth-cc-option-dial" x-text="c.dial_code"></span>
                    <span class="lasles-auth-cc-option-name" x-text="labelOf(c)" :dir="localeRtl ? 'rtl' : 'ltr'"></span>
                  </button>
                </li>
              </template>
              <li x-show="filtered.length === 0" class="lasles-auth-cc-empty" x-cloak>
                {{ __('auth.no_country_found') }}
              </li>
            </ul>
          </div>
        </div>
        <input
          type="tel"
          name="phone"
          value="{{ old('phone') }}"
          required
          :placeholder="placeholder || '5xxxxxxxx'"
          dir="ltr"
          autocomplete="tel-national"
          aria-label="{{ __('auth.phone_aria') }}"
        >
      </div>
      @error('phone')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label for="email">{{ __('auth.email') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
        <input
          type="email"
          name="email"
          id="email"
          value="{{ old('email') }}"
          required
          autocomplete="email"
          dir="ltr"
          placeholder="{{ __('auth.email_placeholder') }}"
          class="lasles-auth-input has-icon @error('email') has-error @enderror"
        >
      </div>
      @error('email')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label for="password">{{ __('auth.password') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <input
          :type="showPassword ? 'text' : 'password'"
          name="password"
          id="password"
          required
          autocomplete="new-password"
          placeholder="{{ __('auth.enter_strong_password') }}"
          class="lasles-auth-input has-icon @error('password') has-error @enderror"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPassword = !showPassword">
          <span x-text="showPassword ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
      @error('password')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-field">
      <label for="password_confirmation">{{ __('auth.password_confirmation') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <input
          :type="showPasswordConfirm ? 'text' : 'password'"
          name="password_confirmation"
          id="password_confirmation"
          required
          autocomplete="new-password"
          placeholder="{{ __('auth.reenter_password') }}"
          class="lasles-auth-input has-icon"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPasswordConfirm = !showPasswordConfirm">
          <span x-text="showPasswordConfirm ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
    </div>

    <div class="lasles-auth-field">
      <label for="timezone">{{ __('auth.timezone') }}</label>
      @php
        $tzOptions = \App\Support\AppTimezone::commonZones();
        $tzValue = old('timezone', session('pending_timezone', ''));
      @endphp
      <select id="timezone" name="timezone" data-timezone-select class="lasles-auth-input">
        <option value="">{{ __('auth.timezone_auto') }}</option>
        @foreach ($tzOptions as $tzId => $tzLabel)
          <option value="{{ $tzId }}" @selected($tzValue === $tzId)>{{ $tzLabel }}</option>
        @endforeach
      </select>
      <input type="hidden" name="timezone_auto" id="timezone_auto" value="">
      <p class="lasles-auth-hint">{{ __('auth.timezone_hint') }}</p>
    </div>

    <label class="lasles-auth-terms">
      <input type="checkbox" id="terms" required>
      <span>
        {{ __('auth.agree_terms') }}
        <a href="{{ route('public.terms') }}" class="lasles-auth-link">{{ __('auth.terms_of_use') }}</a>
        {{ __('auth.and') }}
        <a href="{{ route('public.privacy') }}" class="lasles-auth-link">{{ __('auth.privacy_policy') }}</a>
      </span>
    </label>

    <button type="submit" class="lasles-auth-submit">
      <i class="fas fa-user-plus" aria-hidden="true"></i>
      <span>{{ __('auth.register_cta') }}</span>
    </button>
  </form>

  @if(config('services.google.client_id') && config('services.google.client_secret'))
    <div class="lasles-auth-or" aria-hidden="true"><span>{{ __('auth.or') }}</span></div>
    <a href="{{ route('auth.google.redirect') }}" class="lasles-auth-google">
      <i class="fab fa-google" aria-hidden="true"></i>
      <span>{{ __('auth.signup_with_google') }}</span>
    </a>
  @endif

  <div class="lasles-auth-foot">
    {{ __('auth.already_have_account') }}
    <a href="{{ route('login') }}" class="lasles-auth-link">{{ __('auth.login') }}</a>
  </div>
</div>
@endsection
