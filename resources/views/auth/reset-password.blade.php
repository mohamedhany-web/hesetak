@extends('layouts.auth-landing')

@section('title', __('auth.reset_password'))

@section('body_attrs', 'x-data="{ showPassword: false, showPasswordConfirm: false }"')

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
  <p class="lasles-auth-eyebrow">{{ __('auth.reset_password_eyebrow') }}</p>
  <h1 class="lasles-auth-title">{{ __('auth.reset_password_title') }}</h1>
  <p class="lasles-auth-lead">{{ __('auth.reset_password_help') }}</p>

  @if ($errors->any())
    <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('password.update') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="lasles-auth-field">
      <label for="email">{{ __('auth.email') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
        <input
          type="email"
          name="email"
          id="email"
          value="{{ old('email', $email ?? '') }}"
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
      <label for="password">{{ __('auth.new_password') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
        <input
          :type="showPassword ? 'text' : 'password'"
          name="password"
          id="password"
          required
          autocomplete="new-password"
          autofocus
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
          placeholder="{{ __('auth.password_placeholder') }}"
          class="lasles-auth-input has-icon"
          style="padding-inline-end:3rem"
        >
        <button type="button" class="lasles-auth-pw-btn" @click="showPasswordConfirm = !showPasswordConfirm" aria-label="{{ __('auth.show') }}">
          <span x-text="showPasswordConfirm ? '{{ __('auth.hide') }}' : '{{ __('auth.show') }}'"></span>
        </button>
      </div>
    </div>

    <button type="submit" class="lasles-auth-submit">
      <i class="fas fa-key" aria-hidden="true"></i>
      <span>{{ __('auth.reset_password') }}</span>
    </button>
  </form>

  <div class="lasles-auth-foot">
    <a href="{{ route('login') }}" class="lasles-auth-link">
      <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
      {{ __('auth.go_to_login') }}
    </a>
  </div>
@endsection
