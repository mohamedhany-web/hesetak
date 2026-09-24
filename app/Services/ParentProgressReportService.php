<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\ExamAttempt;
use App\Models\OneToOneSession;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentReport;
use App\Models\StudentServiceEntitlement;
use App\Models\StudentXpLedger;
use App\Models\TutoringClassAttendance;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ParentProgressReportService
{
    /**
     * Build a parent-safe progress dossier for a student share token.
     * Omits email, phone, payment amounts, and private messages.
     *
     * @return array{found: bool, error?: string, student?: array, report?: array}
     */
    public function lookupByShareToken(string $token): array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 24) {
            return [
                'found' => false,
                'error' => app()->getLocale() === 'ar'
                    ? 'رمز المشاركة غير صالح. انسخ الرابط أو الرمز من ملف الطالب.'
                    : 'Invalid share code. Copy the link or code from the student profile.',
            ];
        }

        $student = User::query()
            ->with(['academicYear:id,name'])
            ->where('progress_share_token', $token)
            ->where('role', 'student')
            ->first();

        if (! $student) {
            return [
                'found' => false,
                'error' => app()->getLocale() === 'ar'
                    ? 'لم نعثر على تقرير بهذا الرمز. تأكد من الرابط أو حدّث الرمز من ملف الطالب.'
                    : 'No report found for this code. Check the link or refresh the code on the student profile.',
            ];
        }

        if (! $student->is_active) {
            return [
                'found' => false,
                'error' => app()->getLocale() === 'ar'
                    ? 'حساب الطالب غير نشط حالياً. تواصل مع الأكاديمية.'
                    : 'This student account is inactive. Please contact the academy.',
            ];
        }

        return [
            'found' => true,
            'student' => $this->profileSnapshot($student),
            'report' => $this->buildReport($student),
        ];
    }

    /**
     * @deprecated Public access must use lookupByShareToken(). Kept for internal/admin callers only.
     *
     * @return array{found: bool, error?: string, student?: array, report?: array}
     */
    public function lookup(int $studentId): array
    {
        $student = User::query()
            ->with(['academicYear:id,name'])
            ->find($studentId);

        if (! $student || ! $student->isStudent()) {
            return [
                'found' => false,
                'error' => app()->getLocale() === 'ar'
                    ? 'لم نعثر على طالب بهذا المعرّف.'
                    : 'No student found with this identifier.',
            ];
        }

        if (! $student->is_active) {
            return [
                'found' => false,
                'error' => app()->getLocale() === 'ar'
                    ? 'حساب الطالب غير نشط حالياً. تواصل مع الأكاديمية.'
                    : 'This student account is inactive. Please contact the academy.',
            ];
        }

        return [
            'found' => true,
            'student' => $this->profileSnapshot($student),
            'report' => $this->buildReport($student),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function profileSnapshot(User $student): array
    {
        return [
            'uuid' => (string) $student->uuid,
            'name' => (string) $student->name,
            'academic_year' => $student->academicYear?->name,
            'last_login_at' => $student->last_login_at?->format('Y-m-d H:i'),
            'profile_image' => $student->profile_image,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildReport(User $student): array
    {
        $school = app(StudentSchoolHomeService::class)->build($student);
        $streak = StudentSchoolGameService::streakFor($student);

        return [
            'summary' => $this->summaryBlock($student, $school, $streak),
            'school' => [
                'has_school_life' => (bool) ($school['hasSchoolLife'] ?? false),
                'progress' => $school['progress'] ?? null,
                'classes' => collect($school['classes'] ?? [])->take(8)->map(function ($class) {
                    return [
                        'title' => (string) ($class->title ?? ''),
                        'subject_name' => $class->subject_name ?? null,
                        'instructor_name' => $class->instructor_name ?? null,
                        'schedule' => $class->schedule ?? null,
                        'progress_percent' => (int) ($class->progress_percent ?? 0),
                        'attended' => (int) ($class->attended ?? 0),
                        'completed_sessions' => (int) ($class->completed_sessions ?? 0),
                        'total_sessions' => (int) ($class->total_sessions ?? 0),
                        'next_at' => isset($class->next_session?->starts_at)
                            ? $class->next_session->starts_at->format('Y-m-d H:i')
                            : null,
                    ];
                })->values()->all(),
                'upcoming' => collect($school['upcoming'] ?? [])->take(6)->map(function ($item) {
                    if ($item instanceof \App\Models\TutoringClassSession) {
                        return [
                            'title' => $item->displayTitle(),
                            'starts_at' => $item->starts_at?->format('Y-m-d H:i'),
                            'subtitle' => $item->cohort?->title ?: $item->tutoringGroup?->title,
                        ];
                    }

                    return [
                        'title' => (string) (data_get($item, 'title') ?? '—'),
                        'starts_at' => data_get($item, 'starts_at')
                            ? \Carbon\Carbon::parse(data_get($item, 'starts_at'))->format('Y-m-d H:i')
                            : null,
                        'subtitle' => data_get($item, 'subtitle'),
                    ];
                })->values()->all(),
                'credits_left' => (int) data_get($school, 'credits.total_left', 0),
            ],
            'attendance' => $this->attendanceSummary((int) $student->id),
            'courses' => $this->courseProgress((int) $student->id),
            'exams' => $this->recentExams((int) $student->id),
            'assignments' => $this->recentAssignments((int) $student->id),
            'bookings' => $this->upcomingBookings((int) $student->id),
            'private_sessions' => $this->privateSessions((int) $student->id),
            'entitlements' => $this->entitlements((int) $student->id),
            'engagement' => [
                'xp_total' => $this->xpTotal((int) $student->id),
                'streak_current' => (int) ($streak['current'] ?? 0),
                'streak_longest' => (int) ($streak['longest'] ?? 0),
                'last_activity_date' => $streak['last_activity_date'] ?? null,
            ],
            'certificates' => $this->certificates((int) $student->id),
            'monthly_reports' => $this->monthlyReports((int) $student->id),
            'insights' => $this->insightsBlock($student),
        ];
    }

    /**
     * مقارنة تاريخية مبسّطة لأولياء الأمور (نقاط قوة + مؤشرات تحسّن).
     *
     * @return array<string, mixed>
     */
    protected function insightsBlock(User $student): array
    {
        try {
            $comparison = app(StudentProgressAnalyticsService::class)->comparePeriods($student);

            return [
                'period' => $comparison['period'] ?? null,
                'previous_period' => $comparison['previous_period'] ?? null,
                'trend' => $comparison['trend'] ?? 'stable',
                'current' => $comparison['current'] ?? [],
                'deltas' => $comparison['deltas'] ?? [],
                'strengths' => $comparison['strengths'] ?? [],
                'improvements' => $comparison['improvements'] ?? [],
            ];
        } catch (\Throwable) {
            return [
                'period' => null,
                'previous_period' => null,
                'trend' => 'stable',
                'current' => [],
                'deltas' => [],
                'strengths' => [],
                'improvements' => [],
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $school
     * @param  array<string, mixed>  $streak
     * @return array<string, mixed>
     */
    protected function summaryBlock(User $student, array $school, array $streak): array
    {
        $examAvg = null;
        if (Schema::hasTable('exam_attempts')) {
            $examAvg = ExamAttempt::query()
                ->where('user_id', $student->id)
                ->whereNotNull('percentage')
                ->avg('percentage');
        }

        return [
            'school_progress_percent' => (int) data_get($school, 'progress.percent', 0),
            'sessions_attended' => (int) data_get($school, 'progress.attended', 0),
            'sessions_total' => (int) data_get($school, 'progress.total_sessions', 0),
            'exam_average' => $examAvg !== null ? round((float) $examAvg, 1) : null,
            'credits_left' => (int) data_get($school, 'credits.total_left', 0),
            'streak_current' => (int) ($streak['current'] ?? 0),
            'xp_total' => $this->xpTotal((int) $student->id),
        ];
    }

    /**
     * @return array{present:int,late:int,absent:int,excused:int,total:int,recent:array<int,array>}
     */
    protected function attendanceSummary(int $userId): array
    {
        $empty = ['present' => 0, 'late' => 0, 'absent' => 0, 'excused' => 0, 'total' => 0, 'recent' => []];
        if (! Schema::hasTable('tutoring_class_attendances')) {
            return $empty;
        }

        $rows = TutoringClassAttendance::query()
            ->where('user_id', $userId)
            ->get(['status']);

        $counts = [
            'present' => $rows->where('status', TutoringClassAttendance::STATUS_PRESENT)->count(),
            'late' => $rows->where('status', TutoringClassAttendance::STATUS_LATE)->count(),
            'absent' => $rows->where('status', TutoringClassAttendance::STATUS_ABSENT)->count(),
            'excused' => $rows->where('status', TutoringClassAttendance::STATUS_EXCUSED)->count(),
            'total' => $rows->count(),
        ];

        $recent = TutoringClassAttendance::query()
            ->with(['session:id,title,session_number,starts_at,tutoring_group_cohort_id'])
            ->where('user_id', $userId)
            ->latest('id')
            ->take(10)
            ->get()
            ->map(fn (TutoringClassAttendance $a) => [
                'status' => $a->status,
                'status_label' => $a->statusLabel(),
                'session_title' => $a->session?->displayTitle() ?? ('#'.$a->tutoring_class_session_id),
                'starts_at' => $a->session?->starts_at?->format('Y-m-d H:i'),
                'joined_at' => $a->joined_at?->format('Y-m-d H:i'),
            ])
            ->all();

        return array_merge($counts, ['recent' => $recent]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function courseProgress(int $userId): array
    {
        if (! Schema::hasTable('student_course_enrollments')) {
            return [];
        }

        return StudentCourseEnrollment::query()
            ->with(['course:id,title'])
            ->where('user_id', $userId)
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn (StudentCourseEnrollment $e) => [
                'title' => $e->course?->title ?? '—',
                'progress' => (int) ($e->progress ?? 0),
                'status' => (string) ($e->status ?? ''),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentExams(int $userId): array
    {
        if (! Schema::hasTable('exam_attempts')) {
            return [];
        }

        return ExamAttempt::query()
            ->with(['exam:id,title,total_marks'])
            ->where('user_id', $userId)
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn (ExamAttempt $a) => [
                'title' => $a->exam?->title ?? '—',
                'score' => $a->score,
                'percentage' => $a->percentage !== null ? round((float) $a->percentage, 1) : null,
                'status' => $a->result_status ?? $a->status,
                'date' => $a->created_at?->format('Y-m-d'),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentAssignments(int $userId): array
    {
        if (! Schema::hasTable('assignment_submissions')) {
            return [];
        }

        return AssignmentSubmission::query()
            ->with(['assignment:id,title'])
            ->where('student_id', $userId)
            ->latest('id')
            ->take(12)
            ->get()
            ->map(fn (AssignmentSubmission $s) => [
                'title' => $s->assignment?->title ?? '—',
                'score' => $s->score,
                'status' => (string) ($s->status ?? ''),
                'submitted_at' => $s->submitted_at?->format('Y-m-d H:i'),
                'graded_at' => $s->graded_at?->format('Y-m-d H:i'),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function upcomingBookings(int $userId): array
    {
        if (! Schema::hasTable('tutoring_group_bookings')) {
            return [];
        }

        return TutoringGroupBooking::query()
            ->with(['tutoringGroup:id,title,type', 'instructor:id,name'])
            ->where('user_id', $userId)
            ->whereIn('status', [
                TutoringGroupBooking::STATUS_PENDING,
                TutoringGroupBooking::STATUS_CONFIRMED,
            ])
            ->where('starts_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->take(10)
            ->get()
            ->map(fn (TutoringGroupBooking $b) => [
                'group' => $b->tutoringGroup?->title ?? '—',
                'instructor' => $b->instructor?->name,
                'starts_at' => $b->starts_at?->format('Y-m-d H:i'),
                'status' => $b->status,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function privateSessions(int $userId): array
    {
        if (! Schema::hasTable('one_to_one_sessions')) {
            return [];
        }

        return OneToOneSession::query()
            ->with(['instructor:id,name', 'course:id,title'])
            ->where('student_id', $userId)
            ->whereIn('status', [
                OneToOneSession::STATUS_PENDING,
                OneToOneSession::STATUS_SCHEDULED,
                OneToOneSession::STATUS_COMPLETED,
            ])
            ->orderByDesc('scheduled_at')
            ->take(10)
            ->get()
            ->map(fn (OneToOneSession $s) => [
                'instructor' => $s->instructor?->name,
                'course' => $s->course?->title,
                'scheduled_at' => $s->scheduled_at?->format('Y-m-d H:i'),
                'status' => $s->status,
                'status_label' => OneToOneSession::statusLabels()[$s->status] ?? $s->status,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function entitlements(int $userId): array
    {
        if (! Schema::hasTable('student_service_entitlements')) {
            return [];
        }

        return StudentServiceEntitlement::query()
            ->forUser($userId)
            ->active()
            ->orderByDesc('id')
            ->take(8)
            ->get()
            ->map(fn (StudentServiceEntitlement $e) => [
                'scope' => $e->scope,
                'units_total' => (int) $e->units_total,
                'units_used' => (int) $e->units_used,
                'units_left' => $e->unitsLeft(),
                'bookable' => StudentEntitlementService::bookableUnitsLeft($e),
                'expires_at' => $e->expires_at?->format('Y-m-d'),
            ])
            ->all();
    }

    protected function xpTotal(int $userId): int
    {
        if (! Schema::hasTable('student_xp_ledger')) {
            return 0;
        }

        $latest = StudentXpLedger::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->value('balance_after');

        if ($latest !== null) {
            return (int) $latest;
        }

        return (int) StudentXpLedger::query()->where('user_id', $userId)->sum('amount');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function certificates(int $userId): array
    {
        if (! Schema::hasTable('certificates')) {
            return [];
        }

        $columns = ['id', 'certificate_number', 'issued_at'];
        foreach (['serial_number', 'verification_code', 'title', 'user_id'] as $col) {
            if (Schema::hasColumn('certificates', $col)) {
                $columns[] = $col;
            }
        }

        return Certificate::query()
            ->where('user_id', $userId)
            ->latest('issued_at')
            ->take(8)
            ->get($columns)
            ->map(function (Certificate $c) {
                $code = $c->verification_code ?? null;
                $serial = $c->serial_number ?? null;
                $verifyUrl = null;
                if ($code && \Illuminate\Support\Facades\Route::has('public.certificates.verify.code')) {
                    $verifyUrl = route('public.certificates.verify.code', $code);
                } elseif (($code || $serial) && \Illuminate\Support\Facades\Route::has('public.certificates.verify')) {
                    $verifyUrl = route('public.certificates.verify', ['code' => $code ?: $serial]);
                }

                return [
                    'title' => $c->title ?? $c->certificate_number,
                    'number' => $c->certificate_number,
                    'issued_at' => $c->issued_at?->format('Y-m-d'),
                    'verify_url' => $verifyUrl,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function monthlyReports(int $userId): array
    {
        if (! Schema::hasTable('student_reports')) {
            return [];
        }

        return StudentReport::query()
            ->where('student_id', $userId)
            ->orderByDesc('report_month')
            ->take(6)
            ->get()
            ->map(fn (StudentReport $r) => [
                'month' => $r->report_month,
                'type' => $r->report_type,
                'status' => $r->status,
                'sent_at' => $r->sent_at?->format('Y-m-d'),
                'overall' => data_get($r->report_data, 'overall'),
                'courses_count' => count(data_get($r->report_data, 'courses', [])),
                'exams_count' => count(data_get($r->report_data, 'exams', [])),
            ])
            ->all();
    }
}
