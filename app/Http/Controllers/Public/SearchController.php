<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Services\PublicInstructorDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $locale = app()->getLocale();
        $isRtl = $locale === 'ar';

        $instructors = collect();
        $packages = collect();
        $courses = collect();
        $subjects = collect();
        $years = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%'.$q.'%';

            if (Schema::hasTable('instructor_profiles')) {
                $instructors = InstructorProfile::query()
                    ->approved()
                    ->where(function ($query) use ($like) {
                        $query->where('headline', 'like', $like)
                            ->orWhere('skills', 'like', $like)
                            ->orWhere('bio', 'like', $like)
                            ->orWhereHas('user', function ($userQuery) use ($like) {
                                $userQuery->whereIn('role', ['instructor', 'teacher'])
                                    ->where('is_active', true)
                                    ->where('name', 'like', $like);
                            });
                    })
                    ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect())])
                    ->limit(8)
                    ->get();
            }

            if (Schema::hasTable('service_packages')) {
                $packages = ServicePackage::query()
                    ->storefront()
                    ->where(function ($query) use ($like) {
                        $query->where('name', 'like', $like)
                            ->orWhere('tagline', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    })
                    ->orderByDesc('units_count')
                    ->limit(8)
                    ->get(['id', 'name', 'tagline', 'units_count', 'price', 'currency', 'academic_year_id', 'academic_subject_id']);
            }

            if (Schema::hasTable('advanced_courses')) {
                $courses = AdvancedCourse::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('category', 'like', $like)
                            ->orWhere('programming_language', 'like', $like);
                    })
                    ->limit(8)
                    ->get(['id', 'title', 'thumbnail', 'level', 'duration_hours', 'category', 'language']);
            }

            if (Schema::hasTable('academic_subjects')) {
                $subjects = AcademicSubject::query()
                    ->active()
                    ->where('name', 'like', $like)
                    ->with('academicYear:id,name')
                    ->limit(8)
                    ->get(['id', 'name', 'academic_year_id']);
            }

            if (Schema::hasTable('academic_years')) {
                $years = AcademicYear::query()
                    ->publicCatalog()
                    ->where(function ($query) use ($like) {
                        $query->where('name', 'like', $like)
                            ->orWhere('tagline', 'like', $like);
                    })
                    ->ordered()
                    ->limit(6)
                    ->get(['id', 'name', 'slug', 'tagline']);
            }
        }

        return view('public.marketing.search', [
            'mcActive' => 'search',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => ($isRtl ? 'بحث' : 'Search').' | '.__('landing.nav.brand'),
            'pageDescription' => $isRtl
                ? 'ابحث عن معلم أو مادة أو باقة أو كورس على حصتك.'
                : 'Search teachers, subjects, packages, or courses on Hesetak.',
            'q' => $q,
            'instructors' => $instructors,
            'packages' => $packages,
            'courses' => $courses,
            'subjects' => $subjects,
            'years' => $years,
            'hasQuery' => mb_strlen($q) >= 2,
        ]);
    }
}
