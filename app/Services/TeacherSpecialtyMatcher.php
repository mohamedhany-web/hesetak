<?php

namespace App\Services;

use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Support\HesetakMatchCatalog;
use Illuminate\Validation\ValidationException;

class TeacherSpecialtyMatcher
{
    /**
     * @return array{complete: bool, missing: list<string>, subject_ids: list<int>, year_ids: list<int>, curriculum_types: list<string>}
     */
    public static function summarize(TutorApplication|InstructorProfile $source): array
    {
        $subjectIds = self::subjectIds($source);
        $yearIds = self::yearIds($source);
        $types = self::curriculumTypes($source);

        $missing = [];
        if ($subjectIds === []) {
            $missing[] = "\u{0645}\u{0627}\u{062F}\u{0629}";
        }
        if ($yearIds === [] && $subjectIds === []) {
            $missing[] = "\u{0645}\u{0631}\u{062D}\u{0644}\u{0629}";
        }
        if ($types === []) {
            $missing[] = "\u{0646}\u{0648}\u{0639} \u{0645}\u{0646}\u{0647}\u{062C}";
        }

        return [
            'complete' => $missing === [],
            'missing' => $missing,
            'subject_ids' => $subjectIds,
            'year_ids' => $yearIds,
            'curriculum_types' => $types,
        ];
    }

    public static function assertComplete(TutorApplication|InstructorProfile $source, ?string $message = null): void
    {
        if (! TutorHiringSetting::bool('require_specialty', true)) {
            return;
        }

        $summary = self::summarize($source);
        if ($summary['complete']) {
            return;
        }

        throw ValidationException::withMessages([
            'specialty' => $message ?: ('Specialty incomplete: '.implode(' + ', $summary['missing']).'.'),
        ]);
    }

    public static function matches(
        InstructorProfile|TutorApplication $source,
        ?int $subjectId = null,
        ?int $yearId = null,
        ?string $curriculumType = null,
    ): bool {
        $summary = self::summarize($source);

        if ($subjectId !== null && $subjectId > 0) {
            if ($summary['subject_ids'] !== [] && ! in_array($subjectId, $summary['subject_ids'], true)) {
                return false;
            }
            if ($yearId === null) {
                $subject = AcademicSubject::query()->find($subjectId);
                if ($subject?->academic_year_id) {
                    $yearId = (int) $subject->academic_year_id;
                }
            }
        }

        if ($yearId !== null && $yearId > 0 && $summary['year_ids'] !== []) {
            if (! in_array($yearId, $summary['year_ids'], true)) {
                // Subject may imply year; allow if subject matched and years derived from subjects
                $derivedYears = self::yearsFromSubjects($summary['subject_ids']);
                if ($derivedYears !== [] && ! in_array($yearId, $derivedYears, true)) {
                    return false;
                }
                if ($derivedYears === [] && ! in_array($yearId, $summary['year_ids'], true)) {
                    return false;
                }
            }
        }

        if ($curriculumType !== null && trim($curriculumType) !== '') {
            if ($summary['curriculum_types'] === []) {
                return false;
            }
            if (! HesetakMatchCatalog::profileMatchesCurriculumType($summary['curriculum_types'], $curriculumType)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<int>  $subjectIds
     * @param  list<int>  $yearIds
     * @param  list<string>  $curriculumTypes
     * @return array{subject_ids: list<int>, year_ids: list<int>, curriculum_types: list<string>}
     */
    public static function normalizePayload(array $subjectIds, array $yearIds, array $curriculumTypes): array
    {
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $subjectIds))));
        $yearIds = array_values(array_unique(array_filter(array_map('intval', $yearIds))));
        $allowed = HesetakMatchCatalog::allowedCurriculumTypeKeys();
        $curriculumTypes = array_values(array_unique(array_filter(array_map('strval', $curriculumTypes), function ($k) use ($allowed) {
            return $k !== '' && (in_array($k, $allowed, true) || $allowed === []);
        })));

        if ($yearIds === [] && $subjectIds !== []) {
            $yearIds = self::yearsFromSubjects($subjectIds);
        }

        return [
            'subject_ids' => $subjectIds,
            'year_ids' => $yearIds,
            'curriculum_types' => $curriculumTypes,
        ];
    }

    public static function applyToApplication(TutorApplication $application, array $normalized): void
    {
        $application->forceFill([
            'teaching_subject_ids' => $normalized['subject_ids'],
            'academic_year_ids' => $normalized['year_ids'],
            'curriculum_types' => $normalized['curriculum_types'],
        ])->save();
    }

    public static function syncProfile(InstructorProfile $profile, array $normalized): void
    {
        $profile->forceFill([
            'teaching_subject_ids' => $normalized['subject_ids'],
            'curriculum_types' => $normalized['curriculum_types'],
        ])->save();
    }

    /**
     * @return list<int>
     */
    public static function subjectIds(TutorApplication|InstructorProfile $source): array
    {
        $raw = $source->teaching_subject_ids ?? [];
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    /**
     * @return list<int>
     */
    public static function yearIds(TutorApplication|InstructorProfile $source): array
    {
        if ($source instanceof TutorApplication) {
            $raw = $source->academic_year_ids ?? [];
            if (is_array($raw) && $raw !== []) {
                return array_values(array_unique(array_filter(array_map('intval', $raw))));
            }
        }

        return self::yearsFromSubjects(self::subjectIds($source));
    }

    /**
     * @return list<string>
     */
    public static function curriculumTypes(TutorApplication|InstructorProfile $source): array
    {
        if (method_exists($source, 'curriculumTypeKeys')) {
            return $source->curriculumTypeKeys();
        }

        $raw = $source->curriculum_types ?? [];
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $raw)));
    }

    /**
     * @param  list<int>  $subjectIds
     * @return list<int>
     */
    protected static function yearsFromSubjects(array $subjectIds): array
    {
        if ($subjectIds === []) {
            return [];
        }

        return AcademicSubject::query()
            ->whereIn('id', $subjectIds)
            ->whereNotNull('academic_year_id')
            ->pluck('academic_year_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<array{id:int,name:string,year_id:?int,year_name:?string}>
     */
    public static function subjectOptions(): array
    {
        return AcademicSubject::query()
            ->with('academicYear:id,name')
            ->when(
                AcademicSubject::query()->getModel()->getConnection()->getSchemaBuilder()->hasColumn('academic_subjects', 'is_active'),
                fn ($q) => $q->where('is_active', true)
            )
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'academic_year_id', 'order'])
            ->map(fn (AcademicSubject $s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'year_id' => $s->academic_year_id ? (int) $s->academic_year_id : null,
                'year_name' => $s->academicYear?->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id:int,name:string}>
     */
    public static function yearOptions(): array
    {
        return AcademicYear::query()
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (AcademicYear $y) => [
                'id' => (int) $y->id,
                'name' => (string) $y->name,
            ])
            ->all();
    }
}
