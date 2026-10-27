<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Services\PublicInstructorDirectory;
use Illuminate\Database\Eloquent\Builder;
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
        $tokens = $this->tokens($q);

        $instructors = collect();
        $packages = collect();
        $courses = collect();
        $subjects = collect();
        $years = collect();

        if (count($tokens) >= 1 && mb_strlen($q) >= 2) {
            if (Schema::hasTable('instructor_profiles')) {
                $instructors = InstructorProfile::query()
                    ->approved()
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['headline', 'skills']);
                        $query->orWhereHas('user', function (Builder $userQuery) use ($tokens) {
                            $userQuery->whereIn('role', ['instructor', 'teacher'])
                                ->where('is_active', true);
                            $this->requireAllTokens($userQuery, $tokens, ['name']);
                        });
                    })
                    ->with(['user:'.implode(',', PublicInstructorDirectory::userSelect())])
                    ->limit(8)
                    ->get();
            }

            if (Schema::hasTable('service_packages')) {
                $packages = ServicePackage::query()
                    ->storefront()
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['name', 'tagline']);
                    })
                    ->orderByDesc('units_count')
                    ->limit(8)
                    ->get(['id', 'name', 'tagline', 'units_count', 'price', 'currency', 'academic_year_id', 'academic_subject_id']);
            }

            if (Schema::hasTable('advanced_courses')) {
                $courses = AdvancedCourse::query()
                    ->where('is_active', true)
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['title', 'category']);
                    })
                    ->limit(8)
                    ->get(['id', 'title', 'thumbnail', 'level', 'duration_hours', 'category', 'language']);
            }

            if (Schema::hasTable('academic_subjects')) {
                $subjects = AcademicSubject::query()
                    ->active()
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['name']);
                    })
                    ->with('academicYear:id,name')
                    ->limit(8)
                    ->get(['id', 'name', 'academic_year_id']);
            }

            if (Schema::hasTable('academic_years')) {
                $years = AcademicYear::query()
                    ->publicCatalog()
                    ->where(function (Builder $query) use ($tokens) {
                        $this->requireAllTokens($query, $tokens, ['name', 'tagline']);
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

    /**
     * @return list<string>
     */
    private function tokens(string $q): array
    {
        $parts = preg_split('/\s+/u', mb_strtolower(trim($q))) ?: [];

        return array_values(array_filter($parts, fn ($t) => mb_strlen($t) >= 2));
    }

    /**
     * كل كلمة بحث يجب أن تظهر في أحد الحقول (AND بين الكلمات، OR بين الحقول).
     *
     * @param  list<string>  $tokens
     * @param  list<string>  $columns
     */
    private function requireAllTokens(Builder $query, array $tokens, array $columns): void
    {
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(function (Builder $inner) use ($like, $columns) {
                foreach ($columns as $i => $column) {
                    if ($i === 0) {
                        $inner->where($column, 'like', $like);
                    } else {
                        $inner->orWhere($column, 'like', $like);
                    }
                }
            });
        }
    }
}
