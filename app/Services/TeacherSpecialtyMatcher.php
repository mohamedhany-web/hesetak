<?php

namespace App\Services;

use App\Models\AcademicGrade;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Support\HesetakMatchCatalog;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TeacherSpecialtyMatcher
{
    /**
     * @return array{complete: bool, missing: list<string>, subject_ids: list<int>, year_ids: list<int>, grade_ids: list<int>, year_names: list<string>, grade_names: list<string>, curriculum_types: list<string>}
     */
    public static function summarize(TutorApplication|InstructorProfile $source): array
    {
        $subjectIds = self::subjectIds($source);
        $yearIds = self::yearIds($source);
        $gradeIds = self::gradeIds($source);
        $types = self::curriculumTypes($source);

        $missing = [];
        if ($subjectIds === []) {
            $missing[] = 'مادة';
        }
        if ($yearIds === []) {
            $missing[] = 'مرحلة';
        }
        if (self::yearsNeedGrades($yearIds) && $gradeIds === []) {
            $missing[] = 'صف';
        }
        if ($types === []) {
            $missing[] = 'نوع منهج';
        }

        $labels = self::labelsForIds($yearIds, $gradeIds);

        return [
            'complete' => $missing === [],
            'missing' => $missing,
            'subject_ids' => $subjectIds,
            'year_ids' => $yearIds,
            'grade_ids' => $gradeIds,
            'year_names' => $labels['years'],
            'grade_names' => $labels['grades'],
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
     * @param  list<int>  $gradeIds
     * @return array{subject_ids: list<int>, year_ids: list<int>, grade_ids: list<int>, curriculum_types: list<string>}
     */
    public static function normalizePayload(array $subjectIds, array $yearIds, array $curriculumTypes, array $gradeIds = []): array
    {
        $subjectIds = array_values(array_unique(array_filter(array_map('intval', $subjectIds))));
        $yearIds = array_values(array_unique(array_filter(array_map('intval', $yearIds))));
        $gradeIds = array_values(array_unique(array_filter(array_map('intval', $gradeIds))));
        $allowed = HesetakMatchCatalog::allowedCurriculumTypeKeys();
        $curriculumTypes = array_values(array_unique(array_filter(array_map('strval', $curriculumTypes), function ($k) use ($allowed) {
            return $k !== '' && (in_array($k, $allowed, true) || $allowed === []);
        })));

        if ($yearIds === [] && $subjectIds !== []) {
            $yearIds = self::yearsFromSubjects($subjectIds);
        }

        if ($gradeIds !== [] && Schema::hasTable('academic_grades')) {
            $validQuery = AcademicGrade::query()->whereIn('id', $gradeIds)->active();
            if ($yearIds !== []) {
                $validQuery->whereIn('academic_year_id', $yearIds);
            }
            $valid = $validQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
            $gradeIds = array_values(array_intersect($gradeIds, $valid));
        }

        return [
            'subject_ids' => $subjectIds,
            'year_ids' => $yearIds,
            'grade_ids' => $gradeIds,
            'curriculum_types' => $curriculumTypes,
        ];
    }

    public static function applyToApplication(TutorApplication $application, array $normalized): void
    {
        $payload = [
            'teaching_subject_ids' => $normalized['subject_ids'],
            'academic_year_ids' => $normalized['year_ids'],
            'curriculum_types' => $normalized['curriculum_types'],
        ];
        if (Schema::hasColumn($application->getTable(), 'academic_grade_ids')) {
            $payload['academic_grade_ids'] = $normalized['grade_ids'] ?? [];
        }

        $application->forceFill($payload)->save();
    }

    public static function syncProfile(InstructorProfile $profile, array $normalized): void
    {
        $payload = [
            'teaching_subject_ids' => $normalized['subject_ids'],
            'curriculum_types' => $normalized['curriculum_types'],
        ];
        if (Schema::hasColumn($profile->getTable(), 'academic_year_ids')) {
            $payload['academic_year_ids'] = $normalized['year_ids'] ?? [];
        }
        if (Schema::hasColumn($profile->getTable(), 'academic_grade_ids')) {
            $payload['academic_grade_ids'] = $normalized['grade_ids'] ?? [];
        }

        $profile->forceFill($payload)->save();
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
        if (Schema::hasColumn($source->getTable(), 'academic_year_ids')) {
            $raw = $source->academic_year_ids ?? [];
            if (is_array($raw) && $raw !== []) {
                return array_values(array_unique(array_filter(array_map('intval', $raw))));
            }
        }

        return self::yearsFromSubjects(self::subjectIds($source));
    }

    /**
     * @return list<int>
     */
    public static function gradeIds(TutorApplication|InstructorProfile $source): array
    {
        if (! Schema::hasColumn($source->getTable(), 'academic_grade_ids')) {
            return [];
        }

        $raw = $source->academic_grade_ids ?? [];
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
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
     * @return list<array{id:int,name:string,grades:list<array{id:int,name:string}>}>
     */
    public static function yearOptions(): array
    {
        $query = AcademicYear::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', 'not like', 'TCH-%');
            })
            ->ordered();

        if (Schema::hasTable('academic_grades')) {
            $query->with(['grades' => fn ($grades) => $grades->active()->ordered()]);
        }

        return $query
            ->get(['id', 'name'])
            ->map(fn (AcademicYear $year) => [
                'id' => (int) $year->id,
                'name' => (string) $year->name,
                'grades' => ($year->grades ?? collect())
                    ->map(fn ($grade) => [
                        'id' => (int) $grade->id,
                        'name' => (string) $grade->name,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @param  list<array{id:int,grades?:list<array{id:int}>}>  $yearOptions
     * @return array<string, list<array{id:int,name:string}>>
     */
    public static function gradesByYearMap(array $yearOptions): array
    {
        $map = [];
        foreach ($yearOptions as $year) {
            $map[(string) $year['id']] = $year['grades'] ?? [];
        }

        return $map;
    }

    /**
     * @param  list<int>  $yearIds
     */
    public static function yearsNeedGrades(array $yearIds): bool
    {
        if ($yearIds === [] || ! Schema::hasTable('academic_grades')) {
            return false;
        }

        return AcademicGrade::query()
            ->whereIn('academic_year_id', $yearIds)
            ->active()
            ->exists();
    }

    /**
     * @param  list<int>  $yearIds
     * @param  list<int>  $gradeIds
     * @return array{years: list<string>, grades: list<string>}
     */
    public static function labelsForIds(array $yearIds, array $gradeIds): array
    {
        $years = [];
        if ($yearIds !== [] && Schema::hasTable('academic_years')) {
            $years = AcademicYear::query()
                ->whereIn('id', $yearIds)
                ->ordered()
                ->pluck('name')
                ->map(fn ($name) => (string) $name)
                ->all();
        }

        $grades = [];
        if ($gradeIds !== [] && Schema::hasTable('academic_grades')) {
            $grades = AcademicGrade::query()
                ->whereIn('id', $gradeIds)
                ->ordered()
                ->pluck('name')
                ->map(fn ($name) => (string) $name)
                ->all();
        }

        return ['years' => $years, 'grades' => $grades];
    }
}
