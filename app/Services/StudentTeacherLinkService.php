<?php

namespace App\Services;

use App\Models\AdvancedCourse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * روابط طالب ↔ معلم (كورسات، مجموعات، حصص خاصة، تعيينات، entitlements).
 */
class StudentTeacherLinkService
{
    /**
     * معرفات المعلمين المرتبطين بالطالب حالياً.
     *
     * @return list<int>
     */
    public static function instructorIdsForStudent(User $student): array
    {
        $ids = collect();

        if (Schema::hasTable('student_course_enrollments') && Schema::hasTable('advanced_courses')) {
            $courseIds = DB::table('student_course_enrollments')
                ->where('user_id', $student->id)
                ->when(Schema::hasColumn('student_course_enrollments', 'status'), fn ($q) => $q->where('status', 'active'))
                ->pluck('advanced_course_id');

            if ($courseIds->isNotEmpty()) {
                $ids = $ids->merge(
                    AdvancedCourse::query()
                        ->whereIn('id', $courseIds)
                        ->whereNotNull('instructor_id')
                        ->pluck('instructor_id')
                );
            }
        }

        if (Schema::hasTable('online_enrollments') && Schema::hasTable('advanced_courses')) {
            $onlineCourseQuery = DB::table('online_enrollments')
                ->where('user_id', $student->id);
            if (Schema::hasColumn('online_enrollments', 'is_active')) {
                $onlineCourseQuery->where('is_active', true);
            }
            $onlineCourseIds = $onlineCourseQuery->pluck('advanced_course_id');

            if ($onlineCourseIds->isNotEmpty()) {
                $ids = $ids->merge(
                    AdvancedCourse::query()
                        ->whereIn('id', $onlineCourseIds)
                        ->whereNotNull('instructor_id')
                        ->pluck('instructor_id')
                );
            }
        }

        if (Schema::hasTable('tutoring_cohort_enrollments') && Schema::hasTable('tutoring_groups')) {
            $groupIds = DB::table('tutoring_cohort_enrollments as e')
                ->join('tutoring_group_cohorts as c', 'c.id', '=', 'e.tutoring_group_cohort_id')
                ->where('e.user_id', $student->id)
                ->when(Schema::hasColumn('tutoring_cohort_enrollments', 'status'), fn ($q) => $q->where('e.status', 'active'))
                ->pluck('c.tutoring_group_id');

            if ($groupIds->isNotEmpty()) {
                $ids = $ids->merge(
                    DB::table('tutoring_groups')
                        ->whereIn('id', $groupIds)
                        ->whereNotNull('instructor_id')
                        ->pluck('instructor_id')
                );
            }
        }

        if (Schema::hasTable('one_to_one_sessions')) {
            $ids = $ids->merge(
                DB::table('one_to_one_sessions')
                    ->where('student_id', $student->id)
                    ->whereNotNull('instructor_id')
                    ->distinct()
                    ->pluck('instructor_id')
            );
        }

        if (Schema::hasTable('tutoring_bookings')) {
            $ids = $ids->merge(
                DB::table('tutoring_bookings')
                    ->where('user_id', $student->id)
                    ->whereNotNull('instructor_id')
                    ->distinct()
                    ->pluck('instructor_id')
            );
        }

        // تعيين إداري نشط (بريفيت / عام / كورسات)
        if (Schema::hasTable('student_instructor_assignments')) {
            $assignmentQ = DB::table('student_instructor_assignments')
                ->where('student_id', $student->id)
                ->whereNotNull('instructor_id');

            if (Schema::hasColumn('student_instructor_assignments', 'status')) {
                $assignmentQ->where('status', 'active');
            }

            $ids = $ids->merge($assignmentQ->distinct()->pluck('instructor_id'));
        }

        // entitlement نشط مرتبط بمجموعة فيها معلم (اشتراك خدمة بدون enrollment صف منفصل)
        if (Schema::hasTable('student_service_entitlements')
            && Schema::hasTable('tutoring_groups')
            && Schema::hasColumn('student_service_entitlements', 'tutoring_group_id')
        ) {
            StudentEntitlementService::expireStaleForUser((int) $student->id);

            $groupIds = DB::table('student_service_entitlements')
                ->where('user_id', $student->id)
                ->whereNotNull('tutoring_group_id')
                ->when(
                    Schema::hasColumn('student_service_entitlements', 'status'),
                    fn ($q) => $q->where('status', 'active')
                )
                ->pluck('tutoring_group_id');

            if ($groupIds->isNotEmpty()) {
                $ids = $ids->merge(
                    DB::table('tutoring_groups')
                        ->whereIn('id', $groupIds)
                        ->whereNotNull('instructor_id')
                        ->pluck('instructor_id')
                );
            }
        }

        return $ids->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    public static function studentStudiesWith(User $student, int $instructorId): bool
    {
        return in_array($instructorId, self::instructorIdsForStudent($student), true);
    }
}
