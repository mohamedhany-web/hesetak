@extends('layouts.auth-landing')

@section('title', __('auth.two_factor'))

@section('nav_action')
  <a href="{{ route('login') }}" class="lasles-auth-nav-link">{{ __('auth.two_factor_back_login') }}</a>
@endsection

@section('content')
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<div class="lasles-auth-card">
  <div class="lasles-auth-badge" aria-hidden="true">
    <i class="fas fa-shield-halved"></i>
  </div>

  <h1 class="lasles-auth-title">{{ __('auth.two_factor_challenge_title') }}</h1>
  <p class="lasles-auth-lead">
    @if(!empty($useEmail))
      {!! __('auth.two_factor_challenge_desc_email') !!}
    @else
      {{ __('auth.two_factor_challenge_desc_app') }}
    @endif
  </p>

  @if ($errors->has('code'))
    <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first('code') }}</div>
  @endif

  <form action="{{ route('two-factor.verify') }}" method="POST" novalidate>
    @csrf

    <div class="lasles-auth-field">
      <label for="code">{{ __('auth.two_factor_code_label') }}</label>
      <div class="lasles-auth-input-wrap">
        <span class="lasles-auth-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
        <input
          type="text"
          name="code"
          id="code"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="10"
          autocomplete="one-time-code"
          autofocus
          required
          dir="ltr"
          placeholder="••••••"
          class="lasles-auth-input lasles-auth-input--otp has-icon @error('code') has-error @enderror"
        >
      </div>
      @error('code')<p class="lasles-auth-error">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="lasles-auth-submit">
      <span>{{ __('auth.two_factor_confirm') }}</span>
      <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
    </button>
  </form>

  <div class="lasles-auth-foot">
    @if(empty($useEmail))
      {!! __('auth.two_factor_recovery_hint') !!}
    @else
      {!! __('auth.two_factor_resend_hint', ['login_url' => route('login')]) !!}
    @endif
  </div>

  <div class="lasles-auth-trust">
    <span><i class="fas fa-lock"></i> {{ __('auth.two_factor_security_note') }}</span>
  </div>
</div>
@endsection

@push('head')
<style>
  .lasles-auth-badge {
    width: 3.25rem; height: 3.25rem; margin: 0 auto .9rem;
    border-radius: 16px; display: grid; place-items: center;
    background: linear-gradient(145deg, #0B3D91, #072A66);
    color: #fff; font-size: 1.15rem;
    box-shadow: 0 12px 28px -10px rgba(11,61,145,.55);
  }
  .lasles-auth-input--otp {
    letter-spacing: .42em;
    font-weight: 800;
    font-size: 1.2rem;
    text-align: center;
    padding-inline: 2.55rem !important;
  }
  .lasles-auth-foot strong { color: #0B1220; font-weight: 800; }
  .lasles-auth-foot a { color: #0B3D91; font-weight: 800; text-decoration: none; }
  .lasles-auth-foot a:hover { text-decoration: underline; }
</style>
@endpush
