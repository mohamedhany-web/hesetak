@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $package = $package ?? null;
  $profiles = $profiles ?? collect();
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container" style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:flex-end">
    <div>
      <p class="mc-eyebrow">{{ $isRtl ? 'المسار: الباقة → المعلمون' : 'Flow: package → teachers' }}</p>
      <h1>{{ $package->name }}</h1>
      <p class="mc-lead">{{ $isRtl ? 'اختر معلماً يدرّس المادة، ثم استكشف المواعيد.' : 'Pick a teacher for the subject, then explore slots.' }}</p>
    </div>
    <a href="{{ route('public.package-cart.index') }}" class="mc-btn mc-btn--md mc-btn--outline">{{ $isRtl ? 'السلة' : 'Cart' }} ({{ (int) ($cartCount ?? 0) }})</a>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.25rem;grid-template-columns:minmax(220px,280px) 1fr">
    <aside class="mc-card" style="padding:1rem;align-self:start">
      <h2 class="mc-title" style="font-size:1rem">{{ $isRtl ? 'المنهج / المرحلة / المادة' : 'Curriculum / stage / subject' }}</h2>
      <form method="get" style="display:grid;gap:.75rem;margin-top:.75rem">
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
        <label class="mc-field">{{ $isRtl ? 'المادة' : 'Subject' }}
          <select name="subject" class="mc-input" onchange="this.form.submit()">
            <option value="">{{ $isRtl ? 'الكل' : 'All' }}</option>
            @foreach($subjects as $subject)
              <option value="{{ $subject->id }}" @selected((string)$subjectId === (string)$subject->id)>{{ $subject->name }}</option>
            @endforeach
          </select>
        </label>
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
      </form>
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent)">
        <p>{{ $isRtl ? 'الحصص' : 'Sessions' }}: <strong>{{ (int)$sessions }}</strong></p>
        <p>{{ $isRtl ? 'سعر الحصة' : 'Per lesson' }}: <strong>{{ format_money($unitPrice, 2) }}</strong></p>
        <p>{{ $isRtl ? 'الإجمالي' : 'Total' }}: <strong>{{ format_money($totalPrice, 2) }}</strong></p>
      </div>
    </aside>

    <div>
      @if($profiles->isEmpty())
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
                <a class="mc-btn mc-btn--md mc-btn--primary" style="margin-top:auto" href="{{ route('public.package-booking.slots', array_filter(['servicePackage' => $package->id, 'instructor' => $u->id, 'year' => $yearId, 'subject' => $subjectId, 'weekly' => $weekly, 'weeks' => $weeks])) }}">
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
@endsection
