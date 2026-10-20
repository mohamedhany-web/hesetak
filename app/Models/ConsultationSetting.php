<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationSetting extends Model
{
    protected $fillable = [
        'default_price',
        'default_duration_minutes',
        'payment_instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'default_duration_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row) {
            return $row;
        }

        // القيمة بوحدة عملة المنصة (ر.س افتراضياً) — ليست دولاراً ولا تحويل صرف تلقائي.
        return static::query()->create([
            'default_price' => 500,
            'default_duration_minutes' => 30,
            'payment_instructions' => null,
            'is_active' => true,
        ]);
    }
}
