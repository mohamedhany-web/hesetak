<?php

namespace App\Services;

use App\Models\AdvancedCourse;
use App\Models\ClassroomMeeting;
use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\ServicePackage;
use App\Models\StudentCourseEnrollment;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OneToOneSessionService
{
    public const SESSIONS_PER_MONTH = 4;

    public const MAX_WEEKLY_PATTERN_SLOTS = 7;

    public const MAX_SERIES_WEEKS = 16;

    public const MAX_SESSIONS_PER_BOOKING = 40;

    /**
     * إنشاء حصص بانتظار الجدولة حسب رصيد الباقة (أو حد أقصى للعرض).
     */
    public static function provisionSessionsForEnrollment(StudentCourseEnrollment $enrollment, AdvancedCourse $course): void
    {
        if (! $course->isOneToOne() || ! $course->instructor_id) {
            return;
        }

        if (! CourseSubscriptionService::enrollmentGrantsAccess($enrollment)) {
            return;
        }

        $unitsLeft = StudentEntitlementService::unitsLeft(
            (int) $enrollment->user_id,
            ServicePackage::SCOPE_PRIVATE_LESSONS
        );

        // Fallback for legacy enrollments without packages: keep previous 4-session behavior
        $target = $unitsLeft > 0
            ? min($unitsLeft, self::SESSIONS_PER_MONTH)
            : self::SESSIONS_PER_MONTH;

        $activeCount = OneToOneSession::query()
            ->where('student_course_enrollment_id', $enrollment->id)
            ->whereIn('status', [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED])
            ->count();

        $toCreate = max(0, $target - $activeCount);
        if ($toCreate === 0) {
            return;
        }

        $maxNumber = (int) OneToOneSession::query()
            ->where('student_course_enrollment_id', $enrollment->id)
            ->max('session_number');

        $entitlement = StudentEntitlementService::availableFor(
            (int) $enrollment->user_id,
            ServicePackage::SCOPE_PRIVATE_LESSONS
        );

        for ($i = 1; $i <= $toCreate; $i++) {
            OneToOneSession::create([
                'student_course_enrollment_id' => $enrollment->id,
                'student_service_entitlement_id' => $entitlement?->id,
                'advanced_course_id' => $course->id,
                'instructor_id' => $course->instructor_id,
                'student_id' => $enrollment->user_id,
                'session_number' => $maxNumber + $i,
                'duration_minutes' => OneToOneSession::defaultDurationMinutes(),
                'status' => OneToOneSession::STATUS_PENDING,
            ]);
        }

        Notification::create([
            'user_id' => $enrollment->user_id,
            'sender_id' => null,
            'title' => 'تم تفعيل حصصك الفردية',
            'message' => $unitsLeft > 0
                ? ('تم تجهيز '.$toCreate.' حصص من رصيد باقتك. اختر موعداً من جدول المعلم.')
                : ('تم إنشاء '.$toCreate.' حصص. اشحن باقتك لاحقاً لإضافة المزيد.'),
            'type' => 'general',
            'priority' => 'normal',
            'audience' => 'student',
            'action_url' => route('student.one-to-one-sessions.index'),
            'action_text' => 'عرض الحصص',
        ]);

        Notification::create([
            'user_id' => $course->instructor_id,
            'sender_id' => null,
            'title' => '🎉 New Student Assigned',
            'message' => ($enrollment->student->name ?? 'Student').' has been assigned to you for private lessons — «'.$course->title.'».',
            'type' => 'general',
            'priority' => 'high',
            'audience' => 'instructor',
            'action_url' => route('instructor.one-to-one-sessions.index'),
            'action_text' => 'View schedule',
        ]);
    }

    /**
     * حجز موعد مباشر مع معلم من صفحة الملف العام — يتطلب رصيد باقة حصص خاصة.
     */
    public static function bookStandaloneWithInstructor(User $student, User $instructor, Carbon $scheduledAt): OneToOneSession
    {
        $sessions = self::bookMultipleWithInstructor($student, $instructor, [$scheduledAt]);

        return $sessions->first();
    }

    /**
     * تثبيت شهري: مواعيد أسبوعية (حتى 7) تتكرر لعدد أسابيع (حتى 16).
     *
     * @param  array<int, array{day_of_week:int,time:string}>  $weeklySlots  ISO 1=Mon…7=Sun + H:i
     * @return \Illuminate\Support\Collection<int, OneToOneSession>
     */
    public static function bookMonthlySeriesWithInstructor(
        User $student,
        User $instructor,
        array $weeklySlots,
        int $weeks = 4,
        ?\App\Models\StudentServiceEntitlement $entitlement = null,
        ?User $bookedBy = null,
        ?string $notes = null,
        ?Carbon $from = null,
        bool $requireAvailability = true,
        ?string $timezone = null
    ) {
        $weeks = max(1, min(self::MAX_SERIES_WEEKS, $weeks));
        $normalized = collect($weeklySlots)
            ->map(function ($row) {
                $day = (int) ($row['day_of_week'] ?? 0);
                $time = \App\Support\WeeklyScheduleTime::normalize((string) ($row['time'] ?? ''));

                return $day >= 1 && $day <= 7 && $time !== null
                    ? ['day_of_week' => $day, 'time' => $time]
                    : null;
            })
            ->filter()
            ->unique(fn ($row) => $row['day_of_week'].'|'.$row['time'])
            ->values();

        if ($normalized->isEmpty()) {
            throw new \InvalidArgumentException('اختر موعداً أسبوعياً واحداً على الأقل (يوم + وقت).');
        }
        if ($normalized->count() > self::MAX_WEEKLY_PATTERN_SLOTS) {
            throw new \InvalidArgumentException('الحد الأقصى '.self::MAX_WEEKLY_PATTERN_SLOTS.' مواعيد أسبوعية للتثبيت الشهري.');
        }

        $from = ($from?->copy() ?? now()->addHour())->startOfMinute();
        $dates = self::expandWeeklyPattern(
            $normalized->all(),
            $weeks,
            $from,
            $timezone ?? \App\Support\AppTimezone::forUser($instructor)
        );

        if (count($dates) < 1) {
            throw new \InvalidArgumentException('تعذر توليد مواعيد من النمط الأسبوعي خلال الفترة المحددة.');
        }

        return self::bookMultipleWithInstructor(
            $student,
            $instructor,
            $dates,
            $entitlement,
            $bookedBy,
            trim(($notes ?? '')."\nتثبيت شهري: ".$normalized->count().' موعد/أسبوع × '.$weeks.' أسابيع'),
            $requireAvailability
        );
    }

    /**
     * حجز عدة مواعيد دفعة واحدة مع نفس المعلم (نفس الرصيد).
     *
     * @param  array<int, Carbon|string>  $scheduledAts
     * @return \Illuminate\Support\Collection<int, OneToOneSession>
     */
    public static function bookMultipleWithInstructor(
        User $student,
        User $instructor,
        array $scheduledAts,
        ?\App\Models\StudentServiceEntitlement $entitlement = null,
        ?User $bookedBy = null,
        ?string $notes = null,
        bool $requireAvailability = true
    ) {
        if (! $student->isStudent()) {
            throw new \InvalidArgumentException('الحجز متاح للطلاب فقط.');
        }
        if (! $instructor->isInstructor() || ! $instructor->is_active) {
            throw new \InvalidArgumentException('المعلم غير متاح حالياً.');
        }

        $dates = collect($scheduledAts)
            ->map(function ($at) {
                if ($at instanceof Carbon) {
                    return $at->copy()->utc();
                }

                return \App\Support\AppTimezone::parseAppointmentInput((string) $at)
                    ?? Carbon::parse((string) $at)->utc();
            })
            ->filter(fn (Carbon $at) => $at->gt(now()))
            ->unique(fn (Carbon $at) => $at->format('Y-m-d H:i'))
            ->sortBy(fn (Carbon $at) => $at->timestamp)
            ->values();

        if ($dates->isEmpty()) {
            throw new \InvalidArgumentException('اختر موعداً واحداً على الأقل في المستقبل.');
        }
        if ($dates->count() > self::MAX_SESSIONS_PER_BOOKING) {
            throw new \InvalidArgumentException('الحد الأقصى '.self::MAX_SESSIONS_PER_BOOKING.' حصة في الحجز الواحد.');
        }

        if (! $entitlement) {
            $entitlement = StudentEntitlementService::availableFor(
                (int) $student->id,
                ServicePackage::SCOPE_PRIVATE_LESSONS
            );
        }

        if (! $entitlement || StudentEntitlementService::bookableUnitsLeft($entitlement) < $dates->count()) {
            $left = $entitlement ? StudentEntitlementService::bookableUnitsLeft($entitlement) : 0;
            throw new \InvalidArgumentException(
                'الرصيد غير كافٍ. المطلوب '.$dates->count().' حصة والمتاح '.$left.'.'
            );
        }

        $duration = OneToOneSession::defaultDurationMinutes();
        foreach ($dates as $at) {
            $endsAt = $at->copy()->addMinutes($duration);
            if (OneToOneAvailabilityService::hasConflict((int) $instructor->id, $at, $endsAt)) {
                throw new \InvalidArgumentException(
                    'الموعد '.$at->copy()->timezone(\App\Support\AppTimezone::forUser($instructor))->format('Y-m-d H:i').' متعارض مع حصة أخرى عند هذا المعلم.'
                );
            }
            if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable((int) $instructor->id, $at, $duration)) {
                throw new \InvalidArgumentException(
                    'الموعد '.$at->format('Y-m-d H:i').' غير متاح عند هذا المعلم.'
                );
            }
        }

        $seriesId = $dates->count() > 1 ? (string) Str::uuid() : null;

        $created = DB::transaction(function () use ($student, $instructor, $dates, $entitlement, $duration, $bookedBy, $notes, $seriesId, $requireAvailability) {
            $maxNumber = (int) OneToOneSession::query()
                ->where('student_id', $student->id)
                ->max('session_number');

            $courseId = AdvancedCourse::query()
                ->where('instructor_id', $instructor->id)
                ->where('is_active', true)
                ->where('delivery_type', CourseSubscriptionService::DELIVERY_ONE_TO_ONE)
                ->value('id');

            $created = collect();
            foreach ($dates as $i => $scheduledAt) {
                if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable((int) $instructor->id, $scheduledAt, $duration)) {
                    throw new \InvalidArgumentException(
                        'تعارض في الموعد '.$scheduledAt->format('Y-m-d H:i').' بعد بدء الحجز.'
                    );
                }
                if (! $requireAvailability && OneToOneAvailabilityService::hasConflict(
                    (int) $instructor->id,
                    $scheduledAt,
                    $scheduledAt->copy()->addMinutes($duration)
                )) {
                    throw new \InvalidArgumentException(
                        'تعارض في الموعد '.$scheduledAt->format('Y-m-d H:i').' بعد بدء الحجز.'
                    );
                }

                $session = OneToOneSession::create([
                    'student_course_enrollment_id' => null,
                    'student_service_entitlement_id' => $entitlement->id,
                    'advanced_course_id' => $courseId,
                    'instructor_id' => $instructor->id,
                    'student_id' => $student->id,
                    'session_number' => $maxNumber + $i + 1,
                    'duration_minutes' => $duration,
                    'status' => OneToOneSession::STATUS_PENDING,
                    'booked_by_user_id' => $bookedBy?->id,
                    'notes' => $notes,
                    'series_id' => $seriesId,
                ]);

                self::scheduleSession(
                    $session,
                    $scheduledAt,
                    $duration,
                    $bookedBy ?? $student,
                    requireAvailability: $requireAvailability,
                    notify: $seriesId === null
                );
                $created->push($session->fresh(['instructor', 'classroomMeeting']));
            }

            if ($created->count() > 1) {
                $firstAt = $created->first()?->scheduled_at?->format('Y-m-d H:i') ?? '';
                $lastAt = $created->last()?->scheduled_at?->format('Y-m-d H:i') ?? '';
                Notification::create([
                    'user_id' => $student->id,
                    'sender_id' => $bookedBy?->id,
                    'title' => 'تم تثبيت جدولك الشهري',
                    'message' => 'تم جدولة '.$created->count().' حصص مع '.($instructor->name ?? 'المعلم').' (من '.$firstAt.' إلى '.$lastAt.').',
                    'type' => 'reminder',
                    'priority' => 'high',
                    'audience' => 'student',
                    'action_url' => route('student.one-to-one-sessions.index'),
                    'action_text' => 'عرض الحصص',
                ]);
                Notification::create([
                    'user_id' => $instructor->id,
                    'sender_id' => $bookedBy?->id,
                    'title' => 'جدول شهري 1:1 جديد',
                    'message' => 'الطالب: '.($student->name ?? 'طالب').' — '.$created->count().' حصص مثبتة.',
                    'type' => 'reminder',
                    'priority' => 'normal',
                    'audience' => 'instructor',
                    'action_url' => route('instructor.one-to-one-sessions.index'),
                    'action_text' => 'عرض الجدول',
                ]);
            }

            return $created;
        });

        if (PrivateCoursesCoreService::threadsReady()) {
            PrivateCoursesCoreService::ensureThread(
                (int) $student->id,
                (int) $instructor->id,
                null,
                'تواصل مع المعلم'
            );
        }

        return $created;
    }

    /**
     * حصة مجانية لحامل باقة — تُجدول للطالب والمعلم دون خصم رصيد.
     */
    public static function bookComplimentaryWithInstructor(
        User $student,
        User $instructor,
        Carbon $scheduledAt,
        ?User $bookedBy = null,
        ?string $notes = null,
        ?int $durationMinutes = null,
        bool $requireAvailability = true
    ): OneToOneSession {
        if (! $student->isStudent()) {
            throw new \InvalidArgumentException('الحجز متاح للطلاب فقط.');
        }
        if (! $instructor->isInstructor() || ! $instructor->is_active) {
            throw new \InvalidArgumentException('المعلم غير متاح حالياً.');
        }

        $scheduledAt = $scheduledAt->copy()->utc()->startOfMinute();
        if ($scheduledAt->lte(now())) {
            throw new \InvalidArgumentException('الموعد يجب أن يكون في المستقبل.');
        }

        $duration = max(15, min(180, $durationMinutes ?? OneToOneSession::defaultDurationMinutes()));
        $endsAt = $scheduledAt->copy()->addMinutes($duration);

        if (OneToOneAvailabilityService::hasConflict((int) $instructor->id, $scheduledAt, $endsAt)) {
            throw new \InvalidArgumentException('هذا الموعد متعارض مع حصة أخرى عند هذا المعلم.');
        }
        if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable((int) $instructor->id, $scheduledAt, $duration)) {
            throw new \InvalidArgumentException('هذا الموعد غير متاح عند هذا المعلم.');
        }

        return DB::transaction(function () use ($student, $instructor, $scheduledAt, $duration, $bookedBy, $notes, $requireAvailability) {
            $courseId = AdvancedCourse::query()
                ->where('instructor_id', $instructor->id)
                ->where('is_active', true)
                ->where('delivery_type', CourseSubscriptionService::DELIVERY_ONE_TO_ONE)
                ->value('id');

            $maxNumber = (int) OneToOneSession::query()
                ->where('student_id', $student->id)
                ->max('session_number');

            $session = OneToOneSession::create([
                'student_course_enrollment_id' => null,
                'student_service_entitlement_id' => null,
                'advanced_course_id' => $courseId,
                'instructor_id' => $instructor->id,
                'student_id' => $student->id,
                'session_number' => $maxNumber + 1,
                'duration_minutes' => $duration,
                'status' => OneToOneSession::STATUS_PENDING,
                'is_complimentary' => true,
                'booked_by_user_id' => $bookedBy?->id ?? $student->id,
                'notes' => $notes ?: 'حصة مجانية — لا تُخصم من رصيد الباقة',
            ]);

            self::scheduleSession(
                $session,
                $scheduledAt,
                $duration,
                $bookedBy ?? $student,
                requireAvailability: $requireAvailability
            );

            if (PrivateCoursesCoreService::threadsReady()) {
                PrivateCoursesCoreService::ensureThread(
                    (int) $student->id,
                    (int) $instructor->id,
                    null,
                    'تواصل مع المعلم'
                );
            }

            return $session->fresh(['instructor', 'student', 'classroomMeeting']);
        });
    }

    /**
     * @param  array<int, array{day_of_week:int,time:string}>  $weeklySlots
     * @return array<int, Carbon>
     */
    public static function expandWeeklyPattern(array $weeklySlots, int $weeks, Carbon $from, ?string $timezone = null): array
    {
        $weeks = max(1, min(self::MAX_SERIES_WEEKS, $weeks));
        $clockTz = \App\Support\AppTimezone::normalize($timezone) ?? \App\Support\AppTimezone::academy();
        $cursor = $from->copy()->timezone($clockTz)->startOfDay();
        $hardEnd = $cursor->copy()->addWeeks($weeks)->endOfDay();
        $neededPerPattern = $weeks;
        $byKey = [];

        foreach ($weeklySlots as $slot) {
            $day = (int) ($slot['day_of_week'] ?? 0);
            $time = \App\Support\WeeklyScheduleTime::normalize((string) ($slot['time'] ?? ''));
            if ($day < 1 || $day > 7 || $time === null) {
                continue;
            }
            $byKey[$day.'|'.$time] = ['day_of_week' => $day, 'time' => $time, 'hits' => []];
        }

        if ($byKey === []) {
            return [];
        }

        $guard = 0;
        $dayCursor = $cursor->copy();
        while ($dayCursor->lte($hardEnd) && $guard < 400) {
            $guard++;
            $iso = (int) $dayCursor->dayOfWeekIso;
            foreach ($byKey as $key => $meta) {
                if ($meta['day_of_week'] !== $iso) {
                    continue;
                }
                if (count($meta['hits']) >= $neededPerPattern) {
                    continue;
                }
                $at = \App\Support\AppTimezone::wallClockToUtc($dayCursor->toDateString(), $meta['time'], $clockTz);
                if ($at->gt($from)) {
                    $byKey[$key]['hits'][] = $at;
                }
            }

            $allFull = collect($byKey)->every(fn ($meta) => count($meta['hits']) >= $neededPerPattern);
            if ($allFull) {
                break;
            }
            $dayCursor->addDay();
        }

        return collect($byKey)
            ->flatMap(fn ($meta) => $meta['hits'])
            ->sortBy(fn (Carbon $at) => $at->timestamp)
            ->values()
            ->all();
    }

    public static function scheduleSession(
        OneToOneSession $session,
        Carbon $scheduledAt,
        int $durationMinutes,
        ?User $scheduledBy = null,
        bool $requireAvailability = true,
        bool $notify = true
    ): void {
        if (! in_array($session->status, [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED], true)) {
            throw new \InvalidArgumentException('لا يمكن جدولة هذه الحصة في حالتها الحالية.');
        }

        // Require private-lessons (or global) credit unless complimentary or already linked
        if (! $session->isComplimentary()) {
            $entitlement = $session->entitlement;
            if (! $entitlement || ! $entitlement->hasUnitsLeft()) {
                $entitlement = StudentEntitlementService::availableFor(
                    (int) $session->student_id,
                    ServicePackage::SCOPE_PRIVATE_LESSONS
                );
            }
            // Soft-gate: if student has any entitlement system usage, enforce it
            $hasAnyPrivatePackage = \App\Models\StudentServiceEntitlement::query()
                ->forUser((int) $session->student_id)
                ->whereIn('scope', [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_GLOBAL])
                ->exists();
            if ($hasAnyPrivatePackage && (! $entitlement || ! $entitlement->hasUnitsLeft())) {
                throw new \InvalidArgumentException('لا يوجد رصيد حصص خاصة. اشترِ باقة أو اشحن رصيدك.');
            }
            if ($entitlement && ! $session->student_service_entitlement_id) {
                $session->student_service_entitlement_id = $entitlement->id;
            }
        }

        if ($scheduledAt->lte(now())) {
            throw new \InvalidArgumentException('الموعد يجب أن يكون في المستقبل.');
        }

        if (OneToOneAvailabilityService::hasConflict(
            (int) $session->instructor_id,
            $scheduledAt,
            $scheduledAt->copy()->addMinutes($durationMinutes),
            $session->status === OneToOneSession::STATUS_SCHEDULED ? $session->id : null
        )) {
            throw new \InvalidArgumentException('هذا الموعد متعارض مع حصة أخرى عند المعلم.');
        }

        if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable(
            (int) $session->instructor_id,
            $scheduledAt,
            $durationMinutes,
            $session->status === OneToOneSession::STATUS_SCHEDULED ? $session->id : null
        )) {
            throw new \InvalidArgumentException('هذا الموعد غير متاح — ربما حُجز أو خارج جدول المعلم.');
        }

        $studentName = $session->student->name ?? 'طالب';
        $courseTitle = $session->course->title ?? 'كورس فردي';

        $meeting = ClassroomMeeting::create([
            'user_id' => $session->instructor_id,
            'one_to_one_session_id' => $session->id,
            'code' => ClassroomMeeting::generateCode(),
            'room_name' => 'one-to-one-'.$session->id.'-'.Str::lower(Str::random(6)),
            'title' => $session->isComplimentary()
                ? 'حصة مجانية'
                : ('حصة 1:1: '.$courseTitle),
            'scheduled_for' => $scheduledAt,
            'planned_duration_minutes' => $durationMinutes,
            'max_participants' => 4,
            'settings' => [
                'allow_guest_join' => false,
                'private_lesson' => true,
                'complimentary' => $session->isComplimentary(),
            ],
        ]);

        $session->update([
            'status' => OneToOneSession::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'classroom_meeting_id' => $meeting->id,
            'booked_by_user_id' => $scheduledBy?->id,
            'student_service_entitlement_id' => $session->student_service_entitlement_id,
        ]);

        $joinUrl = \App\Services\ClassroomMeetingAccessService::platformEnterUrl($meeting);
        $session->loadMissing(['student', 'instructor']);
        $studentWhen = \App\Support\AppTimezone::formatFor(
            $scheduledAt,
            \App\Support\AppTimezone::forUser($session->student),
            'D j M · g:i A'
        );
        $instructorWhen = \App\Support\AppTimezone::formatFor(
            $scheduledAt,
            \App\Support\AppTimezone::forUser($session->instructor),
            'D j M · g:i A'
        );

        if (! $notify) {
            return;
        }

        Notification::create([
            'user_id' => $session->student_id,
            'sender_id' => $scheduledBy?->id,
            'title' => 'تم جدولة حصتك الفردية',
            'message' => 'موعد الحصة بتوقيتك: '.$studentWhen.' — رابط الدخول: '.$joinUrl,
            'type' => 'reminder',
            'priority' => 'high',
            'audience' => 'student',
            'action_url' => route('student.one-to-one-sessions.show', $session),
            'action_text' => 'تفاصيل الحصة',
        ]);

        Notification::create([
            'user_id' => $session->instructor_id,
            'sender_id' => $scheduledBy?->id,
            'title' => 'حصة 1:1 مجدولة',
            'message' => 'الطالب: '.$studentName.' — الموعد بتوقيتك: '.$instructorWhen,
            'type' => 'reminder',
            'priority' => 'normal',
            'audience' => 'instructor',
            'action_url' => route('instructor.one-to-one-sessions.show', $session),
            'action_text' => 'تفاصيل الحصة',
        ]);
    }

    /**
     * تعديل موعد حصة 1:1 (تسكين قائم) — الإدارة تكتب الوقت بحرية دون اشتراط جدول المعلم.
     */
    public static function rescheduleSession(
        OneToOneSession $session,
        Carbon $scheduledAt,
        int $durationMinutes,
        ?User $rescheduledBy = null,
        bool $requireAvailability = false,
        bool $mustBeFuture = false,
        bool $notify = true
    ): void {
        $session = OneToOneSession::query()
            ->with(['classroomMeeting', 'student', 'instructor'])
            ->findOrFail($session->id);

        if (! in_array($session->status, [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED], true)) {
            throw new \InvalidArgumentException('لا يمكن تعديل موعد حصة في حالتها الحالية.');
        }

        $meeting = $session->classroomMeeting;
        if ($meeting?->started_at && ! $meeting->ended_at) {
            throw new \InvalidArgumentException('لا يمكن تعديل موعد حصة بدأت بالفعل.');
        }

        $durationMinutes = max(15, min(180, $durationMinutes));

        if ($mustBeFuture && $scheduledAt->lte(now())) {
            throw new \InvalidArgumentException('الموعد يجب أن يكون في المستقبل.');
        }

        $excludeSessionId = $session->status === OneToOneSession::STATUS_SCHEDULED ? $session->id : null;
        $endsAt = $scheduledAt->copy()->addMinutes($durationMinutes);

        if (OneToOneAvailabilityService::hasConflict(
            (int) $session->instructor_id,
            $scheduledAt,
            $endsAt,
            $excludeSessionId
        )) {
            throw new \InvalidArgumentException('هذا الموعد متعارض مع حصة أخرى عند المعلم.');
        }

        if ($requireAvailability && ! OneToOneAvailabilityService::isSlotAvailable(
            (int) $session->instructor_id,
            $scheduledAt,
            $durationMinutes,
            $excludeSessionId
        )) {
            throw new \InvalidArgumentException('هذا الموعد غير متاح في جدول المعلم.');
        }

        if (! $meeting) {
            self::scheduleSession(
                $session,
                $scheduledAt,
                $durationMinutes,
                $rescheduledBy,
                $requireAvailability,
                $notify
            );

            return;
        }

        DB::transaction(function () use ($session, $meeting, $scheduledAt, $durationMinutes, $rescheduledBy, $notify) {
            $session->update([
                'status' => OneToOneSession::STATUS_SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $durationMinutes,
            ]);

            $meeting->update([
                'scheduled_for' => $scheduledAt,
                'planned_duration_minutes' => $durationMinutes,
            ]);

            if (! $notify) {
                return;
            }

            $session->loadMissing(['student', 'instructor']);
            $studentWhen = AppTimezone::formatFor(
                $scheduledAt,
                AppTimezone::forUser($session->student),
                'D j M · g:i A'
            );
            $instructorWhen = AppTimezone::formatFor(
                $scheduledAt,
                AppTimezone::forUser($session->instructor),
                'D j M · g:i A'
            );
            $joinUrl = \App\Services\ClassroomMeetingAccessService::platformEnterUrl($meeting->fresh());

            Notification::create([
                'user_id' => $session->student_id,
                'sender_id' => $rescheduledBy?->id,
                'title' => 'تم تعديل موعد حصتك الفردية',
                'message' => 'الموعد الجديد بتوقيتك: '.$studentWhen.' — رابط الدخول: '.$joinUrl,
                'type' => 'reminder',
                'priority' => 'high',
                'audience' => 'student',
                'action_url' => route('student.one-to-one-sessions.show', $session),
                'action_text' => 'تفاصيل الحصة',
            ]);

            Notification::create([
                'user_id' => $session->instructor_id,
                'sender_id' => $rescheduledBy?->id,
                'title' => 'تعديل موعد حصة 1:1',
                'message' => 'الطالب: '.($session->student?->name ?? 'طالب').' — الموعد الجديد بتوقيتك: '.$instructorWhen,
                'type' => 'reminder',
                'priority' => 'normal',
                'audience' => 'instructor',
                'action_url' => route('instructor.one-to-one-sessions.show', $session),
                'action_text' => 'تفاصيل الحصة',
            ]);
        });
    }

    /**
     * إلغاء حصة 1:1 — يعيد الرصيد المحجوز. مع $entireSeries تُلغى باقي حصص التسكين الشهري غير المكتملة.
     *
     * @return int عدد الحصص التي أُلغيت
     */
    public static function cancelSession(OneToOneSession $session, bool $entireSeries = false, ?string $reason = null): int
    {
        return (int) DB::transaction(function () use ($session, $entireSeries, $reason) {
            $session = OneToOneSession::query()
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->status === OneToOneSession::STATUS_COMPLETED) {
                throw new \InvalidArgumentException('لا يمكن حذف تسكين لحصة مكتملة.');
            }

            $query = OneToOneSession::query()
                ->whereIn('status', [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED]);

            if ($entireSeries && filled($session->series_id)) {
                $query->where('series_id', $session->series_id);
            } else {
                $query->whereKey($session->id);
            }

            $rows = $query->with(['classroomMeeting', 'student'])->lockForUpdate()->get();
            if ($rows->isEmpty()) {
                return 0;
            }

            foreach ($rows as $row) {
                $notes = $row->notes;
                if ($reason) {
                    $notes = trim(($notes ? $notes."\n" : '').$reason);
                }
                $row->update([
                    'status' => OneToOneSession::STATUS_CANCELLED,
                    'notes' => $notes,
                ]);
                if ($row->classroomMeeting && ! $row->classroomMeeting->ended_at) {
                    $row->classroomMeeting->update(['ended_at' => now()]);
                }
            }

            $first = $rows->first();
            $count = $rows->count();
            if ($first?->student_id) {
                Notification::create([
                    'user_id' => $first->student_id,
                    'sender_id' => null,
                    'title' => 'تم حذف التسكين',
                    'message' => $count > 1
                        ? ('تم إلغاء '.$count.' حصص فردية وإرجاع الرصيد المحجوز.')
                        : 'تم إلغاء حصتك الفردية وإرجاع الرصيد المحجوز.',
                    'type' => 'general',
                    'priority' => 'normal',
                    'audience' => 'student',
                    'action_url' => route('student.one-to-one-sessions.index'),
                    'action_text' => 'عرض الحصص',
                ]);
            }
            if ($first?->instructor_id) {
                Notification::create([
                    'user_id' => $first->instructor_id,
                    'sender_id' => null,
                    'title' => 'أُلغي تسكين 1:1',
                    'message' => 'الطالب: '.($first->student?->name ?? 'طالب').($count > 1 ? ' — '.$count.' حصص' : ''),
                    'type' => 'general',
                    'priority' => 'normal',
                    'audience' => 'instructor',
                    'action_url' => route('instructor.one-to-one-sessions.index'),
                    'action_text' => 'عرض الجدول',
                ]);
            }

            return $count;
        });
    }

    public static function markCompleted(OneToOneSession $session, bool $requireLiveAttendance = true): void
    {
        DB::transaction(function () use ($session, $requireLiveAttendance) {
            $session = OneToOneSession::query()
                ->with(['entitlement', 'classroomMeeting'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->status === OneToOneSession::STATUS_COMPLETED) {
                return;
            }
            if ($session->status !== OneToOneSession::STATUS_SCHEDULED) {
                throw new \InvalidArgumentException('لا يمكن إكمال حصة غير مجدولة.');
            }

            $meeting = $session->classroomMeeting;
            if ($requireLiveAttendance) {
                if (! $meeting) {
                    throw new \InvalidArgumentException('لا يمكن إكمال الحصة قبل إنشاء غرفة الاجتماع.');
                }
                if (! $meeting->started_at) {
                    throw new \InvalidArgumentException('لا يمكن تعليم الحصة كمكتملة قبل دخول الغرفة وبدء الاجتماع.');
                }
                // لا يُسمح بخصم الرصيد قبل موعد الحصة (هامش 15 دقيقة للدخول المبكر).
                if ($session->scheduled_at && $session->scheduled_at->gt(now()->addMinutes(15))) {
                    throw new \InvalidArgumentException('لا يمكن إكمال الحصة قبل موعدها المحدد.');
                }
            }

            if (! $session->isComplimentary()) {
                $entitlement = $session->entitlement ?: StudentEntitlementService::availableFor(
                    (int) $session->student_id,
                    ServicePackage::SCOPE_PRIVATE_LESSONS
                );

                if ($entitlement) {
                    StudentEntitlementService::consume($entitlement, 1);
                    if (! $session->student_service_entitlement_id) {
                        $session->student_service_entitlement_id = $entitlement->id;
                    }
                }
            }

            $session->status = OneToOneSession::STATUS_COMPLETED;
            if (\Illuminate\Support\Facades\Schema::hasColumn('one_to_one_sessions', 'report_required_at')
                && ! $session->report_required_at) {
                $session->report_required_at = now();
            }
            $session->save();
            if ($meeting && ! $meeting->ended_at) {
                $meeting->update(['ended_at' => now()]);
            }

            OneToOneSessionRatingService::notifyStudentToRate($session->fresh());
        });
    }

    /**
     * تبديل معلم الحصة دون استهلاك رصيد الباقة.
     */
    public static function reassignInstructor(OneToOneSession $session, User $newInstructor): void
    {
        if (! $newInstructor->isInstructor() && ! $newInstructor->isTeacher()) {
            throw new \InvalidArgumentException('المستخدم المحدد ليس معلماً.');
        }

        if (in_array($session->status, [OneToOneSession::STATUS_COMPLETED, OneToOneSession::STATUS_CANCELLED], true)) {
            throw new \InvalidArgumentException('لا يمكن إعادة تعيين حصة مكتملة أو ملغاة.');
        }

        if ((int) $session->instructor_id === (int) $newInstructor->id) {
            throw new \InvalidArgumentException('المعلم الجديد هو نفس معلم الحصة الحالي.');
        }

        $session->loadMissing('classroomMeeting');
        $session->update(['instructor_id' => $newInstructor->id]);
        if ($session->classroomMeeting) {
            $session->classroomMeeting->update(['user_id' => $newInstructor->id]);
        }
    }
}
