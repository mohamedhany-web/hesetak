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
        'page_keys',
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
        'page_keys' => 'array',
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

    public const PAGE_KEYS = [
        self::PAGE_HOME => 'الرئيسية',
        self::PAGE_PRICING => 'الأسعار /pricing',
        self::PAGE_COURSES => 'الكورسات /courses',
        self::PAGE_PACKAGE_TEACHERS => 'صفحة معلمي الباقة',
    ];

    /**
     * الصفحات التي يظهر فيها الإعلان (قائمة متعددة، مع دعم الحقل القديم page_key).
     *
     * @return list<string>
     */
    public function targetPages(): array
    {
        $keys = array_values(array_filter((array) ($this->page_keys ?? []), fn ($k) => is_string($k) && $k !== ''));
        if ($keys !== []) {
            return $keys;
        }

        return [trim((string) $this->page_key) ?: self::PAGE_HOME];
    }

    public function scopeForPage($query, ?string $pageKey)
    {
        $pageKey = $pageKey ?: self::PAGE_HOME;
        $hasPageKeys = \Illuminate\Support\Facades\Schema::hasColumn('popup_ads', 'page_keys');

        return $query->where(function ($q) use ($pageKey, $hasPageKeys) {
            if ($hasPageKeys) {
                $q->whereJsonContains('page_keys', $pageKey);
            }

            // إعلانات قديمة بلا page_keys
            $q->orWhere(function ($legacy) use ($pageKey, $hasPageKeys) {
                if ($hasPageKeys) {
                    $legacy->whereNull('page_keys');
                }
                if ($pageKey === self::PAGE_HOME) {
                    $legacy->where(function ($home) {
                        $home->whereNull('page_key')->orWhere('page_key', '')->orWhere('page_key', self::PAGE_HOME);
                    });
                } else {
                    $legacy->where('page_key', $pageKey);
                }
            });
        });
    }
}
