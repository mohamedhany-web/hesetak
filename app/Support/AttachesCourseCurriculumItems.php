<?php

namespace App\Support;

use App\Models\AdvancedExam;
use App\Models\Assignment;
use App\Models\CourseSection;
use App\Models\CurriculumItem;
use App\Models\Exam;
use App\Models\Lecture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * يربط عنصراً (واجب، امتحان، محاضرة…) بقسم المنهج حتى يظهر للطالب داخل الكورس.
 */
final class AttachesCourseCurriculumItems
{
    public static function attach(int $courseId, Model $item, string $defaultSectionTitle = 'محتوى الكورس'): void
    {
        if (! Schema::hasTable('course_sections') || ! Schema::hasTable('curriculum_items')) {
            return;
        }

        $section = self::ensureSection($courseId, $defaultSectionTitle);
        if (! $section) {
            return;
        }

        $itemType = $item::class;
        $itemId = (int) $item->getKey();

        $already = CurriculumItem::query()
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->whereHas('section', fn ($q) => $q->where('advanced_course_id', $courseId))
            ->exists();

        if ($already) {
            return;
        }

        $lastOrder = (int) ($section->items()->max('order') ?? 0);
        CurriculumItem::create([
            'course_section_id' => $section->id,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'order' => $lastOrder + 1,
            'is_active' => true,
        ]);
    }

    /**
     * مزامنة محاضرات/واجبات/امتحانات الكورس القديمة التي لم تُربط بعد بعناصر المنهج.
     */
    public static function syncCourseContent(int $courseId): void
    {
        if ($courseId <= 0 || ! Schema::hasTable('course_sections') || ! Schema::hasTable('curriculum_items')) {
            return;
        }

        if (Schema::hasTable('lectures')) {
            Lecture::query()
                ->where('course_id', $courseId)
                ->orderBy('id')
                ->each(fn (Lecture $lecture) => self::attach($courseId, $lecture, 'المحاضرات'));
        }

        if (Schema::hasTable('assignments')) {
            Assignment::query()
                ->where(function ($q) use ($courseId) {
                    if (Schema::hasColumn('assignments', 'advanced_course_id')) {
                        $q->orWhere('advanced_course_id', $courseId);
                    }
                    if (Schema::hasColumn('assignments', 'course_id')) {
                        $q->orWhere('course_id', $courseId);
                    }
                })
                ->orderBy('id')
                ->each(fn (Assignment $assignment) => self::attach($courseId, $assignment, 'الواجبات'));
        }

        if (Schema::hasTable('advanced_exams')) {
            AdvancedExam::query()
                ->where('advanced_course_id', $courseId)
                ->orderBy('id')
                ->each(fn (AdvancedExam $exam) => self::attach($courseId, $exam, 'الامتحانات'));
        }

        if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'advanced_course_id')) {
            Exam::query()
                ->where('advanced_course_id', $courseId)
                ->orderBy('id')
                ->each(fn (Exam $exam) => self::attach($courseId, $exam, 'الامتحانات'));
        }
    }

    private static function ensureSection(int $courseId, string $defaultSectionTitle): ?CourseSection
    {
        $section = CourseSection::query()
            ->where('advanced_course_id', $courseId)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        if ($section) {
            return $section;
        }

        return CourseSection::create([
            'advanced_course_id' => $courseId,
            'title' => $defaultSectionTitle,
            'order' => 1,
            'is_active' => true,
        ]);
    }
}
