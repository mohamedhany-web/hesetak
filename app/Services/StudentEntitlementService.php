<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\ServicePackagePricingRule;
use App\Models\StudentServiceEntitlement;
use App\Models\StudentTutoringSubscription;
use App\Models\TutoringGroup;
use App\Models\TutoringGroupBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentEntitlementService
{
    public static function createOrder(User $user, ServicePackage $package, string $paymentMethod = 'online', ?int $walletId = null): Order
    {
        if (! $package->is_active) {
            throw new InvalidArgumentException('الباقة غير متاحة حالياً.');
        }

        $allowed = ['bank_transfer', 'cash', 'other', 'online', 'wallet'];
        if (! in_array($paymentMethod, $allowed, true)) {
            $paymentMethod = 'bank_transfer';
        }

        $currency = strtoupper((string) ($package->currencyCode() ?: config('currency.code', 'SAR')));
        if (! in_array($currency, ['SAR', 'EGP', 'USD'], true)) {
            $currency = strtoupper((string) config('currency.code', 'SAR')) ?: 'SAR';
        }

        return Order::create([
            'user_id' => $user->id,
            'service_package_id' => $package->id,
            'tutoring_group_id' => $package->tutoring_group_id,
            'order_type' => Order::TYPE_SERVICE_PACKAGE,
            'original_amount' => $package->original_price ?? $package->price,
            'discount_amount' => max(0, (float) ($package->original_price ?? $package->price) - (float) $package->price),
            'amount' => $package->price,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'wallet_id' => $walletId,
            'status' => Order::STATUS_PENDING,
            'notes' => $package->isCommercialPlan()
                ? $package->planLabel().' — '.$package->termLabel().' ('.$package->weeklySessionsTotal().' حصص/أسبوع)'
                : 'باقة خدمات: '.$package->name.' ('.$package->units_count.' حصة)',
        ]);
    }

    public static function createCustomOrder(
        User $user,
        ServicePackagePricingRule $rule,
        int $sessions,
        string $paymentMethod = 'online',
        ?int $walletId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
        ?array $quoteOverride = null,
    ): Order {
        $allowed = ['bank_transfer', 'cash', 'other', 'online', 'wallet'];
        if (! in_array($paymentMethod, $allowed, true)) {
            $paymentMethod = 'bank_transfer';
        }

        $quote = $quoteOverride ?: CustomServicePackagePricingService::calculate($rule, $sessions);
        if ($academicYearId) {
            $quote['academic_year_id'] = $academicYearId;
        }
        if ($academicSubjectId) {
            $quote['academic_subject_id'] = $academicSubjectId;
        }

        $termNote = ! empty($quote['term_months'])
            ? ' · '.$quote['term_months'].' شهر · '.((int) ($quote['weekly_sessions'] ?? 0)).' حصص/أسبوع'
            : '';

        $currency = strtoupper((string) ($quote['currency'] ?? config('currency.code', 'SAR')));
        if (! in_array($currency, ['SAR', 'EGP', 'USD'], true)) {
            $currency = strtoupper((string) config('currency.code', 'SAR')) ?: 'SAR';
        }

        return Order::create([
            'user_id' => $user->id,
            'service_package_id' => null,
            'tutoring_group_id' => null,
            'custom_package_data' => $quote,
            'order_type' => Order::TYPE_CUSTOM_SERVICE_PACKAGE,
            'original_amount' => $quote['original_amount'],
            'discount_amount' => $quote['discount_amount'],
            'amount' => $quote['amount'],
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'wallet_id' => $walletId,
            'status' => Order::STATUS_PENDING,
            'notes' => ($quote['name'] ?? 'باقة مخصصة').' ('.$quote['sessions'].' حصة × '.$quote['session_minutes'].' دقيقة)'.$termNote.' — '.$currency,
        ]);
    }

    public static function createPrivateWeeklyOrder(
        User $user,
        ServicePackagePricingRule $rule,
        int $termMonths,
        int $weeklySessions,
        string $paymentMethod = 'online',
        ?int $walletId = null,
    ): Order {
        $quote = CustomServicePackagePricingService::calculatePrivateWeekly($rule, $termMonths, $weeklySessions);

        return self::createCustomOrder(
            user: $user,
            rule: $rule,
            sessions: (int) $quote['sessions'],
            paymentMethod: $paymentMethod,
            walletId: $walletId,
            quoteOverride: $quote,
        );
    }

    public static function grantFromOrder(Order $order): ?StudentServiceEntitlement
    {
        if ($order->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE && $order->custom_package_data) {
            $existing = StudentServiceEntitlement::query()->where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            $data = $order->custom_package_data;
            $starts = now();
            $durationDays = max(1, (int) ($data['duration_days'] ?? 90));
            $weeklyPrivate = (int) ($data['weekly_private_sessions'] ?? $data['weekly_sessions'] ?? 0);
            $weeklyGroup = (int) ($data['weekly_group_sessions'] ?? 0);
            $planType = $data['plan_type'] ?? (
                in_array(($data['scope'] ?? ''), [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_TUTORING_INDIVIDUAL], true)
                    ? ServicePackage::PLAN_PRIVATE
                    : null
            );

            return StudentServiceEntitlement::create([
                'user_id' => $order->user_id,
                'service_package_id' => null,
                'order_id' => $order->id,
                'scope' => $data['scope'] ?? ServicePackage::SCOPE_PRIVATE_LESSONS,
                'plan_type' => $planType,
                'term_months' => isset($data['term_months']) ? (int) $data['term_months'] : null,
                'weekly_group_sessions' => $weeklyGroup,
                'weekly_private_sessions' => $weeklyPrivate,
                'includes_community' => false,
                'includes_libraries' => true,
                'tutoring_group_id' => null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'academic_subject_id' => $data['academic_subject_id'] ?? null,
                'units_total' => max(1, (int) ($data['sessions'] ?? 1)),
                'units_used' => 0,
                'starts_at' => $starts,
                'expires_at' => $starts->copy()->addDays($durationDays),
                'status' => StudentServiceEntitlement::STATUS_ACTIVE,
                'notes' => 'custom_package:'.json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        }

        if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && $order->service_package_id) {
            $existing = StudentServiceEntitlement::query()->where('order_id', $order->id)->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }

            $package = $order->servicePackage ?: ServicePackage::find($order->service_package_id);
            if (! $package) {
                return null;
            }

            if ($package->isPremier()) {
                return self::grantPremierPlan((int) $order->user_id, $package, (int) $order->id);
            }

            return self::grant(
                userId: (int) $order->user_id,
                package: $package,
                orderId: (int) $order->id,
            );
        }

        return null;
    }

    /**
     * Premier grants two ledgers: collective (school classes) + private lessons.
     */
    public static function grantPremierPlan(
        int $userId,
        ServicePackage $package,
        ?int $orderId = null,
    ): StudentServiceEntitlement {
        if ($orderId) {
            $existing = StudentServiceEntitlement::query()->where('order_id', $orderId)->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }
        }

        $months = max(1, (int) ($package->term_months ?: 1));
        $groupUnits = max(1, (int) $package->weekly_group_sessions * 4 * $months);
        $privateUnits = max(1, (int) $package->weekly_private_sessions * 4 * $months);

        $groupEntitlement = self::createEntitlementFromPackage(
            userId: $userId,
            package: $package,
            orderId: $orderId,
            scope: ServicePackage::SCOPE_TUTORING_COLLECTIVE,
            units: $groupUnits,
            weeklyGroup: (int) $package->weekly_group_sessions,
            weeklyPrivate: 0,
            notes: 'premier:school',
        );

        self::createEntitlementFromPackage(
            userId: $userId,
            package: $package,
            orderId: $orderId,
            scope: ServicePackage::SCOPE_PRIVATE_LESSONS,
            units: $privateUnits,
            weeklyGroup: 0,
            weeklyPrivate: (int) $package->weekly_private_sessions,
            notes: 'premier:private',
        );

        return $groupEntitlement;
    }

    public static function grant(
        int $userId,
        ServicePackage $package,
        ?int $orderId = null,
        ?int $unitsOverride = null,
        ?string $notes = null,
    ): StudentServiceEntitlement {
        if ($orderId) {
            $existing = StudentServiceEntitlement::query()->where('order_id', $orderId)->orderBy('id')->first();
            if ($existing) {
                return $existing;
            }
        }

        if ($package->isPremier() && $unitsOverride === null) {
            return self::grantPremierPlan($userId, $package, $orderId);
        }

        $units = max(1, (int) ($unitsOverride ?? ($package->isCommercialPlan() ? $package->computedUnitsForTerm() : $package->units_count)));

        return self::createEntitlementFromPackage(
            userId: $userId,
            package: $package,
            orderId: $orderId,
            scope: $package->scope ?: ServicePackage::SCOPE_GLOBAL,
            units: $units,
            weeklyGroup: (int) $package->weekly_group_sessions,
            weeklyPrivate: (int) $package->weekly_private_sessions,
            notes: $notes,
        );
    }

    protected static function createEntitlementFromPackage(
        int $userId,
        ServicePackage $package,
        ?int $orderId,
        string $scope,
        int $units,
        int $weeklyGroup = 0,
        int $weeklyPrivate = 0,
        ?string $notes = null,
    ): StudentServiceEntitlement {
        $starts = now();
        $expires = $package->duration_days
            ? $starts->copy()->addDays((int) $package->duration_days)
            : ($package->term_months ? $starts->copy()->addDays((int) $package->term_months * 30) : null);

        $yearId = $package->academic_year_id;
        $subjectId = $package->academic_subject_id;
        if ($package->tutoring_group_id && (! $yearId || ! $subjectId)) {
            $group = $package->tutoringGroup ?: TutoringGroup::query()->find($package->tutoring_group_id);
            if ($group) {
                $yearId = $yearId ?: $group->academic_year_id;
                $subjectId = $subjectId ?: $group->academic_subject_id;
            }
        }

        return StudentServiceEntitlement::create([
            'user_id' => $userId,
            'service_package_id' => $package->id,
            'order_id' => $orderId,
            'scope' => $scope,
            'plan_type' => $package->plan_type,
            'term_months' => $package->term_months,
            'weekly_group_sessions' => $weeklyGroup,
            'weekly_private_sessions' => $weeklyPrivate,
            'includes_community' => (bool) $package->includes_community,
            'includes_libraries' => (bool) $package->includes_libraries,
            'tutoring_group_id' => $package->tutoring_group_id,
            'academic_year_id' => $yearId,
            'academic_subject_id' => $subjectId,
            'units_total' => $units,
            'units_used' => 0,
            'starts_at' => $starts,
            'expires_at' => $expires,
            'status' => StudentServiceEntitlement::STATUS_ACTIVE,
            'notes' => $notes,
        ]);
    }

    public static function grantManual(
        int $userId,
        string $scope,
        int $units,
        ?int $tutoringGroupId = null,
        ?int $durationDays = null,
        ?string $notes = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): StudentServiceEntitlement {
        $starts = now();

        if ($tutoringGroupId && (! $academicYearId || ! $academicSubjectId)) {
            $group = TutoringGroup::query()->find($tutoringGroupId);
            if ($group) {
                $academicYearId = $academicYearId ?: $group->academic_year_id;
                $academicSubjectId = $academicSubjectId ?: $group->academic_subject_id;
            }
        }

        return StudentServiceEntitlement::create([
            'user_id' => $userId,
            'service_package_id' => null,
            'order_id' => null,
            'scope' => $scope,
            'tutoring_group_id' => $tutoringGroupId,
            'academic_year_id' => $academicYearId,
            'academic_subject_id' => $academicSubjectId,
            'units_total' => max(1, $units),
            'units_used' => 0,
            'starts_at' => $starts,
            'expires_at' => $durationDays ? $starts->copy()->addDays($durationDays) : null,
            'status' => StudentServiceEntitlement::STATUS_ACTIVE,
            'notes' => $notes ?: 'منح يدوي من الإدارة',
        ]);
    }

    /**
     * Prefer group-specific, then year/subject-locked, then matching scope, then global.
     */
    public static function availableFor(
        int $userId,
        string $scope,
        ?int $tutoringGroupId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): ?StudentServiceEntitlement {
        self::expireStaleForUser($userId);

        if ($tutoringGroupId && (! $academicYearId || ! $academicSubjectId)) {
            $group = TutoringGroup::query()->find($tutoringGroupId);
            if ($group) {
                $academicYearId = $academicYearId ?: ($group->academic_year_id ? (int) $group->academic_year_id : null);
                $academicSubjectId = $academicSubjectId ?: ($group->academic_subject_id ? (int) $group->academic_subject_id : null);
            }
        }

        $candidates = StudentServiceEntitlement::query()
            ->forUser($userId)
            ->active()
            ->whereColumn('units_used', '<', 'units_total')
            ->whereIn('scope', [$scope, ServicePackage::SCOPE_GLOBAL])
            ->orderBy('expires_at')
            ->orderBy('id')
            ->get()
            ->filter(function (StudentServiceEntitlement $e) use ($tutoringGroupId, $academicYearId, $academicSubjectId) {
                if (! $tutoringGroupId && ! $academicYearId && ! $academicSubjectId) {
                    return true;
                }

                return self::entitlementFitsContext(
                    $e,
                    $tutoringGroupId,
                    $academicYearId,
                    $academicSubjectId,
                );
            })
            ->sortByDesc(fn (StudentServiceEntitlement $e) => self::entitlementSpecificityScore(
                $e,
                $scope,
                $tutoringGroupId,
                $academicYearId,
                $academicSubjectId,
            ))
            ->values();

        foreach ($candidates as $entitlement) {
            if (self::bookableUnitsLeft($entitlement) > 0) {
                return $entitlement;
            }
        }

        return null;
    }

    public static function entitlementFitsContext(
        StudentServiceEntitlement $entitlement,
        ?int $tutoringGroupId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): bool {
        if ($entitlement->tutoring_group_id) {
            if (! $tutoringGroupId || (int) $entitlement->tutoring_group_id !== (int) $tutoringGroupId) {
                return false;
            }
        }

        if ($entitlement->academic_year_id) {
            if (! $academicYearId || (int) $entitlement->academic_year_id !== (int) $academicYearId) {
                return false;
            }
        }

        if ($entitlement->academic_subject_id) {
            if (! $academicSubjectId || (int) $entitlement->academic_subject_id !== (int) $academicSubjectId) {
                return false;
            }
        }

        return true;
    }

    public static function entitlementSpecificityScore(
        StudentServiceEntitlement $entitlement,
        string $scope,
        ?int $tutoringGroupId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): int {
        $score = 0;
        if ($entitlement->tutoring_group_id && $tutoringGroupId
            && (int) $entitlement->tutoring_group_id === (int) $tutoringGroupId) {
            $score += 100;
        }
        if ($entitlement->academic_subject_id && $academicSubjectId
            && (int) $entitlement->academic_subject_id === (int) $academicSubjectId) {
            $score += 20;
        }
        if ($entitlement->academic_year_id && $academicYearId
            && (int) $entitlement->academic_year_id === (int) $academicYearId) {
            $score += 10;
        }
        if ($entitlement->scope === $scope) {
            $score += 5;
        }

        return $score;
    }

    /**
     * Units not yet used and not reserved by open (pending/confirmed) bookings.
     */
    public static function bookableUnitsLeft(StudentServiceEntitlement $entitlement): int
    {
        $reserved = TutoringGroupBooking::query()
            ->where('student_service_entitlement_id', $entitlement->id)
            ->whereIn('status', [
                TutoringGroupBooking::STATUS_PENDING,
                TutoringGroupBooking::STATUS_CONFIRMED,
            ])
            ->count();

        // One-to-one sessions scheduled against this entitlement also reserve a unit
        $otoReserved = 0;
        if (class_exists(\App\Models\OneToOneSession::class)
            && \Illuminate\Support\Facades\Schema::hasColumn('one_to_one_sessions', 'student_service_entitlement_id')) {
            $otoReserved = \App\Models\OneToOneSession::query()
                ->where('student_service_entitlement_id', $entitlement->id)
                ->whereIn('status', [
                    \App\Models\OneToOneSession::STATUS_PENDING,
                    \App\Models\OneToOneSession::STATUS_SCHEDULED,
                ])
                ->count();
        }

        return max(0, $entitlement->unitsLeft() - $reserved - $otoReserved);
    }

    public static function unitsLeft(
        int $userId,
        string $scope,
        ?int $tutoringGroupId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): int {
        self::expireStaleForUser($userId);

        if ($tutoringGroupId && (! $academicYearId || ! $academicSubjectId)) {
            $group = TutoringGroup::query()->find($tutoringGroupId);
            if ($group) {
                $academicYearId = $academicYearId ?: ($group->academic_year_id ? (int) $group->academic_year_id : null);
                $academicSubjectId = $academicSubjectId ?: ($group->academic_subject_id ? (int) $group->academic_subject_id : null);
            }
        }

        return (int) StudentServiceEntitlement::query()
            ->forUser($userId)
            ->active()
            ->whereIn('scope', [$scope, ServicePackage::SCOPE_GLOBAL])
            ->get()
            ->filter(function (StudentServiceEntitlement $e) use ($tutoringGroupId, $academicYearId, $academicSubjectId) {
                // Dashboard / totals with no booking context: include all matching scopes.
                if (! $tutoringGroupId && ! $academicYearId && ! $academicSubjectId) {
                    return true;
                }

                return self::entitlementFitsContext(
                    $e,
                    $tutoringGroupId,
                    $academicYearId,
                    $academicSubjectId,
                );
            })
            ->sum(fn (StudentServiceEntitlement $e) => self::bookableUnitsLeft($e));
    }

    public static function assertCanBook(
        int $userId,
        string $scope,
        ?int $tutoringGroupId = null,
        ?int $academicYearId = null,
        ?int $academicSubjectId = null,
    ): StudentServiceEntitlement {
        $entitlement = self::availableFor($userId, $scope, $tutoringGroupId, $academicYearId, $academicSubjectId);
        if (! $entitlement) {
            throw new InvalidArgumentException('لا يوجد رصيد حصص متاح. اشترِ باقة أو اشحن رصيدك.');
        }

        return $entitlement;
    }

    public static function consume(StudentServiceEntitlement $entitlement, int $units = 1): StudentServiceEntitlement
    {
        return DB::transaction(function () use ($entitlement, $units) {
            $locked = StudentServiceEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);

            if (! $locked->isActive()) {
                throw new InvalidArgumentException('الرصيد غير نشط أو منتهٍ.');
            }
            if ($locked->unitsLeft() < $units) {
                throw new InvalidArgumentException('رصيد الحصص غير كافٍ.');
            }

            $locked->units_used = (int) $locked->units_used + $units;
            if ($locked->units_used >= (int) $locked->units_total) {
                $locked->status = StudentServiceEntitlement::STATUS_EXPIRED;
            }
            $locked->save();

            // Keep legacy tutoring subscription in sync when linked
            foreach (StudentTutoringSubscription::query()->where('student_service_entitlement_id', $locked->id)->get() as $sub) {
                $sub->sessions_used = min((int) $sub->sessions_total, (int) $locked->units_used);
                if ($sub->sessions_used >= (int) $sub->sessions_total || $locked->status === StudentServiceEntitlement::STATUS_EXPIRED) {
                    $sub->status = StudentTutoringSubscription::STATUS_EXPIRED;
                }
                $sub->save();
            }

            return $locked->fresh();
        });
    }

    public static function expireEmpty(StudentServiceEntitlement $entitlement): void
    {
        if ($entitlement->unitsLeft() <= 0 && $entitlement->status === StudentServiceEntitlement::STATUS_ACTIVE) {
            $entitlement->update(['status' => StudentServiceEntitlement::STATUS_EXPIRED]);
        }
    }

    public static function expireStaleForUser(int $userId): void
    {
        StudentServiceEntitlement::query()
            ->forUser($userId)
            ->where('status', StudentServiceEntitlement::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => StudentServiceEntitlement::STATUS_EXPIRED]);
    }

    /**
     * هل لدى الطالب باقة (حصص خاصة أو عامة) ضمن فترة الصلاحية —
     * حتى لو استُهلك الرصيد. يُستخدم لصلاحية حجز الحصة المجانية غير المخصومة.
     */
    public static function hasActivePrivatePackage(int $userId): bool
    {
        self::expireStaleForUser($userId);

        return StudentServiceEntitlement::query()
            ->forUser($userId)
            ->whereIn('scope', [ServicePackage::SCOPE_PRIVATE_LESSONS, ServicePackage::SCOPE_GLOBAL])
            ->where('status', '!=', StudentServiceEntitlement::STATUS_CANCELLED)
            ->where('units_total', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public static function scopeForTutoringGroup(TutoringGroup $group): string
    {
        return $group->isCollective()
            ? ServicePackage::SCOPE_TUTORING_COLLECTIVE
            : ServicePackage::SCOPE_TUTORING_INDIVIDUAL;
    }

    public static function syncLegacySubscription(StudentTutoringSubscription $subscription, StudentServiceEntitlement $entitlement): void
    {
        $status = match ($entitlement->status) {
            StudentServiceEntitlement::STATUS_ACTIVE => StudentTutoringSubscription::STATUS_ACTIVE,
            StudentServiceEntitlement::STATUS_CANCELLED => StudentTutoringSubscription::STATUS_CANCELLED,
            default => StudentTutoringSubscription::STATUS_EXPIRED,
        };

        $subscription->update([
            'student_service_entitlement_id' => $entitlement->id,
            'sessions_total' => $entitlement->units_total,
            'sessions_used' => $entitlement->units_used,
            'status' => $status,
            'starts_at' => $entitlement->starts_at,
            'expires_at' => $entitlement->expires_at,
        ]);
    }

    public static function syncLinkedSubscriptionsFromEntitlement(StudentServiceEntitlement $entitlement): void
    {
        foreach (
            StudentTutoringSubscription::query()
                ->where('student_service_entitlement_id', $entitlement->id)
                ->get() as $subscription
        ) {
            self::syncLegacySubscription($subscription, $entitlement);
        }
    }

    public static function findOrCreatePackageForTutoringGroupPackage($tutoringPackage): ServicePackage
    {
        $existing = ServicePackage::query()
            ->where('scope', ServicePackage::SCOPE_TUTORING_INDIVIDUAL)
            ->where('tutoring_group_id', $tutoringPackage->tutoring_group_id)
            ->where('units_count', (int) $tutoringPackage->sessions_count)
            ->where('price', $tutoringPackage->price)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ServicePackage::create([
            'name' => $tutoringPackage->name,
            'slug' => ServicePackage::uniqueSlug('tgp-'.$tutoringPackage->id.'-'.$tutoringPackage->name),
            'description' => 'باقة مجموعة فردية',
            'badge' => $tutoringPackage->is_featured ? 'مميزة' : null,
            'scope' => ServicePackage::SCOPE_TUTORING_INDIVIDUAL,
            'tutoring_group_id' => $tutoringPackage->tutoring_group_id,
            'units_count' => max(1, (int) $tutoringPackage->sessions_count),
            'duration_days' => max(1, (int) $tutoringPackage->duration_months) * 30,
            'price' => $tutoringPackage->price,
            'original_price' => $tutoringPackage->original_price,
            'currency' => $tutoringPackage->currency ?: platform_currency(),
            'is_active' => (bool) $tutoringPackage->is_active,
            'is_featured' => (bool) $tutoringPackage->is_featured,
            'sort_order' => (int) ($tutoringPackage->sort_order ?? 0),
        ]);
    }
}
