<?php

namespace App\Support;

/**
 * يمنع ظهور بيانات تجريبية على الواجهة العامة (أسماء، وصف، مهارات، آراء، إعلانات، باقات).
 */
final class PublicDemoContent
{
    /** @var list<string> */
    private const EXACT = [
        'test',
        'website',
        'تجريبي',
        'اختبار',
        'test، test',
        'test, test',
        'test test',
    ];

    public static function isPlaceholder(?string $text): bool
    {
        $raw = trim((string) $text);
        if ($raw === '') {
            return false;
        }

        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $raw) ?? $raw);
        $normalized = str_replace(['،'], ',', $normalized);

        foreach (self::EXACT as $needle) {
            if ($normalized === mb_strtolower($needle)) {
                return true;
            }
        }

        if (preg_match('/^(test|website)([,\s]+(test|website))+$/i', $normalized)) {
            return true;
        }

        if (preg_match('/^تجريبي([.\s].*)?$/u', $normalized)) {
            return true;
        }

        if (mb_strlen($raw) <= 48 && preg_match('/\btest\b/i', $raw)) {
            return true;
        }

        if (mb_strlen($raw) <= 48 && preg_match('/\bwebsite\b/i', $raw)) {
            return true;
        }

        if (mb_strlen($raw) <= 64 && str_contains($normalized, 'تجريبي')) {
            return true;
        }

        if (mb_strlen($raw) <= 48 && preg_match('/^اختبار([.\s].*)?$/u', $normalized)) {
            return true;
        }

        if (mb_strlen($raw) <= 48 && preg_match('/\bاختبار\b/u', $normalized)) {
            return true;
        }

        return false;
    }

    /**
     * @param  iterable<int, mixed>  $values
     */
    public static function anyPlaceholder(iterable $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (self::anyPlaceholder($value)) {
                    return true;
                }
                continue;
            }
            if (self::isPlaceholder(is_scalar($value) ? (string) $value : '')) {
                return true;
            }
        }

        return false;
    }

    public static function recordLooksLikeDemo(iterable $values): bool
    {
        return self::anyPlaceholder($values);
    }
}
