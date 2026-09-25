<?php

namespace App\Services;

use App\Models\FreeTrialBooking;
use App\Models\OneToOneSession;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class FreeTrialBookingService
{
    public const DURATION_MINUTES = 30;

    /**
     * مواعيد المعلم الفاضية من جدوله الخاص (ليس نوافذ إدارة عامة).
     *
     * @return Collection<int, array{
     *     starts_at: Carbon,
     *     ends_at: Carbon,
     *     date: string,
     *     time: string,
     *     time_academy: string,
     *     quality: string,
     *     quality_label: string,
     *     label: string,
     *     duration: int,
     *     viewer_timezone: string,
     *     academy_timezone: string
     * }>
     */
    public static function availableSlots(
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?string $viewerTimezone = null,
        ?int $instructorId = null,
    ): Collection {
        if (! $instructorId) {
            return collect();
        }

        $from = ($from ?? now())->copy();
        $to = ($to ?? now()->addDays(14))->copy()->endOfDay();
        $duration = OneToOneSession::defaultDurationMinutes();
        $clockTz = AppTimezone::forInstructorId($instructorId);
        $viewerTz = AppTimezone::normalize($viewerTimezone) ?? AppTimezone::forUser(auth()->user());
        $locale = app()->getLocale();

        return OneToOneAvailabilityService::availableSlots($instructorId, $from, $to, $duration)
            ->map(function (array $slot) use ($viewerTz, $clockTz, $locale, $duration) {
                /** @var Carbon $starts */
                $starts = $slot['starts_at']->copy()->utc();
                $ends = ($slot['ends_at'] ?? $starts->copy()->addMinutes($duration))->copy()->utc();
                $viewerLocal = $starts->copy()->timezone($viewerTz);
                $academyLocal = $starts->copy()->timezone($clockTz);
                $quality = AppTimezone::slotQuality($starts, $viewerTz);
                $qualityLabel = AppTimezone::qualityLabels($quality)[$locale === 'ar' ? 'ar' : 'en'];

                return [
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                    'date' => $viewerLocal->toDateString(),
                    'time' => $viewerLocal->format('H:i'),
                    'time_academy' => $academyLocal->format('H:i'),
                    'quality' => $quality,
                    'quality_label' => $qualityLabel,
                    'label' => $viewerLocal->locale($locale)->translatedFormat('D d M — H:i'),
                    'duration' => $duration,
                    'viewer_timezone' => $viewerTz,
                    'academy_timezone' => $clockTz,
                ];
            })
            ->values();
    }

    /**
     * طلب حصة (تجريبية/استشارة/…) بدون تثبيت جدول — الطرفان ينسّقان لاحقاً.
     *
     * @param  array{name:string,email?:string,phone?:string,country_code?:string,goal?:string,starts_at?:string,notes?:string,timezone?:string,us_state?:string,instructor_id?:int}  $data
     */
    public static function book(array $data, ?int $userId = null): FreeTrialBooking
    {
        $viewerTz = AppTimezone::normalize($data['timezone'] ?? null)
            ?? AppTimezone::timezoneForUsState($data['us_state'] ?? null)
            ?? AppTimezone::academy();

        $duration = self::DURATION_MINUTES;
        $starts = null;
        if (! empty($data['starts_at'])) {
            $starts = AppTimezone::parseAppointmentInput((string) $data['starts_at'], $viewerTz);
            if (! $starts) {
                throw new InvalidArgumentException('موعد غير صالح.');
            }
            $starts = $starts->utc();
            if ($starts->lte(now())) {
                throw new InvalidArgumentException('الموعد المقترح يجب أن يكون في المستقبل.');
            }
        } else {
            // موعد مبدئي للطلب حتى يُعاد ضبطه عند التأكيد
            $starts = now()->addDay()->startOfHour()->utc();
        }
        $ends = $starts->copy()->addMinutes($duration);

        [$countryCode, $fullPhone] = self::normalizePhone(
            $data['phone'] ?? null,
            $data['country_code'] ?? null
        );

        if (empty($data['email']) && empty($fullPhone)) {
            throw new InvalidArgumentException('أدخل البريد أو رقم الواتساب للمتابعة.');
        }

        $goal = isset($data['goal']) ? trim((string) $data['goal']) : null;
        if ($goal === '') {
            $goal = null;
        }
        if ($goal !== null && ! array_key_exists($goal, FreeTrialBooking::goalOptions())) {
            throw new InvalidArgumentException('الغرض من التعلم غير صالح.');
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $fullPhone,
            'country_code' => $countryCode,
            'goal' => $goal,
            'user_id' => $userId,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'duration_minutes' => $duration,
            'status' => FreeTrialBooking::STATUS_PENDING,
            'notes' => trim((string) (($data['notes'] ?? '')."\nطلب تنسيق موعد — بانتظار تأكيد المعلم/الإدارة")),
        ];

        if (Schema::hasColumn('free_trial_bookings', 'timezone')) {
            $payload['timezone'] = $viewerTz;
        }
        if (Schema::hasColumn('free_trial_bookings', 'us_state') && ! empty($data['us_state'])) {
            $payload['us_state'] = trim((string) $data['us_state']);
        }

        $instructorId = isset($data['instructor_id']) ? (int) $data['instructor_id'] : 0;
        if ($instructorId > 0 && Schema::hasColumn('free_trial_bookings', 'instructor_id')) {
            $instructorOk = User::query()
                ->where('id', $instructorId)
                ->where('is_active', true)
                ->whereIn('role', ['instructor', 'teacher'])
                ->whereHas('instructorProfile', fn ($q) => $q->approved())
                ->exists();
            if ($instructorOk) {
                $payload['instructor_id'] = $instructorId;
            }
        }

        $booking = FreeTrialBooking::create($payload);

        if (! empty($booking->instructor_id) && class_exists(\App\Models\Notification::class)) {
            try {
                \App\Models\Notification::create([
                    'user_id' => $booking->instructor_id,
                    'type' => 'general',
                    'title' => 'طلب حصة مجانية / تجريبية',
                    'message' => 'طلب '.$booking->name.' تنسيق موعد'.(
                        $booking->starts_at
                            ? (' (مقترح: '.$booking->starts_at->timezone(AppTimezone::academy())->format('Y-m-d H:i').')')
                            : ''
                    ),
                    'action_url' => route('instructor.free-trial-bookings.show', $booking),
                    'action_text' => 'عرض الطلب',
                    'priority' => 'high',
                    'target_type' => 'individual',
                    'target_id' => $booking->id,
                    'audience' => 'instructor',
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                // لا نُفشل الحجز بسبب إشعار
            }
        }

        return $booking;
    }

    /**
     * حجز حصة مجانية لحامل باقة مع معلم — تُظهر في الإدارة والجداول دون خصم رصيد.
     *
     * @param  array{
     *     starts_at: Carbon|string,
     *     timezone?: string,
     *     notes?: string,
     *     duration_minutes?: int,
     *     require_availability?: bool
     * }  $data
     */
    public static function bookComplimentaryFreeSession(
        User $student,
        User $instructor,
        array $data,
        ?User $bookedBy = null,
        bool $requirePackage = true
    ): FreeTrialBooking {
        if (! $student->isStudent()) {
            throw new InvalidArgumentException('الحجز متاح للطلاب فقط.');
        }
        if ($requirePackage && ! StudentEntitlementService::hasActivePrivatePackage((int) $student->id)) {
            throw new InvalidArgumentException('الحصة المجانية متاحة فقط للطلاب المشتركين في باقة.');
        }

        $viewerTz = AppTimezone::normalize($data['timezone'] ?? null)
            ?? AppTimezone::forUser($student);

        $starts = $data['starts_at'] instanceof Carbon
            ? $data['starts_at']->copy()->utc()
            : AppTimezone::parseAppointmentInput((string) $data['starts_at'], $viewerTz);

        if (! $starts) {
            throw new InvalidArgumentException('موعد غير صالح.');
        }
        $starts = $starts->utc()->startOfMinute();

        $duration = isset($data['duration_minutes'])
            ? max(15, min(180, (int) $data['duration_minutes']))
            : OneToOneSession::defaultDurationMinutes();
        $requireAvailability = array_key_exists('require_availability', $data)
            ? (bool) $data['require_availability']
            : true;

        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;

        return DB::transaction(function () use (
            $student,
            $instructor,
            $starts,
            $duration,
            $requireAvailability,
            $notes,
            $bookedBy,
            $viewerTz
        ) {
            $session = OneToOneSessionService::bookComplimentaryWithInstructor(
                $student,
                $instructor,
                $starts,
                $bookedBy,
                $notes ?: 'حصة مجانية — لا تُخصم من رصيد الباقة',
                $duration,
                $requireAvailability
            );

            $payload = [
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
                'goal' => FreeTrialBooking::GOAL_FREE_SESSION,
                'user_id' => $student->id,
                'instructor_id' => $instructor->id,
                'starts_at' => $session->scheduled_at ?? $starts,
                'ends_at' => ($session->scheduled_at ?? $starts)->copy()->addMinutes($duration),
                'duration_minutes' => $duration,
                'status' => FreeTrialBooking::STATUS_CONFIRMED,
                'notes' => $notes,
            ];

            if (Schema::hasColumn('free_trial_bookings', 'timezone')) {
                $payload['timezone'] = $viewerTz;
            }
            if (Schema::hasColumn('free_trial_bookings', 'one_to_one_session_id')) {
                $payload['one_to_one_session_id'] = $session->id;
            }

            $booking = FreeTrialBooking::create($payload);

            if (class_exists(\App\Models\Notification::class)) {
                try {
                    \App\Models\Notification::create([
                        'user_id' => $instructor->id,
                        'sender_id' => $bookedBy?->id,
                        'type' => 'general',
                        'title' => 'حصة مجانية مجدولة',
                        'message' => 'الطالب '.$student->name.' — '.optional($booking->starts_at)->timezone(AppTimezone::academy())->format('Y-m-d H:i'),
                        'action_url' => route('instructor.one-to-one-sessions.show', $session),
                        'action_text' => 'عرض الحصة',
                        'priority' => 'high',
                        'audience' => 'instructor',
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {
                    // لا نُفشل الحجز بسبب إشعار
                }
            }

            return $booking->fresh(['user', 'instructor', 'oneToOneSession']);
        });
    }

    /**
     * توصيف يدوي من الإدارة: طالب + معلم + موعد → جداول الطرفين + سجل الحجوزات.
     */
    public static function assignManual(
        User $student,
        User $instructor,
        Carbon|string $startsAt,
        ?User $admin = null,
        ?string $notes = null,
        ?string $timezone = null,
        ?int $durationMinutes = null,
        bool $requireAvailability = false
    ): FreeTrialBooking {
        return self::bookComplimentaryFreeSession(
            $student,
            $instructor,
            [
                'starts_at' => $startsAt,
                'timezone' => $timezone,
                'notes' => $notes ?: 'تسكين يدوي لحصة مجانية من الإدارة',
                'duration_minutes' => $durationMinutes,
                'require_availability' => $requireAvailability,
            ],
            $admin,
            requirePackage: false
        );
    }

    /**
     * قبول طلب تجربة/حصة مجانية من المعلم — يثبت الموعد وينشئ حصة 1:1 عند وجود حساب طالب.
     *
     * @param  array{starts_at?:Carbon|string,timezone?:string,notes?:string,duration_minutes?:int}  $data
     */
    public static function confirmByInstructor(FreeTrialBooking $booking, User $instructor, array $data = []): FreeTrialBooking
    {
        if ((int) $booking->instructor_id !== (int) $instructor->id) {
            throw new InvalidArgumentException('هذا الطلب غير مخصص لك.');
        }
        if ($booking->status !== FreeTrialBooking::STATUS_PENDING) {
            throw new InvalidArgumentException('لا يمكن قبول طلب غير معلّق.');
        }

        $viewerTz = AppTimezone::normalize($data['timezone'] ?? null)
            ?? AppTimezone::normalize($booking->timezone ?? null)
            ?? AppTimezone::forInstructorId((int) $instructor->id);

        $startsInput = $data['starts_at'] ?? $booking->starts_at;
        $starts = $startsInput instanceof Carbon
            ? $startsInput->copy()->utc()
            : AppTimezone::parseAppointmentInput((string) $startsInput, $viewerTz);

        if (! $starts || $starts->lte(now())) {
            throw new InvalidArgumentException('اختر موعداً صالحاً في المستقبل لتأكيد الطلب.');
        }
        $starts = $starts->utc()->startOfMinute();

        $duration = isset($data['duration_minutes'])
            ? max(15, min(180, (int) $data['duration_minutes']))
            : max(15, (int) ($booking->duration_minutes ?: self::DURATION_MINUTES));

        $notes = isset($data['notes']) ? trim((string) $data['notes']) : null;
        if ($notes === '') {
            $notes = null;
        }

        return DB::transaction(function () use ($booking, $instructor, $starts, $duration, $viewerTz, $notes) {
            $booking = FreeTrialBooking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status !== FreeTrialBooking::STATUS_PENDING) {
                throw new InvalidArgumentException('لا يمكن قبول طلب غير معلّق.');
            }

            $student = null;
            if ($booking->user_id) {
                $student = User::query()->find($booking->user_id);
            }
            if (! $student && filled($booking->email)) {
                $student = User::query()->where('email', $booking->email)->first();
            }

            $session = null;
            if ($student && $student->isStudent()) {
                $session = OneToOneSessionService::bookComplimentaryWithInstructor(
                    $student,
                    $instructor,
                    $starts,
                    $instructor,
                    $notes ?: 'حصة مجانية — قبول المعلم لطلب #'.$booking->id,
                    $duration,
                    true
                );
            } elseif (! OneToOneAvailabilityService::isSlotAvailable((int) $instructor->id, $starts, $duration)) {
                throw new InvalidArgumentException('الموعد غير متاح في جدولك. اختر وقتاً آخر أو حدّث التوافر.');
            }

            $payload = [
                'status' => FreeTrialBooking::STATUS_CONFIRMED,
                'starts_at' => $session?->scheduled_at ?? $starts,
                'ends_at' => ($session?->scheduled_at ?? $starts)->copy()->addMinutes($duration),
                'duration_minutes' => $duration,
                'notes' => trim(implode("\n", array_filter([
                    (string) $booking->notes,
                    $notes,
                    'تم القبول بواسطة المعلم '.$instructor->name,
                ]))),
            ];
            if (Schema::hasColumn('free_trial_bookings', 'timezone')) {
                $payload['timezone'] = $viewerTz;
            }
            if ($session && Schema::hasColumn('free_trial_bookings', 'one_to_one_session_id')) {
                $payload['one_to_one_session_id'] = $session->id;
            }
            if ($student && ! $booking->user_id) {
                $payload['user_id'] = $student->id;
            }

            $booking->update($payload);

            if ($student && class_exists(\App\Models\Notification::class)) {
                try {
                    \App\Models\Notification::create([
                        'user_id' => $student->id,
                        'sender_id' => $instructor->id,
                        'type' => 'reminder',
                        'title' => 'تم قبول طلب الحصة المجانية',
                        'message' => 'المعلم '.$instructor->name.' أكّد الموعد: '.$starts->timezone($viewerTz)->format('Y-m-d H:i'),
                        'action_url' => $session
                            ? route('student.one-to-one-sessions.show', $session)
                            : route('dashboard'),
                        'action_text' => 'عرض الحصة',
                        'priority' => 'high',
                        'audience' => 'student',
                        'is_read' => false,
                    ]);
                } catch (\Throwable) {
                }
            }

            return $booking->fresh(['user', 'instructor', 'oneToOneSession']);
        });
    }

    /**
     * رفض طلب تجربة من المعلم.
     */
    public static function rejectByInstructor(FreeTrialBooking $booking, User $instructor, ?string $reason = null): FreeTrialBooking
    {
        if ((int) $booking->instructor_id !== (int) $instructor->id) {
            throw new InvalidArgumentException('هذا الطلب غير مخصص لك.');
        }
        if ($booking->status !== FreeTrialBooking::STATUS_PENDING) {
            throw new InvalidArgumentException('لا يمكن رفض طلب غير معلّق.');
        }

        $reason = $reason !== null ? trim($reason) : null;

        return DB::transaction(function () use ($booking, $instructor, $reason) {
            $booking = FreeTrialBooking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status !== FreeTrialBooking::STATUS_PENDING) {
                throw new InvalidArgumentException('لا يمكن رفض طلب غير معلّق.');
            }

            $booking->update([
                'status' => FreeTrialBooking::STATUS_CANCELLED,
                'notes' => trim(implode("\n", array_filter([
                    (string) $booking->notes,
                    'رفض المعلم '.$instructor->name.($reason ? ': '.$reason : ''),
                ]))),
            ]);

            self::syncLinkedSessionStatus($booking, FreeTrialBooking::STATUS_CANCELLED);

            if ($booking->user_id && class_exists(\App\Models\Notification::class)) {
                try {
                    \App\Models\Notification::create([
                        'user_id' => $booking->user_id,
                        'sender_id' => $instructor->id,
                        'type' => 'general',
                        'title' => 'تم رفض طلب الحصة المجانية',
                        'message' => $reason ?: 'المعلم لم يتمكن من قبول الموعد المقترح. يمكنك طلب موعد آخر.',
                        'action_url' => route('public.instructors.show', $instructor),
                        'action_text' => 'اختيار موعد',
                        'priority' => 'normal',
                        'audience' => 'student',
                        'is_read' => false,
                    ]);
                } catch (\Throwable) {
                }
            }

            return $booking->fresh(['user', 'instructor']);
        });
    }

    /**
     * مزامنة حالة الحجز مع الحصة المرتبطة (إلغاء / إكمال).
     */
    public static function syncLinkedSessionStatus(FreeTrialBooking $booking, string $status): void
    {
        if (! $booking->one_to_one_session_id) {
            return;
        }

        $session = OneToOneSession::query()->find($booking->one_to_one_session_id);
        if (! $session) {
            return;
        }

        try {
            if ($status === FreeTrialBooking::STATUS_CANCELLED
                && in_array($session->status, [OneToOneSession::STATUS_PENDING, OneToOneSession::STATUS_SCHEDULED], true)
            ) {
                OneToOneSessionService::cancelSession($session, false, 'إلغاء حجز الحصة المجانية');
            }

            if ($status === FreeTrialBooking::STATUS_COMPLETED
                && $session->status === OneToOneSession::STATUS_SCHEDULED
            ) {
                OneToOneSessionService::markCompleted($session, false);
            }
        } catch (\Throwable $e) {
            // لا نكسر تحديث الحالة الإدارية إن فشلت مزامنة الحصة
            report($e);
        }
    }

    /**
     * @return array{0:?string,1:?string} [country_code, full_phone]
     */
    private static function normalizePhone(?string $phone, ?string $countryCode): array
    {
        $rawPhone = trim((string) $phone);
        $dial = trim((string) $countryCode);

        if ($rawPhone === '') {
            return [null, null];
        }

        $countries = config('phone_countries.countries', []);
        $country = collect($countries)->firstWhere('dial_code', $dial);

        if ($dial === '' || ! $country) {
            throw new InvalidArgumentException('اختر كود الدولة لرقم الواتساب.');
        }

        $national = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $national = ltrim($national, '0');
        $regex = $country['validation']['regex'] ?? '/^\d{6,15}$/';

        if ($national === '' || ! preg_match($regex, $national)) {
            $example = $country['example'] ?? $country['placeholder'] ?? '';
            throw new InvalidArgumentException(
                $example !== ''
                    ? ('رقم الواتساب غير صحيح لهذه الدولة. مثال: '.$example)
                    : 'رقم الواتساب غير صحيح لهذه الدولة.'
            );
        }

        return [$dial, $dial.$national];
    }
}
