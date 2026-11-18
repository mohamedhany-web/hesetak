<?php

namespace App\Support;

/**
 * اختيار الزائر في صفحة الباقات (عدد الحصص / حصة مستعجلة) محفوظ في الجلسة
 * ليُستخدم نفس السعر المعروض عند إنشاء الطلب في كل بوابات الدفع.
 */
final class PackageCheckoutSelection
{
    public static function remember(int $packageId, ?int $sessions, bool $urgent): void
    {
        session([
            'package_checkout_selection.'.$packageId => [
                'sessions' => $sessions !== null && $sessions > 0 ? $sessions : null,
                'urgent' => $urgent,
            ],
        ]);
    }

    /**
     * @return array{sessions: int|null, urgent: bool}
     */
    public static function recall(int $packageId): array
    {
        $stored = session('package_checkout_selection.'.$packageId);
        if (! is_array($stored)) {
            return ['sessions' => null, 'urgent' => false];
        }

        $sessions = isset($stored['sessions']) ? (int) $stored['sessions'] : 0;

        return [
            'sessions' => $sessions > 0 ? $sessions : null,
            'urgent' => (bool) ($stored['urgent'] ?? false),
        ];
    }
}
