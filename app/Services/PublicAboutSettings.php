<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PublicAboutSettings
{
    public const CACHE_KEY = 'public_about_page_payload_v2';

    /** @return array<string, string> */
    public static function defaults(): array
    {
        $content = __('site.pages.about');
        $sections = is_array($content['sections'] ?? null) ? $content['sections'] : [];
        $isRtl = app()->getLocale() === 'ar';

        $defaults = [
            'about_kicker' => (string) ($content['kicker'] ?? 'عن المنصة'),
            'about_title' => (string) ($content['title'] ?? 'من نحن'),
            'about_lead' => (string) ($content['lead'] ?? ''),
            'about_meta_title' => (string) ($content['meta_title'] ?? ''),
            'about_meta_description' => (string) ($content['meta_description'] ?? ''),
            'about_section_1_title' => (string) ($sections[0]['title'] ?? ''),
            'about_section_1_body' => (string) ($sections[0]['body'] ?? ''),
            'about_section_2_title' => (string) ($sections[1]['title'] ?? ''),
            'about_section_2_body' => (string) ($sections[1]['body'] ?? ''),
        ];

        // بطاقات مميزات (lighting cards) — قابلة للتعديل من لوحة الإدارة
        $featureDefaults = [
            1 => [
                'title' => $isRtl ? 'حصص فردية معتمدة' : 'Accredited 1:1 lessons',
                'body' => $isRtl ? 'معلمون مراجعون، مواعيد مرنة، وتقارير بعد كل حصة.' : 'Reviewed teachers, flexible timing, and post-lesson reports.',
            ],
            2 => [
                'title' => $isRtl ? 'باقات حصص واضحة' : 'Clear lesson packages',
                'body' => $isRtl ? 'رصيد ساعات للحجز 1:1 دون خلط مع الكورسات المسجّلة.' : 'Hour credits for 1:1 booking — not mixed with recorded courses.',
            ],
            3 => [
                'title' => $isRtl ? 'متابعة المناهج' : 'Curriculum follow-up',
                'body' => $isRtl ? 'مرحلة ومادة ونوع منهج مطابق لمقرر المدرسة.' : 'Grade, subject, and curriculum type matched to school syllabi.',
            ],
            4 => [
                'title' => $isRtl ? 'ولي الأمر شريك' : 'Parents included',
                'body' => $isRtl ? 'متابعة التقدم والحجوزات من حساب ولي الأمر.' : 'Track progress and bookings from the parent account.',
            ],
        ];

        foreach ($featureDefaults as $i => $feature) {
            $defaults["about_feature_{$i}_title"] = (string) ($sections[$i + 1]['title'] ?? $feature['title']);
            $defaults["about_feature_{$i}_body"] = (string) ($sections[$i + 1]['body'] ?? $feature['body']);
        }

        return $defaults;
    }

    /** @return list<string> */
    public static function editableKeys(): array
    {
        return array_keys(self::defaults());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{
     *   kicker: string,
     *   title: string,
     *   lead: string,
     *   meta_title: string,
     *   meta_description: string,
     *   sections: list<array{title: string, body: string}>
     * }
     */
    public static function payload(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $defaults = self::defaults();
            $stored = Setting::query()
                ->whereIn('key', array_keys($defaults))
                ->pluck('value', 'key')
                ->all();

            $get = function (string $key) use ($defaults, $stored): string {
                if (array_key_exists($key, $stored) && $stored[$key] !== null && trim((string) $stored[$key]) !== '') {
                    return (string) $stored[$key];
                }

                return (string) ($defaults[$key] ?? '');
            };

            $sections = [];
            foreach ([1, 2] as $i) {
                $title = $get("about_section_{$i}_title");
                $body = $get("about_section_{$i}_body");
                if ($title !== '' || $body !== '') {
                    $sections[] = ['title' => $title, 'body' => $body];
                }
            }

            $features = [];
            foreach ([1, 2, 3, 4] as $i) {
                $title = $get("about_feature_{$i}_title");
                $body = $get("about_feature_{$i}_body");
                if ($title !== '' || $body !== '') {
                    $features[] = ['title' => $title, 'body' => $body];
                }
            }

            return [
                'kicker' => $get('about_kicker'),
                'title' => $get('about_title'),
                'lead' => $get('about_lead'),
                'meta_title' => $get('about_meta_title'),
                'meta_description' => $get('about_meta_description'),
                'sections' => $sections,
                'features' => $features,
            ];
        });
    }
}
