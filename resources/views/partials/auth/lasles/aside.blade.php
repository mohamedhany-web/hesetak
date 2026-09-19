@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
@endphp
<aside class="lasles-auth-aside" aria-label="{{ __('auth.cx_aside_aria') }}">
  <p class="lasles-auth-kicker">{{ __('auth.cx_kicker') }}</p>
  <h2 class="lasles-auth-aside__title">
    {!! __('auth.cx_aside_title_html') !!}
  </h2>
  <p class="lasles-auth-aside__lead">{{ __('auth.cx_aside_lead') }}</p>
  <ol class="lasles-auth-loop">
    <li>
      <span class="lasles-auth-loop__num" aria-hidden="true">1</span>
      <div>
        <p class="lasles-auth-loop__label">{{ __('auth.loop_diagnose') }}</p>
        <p class="lasles-auth-loop__desc">{{ __('auth.loop_diagnose_desc') }}</p>
      </div>
    </li>
    <li>
      <span class="lasles-auth-loop__num" aria-hidden="true">2</span>
      <div>
        <p class="lasles-auth-loop__label">{{ __('auth.loop_access') }}</p>
        <p class="lasles-auth-loop__desc">{{ __('auth.loop_access_desc') }}</p>
      </div>
    </li>
    <li>
      <span class="lasles-auth-loop__num" aria-hidden="true">3</span>
      <div>
        <p class="lasles-auth-loop__label">{{ __('auth.loop_develop') }}</p>
        <p class="lasles-auth-loop__desc">{{ __('auth.loop_develop_desc') }}</p>
      </div>
    </li>
    <li>
      <span class="lasles-auth-loop__num" aria-hidden="true">4</span>
      <div>
        <p class="lasles-auth-loop__label">{{ __('auth.loop_measure') }}</p>
        <p class="lasles-auth-loop__desc">{{ __('auth.loop_measure_desc') }}</p>
      </div>
    </li>
  </ol>
</aside>
