<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LiveSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    /**
     * تطبيع نطاق خادم البث: إزالة البروتوكول والشرطة الأخيرة.
     */
    public static function normalizeLiveHost(string $domain): string
    {
        $domain = trim($domain);
        $domain = preg_replace('#^https?://#i', '', $domain);
        $domain = rtrim($domain, '/');

        return $domain;
    }

    /**
     * المنصة تعتمد LiveKit فقط.
     */
    public static function getLiveProvider(): string
    {
        return 'livekit';
    }

    public static function getLiveKitHost(): string
    {
        $host = trim((string) static::get('livekit_host', ''));
        if ($host !== '') {
            return static::normalizeLiveHost($host);
        }

        $fromConfig = trim((string) config('livekit.livekit.host', ''));
        if ($fromConfig !== '') {
            return static::normalizeLiveHost($fromConfig);
        }

        $server = LiveServer::query()
            ->where('status', 'active')
            ->where('provider', 'livekit')
            ->orderByDesc('id')
            ->first();
        if ($server && trim($server->domain) !== '') {
            return static::normalizeLiveHost($server->domain);
        }

        // مفتاح قديم في قاعدة البيانات (قبل الانتقال لـ LiveKit)
        $legacy = trim((string) static::get('legacy_live_domain', ''));
        if ($legacy === '') {
            $legacy = trim((string) static::get('jitsi_domain', ''));
        }
        if ($legacy !== '') {
            return static::normalizeLiveHost($legacy);
        }

        // Legacy infra host — override via LIVEKIT_PUBLIC_HOST / live servers table.
        return static::normalizeLiveHost((string) env('LIVEKIT_PUBLIC_HOST', 'live.glottical.com'));
    }

    public static function get(string $key, $default = null)
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable((new static)->getTable())) {
            return $default;
        }

        $setting = Cache::remember("live_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
        Cache::forget("live_setting_{$key}");
    }

    public static function getByGroup(string $group)
    {
        return static::where('group', $group)->get();
    }
}
