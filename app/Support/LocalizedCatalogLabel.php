<?php

namespace App\Support;

/**
 * عرض أسماء الكتالوج المخزّنة بالعربية داخل النسخة الإنجليزية.
 */
final class LocalizedCatalogLabel
{
    /** @var array<string, string> */
    private const PHRASES = [
        'رياض الأطفال' => 'Kindergarten',
        'المرحلة الابتدائية' => 'Primary school',
        'الابتدائية' => 'Primary',
        'ابتدائي' => 'Primary',
        'المرحلة المتوسطة' => 'Middle school',
        'المتوسطة' => 'Middle school',
        'متوسط' => 'Middle school',
        'المرحلة الثانوية' => 'Secondary school',
        'الثانوية' => 'Secondary',
        'ثانوي' => 'Secondary',
        'المنهج الدولي' => 'International curriculum',
        'مناهج دولية' => 'International curricula',
        'دولي' => 'International',
        'الجامعة' => 'University',
        'جامعي' => 'University',
        'اختبار القياس' => 'Qiyas test',
        'القياس' => 'Qiyas',
        'قدرات' => 'Aptitude',
        'المهارات' => 'Skills',
        'مهارات' => 'Skills',
        'لغة عربية' => 'Arabic',
        'اللغة العربية' => 'Arabic language',
        'رياضيات' => 'Mathematics',
        'علوم' => 'Science',
        'لغة إنجليزية' => 'English',
        'اللغة الإنجليزية' => 'English language',
        'اجتماعيات' => 'Social studies',
        'فيزياء' => 'Physics',
        'كيمياء' => 'Chemistry',
        'أحياء' => 'Biology',
        'حصة' => 'lesson',
        'حصص' => 'lessons',
        'باقة' => 'package',
    ];

    public static function text(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || app()->getLocale() !== 'en') {
            return $value;
        }
        if (! preg_match('/\p{Arabic}/u', $value)) {
            return $value;
        }

        $out = $value;
        foreach (self::PHRASES as $arabic => $english) {
            $out = str_replace($arabic, $english, $out);
        }

        return trim(preg_replace('/\s+/u', ' ', $out) ?? $out);
    }
}
