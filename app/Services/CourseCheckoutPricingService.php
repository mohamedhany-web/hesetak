<?php

namespace App\Services;

use App\Models\AdvancedCourse;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Wallet;

class CourseCheckoutPricingService
{
    /**
     * حساب سعر الكورس بعد كوبون (إن وُجد) ثم تطبيق رصيد محفظة الطالب.
     *
     * @return array{ok: bool, message?: string, original_amount: float, coupon_id: ?int, discount_amount: float, wallet_credit_amount: float, final_amount: float, coupon: ?Coupon}
     */
    public static function resolve(User $user, AdvancedCourse $course, ?string $couponCode, float $walletCreditRequested, ?string $billingMode = null, ?string $currency = null): array
    {
        $currency = strtoupper((string) ($currency ?: platform_currency()));
        if (! in_array($currency, ['SAR', 'USD', 'EGP'], true)) {
            $currency = platform_currency();
        }
        $billingMode = $billingMode ?? ($course->isMonthlyBilling()
            ? \App\Services\CourseSubscriptionService::BILLING_MONTHLY
            : \App\Services\CourseSubscriptionService::BILLING_ONE_TIME);

        $original = round($billingMode === \App\Services\CourseSubscriptionService::BILLING_MONTHLY
            ? $course->effectiveMonthlyPrice()
            : $course->effectivePurchasePrice($currency), 2);
        if ($original <= 0) {
            return [
                'ok' => false,
                'message' => 'هذا الكورس لا يتطلب دفعاً.',
                'original_amount' => 0,
                'coupon_id' => null,
                'discount_amount' => 0,
                'wallet_credit_amount' => 0,
                'final_amount' => 0,
                'coupon' => null,
            ];
        }

        $discountAmount = 0.0;
        $couponModel = null;
        $code = $couponCode !== null ? strtoupper(trim($couponCode)) : '';
        if ($code !== '') {
            $coupon = Coupon::where('code', $code)->first();
            if (! $coupon) {
                return [
                    'ok' => false,
                    'message' => 'الكوبون غير صحيح.',
                    'original_amount' => $original,
                    'coupon_id' => null,
                    'discount_amount' => 0,
                    'wallet_credit_amount' => 0,
                    'final_amount' => $original,
                    'coupon' => null,
                ];
            }
            if (! $coupon->isValid() || ! $coupon->canBeUsedByUser($user->id) || ! $coupon->appliesToAdvancedCourseId((int) $course->id)) {
                return [
                    'ok' => false,
                    'message' => 'لا يمكن استخدام هذا الكوبون على هذا الكورس أو انتهت صلاحيته.',
                    'original_amount' => $original,
                    'coupon_id' => null,
                    'discount_amount' => 0,
                    'wallet_credit_amount' => 0,
                    'final_amount' => $original,
                    'coupon' => null,
                ];
            }
            if ($coupon->minimum_amount && $original < (float) $coupon->minimum_amount) {
                return [
                    'ok' => false,
                    'message' => 'الحد الأدنى لاستخدام هذا الكوبون هو '.number_format((float) $coupon->minimum_amount, 2). ' ' . currency_symbol(),
                    'original_amount' => $original,
                    'coupon_id' => null,
                    'discount_amount' => 0,
                    'wallet_credit_amount' => 0,
                    'final_amount' => $original,
                    'coupon' => null,
                ];
            }
            $discountAmount = (float) $coupon->calculateDiscount($original);
            $couponModel = $coupon;
        }

        $afterCoupon = round(max(0, $original - $discountAmount), 2);

        $wallet = Wallet::where('user_id', $user->id)->first();
        $balance = $wallet ? (float) $wallet->balance : 0.0;
        $requested = round(max(0, $walletCreditRequested), 2);
        $walletApply = min($requested, $balance, $afterCoupon);
        $walletApply = round($walletApply, 2);

        $final = round(max(0, $afterCoupon - $walletApply), 2);

        return [
            'ok' => true,
            'original_amount' => $original,
            'coupon_id' => $couponModel?->id,
            'discount_amount' => $discountAmount,
            'wallet_credit_amount' => $walletApply,
            'final_amount' => $final,
            'coupon' => $couponModel,
            'student_wallet_balance' => $balance,
        ];
    }
}
