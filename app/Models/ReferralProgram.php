<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReferralProgram extends Model
{
    use HasFactory;

    public const REWARD_MODE_CREDITS = 'credits';

    public const REWARD_MODE_DISCOUNT = 'discount';

    public const GRANT_SIGNUP = 'signup';

    public const GRANT_FIRST_PURCHASE = 'first_purchase';

    public const GRANT_BOTH = 'both';

    public const GRANT_NONE = 'none';

    protected $fillable = [
        'name',
        'description',
        'reward_mode',
        'credit_scope',
        'referred_credit_units',
        'referrer_credit_units',
        'credit_duration_days',
        'grant_referred_on',
        'grant_referrer_on',
        'share_message_ar',
        'share_message_en',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'minimum_order_amount',
        'referrer_reward_type',
        'referrer_reward_value',
        'discount_valid_days',
        'referral_code_valid_days',
        'max_referrals_per_user',
        'max_discount_uses_per_referred',
        'allow_self_referral',
        'starts_at',
        'expires_at',
        'is_active',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'referrer_reward_value' => 'decimal:2',
        'discount_valid_days' => 'integer',
        'referral_code_valid_days' => 'integer',
        'max_referrals_per_user' => 'integer',
        'max_discount_uses_per_referred' => 'integer',
        'referred_credit_units' => 'integer',
        'referrer_credit_units' => 'integer',
        'credit_duration_days' => 'integer',
        'allow_self_referral' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * البرنامج المستخدم لتسجيل إحالات جديدة (افتراضي نشط، أو آخر برنامج نشط).
     */
    public static function currentForNewReferrals(): ?self
    {
        $default = static::active()->where('is_default', true)->orderByDesc('id')->first();
        if ($default) {
            return $default;
        }

        return static::active()->orderByDesc('id')->first();
    }

    public function usesCredits(): bool
    {
        return ($this->reward_mode ?: self::REWARD_MODE_CREDITS) === self::REWARD_MODE_CREDITS;
    }

    public function usesDiscount(): bool
    {
        return ($this->reward_mode ?: self::REWARD_MODE_CREDITS) === self::REWARD_MODE_DISCOUNT;
    }

    public function shouldGrantReferredOnSignup(): bool
    {
        return in_array($this->grant_referred_on, [self::GRANT_SIGNUP, self::GRANT_BOTH], true);
    }

    public function shouldGrantReferredOnPurchase(): bool
    {
        return in_array($this->grant_referred_on, [self::GRANT_FIRST_PURCHASE, self::GRANT_BOTH], true);
    }

    public function shouldGrantReferrerOnPurchase(): bool
    {
        return ($this->grant_referrer_on ?: self::GRANT_FIRST_PURCHASE) === self::GRANT_FIRST_PURCHASE;
    }

    public function creditScopeLabel(?string $locale = null): string
    {
        $scope = $this->credit_scope ?: ServicePackage::SCOPE_PRIVATE_LESSONS;
        $labels = ServicePackage::scopes();

        return $labels[$scope] ?? $scope;
    }

    public function resolvedShareMessage(?string $locale = null): string
    {
        $locale = $locale ?: (app()->getLocale() === 'ar' ? 'ar' : 'en');
        $custom = $locale === 'ar' ? $this->share_message_ar : $this->share_message_en;
        if (filled($custom)) {
            return trim((string) $custom);
        }

        return $locale === 'ar'
            ? 'سجّل في حصتك من رابطي واحصل على رصيد حصص مجاني: {link}'
            : 'Join حصتك with my link and get free lesson credits: {link}';
    }

    // العلاقات
    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    // Methods
    public function isValid()
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount)
    {
        if (! $this->isValid()) {
            return 0;
        }

        if ($this->minimum_order_amount && $amount < $this->minimum_order_amount) {
            return 0;
        }

        $discount = 0;

        if ($this->discount_type === 'percentage') {
            $discount = ($amount * $this->discount_value) / 100;
        } else {
            $discount = $this->discount_value;
        }

        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        if ($discount > $amount) {
            $discount = $amount;
        }

        return round($discount, 2);
    }

    public function canUserRefer($userId)
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->max_referrals_per_user) {
            $userReferralsCount = Referral::where('referrer_id', $userId)
                ->where('referral_program_id', $this->id)
                ->count();

            if ($userReferralsCount >= $this->max_referrals_per_user) {
                return false;
            }
        }

        return true;
    }
}
