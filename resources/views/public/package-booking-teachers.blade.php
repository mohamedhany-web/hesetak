@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $package = $package ?? null;
  $profiles = $profiles ?? collect();
  $isMixed = $isMixed ?? false;
  $maxSubjects = $maxSubjects ?? null;
  $teachersBySubject = $teachersBySubject ?? [];
  $selectedSubjectIds = collect($selectedSubjectIds ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
  $subjects = $subjects ?? collect();
  $subjectCap = $maxSubjects ?: max(1, $subjects->count());
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container" style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:flex-end">
    <div>
      <p class="mc-eyebrow">{{ $isRtl ? 'المسار: الباقة → المعلمون' : 'Flow: package → teachers' }}</p>
      <h1>{{ $displayName ?? $package->name }}</h1>
      @if(!empty($scheduleCopy))
        <p class="mc-lead" id="pbt-schedule"><strong>{{ $scheduleCopy }}</strong></p>
      @endif
      @if($package->description)
        <p class="mc-lead">{{ $package->description }}</p>
      @endif
      @if($isMixed)
        <p class="mc-lead">{{ $isRtl ? 'اختر المواد (حسب الحد المحدد من الإدارة)، ثم معلماً لكل مادة مع مواعيده.' : 'Pick subjects (admin cap), then a teacher and slots per subject.' }}</p>
        @if($maxSubjects)
          <p class="mc-lead">{{ $isRtl ? 'الحد الأقصى للمواد:' : 'Subject limit:' }} <strong>{{ (int) $maxSubjects }}</strong></p>
        @endif
      @else
        <p class="mc-lead">{{ $isRtl ? 'اختر معلماً يدرّس المادة، ثم استكشف المواعيد.' : 'Pick a teacher for the subject, then explore slots.' }}</p>
      @endif
      @if(! $isMixed && !empty($lockSubject) && $subjectId)
        @php $lockedSubject = $subjects->firstWhere('id', $subjectId); @endphp
        <p class="mc-lead">{{ $isRtl ? 'المادة المختارة:' : 'Selected subject:' }} <strong>{{ $lockedSubject->name ?? ($displayName ?? '') }}</strong></p>
      @elseif($subjects->isNotEmpty())
        <div class="pbt-subjects" aria-label="{{ $isRtl ? 'مواد الباقة' : 'Package subjects' }}">
          <p class="pbt-subjects__label">{{ $isRtl ? 'مواد الباقة' : 'Package subjects' }} <span>({{ $subjects->count() }})</span></p>
          <ol class="pbt-subjects__list">
            @foreach($subjects as $subject)
              <li class="pbt-subjects__item">
                @if($isMixed)
                  <a href="#pbt-subject-{{ (int) $subject->id }}">{{ $subject->name }}</a>
                @else
                  <span>{{ $subject->name }}</span>
                @endif
              </li>
            @endforeach
          </ol>
        </div>
      @endif
      @if($package->featureList())
        <ul class="mc-lead" style="margin:.5rem 0 0;padding-inline-start:1.1rem">
          @foreach($package->featureList() as $feature)
            <li>{{ $feature }}</li>
          @endforeach
        </ul>
      @endif
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem">
      <a href="{{ route('public.package-cart.index') }}" class="mc-btn mc-btn--md mc-btn--primary">{{ $isRtl ? 'استكمال الشراء' : 'Continue checkout' }} ({{ (int) ($cartCount ?? 0) }})</a>
      <a href="{{ route('public.package-cart.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'السلة' : 'Cart' }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.25rem;grid-template-columns:minmax(220px,280px) 1fr">
    <aside class="mc-card" style="padding:1rem;align-self:start">
      <h2 class="mc-title" style="font-size:1rem">{{ $isRtl ? 'المنهج / المرحلة / المادة' : 'Curriculum / stage / subject' }}</h2>
      <form method="get" id="pkg-booking-filters" style="display:grid;gap:.75rem;margin-top:.75rem">
        <label class="mc-field">{{ $isRtl ? 'نوع المنهج' : 'Curriculum' }}
          <select name="curriculum" class="mc-input" onchange="this.form.submit()">
            <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
            @foreach(($curriculumTypes ?? []) as $key => $meta)
              <option value="{{ $key }}" @selected($curriculum === $key)>{{ is_array($meta) ? ($meta['label'] ?? $key) : $meta }}</option>
            @endforeach
          </select>
        </label>
        <label class="mc-field">{{ $isRtl ? 'المرحلة' : 'Stage' }}
          <select name="year" class="mc-input" onchange="this.form.submit()">
            <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
            @foreach($years as $year)
              <option value="{{ $year->id }}" @selected((string)$yearId === (string)$year->id)>{{ $year->name }}</option>
            @endforeach
          </select>
        </label>

        @if($isMixed)
          <div class="mc-field">
            <span class="pbt-pick__title">
              {{ $isRtl ? 'اختر المواد' : 'Choose subjects' }}
              <small data-pbt-count>{{ count($selectedSubjectIds) }}@if($maxSubjects) / {{ (int) $maxSubjects }}@endif</small>
            </span>
            <div class="pbt-pick">
              @foreach($subjects as $subject)
                <label class="pbt-pick__item">
                  <input
                    type="checkbox"
                    name="subjects[]"
                    value="{{ $subject->id }}"
                    @checked(in_array((int) $subject->id, $selectedSubjectIds, true))
                    data-subject-cap="{{ (int) $subjectCap }}"
                    onchange="window.__hesetakCapSubjects && window.__hesetakCapSubjects(this)"
                  >
                  <span>{{ $subject->name }}</span>
                </label>
              @endforeach
            </div>
            <button type="submit" class="mc-btn mc-btn--sm mc-btn--secondary" style="margin-top:.5rem">{{ $isRtl ? 'تحديث المعلمين' : 'Refresh teachers' }}</button>
          </div>
          <input type="hidden" name="sessions" value="{{ (int) $sessions }}">
        @else
          <label class="mc-field">{{ $isRtl ? 'المادة' : 'Subject' }}
            @if(!empty($lockSubject) && $subjectId)
              <input type="hidden" name="subject" value="{{ (int) $subjectId }}">
              <input type="text" class="mc-input" value="{{ $subjects->firstWhere('id', $subjectId)?->name ?? ($displayName ?? '') }}" readonly>
            @else
              <select name="subject" class="mc-input" onchange="this.form.submit()">
                <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
                @foreach($subjects as $subject)
                  <option value="{{ $subject->id }}" @selected((string)$subjectId === (string)$subject->id)>{{ $subject->name }}</option>
                @endforeach
              </select>
            @endif
          </label>
          <div class="mc-field">
            <label>{{ $isRtl ? 'حصص / أسبوع' : 'Lessons / week' }}</label>
            <div class="mc-filters">
              @foreach($weeklyOptions as $opt)
                <label class="mc-chip"><input type="radio" name="weekly" value="{{ $opt }}" @checked((int)$weekly === (int)$opt) onchange="window.__pbtRefresh(this.form)"> {{ $opt }}</label>
              @endforeach
            </div>
          </div>
          <label class="mc-field">{{ $isRtl ? 'عدد الأسابيع' : 'Weeks' }}
            <input type="number" min="1" max="16" name="weeks" value="{{ (int)$weeks }}" class="mc-input" onchange="window.__pbtRefresh(this.form)" oninput="window.__pbtRefresh(this.form, 450)">
          </label>
        @endif
      </form>
      <div id="pbt-summary" style="margin-top:1rem;padding-top:1rem;border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent)">
        <p>{{ $isRtl ? 'الحصص' : 'Sessions' }}: <strong data-pbt-sessions>{{ (int)$sessions }}</strong>@if($isMixed) <span style="opacity:.75">({{ $isRtl ? 'مثبّت من الإدارة' : 'set by admin' }})</span>@endif</p>
        <p>{{ $isRtl ? 'سعر الحصة' : 'Per lesson' }}: <strong>{{ format_money($unitPrice, 2) }}</strong></p>
        <p>{{ $isRtl ? 'الإجمالي' : 'Total' }}: <strong>{{ format_money($totalPrice, 2) }}</strong></p>
      </div>
    </aside>

    <div id="pbt-main">
      @if($isMixed)
        @forelse(($visibleSubjects ?? collect())->values() as $subjectIndex => $subject)
          @php $subjectTeachers = $teachersBySubject[(int) $subject->id] ?? collect(); @endphp
          <section class="mc-card pbt-section" id="pbt-subject-{{ (int) $subject->id }}">
            <header class="pbt-section__head">
              <span class="pbt-section__num" aria-hidden="true">{{ $subjectIndex + 1 }}</span>
              <h2 class="pbt-section__title">{{ $subject->name }}</h2>
              <span class="pbt-section__count">{{ $isRtl ? $subjectTeachers->count().' معلم' : $subjectTeachers->count().' teachers' }}</span>
            </header>
            @if($subjectTeachers->isEmpty())
              <div class="mc-empty">{{ $isRtl ? 'لا يوجد معلمون لهذه المادة حالياً.' : 'No teachers for this subject yet.' }}</div>
            @else
              <div class="mc-catalog-grid">
                @foreach($subjectTeachers as $profile)
                  @php $u = $profile->user; @endphp
                  @if($u)
                    <article class="mc-card" style="padding:1rem;display:flex;flex-direction:column;gap:.65rem">
                      <h3 class="mc-card__title"><a href="{{ route('public.instructors.show', $u) }}">{{ $u->name }}</a></h3>
                      @if(filled($profile->headline))
                        <p style="color:var(--mc-muted);font-size:.92rem">{{ \Illuminate\Support\Str::limit($profile->headline, 100) }}</p>
                      @endif
                      @if(filled($profile->bio))
                        <p style="font-size:.9rem">{{ \Illuminate\Support\Str::limit(strip_tags($profile->bio), 140) }}</p>
                      @endif
                      @php $skills = $profile->skills_list ?? []; @endphp
                      @if(count($skills))
                        <div class="mc-cats">
                          @foreach(array_slice($skills, 0, 4) as $skill)
                            <span class="mc-cat"><span class="mc-cat__dot" aria-hidden="true"></span>{{ \Illuminate\Support\Str::limit($skill, 28) }}</span>
                          @endforeach
                        </div>
                      @endif
                      <a class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:auto" href="{{ route('public.package-booking.slots', array_filter(['servicePackage' => $package->id, 'instructor' => $u, 'curriculum' => $curriculum, 'year' => $yearId, 'subject' => $subject->id, 'sessions' => $sessions])) }}">
                        {{ $isRtl ? 'مواعيد هذه المادة' : 'Slots for this subject' }}
                      </a>
                    </article>
                  @endif
                @endforeach
              </div>
            @endif
          </section>
        @empty
          <div class="mc-empty">{{ $isRtl ? 'اختر مادة واحدة على الأقل من القائمة.' : 'Select at least one subject.' }}</div>
        @endforelse
      @elseif($profiles->isEmpty())
        <div class="mc-empty">{{ $isRtl ? 'لا يوجد معلمون مطابقون حالياً — جرّب تغيير الفلاتر.' : 'No matching teachers yet — try different filters.' }}</div>
      @else
        <div class="mc-catalog-grid">
          @foreach($profiles as $profile)
            @php $u = $profile->user; @endphp
            @if($u)
              <article class="mc-card" style="padding:1rem;display:flex;flex-direction:column;gap:.65rem">
                <h3 class="mc-card__title"><a href="{{ route('public.instructors.show', $u) }}">{{ $u->name }}</a></h3>
                @if(filled($profile->headline))
                  <p style="color:var(--mc-muted);font-size:.92rem">{{ \Illuminate\Support\Str::limit($profile->headline, 100) }}</p>
                @endif
                @if(filled($profile->bio))
                  <p style="font-size:.9rem">{{ \Illuminate\Support\Str::limit(strip_tags($profile->bio), 140) }}</p>
                @endif
                @php $skills = $profile->skills_list ?? []; @endphp
                @if(count($skills))
                  <div class="mc-cats">
                    @foreach(array_slice($skills, 0, 4) as $skill)
                      <span class="mc-cat"><span class="mc-cat__dot" aria-hidden="true"></span>{{ \Illuminate\Support\Str::limit($skill, 28) }}</span>
                    @endforeach
                  </div>
                @endif
                <a class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:auto" href="{{ route('public.package-booking.slots', array_filter(['servicePackage' => $package->id, 'instructor' => $u, 'curriculum' => $curriculum, 'year' => $yearId, 'subject' => $subjectId, 'weekly' => $weekly, 'weeks' => $weeks, 'sessions' => $sessions])) }}">
                  {{ $isRtl ? 'استكشاف المواعيد' : 'Explore slots' }}
                </a>
              </article>
            @endif
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>
<style>
  .pbt-subjects { margin: .75rem 0 0; }
  .pbt-subjects__label { margin: 0 0 .5rem; font-weight: 800; }
  .pbt-subjects__label span { font-weight: 600; opacity: .7; }
  .pbt-subjects__list { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: .5rem; counter-reset: pbt; }
  .pbt-subjects__item { counter-increment: pbt; }
  .pbt-subjects__item a, .pbt-subjects__item span {
    display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .8rem .35rem .6rem;
    border: 1px solid var(--mc-line, #dfe5ee); border-radius: 999px; background: #fff;
    font-weight: 700; font-size: .88rem; color: var(--mc-ink, #0b1b33); text-decoration: none;
  }
  .pbt-subjects__item a::before, .pbt-subjects__item span::before {
    content: counter(pbt); display: inline-grid; place-items: center; width: 1.45rem; height: 1.45rem;
    border-radius: 50%; background: var(--mc-navy, #0b1b33); color: #fff; font-size: .75rem; font-weight: 800;
  }
  .pbt-subjects__item a:hover { border-color: var(--mc-primary-70, #1e4e8c); }
  .pbt-pick__title { display: flex; justify-content: space-between; align-items: center; font-weight: 800; }
  .pbt-pick__title small { font-weight: 700; padding: .1rem .55rem; border-radius: 999px; background: rgba(30,78,140,.1); }
  .pbt-pick { display: grid; gap: .4rem; margin-top: .5rem; max-height: 260px; overflow: auto; padding-inline-end: .15rem; }
  .pbt-pick__item {
    display: flex; align-items: center; gap: .55rem; padding: .5rem .7rem; cursor: pointer;
    border: 1px solid var(--mc-line, #dfe5ee); border-radius: .75rem; background: #fff; font-size: .9rem; font-weight: 600;
  }
  .pbt-pick__item:has(input:checked) { border-color: var(--mc-primary-70, #1e4e8c); background: rgba(30,78,140,.07); }
  .pbt-section { padding: 1rem; margin-bottom: 1rem; scroll-margin-top: 5rem; }
  .pbt-section__head {
    display: flex; align-items: center; gap: .65rem; margin-bottom: .85rem; padding-bottom: .7rem;
    border-bottom: 1px solid var(--mc-line, #dfe5ee);
  }
  .pbt-section__num {
    display: inline-grid; place-items: center; width: 2rem; height: 2rem; flex: none; border-radius: 50%;
    background: var(--mc-navy, #0b1b33); color: #fff; font-weight: 800;
  }
  .pbt-section__title { margin: 0; font-size: 1.1rem; font-weight: 800; flex: 1; min-width: 0; }
  .pbt-section__count { font-size: .8rem; font-weight: 700; padding: .2rem .65rem; border-radius: 999px; background: rgba(201,162,39,.16); }
  @media (max-width: 860px) {
    .mc-section > .mc-container[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
  }
</style>
<script>
// الحفاظ على موضع التمرير عند تغيير أي فلتر (الصفحة تُعاد بطلب GET).
(function () {
  var KEY = 'pbt-scroll:' + location.pathname;
  try {
    var saved = sessionStorage.getItem(KEY);
    if (saved !== null) {
      sessionStorage.removeItem(KEY);
      if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
      var y = parseInt(saved, 10) || 0;
      window.scrollTo(0, y);
      window.addEventListener('load', function () { window.scrollTo(0, y); });
    }
  } catch (e) {}
  var form = document.getElementById('pkg-booking-filters');
  if (!form) return;
  function remember() {
    try { sessionStorage.setItem(KEY, String(window.scrollY || window.pageYOffset || 0)); } catch (e) {}
  }
  // capture: يعمل قبل onchange="this.form.submit()" المضمّن في الحقول
  form.addEventListener('change', remember, true);
  form.addEventListener('submit', remember);
  var origSubmit = form.submit;
  form.submit = function () { remember(); return origSubmit.call(form); };

  // تحديث فوري لعدد الحصص الكامل والسعر والإجمالي عند تغيير حصص/أسبوع أو الأسابيع (بدون إعادة تحميل الصفحة).
  var timer = null;
  var seq = 0;
  window.__pbtRefresh = function (f, delay) {
    var params = new URLSearchParams(new FormData(f));
    var weekly = parseInt(params.get('weekly') || '0', 10);
    var weeks = Math.max(1, Math.min(16, parseInt(params.get('weeks') || '0', 10) || 0));
    var counter = document.querySelector('[data-pbt-sessions]');
    if (counter && weekly && weeks) counter.textContent = String(weekly * weeks);
    clearTimeout(timer);
    timer = setTimeout(function () {
      var mine = ++seq;
      var url = location.pathname + '?' + params.toString();
      fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { if (!r.ok) throw new Error('bad'); return r.text(); })
        .then(function (html) {
          if (mine !== seq) return;
          var doc = new DOMParser().parseFromString(html, 'text/html');
          ['pbt-schedule', 'pbt-summary', 'pbt-main'].forEach(function (id) {
            var fresh = doc.getElementById(id), current = document.getElementById(id);
            if (fresh && current) current.innerHTML = fresh.innerHTML;
          });
          history.replaceState(null, '', url);
        })
        .catch(function () { f.submit(); });
    }, delay || 0);
  };
})();
</script>
@if($isMixed)
<div id="pbt-upsell" class="pbt-upsell" role="dialog" aria-modal="true" aria-labelledby="pbt-upsell-text" hidden>
  <div class="pbt-upsell__backdrop" data-pbt-upsell-close></div>
  <div class="pbt-upsell__card">
    <button type="button" class="pbt-upsell__x" data-pbt-upsell-close aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}">&times;</button>
    <i class="fas fa-layer-group pbt-upsell__icon" aria-hidden="true"></i>
    <p id="pbt-upsell-text" class="pbt-upsell__text">{{ $isRtl ? 'اضغط للحصول على باقة بمواد دراسية أكثر' : 'Tap to get a package with more subjects' }}</p>
    <a class="mc-btn mc-btn--md mc-btn--primary" href="{{ route('public.pricing') }}#mc-pricing-storefront">{{ $isRtl ? 'احصل على الباقة الحين' : 'Get the package now' }}</a>
  </div>
</div>
<style>
  .pbt-upsell { position: fixed; inset: 0; z-index: 2147482000; display: flex; align-items: center; justify-content: center; padding: 1rem; }
  .pbt-upsell[hidden] { display: none; }
  .pbt-upsell__backdrop { position: absolute; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(3px); }
  .pbt-upsell__card { position: relative; width: min(100%, 26rem); padding: 1.75rem 1.5rem; border-radius: 1.2rem; background: #fff; text-align: center; box-shadow: 0 28px 60px -24px rgba(15,23,42,.5); }
  .pbt-upsell__x { position: absolute; top: .6rem; inset-inline-end: .7rem; width: 2.1rem; height: 2.1rem; border: 0; border-radius: 50%; background: #f1f5f9; font-size: 1.3rem; line-height: 1; cursor: pointer; }
  .pbt-upsell__icon { font-size: 1.8rem; color: var(--mc-gold, #c9952a); margin-bottom: .6rem; }
  .pbt-upsell__text { margin: 0 0 1.1rem; font-weight: 800; font-size: 1.05rem; line-height: 1.6; color: var(--mc-ink, #0b1b33); }
</style>
<script>
window.__hesetakCapSubjects = function (el) {
  var cap = parseInt(el.getAttribute('data-subject-cap') || '0', 10);
  if (!cap || cap < 1) return;
  var form = el.form;
  if (!form) return;
  var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name=\"subjects[]\"]'));
  var checked = boxes.filter(function (b) { return b.checked; });
  var counter = form.querySelector('[data-pbt-count]');
  if (counter) counter.textContent = Math.min(checked.length, cap) + ' / ' + cap;
  if (checked.length > cap) {
    el.checked = false;
    if (counter) counter.textContent = cap + ' / ' + cap;
    var modal = document.getElementById('pbt-upsell');
    if (modal) modal.hidden = false;
  }
};
(function () {
  var modal = document.getElementById('pbt-upsell');
  if (!modal) return;
  modal.querySelectorAll('[data-pbt-upsell-close]').forEach(function (n) {
    n.addEventListener('click', function () { modal.hidden = true; });
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') modal.hidden = true; });
})();
</script>
@endif
@include('partials.popup-ad', ['ad' => $activePopupAd ?? null])
@endsection
