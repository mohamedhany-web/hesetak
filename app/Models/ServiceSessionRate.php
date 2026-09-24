<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSessionRate extends Model
{
    protected $fillable = [
        'academic_year_id',
        'curriculum_type',
        'price_per_session',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_session' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
