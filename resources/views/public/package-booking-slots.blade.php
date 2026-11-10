@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'المسار: المعلمون → المواعيد' : 'Flow: teachers → slots' }}</p>
    <h1>{{ $instructor->name }}</h1>
    <p class="mc-lead">{{ $package->name }} · {{ (int)$sessions }} {{ $isRtl ? 'حصة' : 'sessions' }} · {{ format_money($totalPrice, 2) }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="display:grid;gap:1.25rem;max-width:720px">
    <article class="mc-card" style="padding:1.25rem">
      <h2 class="mc-title" style="font-size:1.1rem">{{ $isRtl ? 'المواعيد المتاحة' : 'Available slots' }}</h2>
      <p class="mc-lead" style="margin-top:.35rem">{{ $isRtl ? 'الأوقات بتوقيتك مع تمييز صباحاً (AM) / مساءً (PM).' : 'Times in your timezone with AM/PM.' }} · {{ \App\Support\AppTimezone::label($viewerTz) }}</p>

      <form method="post" action="{{ route('public.package-booking.add-cart', $package) }}" style="display:grid;gap:1rem;margin-top:1rem">
        @csrf
        <input type="hidden" name="instructor_id" value="{{ $instructor->id }}">
        @if($yearId)<input type="hidden" name="year_id" value="{{ (int) $yearId }}">@endif
        @if($subjectId)<input type="hidden" name="subject_id" value="{{ (int) $subjectId }}">@endif
        <input type="hidden" name="weekly_sessions" value="{{ (int) $weekly }}">
        <input type="hidden" name="weeks" value="{{ (int) $weeks }}">
        <input type="hidden" name="sessions" value="{{ (int) $sessions }}">
        @if(session('error'))
          <p class="mc-lead" style="color:#b91c1c">{{ session('error') }}</p>
        @endif
        @if($errors->any())
          <p class="mc-lead" style="color:#b91c1c">{{ $errors->first() }}</p>
        @endif

        @if($slots->isEmpty())
          <p class="mc-lead">{{ $isRtl ? 'لا مواعيد منشورة حالياً — اقترح موعداً أدناه.' : 'No published slots — suggest a time below.' }}</p>
        @else
          <div class="mc-tp-slots">
            @foreach($slots as $slot)
              <label class="mc-tp-slot">
                <input type="checkbox" name="suggested_slots[]" value="{{ $slot['starts_at'] }}">
                <span>{{ $slot['label'] }}</span>
              </label>
            @endforeach
          </div>
        @endif

        <div style="border-top:1px solid color-mix(in srgb, var(--mc-ink,#0b1b33) 12%, transparent);padding-top:1rem;display:grid;gap:.65rem">
          <strong>{{ $isRtl ? 'اقتراح موعد إضافي (AM/PM)' : 'Suggest another time (AM/PM)' }}</strong>
          <label>{{ $isRtl ? 'التاريخ' : 'Date' }}<input type="date" name="propose_date" min="{{ now()->toDateString() }}" class="mc-input"></label>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.65rem">
            <label>{{ $isRtl ? 'ساعة' : 'Hour' }}
              <select name="propose_hour" class="mc-input">@for($h=1;$h<=12;$h++)<option value="{{ $h }}">{{ $h }}</option>@endfor</select>
            </label>
            <label>{{ $isRtl ? 'دقيقة' : 'Min' }}
              <select name="propose_minute" class="mc-input"><option>00</option><option>15</option><option>30</option><option>45</option></select>
            </label>
            <label>{{ $isRtl ? 'فترة' : 'Period' }}
              <select name="propose_period" class="mc-input"><option value="AM">AM {{ $isRtl ? 'صباحاً' : '' }}</option><option value="PM">PM {{ $isRtl ? 'مساءً' : '' }}</option></select>
            </label>
          </div>
          <label>{{ $isRtl ? 'ملاحظات' : 'Notes' }}<textarea name="schedule_notes" rows="2" class="mc-input"></textarea></label>
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:.65rem">
          <button type="submit" class="mc-btn mc-btn--lg mc-btn--primary">{{ $isRtl ? 'أضف للسلة وتابع' : 'Add to cart & continue' }}</button>
          <a href="{{ route('public.package-booking.teachers', ['servicePackage' => $package->id, 'year' => $yearId, 'subject' => $subjectId, 'weekly' => $weekly, 'weeks' => $weeks]) }}" class="mc-btn mc-btn--lg mc-btn--soft">{{ $isRtl ? 'رجوع للمعلمين' : 'Back to teachers' }}</a>
        </div>
      </form>
    </article>
  </div>
</section>
@endsection
