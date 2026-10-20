@extends('layouts.mycourses-public')

@php
  $isRtl = app()->getLocale() === 'ar';
  $found = is_array($result ?? null) && ($result['found'] ?? false);
  $student = $found ? ($result['student'] ?? null) : null;
  $report = $found ? ($result['report'] ?? []) : [];
  $summary = $report['summary'] ?? [];
  $error = is_array($result ?? null) ? ($result['error'] ?? null) : null;
  $entered = $code ?? $token ?? null;
  $t = fn (string $ar, string $en) => $isRtl ? $ar : $en;
  $pill = function (?string $status) {
      $status = (string) $status;
      if (in_array($status, ['completed', 'present', 'active', 'sent', 'passed'], true)) {
          return 'pr-pill pr-pill--done';
      }
      if (in_array($status, ['scheduled', 'pending', 'pending_schedule', 'late'], true)) {
          return 'pr-pill pr-pill--wait';
      }
      return 'pr-pill pr-pill--off';
  };
@endphp

@push('head')
<link rel="stylesheet" href="{{ route('assets.landing.css', ['sheet' => 'parent-report']) }}?v={{ filemtime(public_path('css/landing/parent-report.css')) }}">
@if($entered)
<meta name="robots" content="noindex,nofollow">
@endif
@endpush

@section('content')
<section class="pr-hero">
  <div class="mc-container">
    <p class="mc-eyebrow">{{ $t('متابعة ولي الأمر', 'Parent follow-up') }}</p>
    <h1>{{ $t('تقرير ابنك على حصتك', 'Your child’s Hesetak report') }}</h1>
    <p class="mc-lead">{{ $t('أدخل رقم الطالب من ملفه، ويظهر لك حضور الحصص، التقارير، الكورسات، والاختبارات في صفحة واحدة.', 'Enter the student ID from their profile to see lessons, reports, courses, and exams on one page.') }}</p>
  </div>
</section>

