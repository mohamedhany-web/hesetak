<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FreeTrialBooking;
use App\Models\OneToOneSession;
use App\Models\StudentServiceEntitlement;
use App\Models\TutoringClassAttendance;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class StudentSchoolHomeService
{
    /**
     * @return array{
     *     greeting: string,
     *     primaryClass: ?object,
     *     classes: Collection,
     *     todayMission: ?object,
     *     upcoming: Collection,
     *     progress: array{percent: int, attended: int, completed_sessions: int, total_sessions: int, label: string},
     *     credits: array{total_left: int, entitlements: Collection},
     *     weekDays: Collection,
     *     todayItems: Collection,
     *     nextAppointment: ?object,
     *     recommendedYear: ?AcademicYear,
     *     placement: ?FreeTrialBooking,
     *     hasSchoolLife: bool
     * }
     */
    public function build(User $user, array $filters = []): array
    {
        $isRtl = app()->getLocale() === 'ar';
        $view = in_array($filters['view'] ?? '', ['week', 'day'], true) ? $filters['view'] : 'week';
        $sort = in_array($filters['sort'] ?? '', ['classes', 'progress', 'name'], true) ? $filters['sort'] : 'classes';
        $search = trim((string) ($filters['q'] ?? ''));
        $tz = AppTimezone::forUser($user);
        $weekAnchor = ! empty($filters['week'])
            ? Carbon::parse((string) $filters['week'], $tz)->startOfDay()
            : now($tz)->startOfDay();
        $weekStart = $weekAnchor->copy()->startOfWeek(Carbon::SATURDAY);

        $hour = (int) now($tz)->format('G');
        $greeting = $hour < 12
            ? ($isRtl ? 'صباح الخير' : 'Good morning')
            : ($hour < 17
                ? ($isRtl ? 'مساء الخير' : 'Good afternoon')
                : ($isRtl ? 'مساء الخير' : 'Good evening'));

        $enrollments = collect();
        if (Schema::hasTable('tutoring_cohort_enrollments')) {
            $enrollments = TutoringCohortEnrollment::query()
                ->with([
                    'cohort.tutoringGroup.instructor:id,name',
                    'cohort.tutoringGroup.schoolYear:id,name,slug,level_number,tagline',
                    'cohort.tutoringGroup.schoolSubject:id,name',
                ])
                ->where('user_id', $user->id)
                ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                ->latest('enrolled_at')
                ->get();
        }

        $cohortIds = $enrollments->pluck('tutoring_group_cohort_id')->filter()->values();

        $sessionsByCohort = collect();
        $upcomingSessions = collect();
        if ($cohortIds->isNotEmpty() && Schema::hasTable('tutoring_class_sessions')) {
            $allSessions = TutoringClassSession::query()
                ->with(['cohort:id,title', 'tutoringGroup:id,title', 'classroomMeeting:id,code'])
                ->whereIn('tutoring_group_cohort_id', $cohortIds)
                ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
                ->orderBy('starts_at')
                ->get();

            $sessionsByCohort = $allSessions->groupBy('tutoring_group_cohort_id');
            $upcomingSessions = $allSessions
                ->filter(fn (TutoringClassSession $s) => $s->starts_at && $s->starts_at->gte(now()->subHour()))
                ->values();
        }

        $attendanceBySession = collect();
        if ($cohortIds->isNotEmpty() && Schema::hasTable('tutoring_class_attendances')) {
            $sessionIds = $sessionsByCohort->flatten()->pluck('id');
            $attendanceBySession = TutoringClassAttendance::query()
                ->where('user_id', $user->id)
                ->whereIn('tutoring_class_session_id', $sessionIds)
                ->get()
                ->keyBy('tutoring_class_session_id');
        }

        $classes = $enrollments->map(function (TutoringCohortEnrollment $enrollment) use ($sessionsByCohort, $attendanceBySession, $isRtl) {
            $cohort = $enrollment->cohort;
            if (! $cohort) {
                return null;
            }

            $sessions = $sessionsByCohort->get($cohort->id, collect());
            $completed = $sessions->where('status', TutoringClassSession::STATUS_COMPLETED);
            $attended = $completed->filter(function (TutoringClassSession $session) use ($attendanceBySession) {
                $row = $attendanceBySession->get($session->id);

                return $row && in_array($row->status, [
                    TutoringClassAttendance::STATUS_PRESENT,
                    TutoringClassAttendance::STATUS_LATE,
                    TutoringClassAttendance::STATUS_EXCUSED,
                ], true);
            })->count();

            $total = max(1, (int) ($cohort->sessions_count ?: $sessions->count() ?: 1));
            $done = $completed->count();
            $percent = (int) round(($done / $total) * 100);

            $next = $sessions
                ->filter(fn (TutoringClassSession $s) => $s->starts_at && $s->starts_at->gte(now()->subMinutes(30))
                    && $s->status !== TutoringClassSession::STATUS_COMPLETED)
                ->sortBy('starts_at')
                ->first();

            $group = $cohort->tutoringGroup;
            $year = $group?->schoolYear;
            $subject = $group?->schoolSubject;

            return (object) [
                'enrollment_id' => $enrollment->id,
                'cohort_id' => $cohort->id,
                'title' => $cohort->title ?: ($group?->title ?: ($isRtl ? 'فصل' : 'Class')),
                'group_title' => $group?->title,
                'year_name' => $year?->name,
                'subject_name' => $subject?->name,
                'instructor_name' => $group?->instructor?->name,
                'schedule' => $cohort->scheduleSummary(),
                'progress_percent' => min(100, $percent),
                'attended' => $attended,
                'completed_sessions' => $done,
                'total_sessions' => $total,
                'next_session' => $next,
                'url' => Route::has('student.classes.show')
                    ? route('student.classes.show', $cohort)
                    : '#',
            ];
        })->filter()->values();

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $classes = $classes->filter(function ($class) use ($needle) {
                return str_contains(mb_strtolower((string) $class->title), $needle)
                    || str_contains(mb_strtolower((string) ($class->subject_name ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($class->group_title ?? '')), $needle);
            })->values();
        }

        $classes = match ($sort) {
            'progress' => $classes->sortByDesc('progress_percent')->values(),
            'name' => $classes->sortBy(fn ($class) => mb_strtolower((string) ($class->subject_name ?: $class->title)))->values(),
            default => $classes,
        };

        $primaryClass = $classes->first();

        $todayMission = null;
        $nextSession = $upcomingSessions->first();
        if ($nextSession instanceof TutoringClassSession) {
            $mins = max(1, (int) ($nextSession->starts_at && $nextSession->ends_at
                ? $nextSession->starts_at->diffInMinutes($nextSession->ends_at)
                : 60));
            $joinable = $nextSession->isJoinable();
            $todayMission = (object) [
                'session_id' => $nextSession->id,
                'title' => $nextSession->displayTitle(),
                'subtitle' => $nextSession->cohort?->title
                    ?: ($nextSession->tutoringGroup?->title ?: ($isRtl ? 'حصة جماعية' : 'Class session')),
                'starts_at' => $nextSession->starts_at,
                'duration_minutes' => $mins,
                'is_today' => $nextSession->starts_at?->isToday() ?? false,
                'is_joinable' => $joinable,
                'status' => $nextSession->status,
                'join_url' => Route::has('student.schedule.join')
                    ? route('student.schedule.join', ['type' => 'class', 'id' => $nextSession->id])
                    : ($nextSession->joinUrl() ?: '#'),
                'class_url' => $nextSession->cohort && Route::has('student.classes.show')
                    ? route('student.classes.show', $nextSession->cohort)
                    : null,
            ];
        }

        $attendedTotal = $classes->sum('attended');
        $completedTotal = $classes->sum('completed_sessions');
        $sessionsTotal = max(1, (int) $classes->sum('total_sessions'));
        $progressPercent = $classes->isEmpty()
            ? 0
            : (int) round($classes->avg('progress_percent'));

        // حصتك: املأ نفس هيكل اللوحة من الحصص الفردية إن لم تكن هناك فصول جماعية
        $privateBundle = $this->buildPrivateLessonBundle($user, $isRtl);
        if ($classes->isEmpty() && $privateBundle['classes']->isNotEmpty()) {
            $classes = $privateBundle['classes'];
            $primaryClass = $classes->first();
            $attendedTotal = $classes->sum('attended');
            $completedTotal = $classes->sum('completed_sessions');
            $sessionsTotal = max(1, (int) $classes->sum('total_sessions'));
            $progressPercent = (int) round($classes->avg('progress_percent'));
        }
        if (! $todayMission && $privateBundle['todayMission']) {
            $todayMission = $privateBundle['todayMission'];
        }
        if ($privateBundle['upcoming']->isNotEmpty()) {
            $upcomingSessions = $upcomingSessions
                ->concat($privateBundle['upcoming'])
                ->sortBy(fn ($s) => $s->starts_at?->timestamp ?? PHP_INT_MAX)
                ->values();
        }

        $entitlements = collect();
        $creditsLeft = 0;
        if (Schema::hasTable('student_service_entitlements')) {
            $entitlements = StudentServiceEntitlement::query()
                ->with(['servicePackage:id,name', 'academicYear:id,name'])
                ->where('user_id', $user->id)
                ->where('status', StudentServiceEntitlement::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->limit(6)
                ->get();
            $creditsLeft = (int) $entitlements->sum(fn (StudentServiceEntitlement $e) => $e->unitsLeft());
        }

        $weekDays = StudentScheduleService::weekDays($user, $weekStart);
        $focusDay = $weekDays->first(fn ($day) => $day->date->toDateString() === $weekAnchor->toDateString())
            ?? $weekDays->firstWhere('is_today')
            ?? $weekDays->first();
        $todayItems = $weekDays->firstWhere('is_today')?->items ?? collect();

        $scheduleRows = $view === 'day' && $focusDay
            ? collect($focusDay->items ?? [])->take(6)
            : $weekDays
                ->flatMap(function ($day) {
                    return collect($day->items ?? [])->map(function ($slot) use ($day) {
                        $slot->day_short = $day->short;

                        return $slot;
                    });
                })
                ->sortBy(fn ($slot) => $slot->starts_at?->timestamp ?? 0)
                ->values()
                ->take(6);

        $timelineQuery = function (array $overrides = []) use ($weekAnchor, $view, $sort, $search, $tz): string {
            $params = array_filter([
                'week' => $overrides['week'] ?? $weekAnchor->toDateString(),
                'view' => $overrides['view'] ?? $view,
                'sort' => $overrides['sort'] ?? $sort,
                'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($search !== '' ? $search : null),
                'lang' => request()->query('lang'),
            ], fn ($value) => $value !== null && $value !== '');

            if (($params['view'] ?? '') === 'week') {
                unset($params['view']);
            }
            if (($params['sort'] ?? '') === 'classes') {
                unset($params['sort']);
            }
            if (($params['week'] ?? '') === now($tz)->toDateString()) {
                unset($params['week']);
            }

            $query = http_build_query($params);

            return request()->url().($query !== '' ? '?'.$query : '');
        };

        $nextDate = $view === 'day'
            ? $weekAnchor->copy()->addDay()->toDateString()
            : $weekAnchor->copy()->addWeek()->toDateString();
        $prevDate = $view === 'day'
            ? $weekAnchor->copy()->subDay()->toDateString()
            : $weekAnchor->copy()->subWeek()->toDateString();
        $nextAppointment = $weekDays->flatMap->items
            ->filter(fn ($a) => $a->starts_at && $a->starts_at->gte(now()->subMinutes(30)))
            ->sortBy('starts_at')
            ->first();

        if (! $todayMission && $nextAppointment) {
            $todayMission = (object) [
                'session_id' => $nextAppointment->ref_id,
                'title' => $nextAppointment->title,
                'subtitle' => $nextAppointment->subtitle,
                'starts_at' => $nextAppointment->starts_at,
                'duration_minutes' => max(1, (int) ($nextAppointment->starts_at && $nextAppointment->ends_at
                    ? $nextAppointment->starts_at->diffInMinutes($nextAppointment->ends_at)
                    : 50)),
                'is_today' => $nextAppointment->starts_at?->isToday() ?? false,
                'is_joinable' => ! empty($nextAppointment->join_url),
                'status' => 'scheduled',
                'join_url' => $nextAppointment->join_url ?: '#',
                'class_url' => Route::has('student.private-lectures.index')
                    ? route('student.private-lectures.index')
                    : route('dashboard'),
            ];
        }

        $placement = null;
        if (Schema::hasTable('free_trial_bookings')) {
            $placement = FreeTrialBooking::query()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->email) {
                        $q->orWhere('email', $user->email);
                    }
                })
                ->with('recommendedSchoolYear:id,name,slug,level_number,tagline')
                ->orderByDesc('starts_at')
                ->first();
        }

        $recommendedYear = $placement?->recommendedSchoolYear;
        if (! $recommendedYear && $primaryClass?->year_name) {
            $yearId = $enrollments->first()?->cohort?->tutoringGroup?->academic_year_id;
            if ($yearId && Schema::hasTable('academic_years')) {
                $recommendedYear = AcademicYear::query()->find($yearId);
            }
        }

        $progressLabel = $classes->isNotEmpty() && ($classes->first()->kind ?? null) === 'private'
            ? ($isRtl
                ? ($progressPercent.'% من حصصك الخاصة')
                : ($progressPercent.'% of your private lessons'))
            : ($isRtl
                ? ($progressPercent.'% من مسار فصلك')
                : ($progressPercent.'% of your class path'));

        return [
            'greeting' => $greeting,
            'primaryClass' => $primaryClass,
            'classes' => $classes,
            'todayMission' => $todayMission,
            'upcoming' => $upcomingSessions->take(5),
            'progress' => [
                'percent' => min(100, $progressPercent),
                'attended' => (int) $attendedTotal,
                'completed_sessions' => (int) $completedTotal,
                'total_sessions' => (int) $sessionsTotal,
                'label' => $progressLabel,
            ],
            'credits' => [
                'total_left' => $creditsLeft,
                'entitlements' => $entitlements,
            ],
            'weekDays' => $weekDays,
            'todayItems' => $todayItems,
            'scheduleRows' => $scheduleRows,
            'weekAnchor' => $weekAnchor,
            'weekStart' => $weekStart,
            'viewMode' => $view,
            'sortMode' => $sort,
            'searchQuery' => $search,
            'focusDay' => $focusDay,
            'timelinePrevUrl' => $timelineQuery(['week' => $prevDate]),
            'timelineNextUrl' => $timelineQuery(['week' => $nextDate]),
            'timelineSortUrl' => $timelineQuery(['sort' => $sort === 'classes' ? 'progress' : 'classes']),
            'timelineViewUrl' => $timelineQuery(['view' => $view === 'week' ? 'day' : 'week']),
            'timelineTodayUrl' => $timelineQuery(['week' => now($tz)->toDateString(), 'view' => $view === 'day' ? 'day' : null]),
            'timelineMonthPrevUrl' => $timelineQuery([
                'week' => $weekAnchor->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            ]),
            'timelineMonthNextUrl' => $timelineQuery([
                'week' => $weekAnchor->copy()->addMonthNoOverflow()->startOfMonth()->toDateString(),
            ]),
            'nextAppointment' => $nextAppointment,
            'recommendedYear' => $recommendedYear,
            'placement' => $placement,
            'hasSchoolLife' => $classes->isNotEmpty() || $creditsLeft > 0 || $todayMission !== null,
            'game' => StudentSchoolGameService::profileSnapshot($user),
        ];
    }

    /**
     * يحوّل الحصص الفردية لنفس شكل بطاقات الفصول / المهمة / القادم في لوحة الجدول الزمني.
     *
     * @return array{classes: Collection, todayMission: ?object, upcoming: Collection}
     */
    private function buildPrivateLessonBundle(User $user, bool $isRtl): array
    {
        $empty = [
            'classes' => collect(),
            'todayMission' => null,
            'upcoming' => collect(),
        ];

        if (! Schema::hasTable('one_to_one_sessions')) {
            return $empty;
        }

        $sessions = OneToOneSession::query()
            ->with(['course:id,title', 'instructor:id,name', 'classroomMeeting'])
            ->where('student_id', $user->id)
            ->whereIn('status', [
                OneToOneSession::STATUS_PENDING,
                OneToOneSession::STATUS_SCHEDULED,
                OneToOneSession::STATUS_COMPLETED,
            ])
            ->orderByRaw("CASE status WHEN 'scheduled' THEN 0 WHEN 'pending_schedule' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at')
            ->get();

        if ($sessions->isEmpty()) {
            return $empty;
        }

        $lessonsUrl = Route::has('student.private-lectures.index')
            ? route('student.private-lectures.index')
            : route('dashboard');

        $classes = $sessions
            ->groupBy(fn (OneToOneSession $s) => ($s->instructor_id ?: 0).':'.($s->advanced_course_id ?: 0))
            ->map(function (Collection $group) use ($isRtl, $lessonsUrl, $user) {
                /** @var OneToOneSession $sample */
                $sample = $group->first();
                $total = max(1, $group->count());
                $completed = $group->where('status', OneToOneSession::STATUS_COMPLETED)->count();
                $percent = (int) round(($completed / $total) * 100);
                $next = $group
                    ->filter(fn (OneToOneSession $s) => $s->status === OneToOneSession::STATUS_SCHEDULED
                        && $s->scheduled_at
                        && $s->scheduled_at->gte(now()->subHour()))
                    ->sortBy('scheduled_at')
                    ->first();

                return (object) [
                    'enrollment_id' => null,
                    'cohort_id' => null,
                    'kind' => 'private',
                    'title' => $sample->course?->title
                        ?: ($isRtl ? 'حصص خاصة' : 'Private lessons'),
                    'group_title' => $sample->instructor?->name,
                    'year_name' => null,
                    'subject_name' => $sample->course?->title
                        ?: ($sample->instructor?->name ?: ($isRtl ? 'حصة فردية' : '1:1 lesson')),
                    'instructor_name' => $sample->instructor?->name,
                    'schedule' => $next?->scheduled_at
                        ? AppTimezone::formatFor($next->scheduled_at, AppTimezone::forUser($user), 'D g:i A')
                        : ($isRtl ? 'بانتظار الجدولة' : 'Awaiting schedule'),
                    'progress_percent' => min(100, $percent),
                    'attended' => $completed,
                    'completed_sessions' => $completed,
                    'total_sessions' => $total,
                    'next_session' => $next,
                    'url' => $next && Route::has('student.one-to-one-sessions.show')
                        ? route('student.one-to-one-sessions.show', $next)
                        : $lessonsUrl,
                ];
            })
            ->values();

        $nextPrivate = $sessions
            ->filter(fn (OneToOneSession $s) => $s->status === OneToOneSession::STATUS_SCHEDULED
                && $s->scheduled_at
                && $s->scheduled_at->gte(now()->subHour()))
            ->sortBy('scheduled_at')
            ->first();

        $todayMission = null;
        if ($nextPrivate) {
            $dur = max(30, (int) ($nextPrivate->duration_minutes ?: 50));
            $joinUrl = Route::has('student.schedule.join')
                ? route('student.schedule.join', ['type' => 'private', 'id' => $nextPrivate->id])
                : ($nextPrivate->joinUrl() ?: '#');
            $todayMission = (object) [
                'session_id' => $nextPrivate->id,
                'title' => $nextPrivate->course?->title
                    ?: ($isRtl ? 'حصة خاصة' : 'Private lesson'),
                'subtitle' => $nextPrivate->instructor?->name
                    ?: ($isRtl ? 'معلم خاص' : 'Private tutor'),
                'starts_at' => $nextPrivate->scheduled_at,
                'duration_minutes' => $dur,
                'is_today' => $nextPrivate->scheduled_at?->isToday() ?? false,
                'is_joinable' => (bool) $nextPrivate->joinUrl(),
                'status' => $nextPrivate->status,
                'join_url' => $joinUrl,
                'class_url' => Route::has('student.one-to-one-sessions.show')
                    ? route('student.one-to-one-sessions.show', $nextPrivate)
                    : $lessonsUrl,
            ];
        }

        $upcoming = $sessions
            ->filter(fn (OneToOneSession $s) => $s->status === OneToOneSession::STATUS_SCHEDULED
                && $s->scheduled_at
                && $s->scheduled_at->gte(now()->subHour()))
            ->sortBy('scheduled_at')
            ->take(5)
            ->values()
            ->map(function (OneToOneSession $s) use ($isRtl) {
                return (object) [
                    'id' => $s->id,
                    'starts_at' => $s->scheduled_at,
                    'title' => $s->course?->title ?: ($isRtl ? 'حصة خاصة' : 'Private lesson'),
                    'subtitle' => $s->instructor?->name ?: '',
                    'join_url' => Route::has('student.schedule.join')
                        ? route('student.schedule.join', ['type' => 'private', 'id' => $s->id])
                        : ($s->joinUrl() ?: '#'),
                    'kind' => 'private',
                    'cohort' => null,
                    'tutoringGroup' => null,
                ];
            });

        return [
            'classes' => $classes,
            'todayMission' => $todayMission,
            'upcoming' => $upcoming,
        ];
    }
}
