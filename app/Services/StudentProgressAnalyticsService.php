<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\LectureWatchProgress;
use App\Models\OneToOneSession;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProgressSnapshot;
use App\Models\StudentXpLedger;
use App\Models\TutoringClassAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class StudentProgressAnalyticsService
{
    /**
     * مقارنة الفترة الحالية بالسابقة + نقاط قوة/تحسّن.
     *
     * @return array{
     *   period: array,
     *   previous_period: array,
     *   current: array,
     *   previous: array|null,
     *   deltas: array,
     *   strengths: list<string>,
     *   improvements: list<string>,
     *   trend: string
     * }
     */
    public function comparePeriods(User $student, ?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?: now())->copy()->endOfDay();
        $currentStart = $asOf->copy()->startOfMonth();
        $currentEnd = $asOf->copy()->endOfMonth();
        $prevStart = $currentStart->copy()->subMonth()->startOfMonth();
        $prevEnd = $currentStart->copy()->subMonth()->endOfMonth();

        $current = $this->metricsForWindow($student, $currentStart, $currentEnd);
        $previous = $this->metricsForWindow($student, $prevStart, $prevEnd);

        $strengths = [];
        $improvements = [];

        if ($current['exam_average'] !== null && $current['exam_average'] >= 75) {
            $strengths[] = app()->getLocale() === 'ar'
                ? 'متوسط نتائج الاختبارات قوي هذا الشهر ('.$current['exam_average'].'%).'
                : 'Strong exam average this month ('.$current['exam_average'].'%).';
        }
        if ($current['attendance_percent'] !== null && $current['attendance_percent'] >= 80) {
            $strengths[] = app()->getLocale() === 'ar'
                ? 'انتظام ممتاز في الحضور.'
                : 'Excellent attendance consistency.';
        }
        if ($current['sessions_completed'] >= 4) {
            $strengths[] = app()->getLocale() === 'ar'
                ? 'أكمل '.$current['sessions_completed'].' حصص هذا الشهر.'
                : 'Completed '.$current['sessions_completed'].' sessions this month.';
        }

        if ($previous['exam_average'] !== null && $current['exam_average'] !== null
            && $current['exam_average'] < $previous['exam_average'] - 5) {
            $improvements[] = app()->getLocale() === 'ar'
                ? 'متوسط الاختبارات انخفض مقارنة بالشهر السابق — يُفضّل مراجعة مركّزة.'
                : 'Exam average dipped vs last month — focused review recommended.';
        }
        if ($current['attendance_percent'] !== null && $current['attendance_percent'] < 70) {
            $improvements[] = app()->getLocale() === 'ar'
                ? 'نسبة الحضور تحتاج انتباهاً.'
                : 'Attendance needs attention.';
        }
        if ($current['sessions_completed'] < 2 && $previous['sessions_completed'] >= 2) {
            $improvements[] = app()->getLocale() === 'ar'
                ? 'نشاط الحصص أقل من الشهر الماضي.'
                : 'Fewer sessions than last month.';
        }
        if ($current['course_progress_percent'] !== null && $current['course_progress_percent'] < 40) {
            $improvements[] = app()->getLocale() === 'ar'
                ? 'تقدّم الكورسات المسجّلة ما زال في البداية — جرّب مسار الفيديو أو الكتب.'
                : 'Recorded course progress is still early — try video or book tracks.';
        }

        if ($strengths === []) {
            $strengths[] = app()->getLocale() === 'ar'
                ? 'الاستمرار في المنصة خطوة إيجابية — راقب النتائج أسبوعياً.'
                : 'Staying active is positive — review results weekly.';
        }

        $deltas = [
            'exam_average' => $this->delta($current['exam_average'], $previous['exam_average']),
            'attendance_percent' => $this->delta($current['attendance_percent'], $previous['attendance_percent']),
            'course_progress_percent' => $this->delta($current['course_progress_percent'], $previous['course_progress_percent']),
            'sessions_completed' => $this->delta($current['sessions_completed'], $previous['sessions_completed']),
            'xp_total' => $this->delta($current['xp_earned'], $previous['xp_earned']),
        ];

        $trendScore = 0;
        foreach (['exam_average', 'attendance_percent', 'sessions_completed'] as $key) {
            $d = $deltas[$key];
            if ($d === null) {
                continue;
            }
            $trendScore += $d > 0 ? 1 : ($d < 0 ? -1 : 0);
        }
        $trend = $trendScore > 0 ? 'up' : ($trendScore < 0 ? 'down' : 'stable');

        $this->persistSnapshot($student, $currentStart->format('Y-m'), $currentStart, $currentEnd, $current, $strengths, $improvements);

        return [
            'period' => [
                'key' => $currentStart->format('Y-m'),
                'start' => $currentStart->toDateString(),
                'end' => $currentEnd->toDateString(),
                'label' => $currentStart->copy()->locale(app()->getLocale())->translatedFormat('F Y'),
            ],
            'previous_period' => [
                'key' => $prevStart->format('Y-m'),
                'start' => $prevStart->toDateString(),
                'end' => $prevEnd->toDateString(),
                'label' => $prevStart->copy()->locale(app()->getLocale())->translatedFormat('F Y'),
            ],
            'current' => $current,
            'previous' => $previous,
            'deltas' => $deltas,
            'strengths' => $strengths,
            'improvements' => array_values(array_unique($improvements)),
            'trend' => $trend,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function metricsForWindow(User $student, Carbon $from, Carbon $to): array
    {
        $userId = (int) $student->id;

        $examAvg = null;
        if (Schema::hasTable('exam_attempts')) {
            $avg = ExamAttempt::query()
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('score')
                ->avg('score');
            $examAvg = $avg !== null ? (int) round((float) $avg) : null;
        }

        $attendancePercent = null;
        $sessionsCompleted = 0;
        if (Schema::hasTable('tutoring_class_attendances')) {
            $att = TutoringClassAttendance::query()
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$from, $to])
                ->get();
            if ($att->isNotEmpty()) {
                $present = $att->filter(fn ($a) => in_array((string) ($a->status ?? ''), ['present', 'attended', 'completed'], true)
                    || (bool) ($a->attended ?? false))->count();
                $attendancePercent = (int) round(($present / max(1, $att->count())) * 100);
                $sessionsCompleted += $present;
            }
        }

        if (Schema::hasTable('one_to_one_sessions')) {
            $sessionsCompleted += OneToOneSession::query()
                ->where('student_id', $userId)
                ->whereIn('status', ['completed', 'done', 'finished'])
                ->whereBetween('starts_at', [$from, $to])
                ->count();
        }

        $courseProgress = null;
        if (Schema::hasTable('student_course_enrollments')) {
            $enrollments = StudentCourseEnrollment::query()
                ->where('user_id', $userId)
                ->where('status', '!=', 'cancelled')
                ->get();
            if ($enrollments->isNotEmpty()) {
                $courseProgress = (int) round((float) $enrollments->avg(fn ($e) => (float) ($e->progress_percent ?? $e->progress ?? 0)));
            }
        }

        $watchPercent = null;
        if (Schema::hasTable('lecture_watch_progress')) {
            $watch = LectureWatchProgress::query()
                ->where('user_id', $userId)
                ->whereBetween('updated_at', [$from, $to])
                ->avg('progress_percent');
            if ($watch !== null) {
                $watchPercent = (int) round((float) $watch);
            }
        }

        $xpEarned = 0;
        if (Schema::hasTable('student_xp_ledgers')) {
            $xpEarned = (int) StudentXpLedger::query()
                ->where('user_id', $userId)
                ->whereBetween('created_at', [$from, $to])
                ->sum('points');
        }

        return [
            'exam_average' => $examAvg,
            'attendance_percent' => $attendancePercent,
            'course_progress_percent' => $courseProgress,
            'watch_percent' => $watchPercent,
            'sessions_completed' => $sessionsCompleted,
            'xp_earned' => $xpEarned,
        ];
    }

    protected function delta(int|float|null $current, int|float|null $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return round((float) $current - (float) $previous, 1);
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  list<string>  $strengths
     * @param  list<string>  $improvements
     */
    protected function persistSnapshot(
        User $student,
        string $periodKey,
        Carbon $start,
        Carbon $end,
        array $metrics,
        array $strengths,
        array $improvements,
    ): void {
        if (! Schema::hasTable('student_progress_snapshots')) {
            return;
        }

        StudentProgressSnapshot::query()->updateOrCreate(
            [
                'user_id' => $student->id,
                'period_key' => $periodKey,
            ],
            [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'exam_average' => $metrics['exam_average'],
                'attendance_percent' => $metrics['attendance_percent'],
                'course_progress_percent' => $metrics['course_progress_percent'],
                'sessions_completed' => $metrics['sessions_completed'],
                'xp_total' => $metrics['xp_earned'],
                'strengths' => $strengths,
                'improvements' => $improvements,
                'metrics' => $metrics,
            ]
        );
    }
}
