@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $brand = config('app.name', 'حصتك');
    $found = is_array($result ?? null) && ($result['found'] ?? false);
    $student = $found ? ($result['student'] ?? null) : null;
    $report = $found ? ($result['report'] ?? []) : [];
    $summary = $report['summary'] ?? [];
    $error = is_array($result ?? null) ? ($result['error'] ?? null) : null;
    $footer = \App\Services\PublicFooterSettings::payload();
    $waUrl = $footer['whatsapp_url'] ?? '#';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $isRtl ? 'متابعة ولي الأمر' : 'Parent progress' }} — {{ $brand }}</title>
  <meta name="description" content="{{ $isRtl ? 'أدخل رقم دخول الطالب لعرض تقارير الحضور والتقدّم والامتحانات والحصص.' : 'Enter the student entry ID to view attendance, progress, exams, and class reports.' }}">
  <meta name="theme-color" content="#0B3D91">
  <meta name="robots" content="noindex,follow">
  <link rel="canonical" href="{{ route('public.parent-progress') }}">
  @include('partials.favicon-links')
  @include('partials.landing.head', ['landingCss' => ['theme', 'courses-catalog', 'contact']])
  <style>
    .gl-pp-page { background: var(--bg, #F4F7FC); }
    .gl-pp-main { padding-top: 72px; }
    @media (max-width: 991px) { .gl-pp-main { padding-top: 64px; } }

    .gl-pp-hero {
      position: relative;
      overflow: hidden;
      padding: clamp(36px, 6vw, 56px) 0 clamp(72px, 10vw, 96px);
      background:
        radial-gradient(ellipse 60% 80% at 100% 0%, rgba(245,184,0,.22), transparent 50%),
        linear-gradient(145deg, #051F4D 0%, #0B3D91 48%, #1A56B0 100%);
      color: #fff;
    }
    .gl-pp-hero__grid {
      display: grid;
      gap: 28px;
      align-items: center;
    }
    @media (min-width: 900px) {
      .gl-pp-hero__grid { grid-template-columns: 1.15fr .85fr; gap: 40px; }
    }
    .gl-pp-hero__eyebrow {
      display: inline-flex; align-items: center; gap: .45rem;
      padding: .4rem .85rem; border-radius: 999px;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      font-size: .72rem; font-weight: 800; margin-bottom: .9rem;
    }
    .gl-pp-hero__eyebrow i { color: var(--gold, #F5B800); }
    .gl-pp-hero h1 {
      font-family: var(--font-display, Cairo, sans-serif);
      font-size: clamp(1.7rem, 4vw, 2.5rem);
      font-weight: 900; line-height: 1.25; margin: 0 0 .7rem;
    }
    .gl-pp-hero h1 .hl { color: var(--gold, #F5B800); }
    .gl-pp-hero__sub {
      margin: 0; max-width: 34rem;
      color: rgba(255,255,255,.88); font-size: .92rem; line-height: 1.8; font-weight: 600;
    }
    .gl-pp-hero__trust {
      display: flex; flex-wrap: wrap; gap: 8px; margin-top: 1.1rem;
    }
    .gl-pp-hero__trust span {
      display: inline-flex; align-items: center; gap: .35rem;
      padding: 6px 11px; border-radius: 999px;
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.16);
      font-size: .68rem; font-weight: 800;
    }
    .gl-pp-hero__trust i { color: var(--gold, #F5B800); }

    .gl-pp-search {
      background: #fff;
      color: #0B1220;
      border-radius: 20px;
      padding: 1.15rem 1.15rem 1.25rem;
      border: 1px solid rgba(255,255,255,.35);
      box-shadow: 0 18px 40px rgba(5, 31, 77, .28);
    }
    .gl-pp-search h2 {
      margin: 0 0 .35rem; font-size: 1rem; font-weight: 900; color: #0B3D91;
    }
    .gl-pp-search p {
      margin: 0 0 1rem; font-size: .78rem; color: #7b8499; line-height: 1.6; font-weight: 600;
    }
    .gl-pp-search__row {
      display: grid;
      grid-template-columns: 1fr;
      gap: .65rem;
    }
    @media (min-width: 520px) {
      .gl-pp-search__row { grid-template-columns: 1fr auto; align-items: stretch; }
    }
    .gl-pp-search input {
      width: 100%; min-height: 3.05rem; border-radius: 14px;
      border: 1.5px solid #d7deea; background: #F8FAFD;
      padding: 0 1rem; font-size: 1.05rem; font-weight: 800;
      font-variant-numeric: tabular-nums; color: #0B1220; outline: none;
    }
    .gl-pp-search input:focus {
      border-color: #0B3D91; background: #fff;
      box-shadow: 0 0 0 3px rgba(11,61,145,.12);
    }
    .gl-pp-search button {
      min-height: 3.05rem; border: 0; border-radius: 14px;
      padding: 0 1.35rem; background: #0B3D91; color: #fff;
      font-weight: 800; cursor: pointer; white-space: nowrap;
    }
    .gl-pp-search button:hover { background: #072A66; }

    .gl-pp-body {
      margin-top: -48px;
      position: relative;
      z-index: 2;
      padding-bottom: clamp(48px, 7vw, 80px);
    }
    .gl-pp-alert {
      border-radius: 16px; padding: 1rem 1.15rem; margin-bottom: 1rem;
      background: #fff4f4; border: 1px solid #f3c4c4;
      color: #8a1f1f; font-size: .9rem; font-weight: 700;
    }
    .gl-pp-card {
      background: #fff;
      border: 1px solid #e6ebf4;
      border-radius: 20px;
      padding: clamp(1rem, 2.5vw, 1.35rem);
      margin-bottom: 1rem;
      overflow: hidden;
    }
    .gl-pp-card__head {
      display: flex; align-items: center; justify-content: space-between;
      gap: .75rem; margin-bottom: .95rem;
    }
    .gl-pp-card__head h2 {
      margin: 0; font-size: 1.02rem; font-weight: 900; color: #0B3D91;
      display: flex; align-items: center; gap: .5rem;
    }
    .gl-pp-card__head h2::before {
      content: ""; width: .55rem; height: .55rem; border-radius: 50%;
      background: #F5B800; flex: 0 0 auto;
    }

    .gl-pp-profile {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 1rem;
      align-items: center;
    }
    .gl-pp-avatar {
      width: 68px; height: 68px; border-radius: 18px; object-fit: cover;
      background: #eef3fb; border: 2px solid #F5B800;
    }
    .gl-pp-avatar--ph {
      display: grid; place-items: center;
      font-weight: 900; color: #0B3D91; font-size: 1.45rem;
    }
    .gl-pp-id {
      display: inline-flex; align-items: center;
      background: #0B3D91; color: #fff; border-radius: 999px;
      padding: .28rem .75rem; font-size: .76rem; font-weight: 800;
      font-variant-numeric: tabular-nums; margin-bottom: .35rem;
    }
    .gl-pp-name {
      margin: 0 0 .2rem; font-size: clamp(1.15rem, 2.5vw, 1.4rem);
      font-weight: 900; color: #0B1220; line-height: 1.3;
    }
    .gl-pp-meta { margin: 0; color: #7b8499; font-size: .82rem; font-weight: 700; }

    .gl-pp-stats {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .7rem;
    }
    @media (min-width: 760px) {
      .gl-pp-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .gl-pp-stat {
      border-radius: 16px; background: #f7f9fd; border: 1px solid #eef2f8;
      padding: .95rem .7rem; text-align: center; min-width: 0;
    }
    .gl-pp-stat strong {
      display: block; font-size: clamp(1.15rem, 2.5vw, 1.4rem);
      font-weight: 900; color: #0B3D91; font-variant-numeric: tabular-nums;
      line-height: 1.2; word-break: break-word;
    }
    .gl-pp-stat small {
      display: block; margin-top: .25rem;
      color: #7b8499; font-size: .7rem; font-weight: 800; line-height: 1.35;
    }

    .gl-pp-grid {
      display: grid; gap: 1rem;
    }
    @media (min-width: 960px) {
      .gl-pp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .gl-pp-scroll {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      margin: 0 -.15rem;
      padding: 0 .15rem;
    }
    .gl-pp-table {
      width: 100%;
      min-width: 420px;
      border-collapse: collapse;
      font-size: .84rem;
    }
    .gl-pp-table th,
    .gl-pp-table td {
      text-align: start;
      padding: .65rem .45rem;
      border-bottom: 1px solid #eef2f8;
      vertical-align: top;
      line-height: 1.45;
    }
    .gl-pp-table th {
      color: #7b8499; font-size: .7rem; font-weight: 800;
      white-space: nowrap;
    }
    .gl-pp-table td strong { display: block; color: #0B1220; }
    .gl-pp-table td .sub {
      display: block; margin-top: .15rem;
      color: #7b8499; font-size: .72rem; font-weight: 600;
    }
    .gl-pp-empty {
      margin: 0; color: #8a93a5; font-size: .86rem; font-weight: 600; line-height: 1.6;
    }
    .gl-pp-pill {
      display: inline-flex; align-items: center;
      border-radius: 999px; padding: .18rem .55rem;
      font-size: .68rem; font-weight: 800;
      background: #eef4ff; color: #0B3D91; white-space: nowrap;
    }
    .gl-pp-pill--ok { background: #e8f8ef; color: #047857; }
    .gl-pp-pill--warn { background: #fff6e5; color: #b45309; }
    .gl-pp-pill--bad { background: #fdecec; color: #b91c1c; }

    .gl-pp-intro ul {
      margin: 0; padding-inline-start: 1.15rem;
      line-height: 1.9; color: #5b6577; font-size: .92rem; font-weight: 600;
    }
    .gl-pp-note {
      margin: .25rem 0 0; font-size: .78rem; color: #7b8499; line-height: 1.7;
    }
    .gl-pp-link {
      color: #0B3D91; font-weight: 800; font-size: .78rem; text-decoration: none;
    }
    .gl-pp-link:hover { text-decoration: underline; }
  </style>
</head>
<body class="sana-home sana-courses-page gl-pp-page">
<div id="sana-scroll-progress"></div>
@include('partials.landing.navbar', ['navActive' => '', 'navHero' => true])

<main class="gl-pp-main">
  <section class="gl-pp-hero">
    <div class="sana-container">
      <div class="gl-pp-hero__grid">
        <div>
          <div class="gl-pp-hero__eyebrow"><i class="fas fa-user-shield"></i> {{ $isRtl ? 'لوحة ولي الأمر' : 'Parent hub' }}</div>
          <h1>{{ $isRtl ? 'تقارير' : 'Student' }} <span class="hl">{{ $isRtl ? 'تقدّم الطالب' : 'progress reports' }}</span></h1>
          <p class="gl-pp-hero__sub">
            {{ $isRtl
              ? 'أدخل رقم الدخول للفصل الظاهر في ملف ابنك/ابنتك لعرض الحضور والحصص والامتحانات والرصيد.'
              : 'Enter the class entry ID from your child’s profile to view attendance, classes, exams, and credits.' }}
          </p>
          <div class="gl-pp-hero__trust">
            <span><i class="fas fa-chart-line"></i> {{ $isRtl ? 'حضور وتقدّم' : 'Attendance & progress' }}</span>
            <span><i class="fas fa-graduation-cap"></i> {{ $isRtl ? 'امتحانات وواجبات' : 'Exams & assignments' }}</span>
            <span><i class="fas fa-lock"></i> {{ $isRtl ? 'بدون بيانات دفع' : 'No payment details' }}</span>
          </div>
        </div>

        <form method="GET" action="{{ route('public.parent-progress') }}" class="gl-pp-search" aria-label="{{ $isRtl ? 'بحث برقم الطالب' : 'Lookup by student ID' }}">
          <h2>{{ $isRtl ? 'رقم الدخول للفصل' : 'Class entry ID' }}</h2>
          <p>{{ $isRtl
            ? 'نفس الرقم الموجود في صفحة الملف الشخصي للطالب.'
            : 'Same number shown on the student profile page.' }}</p>
          <div class="gl-pp-search__row">
            <input
              type="number"
              name="student_id"
              inputmode="numeric"
              min="1"
              required
              value="{{ $studentId }}"
              placeholder="{{ $isRtl ? 'مثال: 7' : 'e.g. 7' }}"
              aria-label="{{ $isRtl ? 'رقم دخول الطالب' : 'Student entry ID' }}"
            >
            <button type="submit"><i class="fas fa-search"></i> {{ $isRtl ? 'عرض' : 'View' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="gl-pp-body">
    <div class="sana-container">
      @if($error)
        <div class="gl-pp-alert" role="alert">{{ $error }}</div>
      @endif

      @if($found && $student)
        <article class="gl-pp-card">
          <div class="gl-pp-profile">
            @if(!empty($student['profile_image']))
              <img class="gl-pp-avatar" src="{{ $student['profile_image'] }}" alt="">
            @else
              <div class="gl-pp-avatar gl-pp-avatar--ph" aria-hidden="true">{{ mb_substr($student['name'], 0, 1) }}</div>
            @endif
            <div>
              <div class="gl-pp-id">ID {{ $student['id'] }}</div>
              <h2 class="gl-pp-name">{{ $student['name'] }}</h2>
              <p class="gl-pp-meta">
                {{ $student['academic_year'] ?: ($isRtl ? 'بدون سنة دراسية محددة' : 'No academic year set') }}
                @if(!empty($student['last_login_at']))
                  · {{ $isRtl ? 'آخر دخول' : 'Last login' }}: <span dir="ltr">{{ $student['last_login_at'] }}</span>
                @endif
              </p>
            </div>
          </div>
        </article>

        <article class="gl-pp-card">
          <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'ملخص سريع' : 'At a glance' }}</h2></div>
          <div class="gl-pp-stats">
            <div class="gl-pp-stat">
              <strong>{{ (int) ($summary['school_progress_percent'] ?? 0) }}%</strong>
              <small>{{ $isRtl ? 'تقدّم الفصول' : 'Class progress' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) ($summary['sessions_attended'] ?? 0) }}/{{ (int) ($summary['sessions_total'] ?? 0) }}</strong>
              <small>{{ $isRtl ? 'حضور الحصص' : 'Sessions attended' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ $summary['exam_average'] !== null ? $summary['exam_average'].'%' : '—' }}</strong>
              <small>{{ $isRtl ? 'متوسط الامتحانات' : 'Exam average' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) ($summary['credits_left'] ?? 0) }}</strong>
              <small>{{ $isRtl ? 'رصيد متبقٍ' : 'Credits left' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) ($summary['xp_total'] ?? 0) }}</strong>
              <small>XP</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) ($summary['streak_current'] ?? 0) }}</strong>
              <small>{{ $isRtl ? 'سلسلة أيام' : 'Day streak' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) data_get($report, 'attendance.present', 0) }}</strong>
              <small>{{ $isRtl ? 'حضور مسجّل' : 'Present marks' }}</small>
            </div>
            <div class="gl-pp-stat">
              <strong>{{ (int) data_get($report, 'attendance.absent', 0) }}</strong>
              <small>{{ $isRtl ? 'غياب' : 'Absent' }}</small>
            </div>
          </div>
        </article>

        <div class="gl-pp-grid">
          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'الفصول والمدرسة' : 'School classes' }}</h2></div>
            @php $classes = $report['school']['classes'] ?? []; @endphp
            @if(count($classes))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الفصل' : 'Class' }}</th>
                      <th>{{ $isRtl ? 'التقدّم' : 'Progress' }}</th>
                      <th>{{ $isRtl ? 'المعلّم' : 'Teacher' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($classes as $class)
                      <tr>
                        <td>
                          <strong>{{ $class['title'] }}</strong>
                          @if(!empty($class['subject_name']))<span class="sub">{{ $class['subject_name'] }}</span>@endif
                          @if(!empty($class['next_at']))<span class="sub" dir="ltr">{{ $isRtl ? 'القادمة' : 'Next' }}: {{ $class['next_at'] }}</span>@endif
                        </td>
                        <td>{{ $class['progress_percent'] }}% · {{ $class['completed_sessions'] }}/{{ $class['total_sessions'] }}</td>
                        <td>{{ $class['instructor_name'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا يوجد انضمام لفصول حالياً.' : 'No class enrollments yet.' }}</p>
            @endif
          </article>

          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'الحضور الأخير' : 'Recent attendance' }}</h2></div>
            @php $attRecent = $report['attendance']['recent'] ?? []; @endphp
            @if(count($attRecent))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الحصة' : 'Session' }}</th>
                      <th>{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                      <th>{{ $isRtl ? 'الموعد' : 'When' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($attRecent as $row)
                      @php
                        $tone = match($row['status'] ?? '') {
                          'present' => 'ok',
                          'late', 'excused' => 'warn',
                          'absent' => 'bad',
                          default => '',
                        };
                      @endphp
                      <tr>
                        <td>{{ $row['session_title'] }}</td>
                        <td><span class="gl-pp-pill {{ $tone ? 'gl-pp-pill--'.$tone : '' }}">{{ $row['status_label'] }}</span></td>
                        <td dir="ltr">{{ $row['starts_at'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا سجلات حضور بعد.' : 'No attendance records yet.' }}</p>
            @endif
          </article>
        </div>

        <div class="gl-pp-grid">
          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'الامتحانات' : 'Exams' }}</h2></div>
            @php $exams = $report['exams'] ?? []; @endphp
            @if(count($exams))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الامتحان' : 'Exam' }}</th>
                      <th>%</th>
                      <th>{{ $isRtl ? 'التاريخ' : 'Date' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($exams as $exam)
                      <tr>
                        <td>{{ $exam['title'] }}</td>
                        <td>{{ $exam['percentage'] !== null ? $exam['percentage'].'%' : '—' }}</td>
                        <td dir="ltr">{{ $exam['date'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا محاولات امتحان مسجّلة.' : 'No exam attempts yet.' }}</p>
            @endif
          </article>

          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'الواجبات' : 'Assignments' }}</h2></div>
            @php $assignments = $report['assignments'] ?? []; @endphp
            @if(count($assignments))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الواجب' : 'Assignment' }}</th>
                      <th>{{ $isRtl ? 'الدرجة' : 'Score' }}</th>
                      <th>{{ $isRtl ? 'التسليم' : 'Submitted' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($assignments as $asg)
                      <tr>
                        <td>{{ $asg['title'] }}</td>
                        <td>{{ $asg['score'] !== null ? $asg['score'] : '—' }}</td>
                        <td dir="ltr">{{ $asg['submitted_at'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا تسليمات واجبات بعد.' : 'No assignment submissions yet.' }}</p>
            @endif
          </article>
        </div>

        <div class="gl-pp-grid">
          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'الحجوزات القادمة' : 'Upcoming bookings' }}</h2></div>
            @php $bookings = $report['bookings'] ?? []; @endphp
            @if(count($bookings))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'المجموعة' : 'Group' }}</th>
                      <th>{{ $isRtl ? 'المعلّم' : 'Teacher' }}</th>
                      <th>{{ $isRtl ? 'الموعد' : 'When' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($bookings as $b)
                      <tr>
                        <td>{{ $b['group'] }} <span class="gl-pp-pill">{{ $b['status'] }}</span></td>
                        <td>{{ $b['instructor'] ?: '—' }}</td>
                        <td dir="ltr">{{ $b['starts_at'] }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا حجوزات قادمة.' : 'No upcoming bookings.' }}</p>
            @endif

            @php $privates = $report['private_sessions'] ?? []; @endphp
            @if(count($privates))
              <div class="gl-pp-card__head" style="margin-top:1.2rem;"><h2>{{ $isRtl ? 'حصص 1:1' : '1:1 sessions' }}</h2></div>
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'المعلّم' : 'Teacher' }}</th>
                      <th>{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                      <th>{{ $isRtl ? 'الموعد' : 'When' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($privates as $p)
                      <tr>
                        <td>{{ $p['instructor'] ?: '—' }}</td>
                        <td>{{ $p['status_label'] }}</td>
                        <td dir="ltr">{{ $p['scheduled_at'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </article>

          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'رصيد الحصص' : 'Session credits' }}</h2></div>
            @php $ents = $report['entitlements'] ?? []; @endphp
            @if(count($ents))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'النطاق' : 'Scope' }}</th>
                      <th>{{ $isRtl ? 'متاح' : 'Bookable' }}</th>
                      <th>{{ $isRtl ? 'ينتهي' : 'Expires' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($ents as $e)
                      <tr>
                        <td>{{ $e['scope'] }}</td>
                        <td>{{ $e['bookable'] }} / {{ $e['units_total'] }}</td>
                        <td dir="ltr">{{ $e['expires_at'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا رصيد نشط حالياً.' : 'No active credits.' }}</p>
            @endif

            @php $certs = $report['certificates'] ?? []; @endphp
            @if(count($certs))
              <div class="gl-pp-card__head" style="margin-top:1.2rem;"><h2>{{ $isRtl ? 'الشهادات' : 'Certificates' }}</h2></div>
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الشهادة' : 'Certificate' }}</th>
                      <th>{{ $isRtl ? 'التاريخ' : 'Issued' }}</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($certs as $c)
                      <tr>
                        <td>{{ $c['title'] }}</td>
                        <td dir="ltr">{{ $c['issued_at'] ?: '—' }}</td>
                        <td>
                          @if(!empty($c['verify_url']))
                            <a class="gl-pp-link" href="{{ $c['verify_url'] }}">{{ $isRtl ? 'تحقق' : 'Verify' }}</a>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </article>
        </div>

        <div class="gl-pp-grid">
          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'تقدم الكورسات' : 'Course progress' }}</h2></div>
            @php $courses = $report['courses'] ?? []; @endphp
            @if(count($courses))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الكورس' : 'Course' }}</th>
                      <th>%</th>
                      <th>{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($courses as $course)
                      <tr>
                        <td>{{ $course['title'] }}</td>
                        <td>{{ $course['progress'] }}%</td>
                        <td>{{ $course['status'] ?: '—' }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا كورسات مسجّلة.' : 'No course enrollments.' }}</p>
            @endif
          </article>

          <article class="gl-pp-card">
            <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'التقارير الشهرية' : 'Monthly digests' }}</h2></div>
            @php $monthly = $report['monthly_reports'] ?? []; @endphp
            @if(count($monthly))
              <div class="gl-pp-scroll">
                <table class="gl-pp-table">
                  <thead>
                    <tr>
                      <th>{{ $isRtl ? 'الشهر' : 'Month' }}</th>
                      <th>{{ $isRtl ? 'التقدير' : 'Grade' }}</th>
                      <th>{{ $isRtl ? 'الحالة' : 'Status' }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($monthly as $m)
                      <tr>
                        <td dir="ltr">{{ $m['month'] }}</td>
                        <td>{{ data_get($m, 'overall.grade') ?: '—' }}</td>
                        <td>{{ $m['status'] }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="gl-pp-empty">{{ $isRtl ? 'لا تقارير شهرية محفوظة بعد.' : 'No saved monthly digests yet.' }}</p>
            @endif
          </article>
        </div>

        <p class="gl-pp-note">
          {{ $isRtl
            ? 'هذه الصفحة تعرض تقارير تعليمية فقط (بدون بريد أو هاتف أو تفاصيل دفع). للمساعدة تواصل عبر واتساب.'
            : 'Educational reports only (no email, phone, or payment details). Contact us on WhatsApp for help.' }}
          @if($waUrl && $waUrl !== '#')
            <a class="gl-pp-link" href="{{ $waUrl }}" target="_blank" rel="noopener">WhatsApp</a>
          @endif
        </p>

      @elseif(! $studentId)
        <article class="gl-pp-card gl-pp-intro">
          <div class="gl-pp-card__head"><h2>{{ $isRtl ? 'ماذا ستشاهد؟' : 'What you’ll see' }}</h2></div>
          <ul>
            <li>{{ $isRtl ? 'ملخص التقدّم والحضور والرصيد' : 'Progress, attendance, and credits summary' }}</li>
            <li>{{ $isRtl ? 'الفصول القادمة وحصص المجموعات و1:1' : 'Upcoming classes, group bookings, and 1:1 sessions' }}</li>
            <li>{{ $isRtl ? 'نتائج الامتحانات والواجبات والكورسات' : 'Exam results, assignments, and courses' }}</li>
            <li>{{ $isRtl ? 'الشهادات والتقارير الشهرية إن وُجدت' : 'Certificates and monthly digests when available' }}</li>
          </ul>
        </article>
      @endif
    </div>
  </section>
</main>

@include('partials.landing.footer')
</body>
</html>
