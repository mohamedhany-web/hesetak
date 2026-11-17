<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PopupAd extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'image',
        'link_url',
        'cta_text',
        'page_key',
        'starts_at',
        'ends_at',
        'max_views_per_visitor',
        'delay_seconds',
        'is_active',
        'order',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'delay_seconds' => 'integer',
    ];

    public const PAGE_HOME = 'home';

    public const PAGE_PRICING = 'pricing';

    public const PAGE_COURSES = 'courses';

    public const PAGE_PACKAGE_TEACHERS = 'package_teachers';

    /**
     * إعلان منبثق نشط يعرض الآن (ضمن الفترة و is_active)
     */
    public function scopeActiveNow($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->orderBy('order');
    }

    public function scopeForPage($query, ?string $pageKey)
    {
        if (! $pageKey) {
            return $query->where(function ($q) {
                $q->whereNull('page_key')->orWhere('page_key', '')->orWhere('page_key', self::PAGE_HOME);
            });
        }

        return $query->where(function ($q) use ($pageKey) {
            $q->where('page_key', $pageKey);
        });
    }
}
