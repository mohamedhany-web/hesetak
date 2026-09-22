<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HesetakCurriculumType extends Model
{
    protected $fillable = [
        'key',
        'label_ar',
        'label_en',
        'aliases',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'aliases' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function label(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        return $locale === 'ar'
            ? (string) $this->label_ar
            : (string) $this->label_en;
    }
}
