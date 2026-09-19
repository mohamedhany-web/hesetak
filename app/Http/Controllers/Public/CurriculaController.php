<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\InstructorProfile;
use App\Support\HesetakMatchCatalog;
use Illuminate\Support\Facades\Schema;

class CurriculaController extends Controller
{
    public function index()
    {
        $years = collect();
        if (Schema::hasTable('academic_years')) {
            $years = AcademicYear::query()
                ->publicCatalog()
                ->ordered()
                ->with(['subjects' => fn ($q) => $q->active()->ordered()])
                ->withCount([
                    'subjects as subjects_count' => fn ($q) => $q->active(),
                    'instructors as teachers_count',
                ])
                ->get();
        }

        $teacherCount = 0;
        try {
            $teacherCount = (int) InstructorProfile::query()
                ->approved()
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['instructor', 'teacher'])
                        ->where('is_active', true);
                })
                ->count();
        } catch (\Throwable) {
            $teacherCount = 0;
        }

        $curriculumTypes = HesetakMatchCatalog::curriculumTypes();

        return view('public.marketing.curricula', [
            'mcActive' => 'curricula',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('hesetak_pages.curricula.meta_title'),
            'pageDescription' => __('hesetak_pages.curricula.meta_description'),
            'teacherCount' => $teacherCount,
            'years' => $years,
            'curriculumTypes' => $curriculumTypes,
        ]);
    }

    public function show(AcademicYear $year)
    {
        if (! $year->is_active || ! $year->is_public) {
            abort(404);
        }

        $year->load([
            'subjects' => fn ($q) => $q->active()->ordered(),
            'instructors' => fn ($q) => $q->where('is_active', true)
                ->whereIn('role', ['instructor', 'teacher'])
                ->with(['instructorProfile' => fn ($p) => $p->approved()]),
        ]);

        $teachers = $year->instructors
            ->filter(fn ($user) => $user->instructorProfile && $user->instructorProfile->status === InstructorProfile::STATUS_APPROVED)
            ->values();

        if ($teachers->isEmpty()) {
            $teachers = InstructorProfile::query()
                ->approved()
                ->whereHas('user', function ($query) {
                    $query->whereIn('role', ['instructor', 'teacher'])
                        ->where('is_active', true);
                })
                ->with('user:id,name,role,is_active')
                ->limit(8)
                ->get()
                ->filter(function (InstructorProfile $profile) use ($year) {
                    $hay = mb_strtolower(implode(' ', [
                        (string) $profile->headline,
                        (string) $profile->skills,
                        (string) $profile->bio,
                        (string) $profile->experience,
                    ]));
                    $needles = array_filter([
                        mb_strtolower((string) $year->name),
                        mb_strtolower((string) $year->tagline),
                    ]);
                    foreach ($needles as $needle) {
                        if ($needle !== '' && mb_strpos($hay, $needle) !== false) {
                            return true;
                        }
                    }

                    return false;
                })
                ->values();
        }

        // Normalize teachers to profile-like cards for the blade.
        $teacherCards = $teachers->map(function ($item) {
            if ($item instanceof InstructorProfile) {
                return $item;
            }
            if ($item instanceof \App\Models\User && $item->instructorProfile) {
                return $item->instructorProfile;
            }

            return null;
        })->filter()->values();

        $siblings = AcademicYear::query()
            ->publicCatalog()
            ->ordered()
            ->where('id', '!=', $year->id)
            ->limit(6)
            ->get(['id', 'name', 'slug', 'tagline', 'description']);

        return view('public.marketing.curricula-show', [
            'mcActive' => 'curricula',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => $year->name.' | '.__('hesetak_pages.curricula.meta_title'),
            'pageDescription' => \Illuminate\Support\Str::limit(
                strip_tags((string) ($year->description ?: $year->tagline ?: __('hesetak_pages.curricula.meta_description'))),
                160
            ),
            'year' => $year,
            'subjects' => $year->subjects,
            'teachers' => $teacherCards,
            'siblings' => $siblings,
            'curriculumTypes' => HesetakMatchCatalog::curriculumTypes(),
        ]);
    }
}
