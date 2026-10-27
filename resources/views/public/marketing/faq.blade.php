@extends('layouts.mycourses-public')

@section('content')
@php
    $p = __('hesetak_pages.faq');
    $hasDbFaqs = isset($faqs) && $faqs->flatten()->count() > 0;
    $teacherCategories = ['teacher', 'teachers', 'معلم', 'مدرب', 'teacher_apply'];
    if ($hasDbFaqs) {
        $allDbFaqs = $faqs->flatten();
        $dbStudentFaqs = $allDbFaqs->filter(function ($faq) use ($teacherCategories) {
            $cat = strtolower(trim((string) ($faq->category ?? '')));
            return $cat === '' || ! in_array($cat, $teacherCategories, true);
        });
        $dbTeacherFaqs = $allDbFaqs->filter(function ($faq) use ($teacherCategories) {
            $cat = strtolower(trim((string) ($faq->category ?? '')));
            return in_array($cat, $teacherCategories, true);
        });
    }
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
    <div class="mc-tabs" role="tablist">
      <button type="button" class="mc-tab is-active" data-mc-tab="student" role="tab" aria-selected="true">{{ $p['tab_student'] }}</button>
      <button type="button" class="mc-tab" data-mc-tab="teacher" role="tab" aria-selected="false">{{ $p['tab_teacher'] }}</button>
    </div>

    <div class="mc-tab-panel" data-mc-panel="student" role="tabpanel">
      <div class="mc-faq-list">
        @if($hasDbFaqs)
          @forelse($dbStudentFaqs as $faq)
            <details class="mc-faq-item">
              <summary>{{ $faq->question }}</summary>
              <p>{{ $faq->answer }}</p>
            </details>
          @empty
            <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'لا أسئلة في هذا القسم بعد.' : 'No questions in this section yet.' }}</p>
          @endforelse
        @else
          @foreach($p['student'] as $faq)
            <details class="mc-faq-item">
              <summary>{{ $faq['q'] }}</summary>
              <p>{{ $faq['a'] }}</p>
            </details>
          @endforeach
        @endif
      </div>
    </div>

    <div class="mc-tab-panel" data-mc-panel="teacher" role="tabpanel" hidden>
      <div class="mc-faq-list">
        @if($hasDbFaqs)
          @forelse($dbTeacherFaqs as $faq)
            <details class="mc-faq-item">
              <summary>{{ $faq->question }}</summary>
              <p>{{ $faq->answer }}</p>
            </details>
          @empty
            <p class="mc-lead">{{ app()->getLocale() === 'ar' ? 'لا أسئلة للمعلمين في لوحة التحكم بعد.' : 'No teacher FAQs from admin yet.' }}</p>
          @endforelse
        @else
          @foreach($p['teacher'] as $faq)
            <details class="mc-faq-item">
              <summary>{{ $faq['q'] }}</summary>
              <p>{{ $faq['a'] }}</p>
            </details>
          @endforeach
        @endif
      </div>
    </div>

    @if(! $hasDbFaqs && !empty($defaultFaqs) && is_array($defaultFaqs) && count($defaultFaqs))
      <div style="margin-top:2rem">
        <h2 class="mc-title" style="font-size:1.25rem;margin-bottom:1rem">{{ app()->getLocale() === 'ar' ? 'المزيد' : 'More' }}</h2>
        <div class="mc-faq-list">
          @foreach($defaultFaqs as $faq)
            <details class="mc-faq-item">
              <summary>{{ is_array($faq) ? ($faq['question'] ?? $faq['q'] ?? '') : '' }}</summary>
              <p>{{ is_array($faq) ? ($faq['answer'] ?? $faq['a'] ?? '') : '' }}</p>
            </details>
          @endforeach
        </div>
      </div>
    @endif

    <div style="margin-top:2rem">
      <a href="{{ route('public.contact') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $p['contact_cta'] }}</a>
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
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-mc-panel') !== id;
      });
    });
  });
})();
</script>
@endpush
