<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PackageGift extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_GRANTED = 'granted';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id',
        'service_package_id',
        'buyer_user_id',
        'recipient_email',
        'recipient_name',
        'recipient_phone',
        'recipient_user_id',
        'message',
        'status',
        'claim_token',
        'academic_year_id',
        'academic_subject_id',
        'curriculum_type',
        'quoted_unit_price',
        'quoted_total',
        'currency',
        'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'quoted_unit_price' => 'decimal:2',
            'quoted_total' => 'decimal:2',
            'granted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PackageGift $gift) {
            if (blank($gift->claim_token)) {
                $gift->claim_token = Str::random(48);
            }
            if (blank($gift->status)) {
                $gift->status = self::STATUS_PENDING_PAYMENT;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function isGranted(): bool
    {
        return $this->status === self::STATUS_GRANTED;
    }

    public function claimUrl(): string
    {
        return route('public.gift-package.claim', ['token' => $this->claim_token]);
    }
}
