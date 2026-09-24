<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Referral;
use App\Models\ReferralProgram;
use App\Models\ServicePackage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * إنشاء كود إحالة للمستخدم
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = 'REF'.strtoupper(Str::random(12));

        while (User::where('referral_code', $code)->exists()) {
            $code = 'REF'.strtoupper(Str::random(12));
        }

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * معالجة إحالة مستخدم جديد
     */
    public function processReferral(User $referrer, User $referred, ?string $referralCode = null): ?Referral
    {
        $program = ReferralProgram::currentForNewReferrals();

        if (! $program) {
            return null;
        }

        if ($referralCode && strtoupper(trim($referralCode)) !== strtoupper(trim((string) $referrer->referral_code))) {
            return null;
        }

        if (! $program->canUserRefer($referrer->id)) {
            return null;
        }

        if (! $program->allow_self_referral && $referrer->id === $referred->id) {
            return null;
        }

        $existingForReferred = Referral::where('referred_id', $referred->id)->first();
        if ($existingForReferred) {
            return $existingForReferred;
        }

        $referral = Referral::create([
            'referral_program_id' => $program->id,
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'code' => $referrer->referral_code,
            'referral_code' => $referrer->referral_code,
            'status' => Referral::STATUS_PENDING,
        ]);

        $referred->update([
            'referred_by' => $referrer->id,
            'referred_at' => now(),
        ]);

        $referrer->increment('total_referrals');

        if ($program->usesDiscount()) {
            $coupon = $this->createAutoCouponForReferred($referral, $program);
            if ($coupon) {
                $referral->update([
                    'auto_coupon_id' => $coupon->id,
                    'discount_expires_at' => Carbon::now()->addDays((int) ($program->discount_valid_days ?: 30)),
                ]);
            }
        } elseif ($program->usesCredits() && $program->shouldGrantReferredOnSignup()) {
            $this->grantReferredCredits($referral, $program);
        }

        return $referral->fresh();
    }

    /**
     * إنشاء كوبون تلقائي للمستخدم المحال (وضع الخصم فقط)
     */
    public function createAutoCouponForReferred(Referral $referral, ReferralProgram $program): ?Coupon
    {
        $referred = $referral->referred;
        $referrer = $referral->referrer;

        $couponCode = 'REF-'.strtoupper(Str::random(8));
        while (Coupon::where('code', $couponCode)->exists()) {
            $couponCode = 'REF-'.strtoupper(Str::random(8));
        }

        return Coupon::create([
            'code' => $couponCode,
            'name' => 'خصم الإحالة - '.$referrer->name,
            'title' => 'خصم خاص من برنامج الإحالة',
            'description' => "خصم خاص للمستخدم المحال من {$referrer->name}. برنامج: {$program->name}",
            'discount_type' => $program->discount_type,
            'discount_value' => $program->discount_value,
            'maximum_discount' => $program->maximum_discount,
            'minimum_amount' => $program->minimum_order_amount,
            'usage_limit' => $program->max_discount_uses_per_referred,
            'usage_limit_per_user' => 1,
            'applicable_user_ids' => [$referred->id],
            'applicable_to' => 'all',
            'starts_at' => now(),
            'expires_at' => Carbon::now()->addDays((int) ($program->discount_valid_days ?: 30)),
            'is_active' => true,
            'is_public' => false,
        ]);
    }

    /**
     * تطبيق خصم الإحالة تلقائياً على الطلب (وضع الخصم فقط)
     */
    public function applyReferralDiscount(User $user, $orderAmount): ?Coupon
    {
        $referral = Referral::where('referred_id', $user->id)
            ->where('status', Referral::STATUS_PENDING)
            ->with(['referralProgram', 'autoCoupon'])
            ->first();

        if (! $referral || ! $referral->referralProgram || ! $referral->referralProgram->usesDiscount()) {
            return null;
        }

        if (! $referral->canUseDiscount()) {
            return null;
        }

        if ($referral->autoCoupon && $referral->autoCoupon->isValid() && $referral->autoCoupon->canBeUsedByUser($user->id)) {
            return $referral->autoCoupon;
        }

        return null;
    }

    /**
     * اكتمال إحالة مستخدم بعد أول طلب مدفوع معتمد.
     */
    public function completePendingForUser(int $userId, $orderAmount = null): ?Referral
    {
        $referral = Referral::query()
            ->where('referred_id', $userId)
            ->where('status', Referral::STATUS_PENDING)
            ->with('referralProgram')
            ->first();

        if (! $referral) {
            return null;
        }

        $this->markReferralAsCompleted($referral, $orderAmount);

        return $referral->fresh();
    }

    /**
     * تحديث حالة الإحالة عند اكتمال الطلب + منح رصيد الحصص
     */
    public function markReferralAsCompleted(Referral $referral, $orderAmount = null): void
    {
        if ($referral->status === Referral::STATUS_COMPLETED) {
            return;
        }

        $referral->loadMissing('referralProgram');
        $program = $referral->referralProgram;

        $referral->update([
            'status' => Referral::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $referrer = $referral->referrer;
        if ($referrer) {
            $referrer->increment('completed_referrals');
        }

        if (! $program) {
            return;
        }

        if ($program->usesCredits()) {
            if ($program->shouldGrantReferredOnPurchase() && (int) $referral->referred_units_granted <= 0) {
                $this->grantReferredCredits($referral, $program);
            }
            if ($program->shouldGrantReferrerOnPurchase() && (int) $referral->referrer_units_granted <= 0) {
                $this->grantReferrerCredits($referral, $program);
            }

            return;
        }

        // وضع الخصم القديم: تسجيل مكافأة مالية/نقاط فقط
        if ($program->referrer_reward_value !== null && (float) $program->referrer_reward_value > 0) {
            if ($program->referrer_reward_type === 'points') {
                $referral->update([
                    'reward_points' => (int) round((float) $program->referrer_reward_value),
                    'reward_amount' => 0,
                ]);
            } elseif ($program->referrer_reward_type === 'percentage' && $orderAmount) {
                $rewardAmount = ($orderAmount * (float) $program->referrer_reward_value) / 100;
                $referral->update(['reward_amount' => round($rewardAmount, 2), 'reward_points' => 0]);
            } else {
                $referral->update([
                    'reward_amount' => round((float) $program->referrer_reward_value, 2),
                    'reward_points' => 0,
                ]);
            }
        }
    }

    public function grantReferredCredits(Referral $referral, ReferralProgram $program): void
    {
        $units = max(0, (int) $program->referred_credit_units);
        if ($units <= 0 || (int) $referral->referred_units_granted > 0) {
            return;
        }

        try {
            $entitlement = StudentEntitlementService::grantManual(
                userId: (int) $referral->referred_id,
                scope: $program->credit_scope ?: ServicePackage::SCOPE_PRIVATE_LESSONS,
                units: $units,
                durationDays: $program->credit_duration_days ? (int) $program->credit_duration_days : null,
                notes: 'مكافأة إحالة — رصيد للمحالة (برنامج: '.$program->name.')',
            );

            $referral->update([
                'referred_entitlement_id' => $entitlement->id,
                'referred_units_granted' => $units,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Referral referred credit grant failed: '.$e->getMessage(), [
                'referral_id' => $referral->id,
            ]);
        }
    }

    public function grantReferrerCredits(Referral $referral, ReferralProgram $program): void
    {
        $units = max(0, (int) $program->referrer_credit_units);
        if ($units <= 0 || (int) $referral->referrer_units_granted > 0) {
            return;
        }

        try {
            $entitlement = StudentEntitlementService::grantManual(
                userId: (int) $referral->referrer_id,
                scope: $program->credit_scope ?: ServicePackage::SCOPE_PRIVATE_LESSONS,
                units: $units,
                durationDays: $program->credit_duration_days ? (int) $program->credit_duration_days : null,
                notes: 'مكافأة إحالة — رصيد للمحيلة (برنامج: '.$program->name.')',
            );

            $referral->update([
                'referrer_entitlement_id' => $entitlement->id,
                'referrer_units_granted' => $units,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Referral referrer credit grant failed: '.$e->getMessage(), [
                'referral_id' => $referral->id,
            ]);
        }
    }

    /**
     * الحصول على كود الإحالة للمستخدم
     */
    public function getUserReferralCode(User $user): string
    {
        if (! $user->referral_code) {
            return $this->generateReferralCode($user);
        }

        return $user->referral_code;
    }

    /**
     * بناء نص مشاركة واتساب من إعدادات البرنامج
     */
    public function buildShareMessage(ReferralProgram $program, string $code, string $link): string
    {
        $units = max((int) $program->referred_credit_units, (int) $program->referrer_credit_units, 1);
        $message = $program->resolvedShareMessage();

        return str_replace(
            ['{link}', '{code}', '{units}'],
            [$link, $code, (string) $units],
            $message
        );
    }
}
