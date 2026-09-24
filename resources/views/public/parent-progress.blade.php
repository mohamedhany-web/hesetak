@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $found = is_array($result ?? null) && ($result['found'] ?? false);
  $student = $found ? ($result['student'] ?? null) : null;
  $report = $found ? ($result['report'] ?? []) : [];
  $summary = $report['summary'] ?? [];
  $error = is_array($result ?? null) ? ($result['error'] ?? null) : null;
@endphp

@push('head')
<meta name="robots" content="noindex,nofollow">
@endpush

@section('content')
<section class="mc-page-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $isRtl ? 'متابعة ولي الأمر' : 'Parent progress' }}</p>
    <h1>{{ $isRtl ? 'اطّلع على تقدّم الطالب' : 'View student progress' }}</h1>
    <p class="mc-lead">{{ $isRtl ? 'الصق رمز المشاركة من ملف الطالب (أو افتح الرابط الكامل الذي يصلك من ولي الأمر).' : 'Paste the share code from the student profile (or open the full link you were given).' }}</p>
  </div>
</section>

<section class="mc-section">
  <div class="mc-container" style="max-width:1050px">
    <form method="GET" action="{{ route('public.parent-progress') }}" class="mc-card" style="padding:1.25rem;margin-bottom:1.5rem">
      <div class="mc-field">
        <label for="token">{{ $isRtl ? 'رمز المشاركة' : 'Share code' }}</label>
        <input id="token" type="text" name="token" required value="{{ $token }}" class="mc-input" dir="ltr" autocomplete="off" spellcheck="false" placeholder="{{ $isRtl ? 'الصق الرمز هنا' : 'Paste code here' }}">
      </div>
      <button type="submit" class="mc-btn mc-btn--md mc-btn--primary"><i class="fas fa-search"></i> {{ $isRtl ? 'عرض التقرير' : 'View report' }}</button>
    </form>

    @if($error)<div class="mc-empty" style="margin-bottom:1rem;color:#991b1b">{{ $error }}</div>@endif

    @if($found && $student)
      <article class="mc-card" style="padding:1.25rem;margin-bottom:1.5rem">
        <h2 style="margin:.5rem 0">{{ $student['name'] }}</h2>
        <p style="margin:0;color:var(--mc-muted)">{{ $student['academic_year'] ?: ($isRtl ? 'لم تُحدد المرحلة' : 'Stage not set') }}</p>
      </article>

      <div class="mc-stats" style="margin-bottom:1.5rem">
        @foreach([
          [$summary['school_progress_percent'] ?? 0, $isRtl ? 'التقدّم %' : 'Progress %'],
          [($summary['sessions_attended'] ?? 0).'/'.($summary['sessions_total'] ?? 0), $isRtl ? 'حضور الحصص' : 'Attendance'],
          [isset($summary['exam_average']) ? $summary['exam_average'].'%' : '−', $isRtl ? 'متوسط النتائج' : 'Exam average'],
          [$summary['credits_left'] ?? 0, $isRtl ? 'رصيد الحصص' : 'Credits'],
        ] as [$value, $label])
          <article class="mc-stat"><strong class="mc-stat__num">{{ $value }}</strong><span class="mc-stat__label">{{ $label }}</span></article>
        @endforeach
      </div>

      @php $insights = $report['insights'] ?? []; @endphp
      @if(!empty($insights['strengths']) || !empty($insights['improvements']))
        <div class="mc-grid" style="margin-bottom:1.5rem">
          <article class="mc-card" style="padding:1.25rem">
            <h2 style="font-size:1.1rem;margin:0 0 1rem">{{ $isRtl ? 'نقاط القوة' : 'Strengths' }}</h2>
            <ul style="margin:0;padding-inline-start:1.1rem;line-height:1.7">
              @foreach(($insights['strengths'] ?? []) as $s)
                <li>{{ $s }}</li>
              @endforeach
            </ul>
          </article>
          <article class="mc-card" style="padding:1.25rem">
            <h2 style="font-size:1.1rem;margin:0 0 1rem">{{ $isRtl ? 'مؤشرات التحسّن' : 'Improvement signals' }}</h2>
            <ul style="margin:0;padding-inline-start:1.1rem;line-height:1.7">
              @forelse(($insights['improvements'] ?? []) as $i)
                <li>{{ $i }}</li>
              @empty
                <li>{{ $isRtl ? 'لا توجد تنبيهات حرجة.' : 'No critical alerts.' }}</li>
              @endforelse
            </ul>
          </article>
        </div>
      @endif

      @php
        $reportSections = [
          'classes' => [$report['school']['classes'] ?? [], $isRtl ? 'الدروس والمناهج' : 'Lessons', ['title', 'subject_name', 'instructor_name']],
          'attendance' => [$report['attendance']['recent'] ?? [], $isRtl ? 'الحضور الأخير' : 'Recent attendance', ['session_title', 'status_label', 'starts_at']],
          'exams' => [$report['exams'] ?? [], $isRtl ? 'الاختبارات' : 'Exams', ['title', 'percentage', 'date']],
          'assignments' => [$report['assignments'] ?? [], $isRtl ? 'الواجبات' : 'Assignments', ['title', 'score', 'submitted_at']],
          'private' => [$report['private_sessions'] ?? [], $isRtl ? 'الحصص الفردية 1:1' : '1:1 sessions', ['instructor', 'status_label', 'scheduled_at']],
          'courses' => [$report['courses'] ?? [], $isRtl ? 'تقدّم الكورسات' : 'Course progress', ['title', 'progress', 'status']],
          'monthly' => [$report['monthly_reports'] ?? [], $isRtl ? 'التقارير الشهرية' : 'Monthly reports', ['month', 'status']],
        ];
      @endphp
      <div class="mc-grid">
        @foreach($reportSections as [$rows, $heading, $fields])
          <article class="mc-card" style="padding:1.25rem">
            <h2 style="font-size:1.1rem;margin:0 0 1rem">{{ $heading }}</h2>
            @forelse($rows as $row)
              <div style="padding:.7rem 0;border-bottom:1px solid var(--mc-line);display:flex;flex-wrap:wrap;gap:.45rem .8rem">
                @foreach($fields as $field)
                  @if(isset($row[$field]) && $row[$field] !== '')<span>{{ $row[$field] }}@if($field === 'percentage' || $field === 'progress')%@endif</span>@endif
                @endforeach
              </div>
            @empty
              <p style="color:var(--mc-muted)">{{ $isRtl ? 'لا توجد بيانات بعد.' : 'No data yet.' }}</p>
            @endforelse
          </article>
        @endforeach
      </div>

      @if(!empty($report['certificates']))
        <section class="mc-section mc-section--tight">
          <div class="mc-section-head"><h2>{{ $isRtl ? 'الشهادات' : 'Certificates' }}</h2></div>
          <div class="mc-grid mc-grid--3">
            @foreach($report['certificates'] as $certificate)
              <article class="mc-card"><div class="mc-card__body"><h3 class="mc-card__title">{{ $certificate['title'] }}</h3><p>{{ $certificate['issued_at'] ?? '' }}</p>@if(!empty($certificate['verify_url']))<a href="{{ $certificate['verify_url'] }}" class="mc-btn mc-btn--sm mc-btn--outline">{{ $isRtl ? 'تحقق' : 'Verify' }}</a>@endif</div></article>
            @endforeach
          </div>
        </section>
      @endif
    @elseif(!$token)
      <div class="mc-tracks">
        <article class="mc-track"><span class="mc-track__icon"><i class="fas fa-calendar-check"></i></span><h3>{{ $isRtl ? 'الحضور والحصص' : 'Attendance' }}</h3><p>{{ $isRtl ? 'تابع الحصص المنفذة والقادمة.' : 'Track completed and upcoming lessons.' }}</p></article>
        <article class="mc-track"><span class="mc-track__icon"><i class="fas fa-chart-line"></i></span><h3>{{ $isRtl ? 'التقدّم والنتائج' : 'Progress' }}</h3><p>{{ $isRtl ? 'راجع نتائج الاختبارات والكورسات.' : 'Review exams and course progress.' }}</p></article>
        <article class="mc-track"><span class="mc-track__icon"><i class="fas fa-file-lines"></i></span><h3>{{ $isRtl ? 'التقارير' : 'Reports' }}</h3><p>{{ $isRtl ? 'اطّلع على التقارير التعليمية المتاحة.' : 'View available learning reports.' }}</p></article>
      </div>
    @endif
  </div>
</section>
@endsection
