<?php

namespace App\Support;

use App\Models\InstructorProfile;
use App\Models\User;
use App\Services\PublicInstructorDirectory;
use Illuminate\Validation\ValidationException;

/**
 * بوابة نشر الملف التعريفي: مطابقة كاملة + بلا محتوى تجريبي.
 * تُستدعى من الموافقة الإدارية ومن حفظ أي حالة «معتمد».
 */
final class InstructorProfilePublication
{
    /**
     * @return array{ok: bool, missing: list<string>, match: array<string, mixed>, is_demo: bool}
     */
    public static function evaluate(InstructorProfile $profile, ?User $user = null): array
    {
        $user = $user ?? $profile->user;
        $match = InstructorMatchCompleteness::evaluate($profile, $user);
        $isDemo = PublicInstructorDirectory::profileLooksLikeDemo($profile);

        $missing = $match['missing'];
        if ($isDemo) {
            $missing[] = 'محتوى تجريبي (عنوان/نبذة/مهارات/اسم مثل اختبار أو test)';
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
            'match' => $match,
            'is_demo' => $isDemo,
        ];
    }

    public static function isPublishable(InstructorProfile $profile, ?User $user = null): bool
    {
        return self::evaluate($profile, $user)['ok'];
    }

    /**
     * يمنع اعتماد أو الإبقاء على «معتمد» إذا نقصت المطابقة أو وُجد محتوى تجريبي.
     *
     * @throws ValidationException
     */
    public static function assertPublishable(InstructorProfile $profile, ?User $user = null): void
    {
        $result = self::evaluate($profile, $user);
        if ($result['ok']) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => 'لا يمكن النشر كمعتمد — '.implode(' · ', $result['missing']),
        ]);
    }

    /**
     * اعتماد الملف بعد اجتياز البوابة.
     *
     * @throws ValidationException
     */
    public static function approve(InstructorProfile $profile, int $reviewerId): InstructorProfile
    {
        if ($profile->status !== InstructorProfile::STATUS_PENDING_REVIEW) {
            throw ValidationException::withMessages([
                'status' => 'يمكن الموافقة فقط على الملفات قيد المراجعة.',
            ]);
        }

        $profile->loadMissing('user.teachingLearningPaths');
        self::assertPublishable($profile);

        $profile->forceFill([
            'status' => InstructorProfile::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewerId,
            'rejection_reason' => null,
        ])->save();

        return $profile->refresh();
    }

    /**
     * إيقاف نشر ملف معتمد ناقص/تجريبي بإعادته لقيد المراجعة.
     */
    public static function demoteIncompleteApproved(InstructorProfile $profile, ?string $reason = null): bool
    {
        if ($profile->status !== InstructorProfile::STATUS_APPROVED) {
            return false;
        }

        $profile->loadMissing('user.teachingLearningPaths');
        if (self::isPublishable($profile)) {
            return false;
        }

        $profile->forceFill([
            'status' => InstructorProfile::STATUS_PENDING_REVIEW,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_reason' => $reason ?? 'أُوقف النشر تلقائياً: ملف المطابقة ناقص أو يحتوي بيانات تجريبية.',
        ])->save();

        return true;
    }
}
