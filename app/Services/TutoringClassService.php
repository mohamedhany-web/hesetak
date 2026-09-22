<?php

namespace App\Services;

use App\Models\ClassroomMeeting;
use App\Models\Notification;
use App\Models\TutoringClassAttendance;
use App\Models\TutoringClassSession;
use App\Models\TutoringCohortEnrollment;
use App\Models\TutoringGroupCohort;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TutoringClassService
{
    /**
     * Generate recurring class sessions from cohort study_days + study_time.
     *
     * @return Collection<int, TutoringClassSession>
     */
    public static function generateSchedule(TutoringGroupCohort $cohort, bool $replaceFuture = false): Collection
    {
        $cohort->loadMissing('tutoringGroup');

        $days = collect($cohort->study_days ?? [])
            ->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 1 && $d <= 7)
            ->unique()
            ->values();

        if ($days->isEmpty()) {
            throw new InvalidArgumentException('حدّد أيام الدراسة للدفعة أولاً.');
        }

        if (! filled($cohort->study_time)) {
            throw new InvalidArgumentException('حدّد وقت الدراسة للدفعة أولاً.');
        }

        if (! $cohort->starts_at) {
            throw new InvalidArgumentException('حدّد تاريخ بداية الدفعة أولاً.');
        }

        $tz = $cohort->timezone ?: AppTimezone::academy();
        $time = Carbon::parse((string) $cohort->study_time, $tz);
        $duration = (int) ($cohort->session_duration_minutes
            ?: $cohort->tutoringGroup?->duration_minutes
            ?: 60);
        $duration = max(15, $duration);

        $targetCount = (int) ($cohort->sessions_count
            ?: $cohort->tutoringGroup?->sessions_per_month
            ?: 8);
        $targetCount = max(1, min(60, $targetCount));

        $cursor = $cohort->starts_at->copy()->timezone($tz)->startOfDay();
        $hardEnd = $cohort->ends_at
            ? $cohort->ends_at->copy()->timezone($tz)
            : $cursor->copy()->addMonths(6);

        $slots = [];
        $guard = 0;
        while (count($slots) < $targetCount && $cursor->lte($hardEnd) && $guard < 400) {
            $guard++;
            // Carbon: 1=Mon ... 7=Sun — matches our study_days map
            $isoDay = (int) $cursor->dayOfWeekIso;
            if ($days->contains($isoDay)) {
                $startsAt = $cursor->copy()->setTime($time->hour, $time->minute, 0);
                if ($startsAt->gte($cohort->starts_at->copy()->timezone($tz)->subMinute())) {
                    $slots[] = [
                        'starts_at' => $startsAt->copy()->utc(),
                        'ends_at' => $startsAt->copy()->addMinutes($duration)->utc(),
                    ];
                }
            }
            $cursor->addDay();
        }

        if ($slots === []) {
            throw new InvalidArgumentException('تعذر توليد مواعيد ضمن النطاق المحدد.');
        }

        return DB::transaction(function () use ($cohort, $slots, $replaceFuture) {
            if ($replaceFuture) {
                TutoringClassSession::query()
                    ->where('tutoring_group_cohort_id', $cohort->id)
                    ->where('status', TutoringClassSession::STATUS_SCHEDULED)
                    ->where('starts_at', '>=', now())
                    ->whereNull('classroom_meeting_id')
                    ->delete();
            }

            $existingNumbers = TutoringClassSession::query()
                ->where('tutoring_group_cohort_id', $cohort->id)
                ->pluck('session_number')
                ->map(fn ($n) => (int) $n)
                ->all();

            $nextNumber = $existingNumbers === [] ? 1 : (max($existingNumbers) + 1);
            $created = collect();

            foreach ($slots as $slot) {
                $duplicate = TutoringClassSession::query()
                    ->where('tutoring_group_cohort_id', $cohort->id)
                    ->where('starts_at', $slot['starts_at'])
                    ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
                    ->exists();

                if ($duplicate) {
                    continue;
                }

                $created->push(TutoringClassSession::create([
                    'tutoring_group_cohort_id' => $cohort->id,
                    'tutoring_group_id' => $cohort->tutoring_group_id,
                    'session_number' => $nextNumber,
                    'title' => 'الحصة '.$nextNumber,
                    'starts_at' => $slot['starts_at'],
                    'ends_at' => $slot['ends_at'],
                    'status' => TutoringClassSession::STATUS_SCHEDULED,
                ]));
                $nextNumber++;
            }

            return $created;
        });
    }

    public static function enrollStudent(
        TutoringGroupCohort $cohort,
        User $user,
        ?int $orderId = null,
        ?int $entitlementId = null,
        bool $countSeat = true,
        ?string $notes = null,
    ): TutoringCohortEnrollment {
        $enrollment = DB::transaction(function () use ($cohort, $user, $orderId, $entitlementId, $countSeat, $notes) {
            $existing = TutoringCohortEnrollment::query()
                ->where('tutoring_group_cohort_id', $cohort->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === TutoringCohortEnrollment::STATUS_ACTIVE) {
                return $existing;
            }

            if ($existing) {
                $existing->update([
                    'status' => TutoringCohortEnrollment::STATUS_ACTIVE,
                    'enrolled_at' => now(),
                    'order_id' => $orderId ?: $existing->order_id,
                    'student_service_entitlement_id' => $entitlementId ?: $existing->student_service_entitlement_id,
                    'notes' => $notes ?: $existing->notes,
                ]);
                $enrollment = $existing->fresh();
            } else {
                if ($countSeat && ! $cohort->isEnrollmentOpen()) {
                    throw new InvalidArgumentException('هذه الدفعة غير متاحة للانضمام حالياً.');
                }

                $enrollment = TutoringCohortEnrollment::create([
                    'tutoring_group_cohort_id' => $cohort->id,
                    'user_id' => $user->id,
                    'status' => TutoringCohortEnrollment::STATUS_ACTIVE,
                    'enrolled_at' => now(),
                    'order_id' => $orderId,
                    'student_service_entitlement_id' => $entitlementId,
                    'notes' => $notes,
                ]);
            }

            if ($countSeat) {
                // Seat counter may already be incremented by booking flow — sync from roster when possible
                self::syncEnrolledCount($cohort);
            }

            Notification::create([
                'user_id' => $user->id,
                'sender_id' => null,
                'title' => 'تم انضمامك للفصل',
                'message' => 'انضممت إلى «'.$cohort->title.'». تابع مواعيد الحصص من صفحتك.',
                'type' => 'reminder',
                'priority' => 'high',
                'audience' => 'student',
                'action_url' => \Illuminate\Support\Facades\Route::has('student.classes.show')
                    ? route('student.classes.show', $cohort)
                    : route('dashboard'),
                'action_text' => 'عرض الفصل',
            ]);

            return $enrollment;
        });

        $cohort->loadMissing('tutoringGroup');
        $instructorId = (int) ($cohort->tutoringGroup?->instructor_id ?? 0);
        if ($instructorId > 0 && PrivateCoursesCoreService::threadsReady()) {
            PrivateCoursesCoreService::ensureThread(
                (int) $user->id,
                $instructorId,
                null,
                'مجتمع الفصل · '.$cohort->title
            );
        }

        return $enrollment;
    }

    public static function cancelEnrollment(TutoringCohortEnrollment $enrollment, bool $releaseSeat = true): TutoringCohortEnrollment
    {
        return DB::transaction(function () use ($enrollment, $releaseSeat) {
            $enrollment = TutoringCohortEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            if ($enrollment->status === TutoringCohortEnrollment::STATUS_CANCELLED) {
                return $enrollment;
            }

            $enrollment->update(['status' => TutoringCohortEnrollment::STATUS_CANCELLED]);

            if ($releaseSeat) {
                self::syncEnrolledCount($enrollment->cohort()->firstOrFail());
            }

            return $enrollment->fresh();
        });
    }

    public static function syncEnrolledCount(TutoringGroupCohort $cohort): TutoringGroupCohort
    {
        return DB::transaction(function () use ($cohort) {
            $locked = TutoringGroupCohort::query()->lockForUpdate()->findOrFail($cohort->id);
            $count = TutoringCohortEnrollment::query()
                ->where('tutoring_group_cohort_id', $locked->id)
                ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
                ->count();

            // Prefer roster count when enrollments exist; otherwise keep booking-based counter
            if ($count > 0 || TutoringCohortEnrollment::query()->where('tutoring_group_cohort_id', $locked->id)->exists()) {
                $locked->enrolled_count = $count;
                if ($locked->enrolled_count >= (int) $locked->capacity) {
                    $locked->status = TutoringGroupCohort::STATUS_FULL;
                } elseif (in_array($locked->status, [TutoringGroupCohort::STATUS_FULL, TutoringGroupCohort::STATUS_OPEN], true)) {
                    $locked->status = TutoringGroupCohort::STATUS_OPEN;
                }
                $locked->save();
            }

            return $locked->fresh();
        });
    }

    public static function ensureSessionMeeting(TutoringClassSession $session): ClassroomMeeting
    {
        $session->loadMissing(['cohort', 'tutoringGroup', 'classroomMeeting']);

        if ($session->classroomMeeting) {
            return $session->classroomMeeting;
        }

        return DB::transaction(function () use ($session) {
            $session = TutoringClassSession::query()
                ->with(['cohort', 'tutoringGroup', 'classroomMeeting'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->classroomMeeting) {
                return $session->classroomMeeting;
            }

            $group = $session->tutoringGroup;
            $cohort = $session->cohort;
            $capacity = max(2, (int) ($cohort?->capacity ?? $group?->capacity ?? 8));
            $duration = (int) ($session->starts_at && $session->ends_at
                ? $session->starts_at->diffInMinutes($session->ends_at)
                : ($cohort?->session_duration_minutes ?: $group?->duration_minutes ?: 60));

            $meeting = ClassroomMeeting::create([
                'user_id' => $group?->instructor_id,
                'code' => ClassroomMeeting::generateCode(),
                'room_name' => 'class-'.$session->id.'-'.Str::lower(Str::random(6)),
                'title' => ($group?->title ?? 'فصل').' — '.$session->displayTitle(),
                'scheduled_for' => $session->starts_at,
                'planned_duration_minutes' => max(15, $duration),
                'max_participants' => $capacity + 2,
                'settings' => [
                    'tutoring_class_session_id' => $session->id,
                    'tutoring_group_cohort_id' => $session->tutoring_group_cohort_id,
                    'shared_class_room' => true,
                    'allow_guest_join' => false,
                ],
            ]);

            $session->update(['classroom_meeting_id' => $meeting->id]);

            return $meeting;
        });
    }

    public static function ensureAllMeetings(TutoringGroupCohort $cohort): int
    {
        $created = 0;
        $sessions = TutoringClassSession::query()
            ->where('tutoring_group_cohort_id', $cohort->id)
            ->where('status', '!=', TutoringClassSession::STATUS_CANCELLED)
            ->whereNull('classroom_meeting_id')
            ->orderBy('session_number')
            ->get();

        foreach ($sessions as $session) {
            self::ensureSessionMeeting($session);
            $created++;
        }

        return $created;
    }

    public static function markAttendanceOnJoin(TutoringClassSession $session, User $user): TutoringClassAttendance
    {
        $lateThreshold = $session->starts_at?->copy()->addMinutes(10);
        $status = ($lateThreshold && now()->gt($lateThreshold))
            ? TutoringClassAttendance::STATUS_LATE
            : TutoringClassAttendance::STATUS_PRESENT;

        $existing = TutoringClassAttendance::query()
            ->where('tutoring_class_session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();

        $attendance = TutoringClassAttendance::query()->updateOrCreate(
            [
                'tutoring_class_session_id' => $session->id,
                'user_id' => $user->id,
            ],
            [
                'status' => $status,
                'joined_at' => now(),
            ]
        );

        // XP مرة واحدة عند أول تسجيل حضور لهذه الحصة
        if (! $existing) {
            StudentSchoolGameService::awardAttendance($session, $user, $status);
        }

        return $attendance;
    }

    public static function userCanAccessCohort(User $user, TutoringGroupCohort $cohort): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $cohort->tutoringGroup?->instructor_id === (int) $user->id) {
            return true;
        }

        return TutoringCohortEnrollment::query()
            ->where('tutoring_group_cohort_id', $cohort->id)
            ->where('user_id', $user->id)
            ->where('status', TutoringCohortEnrollment::STATUS_ACTIVE)
            ->exists();
    }

    public static function cancelSession(TutoringClassSession $session, ?string $notes = null): TutoringClassSession
    {
        $session->update([
            'status' => TutoringClassSession::STATUS_CANCELLED,
            'notes' => trim(($session->notes ? $session->notes."\n" : '').($notes ?: 'أُلغيت الحصة')),
        ]);

        return $session->fresh();
    }

    public static function completeSession(TutoringClassSession $session): TutoringClassSession
    {
        $session->update(['status' => TutoringClassSession::STATUS_COMPLETED]);
        if ($session->classroom_meeting_id) {
            $meeting = $session->classroomMeeting;
            if ($meeting && ! $meeting->ended_at) {
                $meeting->update(['ended_at' => now()]);
            }
        }

        return $session->fresh();
    }
}
