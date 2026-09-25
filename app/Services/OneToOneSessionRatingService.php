<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\OneToOneSessionRating;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class OneToOneSessionRatingService
{
    public static function isReady(): bool
    {
        return Schema::hasTable('one_to_one_session_ratings');
    }

    public static function forSession(OneToOneSession $session): ?OneToOneSessionRating
    {
        if (! self::isReady()) {
            return null;
        }

        return OneToOneSessionRating::query()
            ->where('one_to_one_session_id', $session->id)
            ->first();
    }

    public static function studentMustRate(OneToOneSession $session, ?User $student = null): bool
    {
        if (! self::isReady()) {
            return false;
        }
        if ($session->status !== OneToOneSession::STATUS_COMPLETED) {
            return false;
        }
        if ($student && (int) $session->student_id !== (int) $student->id) {
            return false;
        }

        return self::forSession($session) === null;
    }

    /**
     * @param  array{rating:int,comment?:string|null}  $data
     */
    public static function rate(OneToOneSession $session, User $student, array $data): OneToOneSessionRating
    {
        if (! self::isReady()) {
            throw new InvalidArgumentException('ميزة التقييم غير مفعّلة بعد.');
        }
        if ((int) $session->student_id !== (int) $student->id) {
            throw new InvalidArgumentException('غير مصرح بتقييم هذه الحصة.');
        }
        if ($session->status !== OneToOneSession::STATUS_COMPLETED) {
            throw new InvalidArgumentException('يمكن تقييم الحصة بعد اكتمالها فقط.');
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('اختر تقييماً من 1 إلى 5.');
        }

        $comment = isset($data['comment']) ? trim((string) $data['comment']) : null;
        if ($comment === '') {
            $comment = null;
        }

        return DB::transaction(function () use ($session, $student, $rating, $comment) {
            $existing = OneToOneSessionRating::query()
                ->where('one_to_one_session_id', $session->id)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                throw new InvalidArgumentException('تم تقييم هذه الحصة مسبقاً.');
            }

            $row = OneToOneSessionRating::create([
                'one_to_one_session_id' => $session->id,
                'student_id' => $student->id,
                'instructor_id' => $session->instructor_id,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            if (class_exists(Notification::class) && $session->instructor_id) {
                try {
                    Notification::create([
                        'user_id' => $session->instructor_id,
                        'sender_id' => $student->id,
                        'type' => 'general',
                        'title' => 'تقييم جديد بعد الحصة',
                        'message' => $student->name.' قيّم الحصة بـ '.$rating.'/5',
                        'action_url' => route('instructor.one-to-one-sessions.show', $session),
                        'action_text' => 'عرض الحصة',
                        'priority' => 'normal',
                        'audience' => 'instructor',
                        'is_read' => false,
                    ]);
                } catch (\Throwable) {
                }
            }

            return $row;
        });
    }

    public static function notifyStudentToRate(OneToOneSession $session): void
    {
        if (! self::isReady() || ! $session->student_id || ! class_exists(Notification::class)) {
            return;
        }

        try {
            Notification::create([
                'user_id' => $session->student_id,
                'sender_id' => $session->instructor_id,
                'type' => 'reminder',
                'title' => 'قيّم حصتك الآن',
                'message' => 'الحصة انتهت — تقييمك إلزامي ويساعد في تحسين التجربة.',
                'action_url' => route('student.one-to-one-sessions.show', $session),
                'action_text' => 'تقييم الحصة',
                'priority' => 'high',
                'audience' => 'student',
                'is_read' => false,
            ]);
        } catch (\Throwable) {
        }
    }
}
