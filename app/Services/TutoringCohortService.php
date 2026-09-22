<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroupBooking;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class TutoringCohortService
{
    public static function isEnrollmentOpen(TutoringGroupCohort $cohort): bool
    {
        return $cohort->isEnrollmentOpen();
    }

    public static function enroll(TutoringGroupCohort $cohort, ?User $user = null): TutoringGroupCohort
    {
        return DB::transaction(function () use ($cohort) {
            $locked = TutoringGroupCohort::query()->lockForUpdate()->findOrFail($cohort->id);

            if (! $locked->isEnrollmentOpen()) {
                throw new InvalidArgumentException('هذه الدفعة غير متاحة للاشتراك حالياً.');
            }

            $locked->enrolled_count = (int) $locked->enrolled_count + 1;

            if ($locked->enrolled_count >= (int) $locked->capacity) {
                $locked->status = TutoringGroupCohort::STATUS_FULL;
            }

            $locked->save();

            return $locked->fresh();
        });
    }

    public static function releaseSeat(TutoringGroupCohort $cohort): TutoringGroupCohort
    {
        return DB::transaction(function () use ($cohort) {
            $locked = TutoringGroupCohort::query()->lockForUpdate()->findOrFail($cohort->id);
            $locked->enrolled_count = max(0, (int) $locked->enrolled_count - 1);

            if (
                in_array($locked->status, [TutoringGroupCohort::STATUS_FULL, TutoringGroupCohort::STATUS_OPEN], true)
                && $locked->enrolled_count < (int) $locked->capacity
            ) {
                $locked->status = TutoringGroupCohort::STATUS_OPEN;
            }

            $locked->save();

            return $locked->fresh();
        });
    }

    /**
     * Postpone cohort if start is imminent and min enrollment not met.
     */
    public static function checkAndPostpone(TutoringGroupCohort $cohort, int $hoursBefore = 24): bool
    {
        if (! in_array($cohort->status, [TutoringGroupCohort::STATUS_OPEN, TutoringGroupCohort::STATUS_POSTPONED], true)) {
            return false;
        }

        if (! $cohort->starts_at) {
            return false;
        }

        $threshold = now()->addHours($hoursBefore);
        if ($cohort->starts_at->gt($threshold)) {
            return false;
        }

        if ((int) $cohort->enrolled_count >= (int) $cohort->min_enrollment) {
            return false;
        }

        $newStart = $cohort->starts_at->copy()->addWeeks(1);

        $cohort->update([
            'status' => TutoringGroupCohort::STATUS_POSTPONED,
            'postponed_to' => $newStart,
            'starts_at' => $newStart,
        ]);

        self::notifyCohortStudents($cohort->fresh(), 'تم تأجيل موعد بدء دفعتك إلى '.$newStart->format('Y-m-d H:i'));

        return true;
    }

    public static function closeEnrollmentIfNeeded(TutoringGroupCohort $cohort): void
    {
        if ($cohort->enrollment_closes_at && $cohort->enrollment_closes_at->isPast()
            && $cohort->status === TutoringGroupCohort::STATUS_OPEN) {
            $cohort->update(['status' => TutoringGroupCohort::STATUS_CLOSED]);
        }
    }

    public static function processAll(): array
    {
        $postponed = 0;
        $closed = 0;

        TutoringGroupCohort::query()
            ->whereIn('status', [TutoringGroupCohort::STATUS_OPEN, TutoringGroupCohort::STATUS_POSTPONED])
            ->orderBy('id')
            ->chunkById(50, function ($cohorts) use (&$postponed, &$closed) {
                foreach ($cohorts as $cohort) {
                    self::closeEnrollmentIfNeeded($cohort);
                    $cohort->refresh();
                    if ($cohort->status === TutoringGroupCohort::STATUS_CLOSED) {
                        $closed++;
                    }
                    if (self::checkAndPostpone($cohort)) {
                        $postponed++;
                    }
                }
            });

        return compact('postponed', 'closed');
    }

    protected static function notifyCohortStudents(TutoringGroupCohort $cohort, string $message): void
    {
        $userIds = TutoringGroupBooking::query()
            ->where('cohort_id', $cohort->id)
            ->whereIn('status', [TutoringGroupBooking::STATUS_PENDING, TutoringGroupBooking::STATUS_CONFIRMED])
            ->whereNotNull('user_id')
            ->pluck('user_id');

        $enrollmentIds = TutoringCohortEnrollment::query()
            ->where('tutoring_group_cohort_id', $cohort->id)
            ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
            ->pluck('user_id');

        $userIds = $userIds->merge($enrollmentIds)->unique();

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'sender_id' => null,
                'title' => 'تحديث دفعة المجموعة',
                'message' => $message.' — '.$cohort->title,
                'type' => 'reminder',
                'priority' => 'high',
                'audience' => 'student',
                'action_url' => \Illuminate\Support\Facades\Route::has('student.classes.show')
                    ? route('student.classes.show', $cohort)
                    : (\Illuminate\Support\Facades\Route::has('dashboard')
                        ? route('dashboard')
                        : '/'),
                'action_text' => 'عرض الفصل',
            ]);
        }
    }
}
