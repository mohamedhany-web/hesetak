@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $package = $package ?? null;
  $subjects = $subjects ?? collect();
  $teachersBySubject = $teachersBySubject ?? [];
  $checkoutUrl = $checkoutUrl ?? route('public.pricing');
  $yearId = $yearId ?? null;
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'تفاصيل الباقة' : 'Package details' }}</p>
    <h1>{{ $package->name }}</h1>
    @if(filled($package->tagline))
      <p class="mc-lead">{{ $package->tagline }}</p>
    @elseif(filled($package->description))
      <p class="mc-lead">{{ \Illuminate\Support\Str::limit(strip_tags($package->description), 180) }}</p>
    @endif
    @if($package->coverImageUrl())
      <div style="margin-top:1rem;max-width:420px;border-radius:1rem;overflow:hidden">
        <img src="{{ $package->coverImageUrl() }}" alt="{{ $package->name }}" style="display:block;width:100%;height:auto">
      </div>
    @endif
    <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.65rem">
      <a href="{{ route('public.package-booking.teachers', array_filter(['servicePackage' => $package->id, 'year' => $yearId ?? null, 'subject' => request()->integer('subject') ?: null])) }}" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'اختر معلماً ثم المواعيد' : 'Pick teacher & slots' }}</a>
      <a href="{{ $checkoutUrl }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'دفع مباشر' : 'Pay directly' }}</a>
      <a href="{{ route('public.package-cart.index') }}" class="mc-btn mc-btn--lg mc-btn--outline">{{ $isRtl ? 'السلة' : 'Cart' }}</a>
    </div>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.5rem">
    <article class="mc-card" style="padding:1.25rem">
      <h2 class="mc-title" style="font-size:1.15rem">{{ $isRtl ? 'محتوى الباقة' : 'What’s included' }}</h2>
      <ul style="margin:.75rem 0 0;padding-inline-start:1.1rem;line-height:1.7">
        <li>{{ $isRtl ? 'عدد الحصص:' : 'Sessions:' }} <strong>{{ (int) $package->units_count }}</strong></li>
        @if($package->session_minutes)
          <li>{{ $isRtl ? 'مدة الحصة:' : 'Session length:' }} <strong>{{ (int) $package->session_minutes }} {{ $isRtl ? 'دقيقة' : 'min' }}</strong></li>
        @endif
        @if($package->academicYear)
          <li>{{ $isRtl ? 'المرحلة:' : 'Stage:' }} <strong>{{ $package->academicYear->name }}</strong></li>
        @endif
        @if($subjects->isNotEmpty())
          <li>{{ $isRtl ? 'مواد يمكن اختيارها:' : 'Subjects you can pick:' }} <strong>{{ $subjects->pluck('name')->implode($isRtl ? '، ' : ', ') }}</strong></li>
        @endif
        <li>{{ $isRtl ? 'السعر:' : 'Price:' }} <strong>{{ $package->formattedPrice() }}</strong></li>
      </ul>
      @if(is_array($package->features) && count($package->features))
        <ul style="margin:1rem 0 0;padding-inline-start:1.1rem;line-height:1.7">
          @foreach(array_slice($package->features, 0, 8) as $feat)
            <li>{{ is_string($feat) ? $feat : (string) $feat }}</li>
          @endforeach
        </ul>
      @endif
    </article>

    <form method="get" action="{{ route('public.service-packages.checkout', $package) }}" id="pkg-build-form" class="mc-card" style="padding:1.25rem;display:grid;gap:1.25rem">
      @if($yearId)
        <input type="hidden" name="year" value="{{ (int) $yearId }}">
      @endif
      @if(request()->integer('sessions'))
        <input type="hidden" name="sessions" value="{{ request()->integer('sessions') }}">
      @endif

      <div>
        <h2 class="mc-title" style="font-size:1.1rem">{{ $isRtl ? '1) اختر المواد' : '1) Choose subjects' }}</h2>
        <p class="mc-lead" style="margin-top:.35rem;font-size:.92rem">{{ $isRtl ? 'يمكنك اختيار أكثر من مادة ثم تحديد معلم لكل مادة قبل إتمام الدفع عبر البوابة.' : 'Pick one or more subjects, then a teacher per subject before gateway checkout.' }}</p>
        @if($subjects->isEmpty())
          <p class="mc-lead" style="margin-top:.75rem">{{ $isRtl ? 'باقة عامة — أكمل الشراء ثم اختر المعلم من الدليل.' : 'General package — complete purchase then pick a teacher from the directory.' }}</p>
        @else
          <div style="display:flex;flex-wrap:wrap;gap:.65rem;margin-top:.85rem">
            @foreach($subjects as $subject)
              <label class="mc-btn mc-btn--sm mc-btn--outline" style="cursor:pointer;display:inline-flex;align-items:center;gap:.4rem">
                <input type="checkbox" name="subjects[]" value="{{ (int) $subject->id }}" data-subject-toggle="{{ (int) $subject->id }}" @checked(request()->integer('subject') === (int) $subject->id || $subjects->count() === 1)>
                {{ $subject->name }}
              </label>
            @endforeach
          </div>
        @endif
      </div>

      @foreach($subjects as $subject)
        @php $teachers = $teachersBySubject[(int) $subject->id] ?? collect(); @endphp
        <div data-subject-panel="{{ (int) $subject->id }}" hidden>
          <h3 class="mc-title" style="font-size:1rem">{{ $isRtl ? 'معلمون لمادة' : 'Teachers for' }} {{ $subject->name }}</h3>
          @if($teachers->isEmpty())
            <p class="mc-lead" style="margin-top:.5rem">{{ $isRtl ? 'لا يوجد معلمون منشورون لهذه المادة حالياً — يمكنك الشراء واختيار معلم لاحقاً.' : 'No published teachers for this subject yet — you can buy and pick later.' }}</p>
            <a href="{{ route('public.instructors.index', ['skill' => $subject->slug]) }}" class="mc-btn mc-btn--sm mc-btn--outline" style="margin-top:.75rem">{{ $isRtl ? 'تصفح دليل المعلمين' : 'Browse teachers' }}</a>
          @else
            <div class="mc-catalog-grid" style="margin-top:.85rem">
              @foreach($teachers as $profile)
                @php $u = $profile->user; @endphp
                @if($u)
                  <label class="mc-card" style="cursor:pointer;padding:1rem;display:block">
                    <div style="display:flex;gap:.65rem;align-items:flex-start">
                      <input type="radio" name="teacher_for[{{ (int) $subject->id }}]" value="{{ (int) $u->id }}" style="margin-top:.35rem">
                      <div>
                        <strong>{{ $u->name }}</strong>
                        @if(filled($profile->headline))
                          <p style="margin:.35rem 0;color:var(--mc-muted);font-size:.9rem">{{ \Illuminate\Support\Str::limit($profile->headline, 90) }}</p>
                        @endif
                        <a href="{{ route('public.instructors.show', $u) }}" class="mc-link-more" style="font-size:.85rem" onclick="event.stopPropagation()">{{ $isRtl ? 'الملف والمواعيد' : 'Profile & slots' }}</a>
                      </div>
                    </div>
                  </label>
                @endif
              @endforeach
            </div>
          @endif
        </div>
      @endforeach

      <div>
        <h3 class="mc-title" style="font-size:1rem">{{ $isRtl ? '2) ملاحظات المواعيد (اختياري)' : '2) Preferred times (optional)' }}</h3>
        <textarea name="schedule_notes" rows="3" class="mc-input" style="width:100%;margin-top:.5rem;padding:.75rem;border-radius:.75rem;border:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 16%, transparent)" placeholder="{{ $isRtl ? 'مثال: السبت والأحد مساءً بتوقيت الرياض' : 'e.g. Sat–Sun evenings Riyadh time' }}">{{ request('schedule_notes') }}</textarea>
      </div>

      <div style="display:flex;flex-wrap:wrap;gap:.65rem">
        <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'متابعة الدفع عبر البوابة' : 'Continue to gateway checkout' }}</button>
        <a href="{{ route('public.pricing') }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'كل الباقات' : 'All packages' }}</a>
      </div>
    </form>
  </div>
</section>

<script>
(function () {
  var form = document.getElementById('pkg-build-form');
  if (!form) return;
  function sync() {
    form.querySelectorAll('[data-subject-toggle]').forEach(function (cb) {
      var id = cb.getAttribute('data-subject-toggle');
      var panel = form.querySelector('[data-subject-panel="' + id + '"]');
      if (!panel) return;
      panel.hidden = !cb.checked;
      if (!cb.checked) {
        panel.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
      }
    });
  }
  form.addEventListener('change', sync);
  sync();
})();
</script>
@endsection
