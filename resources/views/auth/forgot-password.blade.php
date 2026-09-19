@extends('layouts.auth-landing')

@section('title', __('auth.forgot_password'))

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
  <p class="lasles-auth-eyebrow">{{ __('auth.forgot_password_eyebrow') }}</p>
  <h1 class="lasles-auth-title">{{ __('auth.forgot_password_title') }}</h1>
  <p class="lasles-auth-lead">{{ __('auth.forgot_password_help') }}</p>

  @if (session('status'))
    <div class="lasles-auth-alert lasles-auth-alert--ok">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('password.email') }}" novalidate>
    @csrf

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

    <button type="submit" class="lasles-auth-submit">
      <i class="fas fa-paper-plane" aria-hidden="true"></i>
      <span>{{ __('auth.send_reset_link') }}</span>
    </button>
  </form>

  <div class="lasles-auth-foot">
    <a href="{{ route('login') }}" class="lasles-auth-link">
      <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
      {{ __('auth.go_to_login') }}
    </a>
  </div>
@endsection
