<?php

namespace App\Support;

use App\Models\CourseSection;
use App\Models\CurriculumItem;
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

        $section = CourseSection::query()
            ->where('advanced_course_id', $courseId)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        if (! $section) {
            $section = CourseSection::create([
                'advanced_course_id' => $courseId,
                'title' => $defaultSectionTitle,
                'order' => 1,
                'is_active' => true,
            ]);
        }

        $itemType = $item::class;
        $itemId = (int) $item->getKey();

        $already = CurriculumItem::query()
            ->where('course_section_id', $section->id)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
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
}
