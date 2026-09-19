<?php

namespace App\Services;

use App\Models\TutoringGroupPackage;

class TutoringGroupPackagePricingService
{
    /**
     * @return array{sessions_count:int,original_price:float,price:float}
     */
    public static function calculate(
        float $hourlyRate,
        int $sessionsPerMonth,
        int $durationMonths,
        ?float $overridePrice = null
    ): array {
        $sessionsPerMonth = max(1, $sessionsPerMonth);
        $durationMonths = max(1, $durationMonths);
        $sessionsCount = $sessionsPerMonth * $durationMonths;
        $original = round($hourlyRate * $sessionsCount, 2);

        $price = $overridePrice !== null
            ? round(max(0, $overridePrice), 2)
            : $original;

        return [
            'sessions_count' => $sessionsCount,
            'original_price' => $original,
            'price' => $price,
        ];
    }

    public static function applyToPackage(TutoringGroupPackage $package, ?float $overridePrice = null): TutoringGroupPackage
    {
        $calc = self::calculate(
            (float) $package->hourly_rate,
            (int) $package->sessions_per_month,
            (int) $package->duration_months,
            $overridePrice
        );

        $package->sessions_count = $calc['sessions_count'];
        $package->original_price = $calc['original_price'];
        $package->price = $calc['price'];

        return $package;
    }

    /**
     * Suggested discount tiers from حصتك spec (3 months ≈ 16.7% off).
     */
    public static function suggestedPrice(float $hourlyRate, int $sessionsPerMonth, int $durationMonths): float
    {
        $calc = self::calculate($hourlyRate, $sessionsPerMonth, $durationMonths);
        $discountMap = [
            1 => 0.0,
            3 => 0.167,
            6 => 0.20,
            12 => 0.25,
        ];
        $rate = $discountMap[$durationMonths] ?? min(0.15, 0.05 * max(0, $durationMonths - 1));

        return round($calc['original_price'] * (1 - $rate), 2);
    }
}
