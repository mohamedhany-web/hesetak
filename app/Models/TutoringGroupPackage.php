<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutoringGroupPackage extends Model
{
    protected $fillable = [
        'tutoring_group_id',
        'name',
        'duration_months',
        'sessions_count',
        'sessions_per_month',
        'hourly_rate',
        'price',
        'original_price',
        'currency',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'sessions_count' => 'integer',
            'sessions_per_month' => 'integer',
            'hourly_rate' => 'decimal:2',
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function tutoringGroup(): BelongsTo
    {
        return $this->belongsTo(TutoringGroup::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentTutoringSubscription::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TutoringGroupBooking::class);
    }

    public function savingsAmount(): float
    {
        $original = (float) ($this->original_price ?? 0);
        $price = (float) $this->price;

        return max(0, $original - $price);
    }

    public function savingsPercent(): int
    {
        $original = (float) ($this->original_price ?? 0);
        if ($original <= 0) {
            return 0;
        }

        return (int) round(($this->savingsAmount() / $original) * 100);
    }

    public function formattedPrice(): string
    {
        return number_format((float) $this->price, 0).' '.($this->currency ?: platform_currency());
    }

    public function formattedOriginalPrice(): ?string
    {
        if ($this->original_price === null || (float) $this->original_price <= (float) $this->price) {
            return null;
        }

        return number_format((float) $this->original_price, 0).' '.($this->currency ?: platform_currency());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
