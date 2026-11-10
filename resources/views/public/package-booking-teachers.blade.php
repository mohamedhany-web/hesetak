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
        <p class="mc-lead"><strong>{{ $scheduleCopy }}</strong></p>
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
        <p class="mc-lead">{{ $isRtl ? 'مواد الباقة:' : 'Package subjects:' }} {{ $subjects->pluck('name')->implode(' · ') }}</p>
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
            <span>{{ $isRtl ? 'اختر المواد' : 'Choose subjects' }}@if($maxSubjects) (≤ {{ (int) $maxSubjects }})@endif</span>
            <div style="display:grid;gap:.4rem;margin-top:.4rem;max-height:220px;overflow:auto">
              @foreach($subjects as $subject)
                <label class="mc-chip" style="justify-content:flex-start">
                  <input
                    type="checkbox"
                    name="subjects[]"
                    value="{{ $subject->id }}"
                    @checked(in_array((int) $subject->id, $selectedSubjectIds, true))
                    data-subject-cap="{{ (int) $subjectCap }}"
                    onchange="window.__hesetakCapSubjects && window.__hesetakCapSubjects(this)"
                  >
                  {{ $subject->name }}
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
          @if(request()->filled('sessions'))
            <input type="hidden" name="sessions" value="{{ (int) request('sessions') }}">
          @endif
          <div class="mc-field">
            <label>{{ $isRtl ? 'حصص / أسبوع' : 'Lessons / week' }}</label>
            <div class="mc-filters">
              @foreach($weeklyOptions as $opt)
                <label class="mc-chip"><input type="radio" name="weekly" value="{{ $opt }}" @checked((int)$weekly === (int)$opt) onchange="this.form.submit()"> {{ $opt }}</label>
              @endforeach
            </div>
          </div>
          <label class="mc-field">{{ $isRtl ? 'عدد الأسابيع' : 'Weeks' }}
            <input type="number" min="1" max="16" name="weeks" value="{{ (int)$weeks }}" class="mc-input" onchange="this.form.submit()">
          </label>
        @endif
      </form>
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent)">
        <p>{{ $isRtl ? 'الحصص' : 'Sessions' }}: <strong>{{ (int)$sessions }}</strong>@if($isMixed) <span style="opacity:.75">({{ $isRtl ? 'مثبّت من الإدارة' : 'set by admin' }})</span>@endif</p>
        <p>{{ $isRtl ? 'سعر الحصة' : 'Per lesson' }}: <strong>{{ format_money($unitPrice, 2) }}</strong></p>
        <p>{{ $isRtl ? 'الإجمالي' : 'Total' }}: <strong>{{ format_money($totalPrice, 2) }}</strong></p>
      </div>
    </aside>

    <div>
      @if($isMixed)
        @php
          $visibleSubjects = $selectedSubjectIds !== []
            ? $subjects->whereIn('id', $selectedSubjectIds)
            : $subjects;
        @endphp
        @forelse($visibleSubjects as $subject)
          @php $subjectTeachers = $teachersBySubject[(int) $subject->id] ?? collect(); @endphp
          <section class="mc-card" style="padding:1rem;margin-bottom:1rem">
            <h2 class="mc-title" style="font-size:1.05rem;margin-bottom:.75rem">{{ $subject->name }}</h2>
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
                      <a class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:auto" href="{{ route('public.package-booking.slots', array_filter(['servicePackage' => $package->id, 'instructor' => $u, 'year' => $yearId, 'subject' => $subject->id, 'sessions' => $sessions])) }}">
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
                <a class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:auto" href="{{ route('public.package-booking.slots', array_filter(['servicePackage' => $package->id, 'instructor' => $u, 'year' => $yearId, 'subject' => $subjectId, 'weekly' => $weekly, 'weeks' => $weeks, 'sessions' => $sessions])) }}">
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
@if($isMixed)
<script>
window.__hesetakCapSubjects = function (el) {
  var cap = parseInt(el.getAttribute('data-subject-cap') || '0', 10);
  if (!cap || cap < 1) return;
  var form = el.form;
  if (!form) return;
  var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name=\"subjects[]\"]'));
  var checked = boxes.filter(function (b) { return b.checked; });
  if (checked.length > cap) {
    el.checked = false;
    alert({{ \Illuminate\Support\Js::from($isRtl ? 'تجاوزت الحد الأقصى لعدد المواد.' : 'Subject limit exceeded.') }});
  }
};
</script>
@endif
@endsection
