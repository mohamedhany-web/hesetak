<?php

namespace App\Support;

/**
 * Egyptian mobile numbers as 11 digits: 01[0125]XXXXXXXX.
 */
final class EgyptMobilePhone
{
    public const PATTERN = '/^01[0125]\d{8}$/';

    public const MESSAGE = 'رقم الجوال يجب أن يكون 11 رقماً بصيغة مصرية (مثال: 01012345678).';

    /**
     * Normalize to 01XXXXXXXXX or null if not a valid Egypt mobile.
     */
    public static function normalize(?string $phone): ?string
    {
        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if (str_starts_with($digits, '20') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        if (! preg_match(self::PATTERN, $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * @return list<string|\Closure>
     */
    public static function rules(bool $required = true): array
    {
        $base = $required ? ['required'] : ['nullable'];

        return array_merge($base, [
            'string',
            'max:40',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                if (self::normalize(is_string($value) ? $value : (string) $value) === null) {
                    $fail(self::MESSAGE);
                }
            },
        ]);
    }
}
