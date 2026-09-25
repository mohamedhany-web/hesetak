<?php

namespace App\Support;

use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * تفضيلات التعلّم للطالب المسجّل (مرحلة + نوع منهج) لفلترة الباقات والمعلمين.
 * الزائر بدون حساب → لا تفضيل → يظهر الكل.
 */
final class StudentLearningProfile
{
    public function __construct(
        public readonly ?int $yearId = null,
        public readonly ?string $curriculumType = null,
    ) {}

    public static function fromAuth(): self
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->isStudent()) {
            return new self;
        }

        return self::fromUser($user);
    }

    public static function fromUser(User $user): self
    {
        $yearId = $user->academic_year_id ? (int) $user->academic_year_id : null;
        $curriculum = null;
        if (Schema::hasColumn('users', 'preferred_curriculum_type')) {
            $curriculum = trim((string) ($user->preferred_curriculum_type ?? ''));
            $curriculum = $curriculum !== '' ? $curriculum : null;
        }

        if ($curriculum !== null) {
            $allowed = HesetakMatchCatalog::allowedCurriculumTypeKeys();
            if (! in_array($curriculum, $allowed, true)) {
                $curriculum = null;
            }
        }

        return new self($yearId, $curriculum);
    }

    public function hasStage(): bool
    {
        return $this->yearId !== null && $this->yearId > 0;
    }

    public function hasCurriculum(): bool
    {
        return $this->curriculumType !== null && $this->curriculumType !== '';
    }

    public function hasPreferences(): bool
    {
        return $this->hasStage() || $this->hasCurriculum();
    }

    /**
     * @param  Collection<int, InstructorProfile>  $profiles
     * @return Collection<int, InstructorProfile>
     */
    public function filterInstructorProfiles(Collection $profiles): Collection
    {
        if (! $this->hasPreferences() || $profiles->isEmpty()) {
            return $profiles;
        }

        return $profiles
            ->filter(function (InstructorProfile $profile) {
                if ($this->hasStage()) {
                    $user = $profile->user;
                    if (! $user) {
                        return false;
                    }
                    if (Schema::hasTable('academic_year_instructors') && method_exists($user, 'teachingLearningPaths')) {
                        $teaches = $user->teachingLearningPaths()
                            ->where('academic_years.id', $this->yearId)
                            ->exists();
                        if (! $teaches) {
                            return false;
                        }
                    }
                }

                if ($this->hasCurriculum()) {
                    $keys = $profile->curriculumTypeKeys();
                    if ($keys === []) {
                        return true;
                    }

                    return in_array($this->curriculumType, $keys, true)
                        || HesetakMatchCatalog::profileMatchesCurriculumType($keys, $this->curriculumType, '');
                }

                return true;
            })
            ->values();
    }

    /**
     * @return Collection<int, AcademicYear>
     */
    public static function publicYears(): Collection
    {
        if (! Schema::hasTable('academic_years')) {
            return collect();
        }

        return AcademicYear::query()
            ->publicCatalog()
            ->ordered()
            ->get(['id', 'name', 'slug', 'level_number']);
    }
}
