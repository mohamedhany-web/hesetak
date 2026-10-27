@extends('layouts.auth-landing')

@section('title', !empty($sent) ? __('auth.forgot_password_sent_title') : __('auth.forgot_password'))

@section('content')
@php
  $isRtl = app()->getLocale() === 'ar';
  $sent = (bool) ($sent ?? (request()->boolean('sent') || filled(session('status'))));
  $sentEmail = $sentEmail ?? old('email', session('reset_email'));
@endphp
  <p class="lasles-auth-eyebrow">{{ __('auth.forgot_password_eyebrow') }}</p>
  <h1 class="lasles-auth-title">
    {{ $sent ? __('auth.forgot_password_sent_title') : __('auth.forgot_password_title') }}
  </h1>
  <p class="lasles-auth-lead">
    {{ $sent ? __('auth.forgot_password_sent_lead') : __('auth.forgot_password_help') }}
  </p>

  @if ($sent)
    <div class="lasles-auth-alert lasles-auth-alert--ok" role="status" aria-live="polite">
      <strong style="font-size:1.05rem">{{ __('auth.check_inbox_title') }}</strong>
      <p style="margin:.55rem 0 0">{{ session('status', __('auth.reset_link_sent')) }}</p>
      @if(filled($sentEmail))
        <p style="margin:.55rem 0 0">{{ __('auth.check_inbox_sent_to') }}</p>
        <p style="margin:.25rem 0 0" dir="ltr"><strong>{{ $sentEmail }}</strong></p>
      @endif
    </div>
    <ol class="lasles-auth-hint" style="margin:1rem 0 1.25rem;padding-inline-start:1.25rem;line-height:1.75">
      <li>{{ __('auth.check_inbox_step_1') }}</li>
      <li>{{ __('auth.check_inbox_step_2') }}</li>
      <li>{{ __('auth.check_inbox_step_3') }}</li>
    </ol>
    <p class="lasles-auth-hint">{{ __('auth.check_inbox_note') }}</p>
    <div class="lasles-auth-foot" style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">
      <a href="{{ route('login') }}" class="lasles-auth-submit" style="text-decoration:none;display:inline-flex">
        <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
        <span>{{ __('auth.go_to_login') }}</span>
      </a>
      <a href="{{ route('password.request', ['sent' => 1, 'again' => 1]) }}" class="lasles-auth-link">{{ __('auth.send_reset_again') }}</a>
    </div>
  @else
    @if ($errors->any())
      <div class="lasles-auth-alert lasles-auth-alert--err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate id="forgot-password-form" data-loading-label="{{ __('auth.sending_reset_link') }}">
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

      <button type="submit" class="lasles-auth-submit" id="forgot-password-submit">
        <i class="fas fa-paper-plane" aria-hidden="true"></i>
        <span data-label>{{ __('auth.send_reset_link') }}</span>
      </button>
    </form>

    <div class="lasles-auth-foot">
      <a href="{{ route('login') }}" class="lasles-auth-link">
        <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}" aria-hidden="true"></i>
        {{ __('auth.go_to_login') }}
      </a>
    </div>
  @endif
@endsection

@if (! $sent)
@push('scripts')
<script>
(function () {
  var form = document.getElementById('forgot-password-form');
  if (!form) return;
  var btn = document.getElementById('forgot-password-submit');
  var label = btn ? btn.querySelector('[data-label]') : null;
  var loadingText = form.getAttribute('data-loading-label') || '...';
  var idleText = label ? label.textContent : '';
  var submitted = false;
  var unlockTimer = null;

  function unlock() {
    submitted = false;
    if (btn) {
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
    }
    if (label) label.textContent = idleText;
    if (unlockTimer) {
      clearTimeout(unlockTimer);
      unlockTimer = null;
    }
  }

  form.addEventListener('submit', function (e) {
    if (submitted) {
      e.preventDefault();
      return false;
    }
    submitted = true;
    if (btn) {
      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
    }
    if (label) label.textContent = loadingText;
    // لو الطلب علق (شبكة/SMTP) نرجّع الزر بعد 20 ثانية بدل التحميل اللانهائي
    unlockTimer = setTimeout(unlock, 20000);
  });

  window.addEventListener('pageshow', function () { unlock(); });
})();
</script>
@endpush
@endif
