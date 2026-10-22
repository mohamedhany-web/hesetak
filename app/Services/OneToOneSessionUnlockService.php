<?php

namespace App\Services;

use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * فتح حصص 1:1 للطالب بالتسلسل — الحصة الحالية فقط (أو التي فتحها الأدمن يدوياً).
 */
class OneToOneSessionUnlockService
{
    /**
     * @return Builder<OneToOneSession>
     */
    public static function seriesScope(OneToOneSession $session): Builder
    {
        $query = OneToOneSession::query()->where('student_id', $session->student_id);

        if (Schema::hasColumn('one_to_one_sessions', 'series_id') && filled($session->series_id)) {
            return $query->where('series_id', $session->series_id);
        }

        if (Schema::hasColumn('one_to_one_sessions', 'student_course_enrollment_id') && $session->student_course_enrollment_id) {
            return $query->where('student_course_enrollment_id', $session->student_course_enrollment_id);
        }

        $query->where('instructor_id', $session->instructor_id);

        if (Schema::hasColumn('one_to_one_sessions', 'advanced_course_id')) {
            if (filled($session->advanced_course_id)) {
                $query->where('advanced_course_id', $session->advanced_course_id);
            } else {
                $query->whereNull('advanced_course_id');
            }
        }

        return $query;
    }

    /** دقائق سماح بعد نهاية الحصة قبل أن تتوقف عن حجب الحصة التالية. */
    public const STALE_GRACE_MINUTES = 60;

    public static function currentSessionInSeries(OneToOneSession $session): ?OneToOneSession
    {
        return self::seriesScope($session)
            ->whereNotIn('status', [
                OneToOneSession::STATUS_COMPLETED,
                OneToOneSession::STATUS_CANCELLED,
            ])
            ->with('classroomMeeting')
            ->orderBy('session_number')
            ->orderBy('id')
            ->get()
            ->first(fn (OneToOneSession $candidate) => ! self::isStale($candidate));
    }

    /**
     * حصة فات وقتها أو أُنهيت غرفتها دون أن تُعلَّم مكتملة (المعلم أغلق التبويب، أو لم يحضر أحد)
     * لا يجب أن تقفل باقي حصص السلسلة على الطالب.
     */
    public static function isStale(OneToOneSession $session): bool
    {
        if ($session->classroomMeeting?->ended_at) {
            return true;
        }

        if (! $session->scheduled_at) {
            return false;
        }

        $duration = (int) ($session->duration_minutes ?: 50);

        return $session->scheduled_at->copy()
            ->addMinutes($duration + self::STALE_GRACE_MINUTES)
            ->isPast();
    }

    public static function isManuallyUnlocked(OneToOneSession $session): bool
    {
        if (! Schema::hasColumn('one_to_one_sessions', 'student_unlocked_at')) {
            return false;
        }

        return filled($session->student_unlocked_at);
    }

    public static function canStudentJoin(OneToOneSession $session, User $user): bool
    {
        if (! $user->isStudent() || (int) $session->student_id !== (int) $user->id) {
            return true;
        }

        if ($session->status !== OneToOneSession::STATUS_SCHEDULED) {
            return false;
        }

        if (! self::sessionHasJoinableMeeting($session)) {
            return false;
        }

        if (self::isManuallyUnlocked($session)) {
            return true;
        }

        $current = self::currentSessionInSeries($session);

        return $current !== null && (int) $current->id === (int) $session->id;
    }

    public static function lockReason(OneToOneSession $session, User $user): ?string
    {
        if (self::canStudentJoin($session, $user)) {
            return null;
        }

        if ($session->status !== OneToOneSession::STATUS_SCHEDULED) {
            return 'هذه الحصة غير متاحة للدخول حالياً.';
        }

        if (! self::sessionHasJoinableMeeting($session)) {
            return 'لم تُنشأ غرفة الاجتماع بعد.';
        }

        $current = self::currentSessionInSeries($session);
        if ($current && (int) $current->id !== (int) $session->id) {
            return 'أكمل الحصة رقم '.$current->session_number.' أولاً — الحصص تُفتح بالتسلسل.';
        }

        return 'هذه الحصة مقفلة حالياً.';
    }

    public static function nextJoinableForStudent(User $student): ?OneToOneSession
    {
        return OneToOneSession::query()
            ->where('student_id', $student->id)
            ->where('status', OneToOneSession::STATUS_SCHEDULED)
            ->whereNotNull('classroom_meeting_id')
            ->with(['course:id,title', 'instructor:id,name', 'classroomMeeting'])
            ->orderBy('scheduled_at')
            ->orderBy('session_number')
            ->get()
            ->first(fn (OneToOneSession $session) => self::canStudentJoin($session, $student));
    }

    public static function adminUnlockForStudent(OneToOneSession $session, User $admin): void
    {
        $session->update([
            'student_unlocked_at' => now(),
            'student_unlocked_by_user_id' => $admin->id,
        ]);
    }

    public static function adminRevokeUnlock(OneToOneSession $session): void
    {
        $session->update([
            'student_unlocked_at' => null,
            'student_unlocked_by_user_id' => null,
        ]);
    }

    private static function sessionHasJoinableMeeting(OneToOneSession $session): bool
    {
        if ($session->classroom_meeting_id) {
            return true;
        }

        if ($session->relationLoaded('classroomMeeting') && $session->classroomMeeting) {
            return true;
        }

        return $session->classroomMeeting()->exists();
    }
}
