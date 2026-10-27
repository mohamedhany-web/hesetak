@php
    $isRtl = $isRtl ?? (app()->getLocale() === 'ar');
    $tracks = $curriculumBrowserTracks ?? [];
    $stages = $curriculumBrowserStages ?? collect();
    $browserPayload = [
        'tracks' => array_values($tracks),
        'stages' => $stages->map(fn ($y) => [
            'id' => (string) $y->id,
            'name' => $y->name,
            'slug' => $y->slug,
            'subjects' => $y->subjects->map(fn ($s) => [
                'id' => (string) $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
            ])->values()->all(),
        ])->values()->all(),
        'pricingBase' => route('public.pricing'),
        'teachersBase' => route('public.instructors.index'),
        'labels' => [
            'emptySubjects' => $isRtl ? 'لا توجد مواد لهذه المرحلة بعد.' : 'No subjects for this stage yet.',
            'pickSubject' => $isRtl ? 'اختر مادة من التبويب أعلاه لعرض المحتوى والخطوات التالية.' : 'Pick a subject above to see content and next steps.',
            'body' => $isRtl
                ? 'المحتوى الدراسي لهذه المادة جاهز للمتابعة بحصص فردية أو اختيار باقة حصص مناسبة.'
                : 'This subject is ready for 1:1 follow-up or a matching lesson package.',
            'ctaPackages' => $isRtl ? 'باقات هذه المادة' : 'Packages for this subject',
            'ctaTeachers' => $isRtl ? 'المعلمون المناسبون' : 'Matching teachers',
        ],
    ];
@endphp
@if($stages->isNotEmpty() && count($tracks) > 0)
<section class="mc-section mc-section--compact" id="curriculum-browser" aria-labelledby="mc-curr-browser-title">
  <div class="mc-container dp-shell">
    <div class="mc-section-head">
      <div>
        <p class="mc-eyebrow">{{ $isRtl ? 'تصفّح المنهج' : 'Browse curriculum' }}</p>
        <h2 class="mc-title" id="mc-curr-browser-title">{{ $isRtl ? 'اختر المنهج ثم الصف ثم المادة' : 'Pick curriculum, then grade, then subject' }}</h2>
        <p class="mc-lead">{{ $isRtl ? 'ثلاث خطوات واضحة لعرض المحتوى الدراسي المناسب لطلبك.' : 'Three clear steps to see the right school content for your request.' }}</p>
      </div>
      <a class="mc-link-more" href="{{ route('public.curricula') }}">{{ $isRtl ? 'صفحة المناهج' : 'Curricula page' }} →</a>
    </div>

    <div class="mc-curr-browser" data-mc-curr-browser='@json($browserPayload)'>
      <p class="mc-curr-browser__label">{{ $isRtl ? '1) نوع المنهج' : '1) Curriculum type' }}</p>
      <div class="mc-curr-browser__tabs" data-role="tracks" role="tablist" aria-label="{{ $isRtl ? 'نوع المنهج' : 'Curriculum type' }}"></div>

      <p class="mc-curr-browser__label">{{ $isRtl ? '2) الصف / المرحلة' : '2) Grade / stage' }}</p>
      <div class="mc-curr-browser__tabs mc-curr-browser__tabs--stage" data-role="stages" role="tablist" aria-label="{{ $isRtl ? 'الصف / المرحلة' : 'Grade / stage' }}"></div>

      <p class="mc-curr-browser__label">{{ $isRtl ? '3) المادة الدراسية' : '3) Subject' }}</p>
      <div class="mc-curr-browser__tabs mc-curr-browser__tabs--subject" data-role="subjects" role="tablist" aria-label="{{ $isRtl ? 'المادة' : 'Subject' }}"></div>

      <div class="mc-curr-browser__panel" data-role="panel" hidden></div>
      <div class="mc-curr-browser__panel mc-curr-browser__panel--hint" data-role="hint">
        <p class="mc-lead" style="margin:0">{{ $browserPayload['labels']['pickSubject'] }}</p>
      </div>
    </div>
  </div>
