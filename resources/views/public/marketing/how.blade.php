@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.how');
@endphp
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $p['eyebrow'] }}</p>
    <h1>{{ $p['title'] }}</h1>
    <p class="mc-lead">{{ $p['lead'] }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container">
    <div class="mc-tabs" role="tablist" aria-label="{{ $p['title'] }}">
      <button type="button" class="mc-tab is-active" data-mc-tab="student" role="tab" aria-selected="true">{{ $p['tab_student'] }}</button>
      <button type="button" class="mc-tab" data-mc-tab="teacher" role="tab" aria-selected="false">{{ $p['tab_teacher'] }}</button>
    </div>

    <div class="mc-tab-panel" data-mc-panel="student" role="tabpanel">
      <div class="mc-steps">
        @foreach($p['student_steps'] as $i => $step)
          <div class="mc-step">
            <div class="mc-step__n">{{ $i + 1 }}</div>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['body'] }}</p>
          </div>
        @endforeach
      </div>
      <div style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
        <a href="{{ route('register') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ __('landing.nav.register') }}</a>
        <a href="{{ route('public.instructors.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ __('landing.mc.hero.cta_primary') }}</a>
      </div>
    </div>

    <div class="mc-tab-panel" data-mc-panel="teacher" role="tabpanel" hidden>
      <div class="mc-steps">
        @foreach($p['teacher_steps'] as $i => $step)
          <div class="mc-step">
            <div class="mc-step__n">{{ $i + 1 }}</div>
            <h3>{{ $step['title'] }}</h3>
            <p>{{ $step['body'] }}</p>
          </div>
        @endforeach
      </div>
      <div style="margin-top:1.5rem">
        <a href="{{ route('public.tutor.apply') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ __('landing.mc.hero.cta_secondary') }}</a>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  var tabs = document.querySelectorAll('[data-mc-tab]');
  var panels = document.querySelectorAll('[data-mc-panel]');
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var id = tab.getAttribute('data-mc-tab');
      tabs.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (p) {
        p.hidden = p.getAttribute('data-mc-panel') !== id;
      });
    });
  });
})();
</script>
@endpush
