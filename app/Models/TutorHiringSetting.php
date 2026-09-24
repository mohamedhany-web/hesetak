<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TutorHiringSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public const CACHE_KEY = 'tutor_hiring_settings_map_v1';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'require_interview' => true,
            'require_contract' => true,
            'require_specialty' => true,
            'interview_duration_minutes' => 30,
            'no_show_grace_minutes' => 15,
            'allow_reschedule' => true,
            'interview_email_subject' => 'تأكيد مقابلة التوظيف — {app_name}',
            'interview_email_body' => "مرحباً {name},\n\nتم تأكيد موعد مقابلتك التقنية:\n{datetime}\n\nرابط الانضمام:\n{join_url}\n\nفريق {app_name}",
            'interview_whatsapp_body' => "مرحباً {name} 👋\nتم تأكيد مقابلتك في {app_name}:\n{datetime}\nالرابط: {join_url}",
            'contract_email_subject' => 'عرض التعاقد — {app_name}',
            'contract_email_body' => "مرحباً {name},\n\nتم إرسال عرض تعاقد للمراجعة والتوقيع:\n{sign_url}\n\nفريق {app_name}",
            'contract_whatsapp_body' => "مرحباً {name}\nعرض التعاقد جاهز للتوقيع:\n{sign_url}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function allMapped(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $defaults = self::defaults();
            $rows = self::query()->pluck('value', 'key');
            foreach ($rows as $key => $value) {
                if (! array_key_exists($key, $defaults)) {
                    $defaults[$key] = $value;
                    continue;
                }
                $defaults[$key] = self::castValue($key, $value);
            }

            return $defaults;
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::allMapped();
        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (self::defaults()[$key] ?? null);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    /**
     * @param  array<string, mixed>  $pairs
     */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            $stored = is_bool($value)
                ? ($value ? '1' : '0')
                : (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value);

            self::query()->updateOrCreate(
                ['key' => (string) $key],
                ['value' => $stored]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    protected static function castValue(string $key, mixed $value): mixed
    {
        $defaults = self::defaults();
        $sample = $defaults[$key] ?? null;

        if (is_bool($sample)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        if (is_int($sample)) {
            return (int) $value;
        }

        return $value;
    }
}
