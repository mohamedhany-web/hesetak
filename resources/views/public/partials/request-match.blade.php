@php
    $p = $p ?? __('hesetak_pages.curricula');
    $matchDom = $matchDom ?? 'mc-curr';
    $matchHeading = $matchHeading ?? 'h2';
    $matchYears = $matchYears ?? collect();
    $curriculumTypes = $curriculumTypes ?? [];
@endphp
<form class="mc-curr-match {{ $matchClass ?? '' }}" id="{{ $matchDom }}-match" action="{{ route('public.pricing') }}" method="get">
  <{{ $matchHeading }} class="mc-curr-match__title" @if($matchHeading === 'h1') id="mc-request-title" @endif>{{ $p['match_title'] }}</{{ $matchHeading }}>
  <p class="mc-curr-match__lead">{{ $p['match_lead'] }}</p>
  <div class="mc-curr-match__grid">
    <label>
      <span>{{ $p['match_curriculum'] }}</span>
      <select class="mc-select" id="{{ $matchDom }}-track" name="curriculum_type" required>
        <option value="">{{ $p['match_pick'] }}</option>
        @foreach($curriculumTypes as $key => $type)
          <option value="{{ $key }}">{{ $type['label'] }}</option>
        @endforeach
      </select>
    </label>
    <label>
      <span>{{ $p['match_stage'] }}</span>
      <select class="mc-select" id="{{ $matchDom }}-stage" required>
        <option value="">{{ $p['match_pick'] }}</option>
        @foreach($matchYears as $year)
          <option value="{{ $year['id'] }}" data-slug="{{ $year['slug'] }}">{{ $year['name'] }}</option>
        @endforeach
      </select>
    </label>
    <label>
      <span>{{ $p['match_subject'] }}</span>
      <select class="mc-select" id="{{ $matchDom }}-subject" required disabled>
        <option value="">{{ $p['match_pick_stage_first'] }}</option>
      </select>
    </label>
  </div>
  <p class="mc-curr-match__goal" id="{{ $matchDom }}-goal" hidden></p>
  <div class="mc-curr-match__actions">
    <a class="mc-btn mc-btn--md mc-btn--primary is-locked" id="{{ $matchDom }}-package" href="{{ route('public.pricing') }}#mc-pricing-storefront" aria-disabled="true">{{ $p['match_package'] }}</a>
    <a class="mc-btn mc-btn--md mc-btn--outline is-locked" id="{{ $matchDom }}-teachers" href="{{ route('public.instructors.index') }}" aria-disabled="true">{{ $p['match_teachers'] }}</a>
  </div>
  <p class="mc-curr-note">{{ $p['match_need'] }}</p>
</form>
<script>
(function () {
  var rootId = @json($matchDom);
  var years = @json($matchYears);
  var track = document.getElementById(rootId + '-track');
  var stage = document.getElementById(rootId + '-stage');
  var subject = document.getElementById(rootId + '-subject');
  var goal = document.getElementById(rootId + '-goal');
  var packageLink = document.getElementById(rootId + '-package');
  var teacherLink = document.getElementById(rootId + '-teachers');
  var form = document.getElementById(rootId + '-match');
  var note = form ? form.querySelector('.mc-curr-note') : null;
  if (!track || !stage || !subject || !form) return;

  var pricingBase = @json(route('public.pricing'));
  var teachersBase = @json(route('public.instructors.index'));
  var goalPrefix = @json($p['match_goal']);
  var readyNote = @json($p['match_ready']);
  var needNote = @json($p['match_need']);
  var pickLabel = @json($p['match_pick']);
  var pickStageFirst = @json($p['match_pick_stage_first']);

  function selectedText(select) {
    var option = select.options[select.selectedIndex];
    return option && option.value ? option.textContent.trim() : '';
  }

  function fillSubjects() {
    var year = years.find(function (item) { return String(item.id) === String(stage.value); });
    var current = subject.value;
    subject.innerHTML = '';
    var empty = document.createElement('option');
    empty.value = '';
    empty.textContent = year ? pickLabel : pickStageFirst;
    subject.appendChild(empty);
    (year ? year.subjects : []).forEach(function (item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = item.name;
      option.dataset.slug = item.slug;
      subject.appendChild(option);
    });
    subject.disabled = !year || !year.subjects.length;
    if (current && year && year.subjects.some(function (item) { return String(item.id) === current; })) {
      subject.value = current;
    }
  }

  function lock(link, href) {
    if (href) {
      link.href = href;
      link.classList.remove('is-locked');
      link.removeAttribute('aria-disabled');
    } else {
      link.classList.add('is-locked');
      link.setAttribute('aria-disabled', 'true');
    }
  }

  function sync() {
    fillSubjects();
    var ready = track.value && stage.value && subject.value;
    if (!ready) {
      goal.hidden = true;
      goal.textContent = '';
      lock(packageLink, '');
      lock(teacherLink, '');
      if (note) note.textContent = needNote;
      return;
    }
    goal.hidden = false;
    goal.textContent = goalPrefix + ' ' + selectedText(track) + ' · ' + selectedText(stage) + ' · ' + selectedText(subject);
    if (note) note.textContent = readyNote;
    var stageOption = stage.options[stage.selectedIndex];
    var subjectOption = subject.options[subject.selectedIndex];
    var pricing = new URL(pricingBase, window.location.origin);
    pricing.searchParams.set('curriculum_type', track.value);
    pricing.searchParams.set('year', stage.value);
    pricing.searchParams.set('subject', subject.value);
    pricing.hash = 'mc-pricing-storefront';
    var teachers = new URL(teachersBase, window.location.origin);
    teachers.searchParams.set('curriculum', track.value);
    teachers.searchParams.set('stage', stageOption.dataset.slug || stage.value);
    teachers.searchParams.set('skill', subjectOption.dataset.slug || subject.value);
    lock(packageLink, pricing.pathname + pricing.search + pricing.hash);
    lock(teacherLink, teachers.pathname + teachers.search);
  }

  if (rootId === 'mc-curr') {
    document.querySelectorAll('.mc-curr-stage[data-stage-id]').forEach(function (card) {
      card.addEventListener('click', function (event) {
        event.preventDefault();
        stage.value = card.getAttribute('data-stage-id') || '';
        subject.value = '';
        sync();
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (!track.value) {
          track.focus();
          if (goal) {
            goal.hidden = false;
            goal.textContent = @json(($p['match_pick_curriculum_next'] ?? null) ?: 'اختر نوع المنهج ثم المادة لإكمال طلبك بالأعلى');
          }
          if (note) note.textContent = needNote;
        } else {
          subject.focus();
        }
      });
    });
  }

  track.addEventListener('change', sync);
  stage.addEventListener('change', function () {
    subject.value = '';
    sync();
  });
  subject.addEventListener('change', sync);
  form.addEventListener('submit', function (event) {
    event.preventDefault();
  });

  // بعد الرجوع من المتصفح تُستعاد القيم بدون change → نعيد مزامنة الأزرار
  sync();
  window.addEventListener('pageshow', function () { sync(); });
})();
</script>
