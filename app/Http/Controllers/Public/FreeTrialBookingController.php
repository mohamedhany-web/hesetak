<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FreeTrialBooking;
use App\Models\User;
use App\Services\FreeTrialBookingService;
use App\Services\OneToOneAvailabilityService;
use App\Services\StudentEntitlementService;
use App\Support\AppTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreeTrialBookingController extends Controller
{
    public function slots(Request $request): JsonResponse
    {
        $instructorId = (int) $request->input('instructor_id', 0);
        if ($instructorId < 1) {
            return response()->json([
                'message' => 'اختر معلماً لعرض مواعيده الفاضية.',
                'duration_minutes' => \App\Models\OneToOneSession::defaultDurationMinutes(),
                'dates' => [],
                'slots_by_date' => [],
                'total' => 0,
                'mode' => 'request',
            ], 422);
        }

        $days = min(21, max(7, (int) $request->input('days', 14)));
        $viewerTz = AppTimezone::normalize($request->input('timezone'))
            ?? AppTimezone::timezoneForUsState($request->input('us_state'))
            ?? AppTimezone::academy();

        $from = now();
        $to = now()->addDays($days)->endOfDay();

        $slots = FreeTrialBookingService::availableSlots($from, $to, $viewerTz, $instructorId);

        $byDate = $slots->groupBy('date')->map(function ($group) {
            return $group->values()->map(fn (array $slot) => [
                'starts_at' => $slot['starts_at']->toIso8601String(),
                'date' => $slot['date'],
                'time' => $slot['time'],
                'time_academy' => $slot['time_academy'],
                'quality' => $slot['quality'],
                'quality_label' => $slot['quality_label'],
                'label' => $slot['label'],
                'duration' => $slot['duration'],
            ])->all();
        })->all();

        return response()->json([
            'duration_minutes' => \App\Models\OneToOneSession::defaultDurationMinutes(),
            'viewer_timezone' => $viewerTz,
            'academy_timezone' => AppTimezone::academy(),
            'instructor_id' => $instructorId,
            'dates' => array_keys($byDate),
            'slots_by_date' => $byDate,
            'total' => $slots->count(),
            'mode' => $slots->isEmpty() ? 'request' : 'slots',
            'hint' => $slots->isEmpty()
                ? 'المعلم لم ينشر مواعيد فاضية بعد — أرسل طلباً واتفقا على الموعد معاً.'
                : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $goalKeys = array_keys(FreeTrialBooking::goalOptions());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'country_code' => ['nullable', 'string', 'max:12'],
            'goal' => ['required', 'string', 'in:'.implode(',', $goalKeys)],
            'starts_at' => ['nullable', 'string', 'max:64'],
            'timezone' => AppTimezone::inputRules(false),
            'us_state' => ['nullable', 'string', 'max:64'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'as_request' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $goal = (string) $data['goal'];
        $isFreeSession = $goal === FreeTrialBooking::GOAL_FREE_SESSION;
        $asRequest = $request->boolean('as_request') || empty($data['starts_at']);

        try {
            if ($isFreeSession && ! $asRequest) {
                if (! $user || ! $user->isStudent()) {
                    return response()->json([
                        'message' => 'سجّل دخولك كطالب لحجز حصة مجانية مع معلم.',
                    ], 401);
                }
                if (! StudentEntitlementService::hasActivePrivatePackage((int) $user->id)) {
                    return response()->json([
                        'message' => 'الحصة المجانية متاحة فقط لمن لديهم باقة نشطة على المنصة.',
                    ], 422);
                }
                if (empty($data['instructor_id'])) {
                    return response()->json([
                        'message' => 'اختر معلماً لحجز الحصة المجانية.',
                    ], 422);
                }

                $instructor = User::query()->findOrFail((int) $data['instructor_id']);
                $duration = \App\Models\OneToOneSession::defaultDurationMinutes();
                $viewerTz = AppTimezone::normalize($data['timezone'] ?? null) ?? AppTimezone::forUser($user);
                $starts = AppTimezone::parseAppointmentInput((string) $data['starts_at'], $viewerTz);

                if (! $starts || ! OneToOneAvailabilityService::isSlotAvailable(
                    (int) $instructor->id,
                    $starts->copy()->utc(),
                    $duration
                )) {
                    // الموعد مش من جدول المعلم → حوّل لطلب تنسيق
                    $asRequest = true;
                } else {
                    $booking = FreeTrialBookingService::bookComplimentaryFreeSession(
                        $user,
                        $instructor,
                        [
                            'starts_at' => $starts,
                            'timezone' => $data['timezone'] ?? null,
                            'notes' => null,
                            'require_availability' => true,
                        ],
                        $user,
                        requirePackage: true
                    );
                }
            }

            if (! isset($booking)) {
                if ($isFreeSession) {
                    if (! $user || ! $user->isStudent()) {
                        return response()->json([
                            'message' => 'سجّل دخولك كطالب لإرسال طلب حصة مجانية.',
                        ], 401);
                    }
                    if (! StudentEntitlementService::hasActivePrivatePackage((int) $user->id)) {
                        return response()->json([
                            'message' => 'الحصة المجانية متاحة فقط لمن لديهم باقة نشطة على المنصة.',
                        ], 422);
                    }
                    $data['name'] = $user->name;
                    $data['email'] = $data['email'] ?? $user->email;
                }

                $booking = FreeTrialBookingService::book($data, $user?->id);
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $viewerTz = $booking->timezone
            ?: AppTimezone::normalize($data['timezone'] ?? null)
            ?: AppTimezone::academy();
        $dual = AppTimezone::dualLabel($booking->starts_at, $viewerTz, app()->getLocale(), 'l d F Y — g:i A');

        $isPendingRequest = $booking->status === FreeTrialBooking::STATUS_PENDING;

        return response()->json([
            'message' => $isPendingRequest
                ? 'تم إرسال طلبك. تواصل مع المعلم أو انتظر تأكيد الموعد من الإدارة.'
                : ($booking->goal === FreeTrialBooking::GOAL_FREE_SESSION
                    ? 'تم حجز حصتك المجانية بنجاح — ظهرت في جدولك وجدول المعلم دون خصم من رصيد الباقة.'
                    : 'تم تأكيد الحجز.'),
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'starts_at' => $booking->starts_at->toIso8601String(),
                'label' => $dual['primary'],
                'label_secondary' => $dual['secondary'],
                'timezone' => $viewerTz,
                'duration_minutes' => $booking->duration_minutes,
                'goal' => $booking->goal,
                'goal_label' => $booking->goalLabel(),
                'instructor_id' => $booking->instructor_id,
                'one_to_one_session_id' => $booking->one_to_one_session_id,
                'is_request' => $isPendingRequest,
            ],
        ], 201);
    }
}
