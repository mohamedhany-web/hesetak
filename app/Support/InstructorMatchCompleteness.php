<?php

namespace App\Support;

use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class InstructorMatchCompleteness
{
    /**
     * @return array{ok: bool, missing: list<string>, checklist: array<string, bool>}
     */
    public static function evaluate(InstructorProfile $profile, ?User $user = null): array
    {
        $user = $user ?? $profile->user;
        $headline = trim((string) ($profile->headline_clean ?: $profile->headline));
        $bio = trim((string) ($profile->bio_clean ?: $profile->bio));
        $skillsCount = count($profile->skills_list);
        $curriculumOk = count($profile->curriculumTypeKeys()) >= 1;
        $subjectsOk = count($profile->teachingSubjectIds()) >= 1;

        $yearsOk = false;
        if ($user && Schema::hasTable('academic_year_instructors')) {
            $yearsOk = $user->teachingLearningPaths()->exists();
        }
        if (! $yearsOk && is_array($user?->private_teaching_meta ?? null)) {
            $yearIds = $user->private_teaching_meta['year_ids'] ?? [];
            $yearsOk = is_array($yearIds) && count(array_filter($yearIds)) >= 1;
        }

        $stageOrSubjectOk = $yearsOk || $subjectsOk;

        $checklist = [
            'headline' => $headline !== '',
            'bio' => $bio !== '',
            'skills' => $skillsCount >= 3,
            'curriculum_types' => $curriculumOk,
            'teaching_stage_or_subject' => $stageOrSubjectOk,
        ];

        $labels = [
            'headline' => 'عنوان تعريفي',
            'bio' => 'نبذة تعريفية',
            'skills' => '٣ مهارات على الأقل',
            'curriculum_types' => 'نوع منهج واحد على الأقل',
            'teaching_stage_or_subject' => 'مرحلة تدريس أو مادة واحدة على الأقل',
        ];

        $missing = [];
        foreach ($checklist as $key => $ok) {
            if (! $ok) {
                $missing[] = $labels[$key] ?? $key;
            }
        }

        return [
            'ok' => $missing === [],
            'missing' => $missing,
            'checklist' => $checklist,
            'labels' => $labels,
        ];
    }

    public static function isComplete(InstructorProfile $profile, ?User $user = null): bool
    {
        return self::evaluate($profile, $user)['ok'];
    }
}
