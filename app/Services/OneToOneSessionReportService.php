<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\OneToOneSession;
use App\Models\OneToOneSessionReport;
use App\Models\User;
use InvalidArgumentException;

class OneToOneSessionReportService
{
    /**
     * يحفظ (أو يعدّل) تقرير المعلم للطالب عن حصة مكتملة، ثم يُشعِر الطالب ويُسجّل مستحق المعلم.
     *
     * @param  array{summary: string, topics_covered?: ?string, performance_level?: ?string, homework?: ?string, next_steps?: ?string}  $data
     */
    public static function submit(OneToOneSession $session, User $instructor, array $data): OneToOneSessionReport
    {
        if ((int) $session->instructor_id !== (int) $instructor->id) {
            throw new InvalidArgumentException('هذه الحصة غير مخصصة لك.');
        }
        if ($session->status !== OneToOneSession::STATUS_COMPLETED) {
            throw new InvalidArgumentException('يمكن رفع التقرير بعد اكتمال الحصة فقط.');
        }

        $existing = $session->teacherReport()->first();
        $payload = [
            'instructor_id' => $instructor->id,
            'student_id' => $session->student_id,
            'summary' => trim((string) $data['summary']),
            'topics_covered' => self::clean($data['topics_covered'] ?? null),
            'performance_level' => $data['performance_level'] ?? null,
            'homework' => self::clean($data['homework'] ?? null),
            'next_steps' => self::clean($data['next_steps'] ?? null),
        ];

        $report = $existing
            ? tap($existing)->update($payload + ['student_read_at' => null])
            : OneToOneSessionReport::create($payload + [
                'one_to_one_session_id' => $session->id,
                'submitted_at' => now(),
            ]);

        self::notifyStudent($session, $report, (bool) $existing);
        OneToOneSessionService::accrueInstructorEarningWhenReported($session);

        return $report->fresh();
    }

    public static function markReadByStudent(OneToOneSessionReport $report, User $student): void
    {
        if ((int) $report->student_id !== (int) $student->id || $report->student_read_at) {
            return;
        }
        $report->forceFill(['student_read_at' => now()])->save();
    }

    /** تذكير المعلم فور اكتمال الحصة بأن رفع التقرير إلزامي. */
    public static function remindInstructor(OneToOneSession $session): void
    {
        if (! $session->instructor_id) {
            return;
        }

        self::notify(
            (int) $session->instructor_id,
            $session->student_id ? (int) $session->student_id : null,
            'ارفع تقرير الحصة (إلزامي)',
            'اكتملت حصتك مع '.($session->student?->name ?? 'الطالب').' — ارفع تقريرها للطالب ليُسجَّل مستحقك.',
            route('instructor.session-reports.edit', $session),
            'كتابة التقرير',
            'instructor'
        );
    }

    private static function notifyStudent(OneToOneSession $session, OneToOneSessionReport $report, bool $updated): void
    {
        self::notify(
            (int) $session->student_id,
            (int) $session->instructor_id,
            $updated ? 'تم تحديث تقرير حصتك' : 'وصلك تقرير الحصة',
            'كتب المعلم '.($session->instructor?->name ?? '').' تقريراً عن حصتك الأخيرة.',
            route('student.session-reports.show', $report),
            'عرض التقرير',
            'student'
        );
    }

    private static function notify(int $userId, ?int $senderId, string $title, string $message, string $url, string $actionText, string $audience): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'sender_id' => $senderId,
                'type' => 'general',
                'title' => $title,
                'message' => $message,
                'action_url' => $url,
                'action_text' => $actionText,
                'priority' => 'high',
                'audience' => $audience,
                'is_read' => false,
            ]);
        } catch (\Throwable) {
            // الإشعار لا يُفشل العملية الأساسية
        }
    }

    private static function clean(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