</section>
<script>
(function () {
  var root = document.querySelector('[data-mc-curr-browser]');
  if (!root) return;
  var data = {};
  try { data = JSON.parse(root.getAttribute('data-mc-curr-browser') || '{}'); } catch (e) { return; }
  var tracks = data.tracks || [];
  var stages = data.stages || [];
  var labels = data.labels || {};
  var state = {
    track: tracks[0] ? tracks[0].key : '',
    stageId: stages[0] ? stages[0].id : '',
    subjectId: ''
  };

  var tracksEl = root.querySelector('[data-role="tracks"]');
  var stagesEl = root.querySelector('[data-role="stages"]');
  var subjectsEl = root.querySelector('[data-role="subjects"]');
  var panelEl = root.querySelector('[data-role="panel"]');
  var hintEl = root.querySelector('[data-role="hint"]');

  function currentStage() {
    return stages.find(function (s) { return s.id === state.stageId; }) || stages[0] || null;
  }
  function currentSubject() {
    var stage = currentStage();
    if (!stage) return null;
    return (stage.subjects || []).find(function (s) { return s.id === state.subjectId; }) || null;
  }
  function trackLabel() {
    var t = tracks.find(function (x) { return x.key === state.track; });
    return t ? t.label : state.track;
  }
  function makeTab(text, isOn, onClick) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'mc-curr-browser__tab' + (isOn ? ' is-on' : '');
    btn.setAttribute('role', 'tab');
    btn.textContent = text;
    btn.addEventListener('click', onClick);
    return btn;
  }
  function renderTracks() {
    tracksEl.innerHTML = '';
    tracks.forEach(function (t) {
      tracksEl.appendChild(makeTab(t.label, state.track === t.key, function () {
        state.track = t.key;
        state.subjectId = '';
        render();
      }));
    });
  }
  function renderStages() {
    stagesEl.innerHTML = '';
    stages.forEach(function (s) {
      stagesEl.appendChild(makeTab(s.name, state.stageId === s.id, function () {
        state.stageId = s.id;
        state.subjectId = '';
        render();
      }));
    });
  }
  function renderSubjects() {
    subjectsEl.innerHTML = '';
    var stage = currentStage();
    var list = stage ? (stage.subjects || []) : [];
    if (!list.length) {
      var p = document.createElement('p');
      p.className = 'mc-curr-browser__empty';
      p.textContent = labels.emptySubjects || '';
      subjectsEl.appendChild(p);
      return;
    }
    list.forEach(function (sub) {
      subjectsEl.appendChild(makeTab(sub.name, state.subjectId === sub.id, function () {
        state.subjectId = sub.id;
        render();
      }));
    });
  }
  function renderPanel() {
    var subject = currentSubject();
    var stage = currentStage();
    if (!subject || !stage) {
      panelEl.hidden = true;
      hintEl.hidden = false;
      return;
    }
    hintEl.hidden = true;
    panelEl.hidden = false;
    var pricing = new URL(data.pricingBase, window.location.origin);
    pricing.searchParams.set('curriculum_type', state.track);
    pricing.searchParams.set('year', stage.id);
    pricing.searchParams.set('subject', subject.id);
    pricing.hash = 'mc-pricing-storefront';
    var teachers = new URL(data.teachersBase, window.location.origin);
    teachers.searchParams.set('curriculum', state.track);
    if (stage.slug) teachers.searchParams.set('stage', stage.slug);
    if (subject.slug) teachers.searchParams.set('skill', subject.slug);

    panelEl.innerHTML =
      '<p class="mc-curr-browser__summary">' + trackLabel() + ' · ' + stage.name + ' · ' + subject.name + '</p>' +
      '<p class="mc-lead" style="margin:0 0 1rem">' + (labels.body || '') + '</p>' +
      '<div style="display:flex;flex-wrap:wrap;gap:.65rem">' +
        '<a href="' + pricing.pathname + pricing.search + pricing.hash + '" class="mc-btn mc-btn--md mc-btn--secondary">' + (labels.ctaPackages || '') + '</a>' +
        '<a href="' + teachers.pathname + teachers.search + '" class="mc-btn mc-btn--md mc-btn--outline">' + (labels.ctaTeachers || '') + '</a>' +
      '</div>';
  }
  function render() {
    renderTracks();
    renderStages();
    renderSubjects();
    renderPanel();
  }
  render();
})();
</script>
@endif
