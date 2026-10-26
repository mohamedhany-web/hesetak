<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One active browser session per pure student account (not employees/instructors/admins).
 * Relies on SESSION_DRIVER=database when available, plus a cache token for enforcement.
 */
final class StudentExclusiveSession
{
    private const CACHE_TTL_DAYS = 7;

    public static function appliesTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_employee || $user->isEmployee()) {
            return false;
        }

        if (method_exists($user, 'isInstructor') && $user->isInstructor()) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'admin', 'instructor', 'teacher', 'parent'], true)) {
            return false;
        }

        return $user->role === 'student';
    }

    public static function cacheKey(int $userId): string
    {
        return "user_session_{$userId}";
    }

    /**
     * Mark $sessionId as the only valid session and drop other DB session rows.
     */
    public static function claim(User $user, string $sessionId): void
    {
        if (! self::appliesTo($user) || $sessionId === '') {
            return;
        }

        Cache::put(self::cacheKey((int) $user->id), $sessionId, now()->addDays(self::CACHE_TTL_DAYS));
        self::deleteOtherDatabaseSessions((int) $user->id, $sessionId);
    }

    public static function forget(User $user): void
    {
        Cache::forget(self::cacheKey((int) $user->id));
    }

    public static function isCurrent(User $user, string $sessionId): bool
    {
        $stored = Cache::get(self::cacheKey((int) $user->id));

        return ! $stored || $stored === $sessionId;
    }

    public static function refreshTtl(User $user, string $sessionId): void
    {
        Cache::put(self::cacheKey((int) $user->id), $sessionId, now()->addDays(self::CACHE_TTL_DAYS));
    }

    private static function deleteOtherDatabaseSessions(int $userId, string $keepSessionId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = (string) config('session.table', 'sessions');
        if ($table === '' || ! Schema::hasTable($table)) {
            return;
        }

        try {
            DB::table($table)
                ->where('user_id', $userId)
                ->where('id', '!=', $keepSessionId)
                ->delete();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
