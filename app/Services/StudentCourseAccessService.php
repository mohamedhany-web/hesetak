<?php

namespace App\Services;

use App\Models\AdvancedCourse;
use App\Models\OneToOneSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * مصدر واحد لتحديد الكورسات التي يرى الطالب محتواها (محاضرات / واجبات / امتحانات / كورساتي):
 *  - كورسات التسجيل النشط (student_course_enrollments)
 *  - كورسات الحصص الفردية المرتبطة به (طلاب الباقات والحصص الخاصة بلا تسجيل كورس)
 */
final class StudentCourseAccessService
{
    /**
     * @return Collection<int, int>
     */
    public static function courseIds(User $user): Collection
    {
        $enrolledIds = Schema::hasTable('student_course_enrollments')
            ? $user->activeCourses()->pluck('advanced_courses.id')
            : collect();

        return $enrolledIds
            ->merge(self::oneToOneCourseIds($user))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    public static function canAccessCourse(User $user, int|string|null $courseId): bool
    {
        $courseId = (int) $courseId;

        return $courseId > 0 && self::courseIds($user)->contains($courseId);
    }

    /**
     * @return Builder<AdvancedCourse>
     */
    public static function accessibleCourses(User $user): Builder
    {
        return AdvancedCourse::query()->whereIn('advanced_courses.id', self::courseIds($user));
    }

    /**
     * @return Collection<int, int>
     */
    private static function oneToOneCourseIds(User $user): Collection
    {
        if (! Schema::hasTable('one_to_one_sessions')) {
            return collect();
        }

        return OneToOneSession::query()
            ->where('student_id', $user->id)
            ->where('status', '!=', OneToOneSession::STATUS_CANCELLED)
            ->whereNotNull('advanced_course_id')
            ->pluck('advanced_course_id');
    }
}
