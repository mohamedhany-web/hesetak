<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\StudentTutoringSubscription;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\TutoringGroupCohort;
use App\Models\TutoringGroupPackage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TutoringGroupCheckoutService
{
    public static function createPackageOrder(
        User $user,
        TutoringGroup $group,
        TutoringGroupPackage $package,
        string $paymentMethod = 'online',
        ?int $walletId = null
    ): Order {
        if (! $group->isIndividual()) {
            throw new InvalidArgumentException('الباقات متاحة للمجموعات الفردية فقط.');
        }

        if (! $package->is_active || (int) $package->tutoring_group_id !== (int) $group->id) {
            throw new InvalidArgumentException('الباقة غير متاحة.');
        }

        return Order::create([
            'user_id' => $user->id,
            'tutoring_group_id' => $group->id,
            'tutoring_group_package_id' => $package->id,
            'order_type' => Order::TYPE_TUTORING_PACKAGE,
            'original_amount' => $package->original_price ?? $package->price,
            'discount_amount' => max(0, (float) ($package->original_price ?? $package->price) - (float) $package->price),
            'amount' => $package->price,
            'payment_method' => $paymentMethod,
            'wallet_id' => $walletId,
            'status' => Order::STATUS_PENDING,
            'notes' => 'اشتراك باقة tutoring: '.$package->name,
        ]);
    }

    public static function createCohortOrder(
        User $user,
        TutoringGroup $group,
        TutoringGroupCohort $cohort,
        string $paymentMethod = 'online',
        ?int $walletId = null,
        ?Carbon $startsAt = null
    ): Order {
        if (! $group->isCollective()) {
            throw new InvalidArgumentException('الدفعات متاحة للمجموعات الجماعية فقط.');
        }

        if ((int) $cohort->tutoring_group_id !== (int) $group->id) {
            throw new InvalidArgumentException('الدفعة لا تنتمي لهذه المجموعة.');
        }

        if (! TutoringCohortService::isEnrollmentOpen($cohort)) {
            throw new InvalidArgumentException('هذه الدفعة غير متاحة للاشتراك.');
        }

        $amount = (float) ($group->price ?? 0);

        return Order::create([
            'user_id' => $user->id,
            'tutoring_group_id' => $group->id,
            'tutoring_group_cohort_id' => $cohort->id,
            'order_type' => Order::TYPE_TUTORING_COHORT,
            'original_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'wallet_id' => $walletId,
            'status' => Order::STATUS_PENDING,
            'notes' => 'اشتراك دفعة: '.$cohort->title.($startsAt ? ' | starts_at='.$startsAt->toIso8601String() : ''),
        ]);
    }

    /**
     * Fulfill approved tutoring / service-package order: entitlement + optional booking.
     */
    public static function fulfillApprovedOrder(Order $order, ?Carbon $preferredStartsAt = null): ?StudentTutoringSubscription
    {
        $subscription = self::fulfillApprovedOrderCore($order, $preferredStartsAt);

        try {
            app(ReferralService::class)->completePendingForUser((int) $order->user_id, $order->amount);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Referral update failed after tutoring fulfill: '.$e->getMessage(),
                ['order_id' => $order->id]
            );
        }

        return $subscription;
    }

    protected static function fulfillApprovedOrderCore(Order $order, ?Carbon $preferredStartsAt = null): ?StudentTutoringSubscription
    {
        if ($order->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE) {
            StudentEntitlementService::grantFromOrder($order);

            return null;
        }

        // Pure service package without a tutoring group (global / private_lessons)
        if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && ! $order->tutoring_group_id) {
            StudentEntitlementService::grantFromOrder($order->loadMissing('servicePackage'));

            return null;
        }

        if (! $order->isTutoringOrder()) {
            return null;
        }

        return DB::transaction(function () use ($order, $preferredStartsAt) {
            $order->loadMissing(['tutoringGroup', 'tutoringPackage', 'tutoringCohort', 'servicePackage', 'user']);

            // Service package tied to a group — grant then fall through for optional booking
            if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && $order->servicePackage && ! $order->tutoringGroup) {
                StudentEntitlementService::grantFromOrder($order);

                return null;
            }

            $group = $order->tutoringGroup;
            if (! $group && $order->servicePackage?->tutoring_group_id) {
                $group = TutoringGroup::find($order->servicePackage->tutoring_group_id);
            }
            if (! $group && $order->order_type !== Order::TYPE_SERVICE_PACKAGE) {
                return null;
            }

            if (! $group && $order->order_type === Order::TYPE_SERVICE_PACKAGE) {
                StudentEntitlementService::grantFromOrder($order);

                return null;
            }

            $subscription = null;

            if ($order->order_type === Order::TYPE_TUTORING_PACKAGE && $order->tutoringPackage) {
                $package = $order->tutoringPackage;
                $servicePackage = StudentEntitlementService::findOrCreatePackageForTutoringGroupPackage($package);
                $entitlement = StudentEntitlementService::grant(
                    userId: (int) $order->user_id,
                    package: $servicePackage,
                    orderId: (int) $order->id,
                    unitsOverride: (int) $package->sessions_count,
                    notes: 'from_tutoring_group_package:'.$package->id,
                );

                $subscription = StudentTutoringSubscription::create([
                    'user_id' => $order->user_id,
                    'tutoring_group_id' => $group->id,
                    'tutoring_group_package_id' => $package->id,
                    'sessions_total' => (int) $package->sessions_count,
                    'sessions_used' => 0,
                    'starts_at' => $entitlement->starts_at,
                    'expires_at' => $entitlement->expires_at,
                    'status' => StudentTutoringSubscription::STATUS_ACTIVE,
                    'order_id' => $order->id,
                    'student_service_entitlement_id' => $entitlement->id,
                ]);
            }

            if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && $order->servicePackage) {
                $entitlement = StudentEntitlementService::grantFromOrder($order);
                if ($entitlement && $entitlement->tutoring_group_id && $entitlement->scope === ServicePackage::SCOPE_TUTORING_INDIVIDUAL) {
                    $subscription = StudentTutoringSubscription::create([
                        'user_id' => $order->user_id,
                        'tutoring_group_id' => $entitlement->tutoring_group_id,
                        'tutoring_group_package_id' => null,
                        'sessions_total' => $entitlement->units_total,
                        'sessions_used' => 0,
                        'starts_at' => $entitlement->starts_at,
                        'expires_at' => $entitlement->expires_at,
                        'status' => StudentTutoringSubscription::STATUS_ACTIVE,
                        'order_id' => $order->id,
                        'student_service_entitlement_id' => $entitlement->id,
                    ]);
                }
            }

            $startsAt = $preferredStartsAt;
            if (! $startsAt && $order->notes && preg_match('/starts_at=([^\s|]+)/', $order->notes, $m)) {
                try {
                    $startsAt = Carbon::parse($m[1]);
                } catch (\Throwable) {
                    $startsAt = null;
                }
            }

            if ($order->order_type === Order::TYPE_TUTORING_COHORT && $order->tutoringCohort) {
                $cohort = $order->tutoringCohort;
                $startsAt = $startsAt ?: ($cohort->starts_at ?: now()->addDay()->setTime(18, 0));
                $duration = max(30, (int) ($group->duration_minutes ?? 60));

                // Grant collective session credit pool (default 8 or group sessions_per_month)
                $units = max(1, (int) ($group->sessions_per_month ?: 8));
                $collectivePackage = ServicePackage::query()
                    ->where('scope', ServicePackage::SCOPE_TUTORING_COLLECTIVE)
                    ->where('tutoring_group_id', $group->id)
                    ->where('units_count', $units)
                    ->first();
                if (! $collectivePackage) {
                    $collectivePackage = ServicePackage::create([
                        'name' => 'دفعة: '.($cohort->title ?: $group->title),
                        'slug' => ServicePackage::uniqueSlug('cohort-'.$cohort->id),
                        'description' => 'رصيد حصص جماعية من اشتراك دفعة',
                        'scope' => ServicePackage::SCOPE_TUTORING_COLLECTIVE,
                        'tutoring_group_id' => $group->id,
                        'units_count' => $units,
                        'duration_days' => 90,
                        'price' => $order->amount,
                        'currency' => platform_currency(),
                        'is_active' => true,
                        'sort_order' => 0,
                    ]);
                }
                $collectiveEntitlement = StudentEntitlementService::grant(
                    userId: (int) $order->user_id,
                    package: $collectivePackage,
                    orderId: (int) $order->id,
                    notes: 'from_tutoring_cohort:'.$cohort->id,
                );

                $booking = TutoringGroupBooking::create([
                    'tutoring_group_id' => $group->id,
                    'cohort_id' => $cohort->id,
                    'order_id' => $order->id,
                    'student_service_entitlement_id' => $collectiveEntitlement->id,
                    'payment_status' => TutoringGroupBooking::PAYMENT_PAID,
                    'instructor_id' => $group->instructor_id,
                    'user_id' => $order->user_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($duration),
                    'status' => TutoringGroupBooking::STATUS_PENDING,
                ]);

                TutoringCohortService::enroll($cohort);
                if ($order->user) {
                    TutoringClassService::enrollStudent(
                        $cohort,
                        $order->user,
                        orderId: (int) $order->id,
                        entitlementId: (int) $collectiveEntitlement->id,
                        countSeat: false,
                        notes: 'from_order:'.$order->id,
                    );
                }
                if ($cohort->classSessions()->count() === 0
                    && filled($cohort->study_days)
                    && filled($cohort->study_time)
                    && $cohort->starts_at) {
                    try {
                        TutoringClassService::generateSchedule($cohort);
                        TutoringClassService::ensureAllMeetings($cohort->fresh());
                    } catch (\Throwable) {
                        // جدول الحصص يمكن توليده لاحقاً من لوحة الإدارة
                    }
                }
                TutoringCrmHookService::onBookingCreated($booking->fresh(['tutoringGroup', 'user']));
                TutoringGroupOrchestrationService::confirmBooking($booking);
            }

            if ($subscription && $startsAt) {
                $duration = max(30, (int) ($group->duration_minutes ?? 60));
                $booking = TutoringGroupBooking::create([
                    'tutoring_group_id' => $group->id,
                    'tutoring_group_package_id' => $subscription->tutoring_group_package_id,
                    'student_tutoring_subscription_id' => $subscription->id,
                    'student_service_entitlement_id' => $subscription->student_service_entitlement_id,
                    'order_id' => $order->id,
                    'payment_status' => TutoringGroupBooking::PAYMENT_PAID,
                    'instructor_id' => $group->instructor_id,
                    'user_id' => $order->user_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($duration),
                    'status' => TutoringGroupBooking::STATUS_PENDING,
                ]);
                TutoringCrmHookService::onBookingCreated($booking->fresh(['tutoringGroup', 'user']));
                TutoringGroupOrchestrationService::confirmBooking($booking);
            }

            return $subscription;
        });
    }

    /**
     * Book a session against an active individual subscription (no new payment).
     */
    public static function bookFromSubscription(
        StudentTutoringSubscription $subscription,
        Carbon $startsAt
    ): TutoringGroupBooking {
        if (! $subscription->hasSessionsLeft()) {
            throw new InvalidArgumentException('لا توجد حصص متبقية في باقتك.');
        }

        $group = $subscription->tutoringGroup;
        if (! $group || ! $group->isIndividual()) {
            throw new InvalidArgumentException('الاشتراك غير صالح.');
        }

        $entitlement = $subscription->entitlement;
        if (! $entitlement) {
            $entitlement = StudentEntitlementService::availableFor(
                (int) $subscription->user_id,
                ServicePackage::SCOPE_TUTORING_INDIVIDUAL,
                (int) $group->id
            );
        }
        if (! $entitlement || StudentEntitlementService::bookableUnitsLeft($entitlement) < 1) {
            throw new InvalidArgumentException('لا يوجد رصيد حصص متاح. اشترِ باقة أو اشحن رصيدك.');
        }

        $duration = max(30, (int) ($group->duration_minutes ?? 60));
        $endsAt = $startsAt->copy()->addMinutes($duration);

        $allowed = TutoringGroupAvailabilityService::availableSlots(
            $group,
            $startsAt->copy()->startOfDay(),
            $startsAt->copy()->endOfDay()
        )->first(fn ($s) => Carbon::parse($s['starts_at'])->equalTo($startsAt));

        if (! $allowed) {
            throw new InvalidArgumentException('هذا الموعد غير متاح.');
        }

        $booking = TutoringGroupBooking::create([
            'tutoring_group_id' => $group->id,
            'tutoring_group_package_id' => $subscription->tutoring_group_package_id,
            'student_tutoring_subscription_id' => $subscription->id,
            'student_service_entitlement_id' => $entitlement->id,
            'payment_status' => TutoringGroupBooking::PAYMENT_PAID,
            'instructor_id' => $group->instructor_id,
            'user_id' => $subscription->user_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => TutoringGroupBooking::STATUS_PENDING,
        ]);

        TutoringCrmHookService::onBookingCreated($booking->fresh(['tutoringGroup', 'user']));

        return TutoringGroupOrchestrationService::confirmBooking($booking);
    }

    /**
     * Book using any available entitlement for a tutoring group (individual or collective).
     */
    public static function bookFromEntitlement(
        User $user,
        TutoringGroup $group,
        Carbon $startsAt
    ): TutoringGroupBooking {
        $scope = StudentEntitlementService::scopeForTutoringGroup($group);
        $entitlement = StudentEntitlementService::assertCanBook((int) $user->id, $scope, (int) $group->id);

        $duration = max(30, (int) ($group->duration_minutes ?? 60));
        $endsAt = $startsAt->copy()->addMinutes($duration);

        $allowed = TutoringGroupAvailabilityService::availableSlots(
            $group,
            $startsAt->copy()->startOfDay(),
            $startsAt->copy()->endOfDay()
        )->first(fn ($s) => Carbon::parse($s['starts_at'])->equalTo($startsAt));

        if (! $allowed) {
            throw new InvalidArgumentException('هذا الموعد غير متاح.');
        }

        $booking = TutoringGroupBooking::create([
            'tutoring_group_id' => $group->id,
            'student_service_entitlement_id' => $entitlement->id,
            'payment_status' => TutoringGroupBooking::PAYMENT_PAID,
            'instructor_id' => $group->instructor_id,
            'user_id' => $user->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => TutoringGroupBooking::STATUS_PENDING,
        ]);

        TutoringCrmHookService::onBookingCreated($booking->fresh(['tutoringGroup', 'user']));

        return TutoringGroupOrchestrationService::confirmBooking($booking);
    }
}
