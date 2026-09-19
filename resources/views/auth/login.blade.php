@extends('layouts.auth-landing')

@section('title', __('auth.login'))

@section('body_attrs', 'x-data="{ showPassword: false }"')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
  <p class="lasles-auth-eyebrow">{{ __('auth.login_eyebrow') }}</p>
  <h1 class="lasles-auth-title">{!! __('auth.login_title_html') !!}</h1>
  <p class="lasles-auth-lead">{{ __('auth.login_lead') }}</p>

  <form action="{{ route('login') }}" method="POST" novalidate>
    @csrf

    @if (session('status'))
      <div class="lasles-auth-alert lasles-auth-alert--ok">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
      <div class="lasles-auth-alert lasles-auth-alert--warn">{{ session('warning') }}</div>
    @endif
    @if ($errors->any())
      <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
    @endif

    <div class="lasles-auth-sr-only" aria-hidden="true">
      <label>website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
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
          autofocus
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
          autocomplete="current-password"
          placeholder="{{ __('auth.password_placeholder') }}"
          class="lasles-auth-input has-icon @error('password') has-error @enderror"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPassword = !showPassword" aria-label="{{ __('auth.show') }}">
          <span x-text="showPassword ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
      @error('password')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <div class="lasles-auth-row">
      <label class="lasles-auth-check">
        <input type="checkbox" name="remember" id="remember">
        <span>{{ __('auth.remember') }}</span>
      </label>
      <a href="{{ route('password.request') }}" class="lasles-auth-link">{{ __('auth.forgot_password') }}</a>
    </div>

    <button type="submit" class="lasles-auth-submit">
      <span>{{ __('auth.login_cta') }}</span>
      <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
    </button>
  </form>

  @if(config('services.google.client_id') && config('services.google.client_secret'))
    <div class="lasles-auth-or" aria-hidden="true"><span>{{ __('auth.or') }}</span></div>
    <a href="{{ route('auth.google.redirect') }}" class="lasles-auth-google">
      <i class="fab fa-google" aria-hidden="true"></i>
      <span>{{ __('auth.continue_with_google') }}</span>
    </a>
  @endif

  <div class="lasles-auth-foot">
    <p class="lasles-auth-foot__q">{{ __('auth.no_account_question') }}</p>
    <a href="{{ route('register') }}" class="mc-btn mc-btn--md mc-btn--soft lasles-auth-register-cta">{{ __('auth.no_account_register_now') }}</a>
  </div>
@endsection
