<?php

namespace App\Support;

use App\Models\User;

/**
 * إخفاء البريد/الجوال في دليل المستخدمين عمن لا يملك صلاحية الاطلاع الحساس.
 */
final class UserContactPrivacy
{
    public static function canViewContacts(?User $actor): bool
    {
        if (! $actor) {
            return false;
        }

        if (in_array((string) $actor->role, ['admin', 'super_admin'], true) || $actor->isSuperAdmin()) {
            return true;
        }

        // صلاحيات إدارة النظام الحساسة (ليست مجرد manage.users التشغيلي)
        return $actor->hasPermission('manage.user-permissions')
            || $actor->hasPermission('users.permissions')
            || $actor->hasPermission('manage.permissions');
    }

    public static function maskEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '—';
        }

        [$local, $domain] = explode('@', $email, 2);
        $keep = max(1, (int) floor(mb_strlen($local) / 3));

        return mb_substr($local, 0, $keep).str_repeat('*', max(3, mb_strlen($local) - $keep)).'@'.$domain;
    }

    public static function maskPhone(?string $phone): string
    {
        $phone = preg_replace('/\s+/', '', (string) $phone) ?: '';
        if ($phone === '') {
            return '—';
        }

        $len = mb_strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return mb_substr($phone, 0, 2).str_repeat('*', max(3, $len - 4)).mb_substr($phone, -2);
    }

    public static function displayEmail(?User $actor, ?string $email): string
    {
        $email = trim((string) $email);

        return self::canViewContacts($actor)
            ? ($email !== '' ? $email : '—')
            : self::maskEmail($email);
    }

    public static function displayPhone(?User $actor, ?string $phone): string
    {
        $phone = trim((string) $phone);

        return self::canViewContacts($actor)
            ? ($phone !== '' ? $phone : '—')
            : self::maskPhone($phone);
    }
}
