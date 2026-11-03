<?php

namespace App\Services;

use App\Models\FreeTrialBooking;
use App\Models\ServicePackage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * حد رصيد الحصص التجريبية لكل طالب (عام أو حسب الباقة).
 */
class FreeTrialCreditSettings
{
    public const KEY_DEFAULT_LIMIT = 'free_trial_credit_limit_default';

    public const KEY_PER_PACKAGE_PREFIX = 'free_trial_credit_limit_package_';

    public static function defaultLimit(): int
    {
        $raw = Setting::getValue(self::KEY_DEFAULT_LIMIT);

        return max(0, (int) ($raw !== null && $raw !== '' ? $raw : 1));
    }

    public static function setDefaultLimit(int $limit): void
    {
        Setting::setValue(self::KEY_DEFAULT_LIMIT, (string) max(0, $limit));
    }

    public static function packageLimit(int $packageId): ?int
    {
        if ($packageId <= 0) {
            return null;
        }
        $raw = Setting::getValue(self::KEY_PER_PACKAGE_PREFIX.$packageId);
        if ($raw === null || $raw === '') {
            return null;
        }

        return max(0, (int) $raw);
    }

    public static function setPackageLimit(int $packageId, ?int $limit): void
    {
        $key = self::KEY_PER_PACKAGE_PREFIX.$packageId;
        if ($limit === null) {
            Setting::setValue($key, null);

            return;
        }
        Setting::setValue($key, (string) max(0, $limit));
    }

    /**
     * الحد الفعّال للطالب: أعلى حد باقة نشطة لديه، وإلا الافتراضي.
     */
    public static function limitForStudent(?User $user): int
    {
        $default = self::defaultLimit();
        if (! $user || ! Schema::hasTable('service_packages')) {
            return $default;
        }

        $packageIds = collect();
        if (Schema::hasTable('student_service_entitlements')) {
            $packageIds = $packageIds->merge(
                \Illuminate\Support\Facades\DB::table('student_service_entitlements')
                    ->where('user_id', $user->id)
                    ->when(
                        Schema::hasColumn('student_service_entitlements', 'expires_at'),
                        fn ($q) => $q->where(function ($inner) {
                            $inner->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                    )
                    ->pluck('service_package_id')
            );
        }
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'service_package_id')) {
            $packageIds = $packageIds->merge(
                \Illuminate\Support\Facades\DB::table('orders')
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['paid', 'completed', 'active'])
                    ->whereNotNull('service_package_id')
                    ->pluck('service_package_id')
            );
        }

        $packageIds = $packageIds->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($packageIds->isEmpty()) {
            return $default;
        }

        $best = $default;
        foreach ($packageIds as $pid) {
            $pkgLimit = self::packageLimit((int) $pid);
            if ($pkgLimit !== null) {
                $best = max($best, $pkgLimit);
            }
        }

        return $best;
    }

    public static function usedCount(User $user): int
    {
        if (! Schema::hasTable('free_trial_bookings')) {
            return 0;
        }

        return (int) FreeTrialBooking::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                FreeTrialBooking::STATUS_PENDING,
                FreeTrialBooking::STATUS_CONFIRMED,
                FreeTrialBooking::STATUS_COMPLETED,
            ])
            ->count();
    }

    public static function remainingForStudent(?User $user): int
    {
        if (! $user) {
            return self::defaultLimit();
        }
        $limit = self::limitForStudent($user);

        return max(0, $limit - self::usedCount($user));
    }

    public static function assertStudentMayBook(User $user): void
    {
        if (self::remainingForStudent($user) > 0) {
            return;
        }

        throw new \InvalidArgumentException(
            'تم استهلاك رصيد الحصص التجريبية المسموح لهذا الحساب. تواصل مع الدعم أو اشترك في باقة تتيح رصيداً إضافياً.'
        );
    }

    /**
     * @return list<array{id:int,name:string,limit:?int}>
     */
    public static function packageLimitRows(): array
    {
        if (! Schema::hasTable('service_packages')) {
            return [];
        }

        return ServicePackage::query()
            ->storefront()
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn (ServicePackage $p) => [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'limit' => self::packageLimit((int) $p->id),
            ])
            ->all();
    }
}
