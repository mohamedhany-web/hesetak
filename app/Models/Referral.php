<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_program_id',
        'referrer_id',
        'referred_id',
        'code',
        'referral_code',
        'device_fingerprint',
        'status',
        'commission_amount',
        'commission_type',
        'paid_at',
        'metadata',
        'auto_coupon_id',
        'referred_entitlement_id',
        'referrer_entitlement_id',
        'referred_units_granted',
        'referrer_units_granted',
        'discount_amount',
        'discount_used_count',
        'discount_expires_at',
        'completed_at',
        'reward_amount',
        'reward_points',
        'invoice_id',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_used_count' => 'integer',
        'reward_points' => 'integer',
        'referred_units_granted' => 'integer',
        'referrer_units_granted' => 'integer',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'discount_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    public function referralProgram()
    {
        return $this->belongsTo(ReferralProgram::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function autoCoupon()
    {
        return $this->belongsTo(Coupon::class, 'auto_coupon_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function referredEntitlement(): BelongsTo
    {
        return $this->belongsTo(StudentServiceEntitlement::class, 'referred_entitlement_id');
    }

    public function referrerEntitlement(): BelongsTo
    {
        return $this->belongsTo(StudentServiceEntitlement::class, 'referrer_entitlement_id');
    }

    public function isDiscountValid()
    {
        if (! $this->discount_expires_at) {
            return true;
        }

        return $this->discount_expires_at > now();
    }

    public function canUseDiscount()
    {
        if (! $this->isDiscountValid()) {
            return false;
        }

        if (! $this->referralProgram) {
            return false;
        }

        $maxUses = $this->referralProgram->max_discount_uses_per_referred;

        if ($maxUses && $this->discount_used_count >= $maxUses) {
            return false;
        }

        return true;
    }

    public function incrementDiscountUsage()
    {
        $this->increment('discount_used_count');
        $this->save();
    }

    public function totalCreditsGranted(): int
    {
        return (int) $this->referred_units_granted + (int) $this->referrer_units_granted;
    }
}
