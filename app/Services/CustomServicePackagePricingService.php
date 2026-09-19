<?php

namespace App\Services;

use App\Models\ServicePackage;
use App\Models\ServicePackagePricingRule;
use InvalidArgumentException;

class CustomServicePackagePricingService
{
    public const PRIVATE_TERM_MONTHS = [1, 3];

    public const PRIVATE_WEEKLY_OPTIONS = [1, 2, 3, 4];

    public static function calculate(ServicePackagePricingRule $rule, int $sessions): array
    {
        if (! $rule->is_active) {
            throw new InvalidArgumentException('خيار التسعير غير متاح حالياً.');
        }

        $min = max(1, (int) $rule->min_sessions);
        $max = max($min, (int) $rule->max_sessions);
        $step = max(1, (int) $rule->session_step);

        if ($sessions < $min || $sessions > $max || (($sessions - $min) % $step !== 0)) {
            throw new InvalidArgumentException("عدد الحصص يجب أن يكون بين {$min} و{$max} وبزيادة {$step}.");
        }

        return self::quoteFromSessions($rule, $sessions, [
            'duration_days' => (int) $rule->duration_days,
        ]);
    }

    /**
     * Private custom plan: fixed weekly sessions × term months.
     * Total sessions = weekly × 4 weeks × months.
     */
    public static function calculatePrivateWeekly(
        ServicePackagePricingRule $rule,
        int $termMonths,
        int $weeklySessions,
    ): array {
        if (! $rule->is_active) {
            throw new InvalidArgumentException('خيار التسعير غير متاح حالياً.');
        }

        if (! in_array($rule->scope, [
            ServicePackage::SCOPE_PRIVATE_LESSONS,
            ServicePackage::SCOPE_TUTORING_INDIVIDUAL,
        ], true)) {
            throw new InvalidArgumentException('هذه القاعدة ليست لباقة الحصص الخاصة.');
        }

        if (! in_array($termMonths, self::PRIVATE_TERM_MONTHS, true)) {
            throw new InvalidArgumentException('مدة الاشتراك يجب أن تكون شهر أو 3 أشهر.');
        }

        if (! in_array($weeklySessions, self::PRIVATE_WEEKLY_OPTIONS, true)) {
            throw new InvalidArgumentException('عدد الحصص الأسبوعية غير مدعوم.');
        }

        $sessions = $weeklySessions * 4 * $termMonths;
        $quote = self::quoteFromSessions($rule, $sessions, [
            'duration_days' => $termMonths * 30,
            'term_months' => $termMonths,
            'weekly_sessions' => $weeklySessions,
            'weekly_private_sessions' => $weeklySessions,
            'weekly_group_sessions' => 0,
            'plan_type' => ServicePackage::PLAN_PRIVATE,
            'builder' => 'private_weekly',
        ]);

        $quote['name'] = $termMonths === 1
            ? 'باقة خاصة مخصصة — شهر ('.$weeklySessions.' حصص/أسبوع)'
            : 'باقة خاصة مخصصة — '.$termMonths.' أشهر ('.$weeklySessions.' حصص/أسبوع)';

        return $quote;
    }

    protected static function quoteFromSessions(ServicePackagePricingRule $rule, int $sessions, array $extra = []): array
    {
        $min = max(1, (int) $rule->min_sessions);
        $max = max($min, (int) $rule->max_sessions);

        if ($sessions < $min || $sessions > $max) {
            throw new InvalidArgumentException("عدد الحصص الناتج ({$sessions}) خارج حدود التسعير ({$min}–{$max}). عدّل المدة أو الحصص الأسبوعية.");
        }

        $unitPrice = round((float) $rule->price_per_session, 2);
        $originalAmount = round($sessions * $unitPrice, 2);
        $discountPercent = self::discountPercent($rule, $sessions);
        $discountAmount = round($originalAmount * $discountPercent / 100, 2);
        $amount = round($originalAmount - $discountAmount, 2);

        return array_merge([
            'pricing_rule_id' => $rule->id,
            'name' => 'باقة مخصصة — '.$rule->name,
            'scope' => $rule->scope,
            'scope_label' => $rule->scopeLabel(),
            'sessions' => $sessions,
            'session_minutes' => (int) $rule->session_minutes,
            'duration_days' => (int) ($extra['duration_days'] ?? $rule->duration_days),
            'price_per_session' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'amount' => $amount,
            'final_price_per_session' => $sessions > 0 ? round($amount / $sessions, 2) : 0,
            'currency' => platform_currency(),
        ], $extra);
    }

    private static function discountPercent(ServicePackagePricingRule $rule, int $sessions): float
    {
        $discount = 0.0;

        foreach ($rule->discount_tiers ?? [] as $tier) {
            $minimum = (int) ($tier['min_sessions'] ?? 0);
            $percent = min(100, max(0, (float) ($tier['discount_percent'] ?? 0)));
            if ($sessions >= $minimum && $minimum > 0) {
                $discount = max($discount, $percent);
            }
        }

        return $discount;
    }
}
