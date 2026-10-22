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
                ->get()
                ->values();
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

        // Public curricula browse stays open: do not collapse the catalog to the viewer's stage.

        $curriculumTypes = HesetakMatchCatalog::curriculumTypes();

        return view('public.marketing.curricula', [
            'mcActive' => 'curricula',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('seo.curricula.title'),
            'pageDescription' => __('seo.curricula.description'),
            'teacherCount' => $teacherCount,
            'years' => $years,
            'curriculumTypes' => $curriculumTypes,
        ]);
    }

    public function requestForm()
    {
        $years = collect();
        if (Schema::hasTable('academic_years')) {
            $years = AcademicYear::query()
                ->publicCatalog()
                ->ordered()
                ->with(['subjects' => fn ($q) => $q->active()->ordered()])
                ->get()
                ->values();
        }

        return view('public.marketing.request', [
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('hesetak_pages.curricula.match_title').' | '.__('landing.nav.brand'),
            'pageDescription' => __('hesetak_pages.curricula.match_lead'),
            'years' => $years,
            'curriculumTypes' => HesetakMatchCatalog::curriculumTypes(),
        ]);
    }

    public function stage(string $stage)
    {
        $descriptions = [
            'primary' => __('seo.curricula_stages.primary'),
            'middle' => __('seo.curricula_stages.middle'),
            'secondary' => __('seo.curricula_stages.secondary'),
            'international' => __('seo.curricula_stages.international'),
            'university' => __('seo.curricula_stages.university'),
            'qiyas' => __('seo.curricula_stages.qiyas'),
            'skills' => __('seo.curricula_stages.skills'),
        ];
        $needles = [
            'primary' => ['ابتدائي', 'primary'],
            'middle' => ['متوسط', 'middle', 'اعدادي', 'إعدادي'],
            'secondary' => ['ثانوي', 'secondary'],
            'international' => ['دولي', 'international'],
            'university' => ['جامع', 'university'],
            'qiyas' => ['قياس', 'قدرات', 'qiyas'],
            'skills' => ['مهار', 'skills'],
        ];

        $years = collect();
        if (Schema::hasTable('academic_years')) {
            $years = AcademicYear::query()->publicCatalog()->ordered()->with(['subjects' => fn ($q) => $q->active()->ordered()])->get()
                ->filter(function ($year) use ($needles, $stage) {
                    $hay = mb_strtolower($year->name.' '.$year->slug.' '.$year->tagline);
                    foreach ($needles[$stage] ?? [] as $needle) {
                        if (mb_strpos($hay, mb_strtolower($needle)) !== false) {
                            return true;
                        }
                    }

                    return false;
                })
                ->values();
        }

        return view('public.marketing.curricula', [
            'mcActive' => 'curricula',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => __('seo.curricula_stage_titles.'.$stage),
            'pageDescription' => $descriptions[$stage] ?? __('seo.curricula.description'),
            'teacherCount' => 0,
            'years' => $years,
            'curriculumTypes' => HesetakMatchCatalog::curriculumTypes(),
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
                ->with(['user:'.implode(',', \App\Services\PublicInstructorDirectory::userSelect())])
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