<div class="pr-wrap">
  <form method="GET" action="{{ route('public.parent-progress') }}" class="pr-lookup">
    <div>
      <label for="student-code">{{ $t('رقم الطالب', 'Student ID') }}</label>
      <input id="student-code" name="code" required value="{{ $entered }}" dir="ltr" autocomplete="off" spellcheck="false" placeholder="HS-XXXX-XXXX" inputmode="text">
    </div>
    <button type="submit" class="mc-btn mc-btn--md mc-btn--primary"><i class="fas fa-file-lines"></i> {{ $t('عرض التقرير', 'View report') }}</button>
    <p class="pr-hint">{{ $t('الرقم يظهر في ملف الطالب. شاركه مع ولي الأمر فقط، ولا تستخدم رقم الحساب الداخلي.', 'The ID is on the student profile. Share it with a parent only. It is not the internal account number.') }}</p>
  </form>

  @if($error)
    <div class="pr-error" role="alert">{{ $error }}</div>
  @endif

  @if($found && $student)
    @php
      $initial = mb_substr((string) $student['name'], 0, 1);
      $insights = $report['insights'] ?? [];
      $upcoming = $report['school']['upcoming'] ?? [];
      $sessions = $report['private_sessions'] ?? [];
      $courses = $report['courses'] ?? [];
      $exams = $report['exams'] ?? [];
      $assignments = $report['assignments'] ?? [];
      $attendance = $report['attendance'] ?? [];
      $certs = $report['certificates'] ?? [];
      $credits = $report['entitlements'] ?? [];
      $monthly = $report['monthly_reports'] ?? [];
      $classes = $report['school']['classes'] ?? [];
    @endphp

    <div class="pr-sheet">
      <header class="pr-id">
        <div class="pr-avatar" aria-hidden="true">{{ $initial }}</div>
        <div>
          <h2>{{ $student['name'] }}</h2>
          <p>{{ $student['academic_year'] ?: $t('المرحلة غير محددة بعد', 'Stage not set yet') }}</p>
        </div>
        <div class="pr-code">
          <span>{{ $t('رقم الطالب', 'Student ID') }}</span>
          <strong dir="ltr">{{ $student['public_code'] ?? '' }}</strong>
        </div>
      </header>

      <div class="pr-kpis">
        <article class="pr-kpi"><strong>{{ $summary['school_progress_percent'] ?? 0 }}%</strong><span>{{ $t('التقدّم العام', 'Overall progress') }}</span></article>
        <article class="pr-kpi"><strong>{{ ($summary['sessions_attended'] ?? 0) }}/{{ $summary['sessions_total'] ?? 0 }}</strong><span>{{ $t('حصص حضرت', 'Lessons attended') }}</span></article>
        <article class="pr-kpi"><strong>{{ isset($summary['exam_average']) ? $summary['exam_average'].'%' : '—' }}</strong><span>{{ $t('متوسط الاختبارات', 'Exam average') }}</span></article>
        <article class="pr-kpi"><strong>{{ $summary['credits_left'] ?? 0 }}</strong><span>{{ $t('رصيد حصص متبقٍ', 'Lessons left') }}</span></article>
      </div>

      @if(!empty($insights['strengths']) || !empty($insights['improvements']))
        <div class="pr-split">
          <article class="pr-card">
            <h3>{{ $t('ما يسير بشكل جيد', 'What is going well') }}</h3>
            <ul>
              @forelse(($insights['strengths'] ?? []) as $item)
                <li>{{ $item }}</li>
              @empty
                <li>{{ $t('لا توجد نقاط قوة مسجّلة في هذه الفترة.', 'No strengths recorded for this period.') }}</li>
              @endforelse
            </ul>
          </article>
          <article class="pr-card">
            <h3>{{ $t('ما يحتاج متابعة', 'What needs attention') }}</h3>
            <ul>
              @forelse(($insights['improvements'] ?? []) as $item)
                <li>{{ $item }}</li>
              @empty
                <li>{{ $t('لا توجد تنبيهات حالية.', 'No current alerts.') }}</li>
              @endforelse
            </ul>
          </article>
        </div>
      @endif

      <p class="pr-section-label">{{ $t('القادم والرصيد', 'Coming up and balance') }}</p>
      <div class="pr-split">
        <article class="pr-card">
          <h3>{{ $t('الحصص القادمة', 'Upcoming lessons') }}</h3>
          @forelse($upcoming as $item)
            <div class="pr-row">
              <strong>{{ $item['title'] ?? '' }}</strong>
              <div class="pr-meta">
                @if(!empty($item['starts_at']))<span dir="ltr">{{ $item['starts_at'] }}</span>@endif
                @if(!empty($item['subtitle']))<span>{{ $item['subtitle'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد حصص قادمة حالياً.', 'No upcoming lessons right now.') }}</p>
          @endforelse
        </article>
        <article class="pr-card">
          <h3>{{ $t('رصيد الباقات', 'Package balance') }}</h3>
          @forelse($credits as $credit)
            <div class="pr-row">
              <strong>{{ $t('باقة حصص', 'Lesson package') }}</strong>
              <div class="pr-meta">
                <span>{{ $t('المتبقي', 'Left') }} {{ $credit['units_left'] ?? 0 }}</span>
                <span>{{ $t('المستخدم', 'Used') }} {{ $credit['units_used'] ?? 0 }}/{{ $credit['units_total'] ?? 0 }}</span>
                @if(!empty($credit['expires_at']))<span>{{ $t('حتى', 'Until') }} {{ $credit['expires_at'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا يوجد رصيد باقة نشط.', 'No active package balance.') }}</p>
          @endforelse
        </article>
      </div>

      <p class="pr-section-label">{{ $t('سجل التعلّم', 'Learning record') }}</p>
      <article class="pr-card">
        <h3>{{ $t('الحصص الفردية', 'Private lessons') }}</h3>
        @forelse($sessions as $session)
          <div class="pr-row">
            <strong>{{ $session['instructor'] ?: $t('معلم حصتك', 'Hesetak teacher') }}</strong>
            <div class="pr-meta">
              @if(!empty($session['course']))<span>{{ $session['course'] }}</span>@endif
              @if(!empty($session['scheduled_at']))<span dir="ltr">{{ $session['scheduled_at'] }}</span>@endif
              <span class="{{ $pill($session['status'] ?? '') }}">{{ $session['status_label'] ?? '' }}</span>
            </div>
          </div>
        @empty
          <p class="pr-empty">{{ $t('لم تُحجز حصص فردية بعد.', 'No private lessons yet.') }}</p>
        @endforelse
      </article>

      <div class="pr-split">
        <article class="pr-card">
          <h3>{{ $t('الكورسات', 'Courses') }}</h3>
          @forelse($courses as $course)
            <div class="pr-row">
              <strong>{{ $course['title'] }}</strong>
              <div class="pr-meta"><span>{{ (int) ($course['progress'] ?? 0) }}%</span><span>{{ $course['status'] }}</span></div>
              <div class="pr-bar" aria-hidden="true"><span style="width: {{ min(100, (int) ($course['progress'] ?? 0)) }}%"></span></div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد كورسات مسجّلة.', 'No enrolled courses.') }}</p>
          @endforelse
        </article>
        <article class="pr-card">
          <h3>{{ $t('الدروس والمناهج', 'Lessons and curricula') }}</h3>
          @forelse($classes as $class)
            <div class="pr-row">
              <strong>{{ $class['title'] }}</strong>
              <div class="pr-meta">
                @if(!empty($class['subject_name']))<span>{{ $class['subject_name'] }}</span>@endif
                @if(!empty($class['instructor_name']))<span>{{ $class['instructor_name'] }}</span>@endif
                <span>{{ $class['attended'] ?? 0 }}/{{ $class['total_sessions'] ?? 0 }}</span>
              </div>
              <div class="pr-bar" aria-hidden="true"><span style="width: {{ min(100, (int) ($class['progress_percent'] ?? 0)) }}%"></span></div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد دروس منهج مرتبطة بعد.', 'No curriculum lessons linked yet.') }}</p>
          @endforelse
        </article>
      </div>

      <div class="pr-split">
        <article class="pr-card">
          <h3>{{ $t('الاختبارات', 'Exams') }}</h3>
          @forelse($exams as $exam)
            <div class="pr-row">
              <strong>{{ $exam['title'] }}</strong>
              <div class="pr-meta">
                @if($exam['percentage'] !== null)<span>{{ $exam['percentage'] }}%</span>@endif
                @if(!empty($exam['date']))<span dir="ltr">{{ $exam['date'] }}</span>@endif
                @if(!empty($exam['status']))<span class="{{ $pill($exam['status']) }}">{{ $exam['status'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد اختبارات بعد.', 'No exams yet.') }}</p>
          @endforelse
        </article>
        <article class="pr-card">
          <h3>{{ $t('الواجبات', 'Assignments') }}</h3>
          @forelse($assignments as $assignment)
            <div class="pr-row">
              <strong>{{ $assignment['title'] }}</strong>
              <div class="pr-meta">
                @if($assignment['score'] !== null)<span>{{ $t('الدرجة', 'Score') }} {{ $assignment['score'] }}</span>@endif
                @if(!empty($assignment['submitted_at']))<span dir="ltr">{{ $assignment['submitted_at'] }}</span>@endif
                @if(!empty($assignment['status']))<span class="{{ $pill($assignment['status']) }}">{{ $assignment['status'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد واجبات بعد.', 'No assignments yet.') }}</p>
          @endforelse
        </article>
      </div>

      <div class="pr-split">
        <article class="pr-card">
          <h3>{{ $t('الحضور', 'Attendance') }}</h3>
          <div class="pr-meta" style="margin-bottom:.6rem">
            <span class="pr-pill pr-pill--done">{{ $t('حاضر', 'Present') }} {{ $attendance['present'] ?? 0 }}</span>
            <span class="pr-pill pr-pill--wait">{{ $t('متأخر', 'Late') }} {{ $attendance['late'] ?? 0 }}</span>
            <span class="pr-pill pr-pill--off">{{ $t('غائب', 'Absent') }} {{ $attendance['absent'] ?? 0 }}</span>
          </div>
          @forelse(($attendance['recent'] ?? []) as $row)
            <div class="pr-row">
              <strong>{{ $row['session_title'] }}</strong>
              <div class="pr-meta">
                <span class="{{ $pill($row['status'] ?? '') }}">{{ $row['status_label'] ?? '' }}</span>
                @if(!empty($row['starts_at']))<span dir="ltr">{{ $row['starts_at'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا يوجد سجل حضور بعد.', 'No attendance record yet.') }}</p>
          @endforelse
        </article>
        <article class="pr-card">
          <h3>{{ $t('التقارير الشهرية', 'Monthly reports') }}</h3>
          @forelse($monthly as $month)
            <div class="pr-row">
              <strong dir="ltr">{{ $month['month'] }}</strong>
              <div class="pr-meta">
                <span class="{{ $pill($month['status'] ?? '') }}">{{ $month['status'] }}</span>
                @if(!empty($month['sent_at']))<span>{{ $month['sent_at'] }}</span>@endif
              </div>
            </div>
          @empty
            <p class="pr-empty">{{ $t('لا توجد تقارير شهرية بعد.', 'No monthly reports yet.') }}</p>
          @endforelse
        </article>
      </div>

      @if($certs)
        <article class="pr-card">
          <h3>{{ $t('الشهادات', 'Certificates') }}</h3>
          <div class="pr-certs">
            @foreach($certs as $certificate)
              <div class="pr-cert">
                <strong>{{ $certificate['title'] }}</strong>
                <p>{{ $certificate['issued_at'] ?? '' }}</p>
                @if(!empty($certificate['verify_url']))
                  <a href="{{ $certificate['verify_url'] }}" class="mc-btn mc-btn--sm mc-btn--outline">{{ $t('تحقق', 'Verify') }}</a>
                @endif
              </div>
            @endforeach
          </div>
        </article>
      @endif
    </div>
  @elseif(!$entered)
    <div class="pr-intro">
      <article><i class="fas fa-calendar-check"></i><h3>{{ $t('الحصص والحضور', 'Lessons and attendance') }}</h3><p>{{ $t('الحصص الفردية القادمة والمنتهية، وحالة الحضور.', 'Upcoming and completed private lessons, plus attendance.') }}</p></article>
      <article><i class="fas fa-chart-line"></i><h3>{{ $t('الكورسات والنتائج', 'Courses and results') }}</h3><p>{{ $t('نسبة إنجاز الكورسات، الاختبارات، والواجبات.', 'Course progress, exams, and assignments.') }}</p></article>
      <article><i class="fas fa-wallet"></i><h3>{{ $t('الرصيد والتقارير', 'Balance and reports') }}</h3><p>{{ $t('ما تبقّى من الباقة، والشهادات، والتقارير الشهرية.', 'Remaining package units, certificates, and monthly reports.') }}</p></article>
    </div>
  @endif
</div>
@endsection
