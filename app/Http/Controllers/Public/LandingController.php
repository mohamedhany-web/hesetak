<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Models\SiteService;
use App\Models\SiteTestimonial;
use App\Models\User;
use App\Services\CourseSubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * الصفحة الرئيسية (Landing).
 * اللغة تُحدد عبر Middleware SetLandingLocale من ?lang= أو الجلسة.
 */
class LandingController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();
        $buildHomePayload = function () {
            $featuredCourses = AdvancedCourse::query()
                ->where('is_active', true)
                ->with(['instructor:id,name', 'courseCategory:id,name'])
                ->withCount('lessons')
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();

            $oneToOneCourses = AdvancedCourse::query()
                ->where('is_active', true)
                ->where('delivery_type', CourseSubscriptionService::DELIVERY_ONE_TO_ONE)
                ->with(['instructor:id,name', 'courseCategory:id,name'])
                ->withCount('lessons')
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();

            $homeInstructors = $this->buildHomeInstructors();
            $trialInstructors = $this->buildTrialInstructors();
            $homePackages = $this->buildHomePackages();
            $homeCategories = $this->buildHomeCategories();
            $homeTrustStats = $this->buildHomeTrustStats($homeInstructors->count());

            $homeTestimonials = Schema::hasTable('site_testimonials')
                ? SiteTestimonial::query()->active()->ordered()->limit(12)->get()
                : collect();

            $schoolYears = Schema::hasTable('academic_years')
                ? AcademicYear::query()->active()->ordered()->get(['id', 'name', 'slug', 'level_number', 'tagline', 'icon', 'color'])
                : collect();

            $schoolSubjects = Schema::hasTable('academic_subjects')
                ? AcademicSubject::query()
                    ->active()
                    ->ordered()
                    ->where(function ($q) {
                        $q->whereNull('academic_year_id')
                            ->orWhere('code', 'like', 'SCH-%');
                    })
                    ->limit(12)
                    ->get(['id', 'name', 'code', 'slug', 'icon', 'color'])
                : collect();

            return compact(
                'featuredCourses',
                'oneToOneCourses',
                'homeInstructors',
                'trialInstructors',
                'homePackages',
                'homeCategories',
                'homeTrustStats',
                'homeTestimonials',
                'schoolYears',
                'schoolSubjects'
            );
        };

        $payload = config('app.debug')
            ? $buildHomePayload()
            : Cache::remember('landing.home.v17.'.$locale, 180, $buildHomePayload);

        return view('welcome', $payload);
    }

    /**
     * @return Collection<int, InstructorProfile>
     */
    private function buildHomeInstructors(): Collection
    {
        if (! Schema::hasTable('instructor_profiles')) {
            return collect();
        }

        return InstructorProfile::query()
            ->approved()
            ->whereHas('user', function ($q) {
                $q->whereIn('role', ['instructor', 'teacher'])
                    ->where('is_active', true);
            })
            ->with(['user:id,name,role,is_active,bio,profile_image'])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();
    }

    /**
     * معلمون لاختيار الحصة التجريبية في الشيت.
     *
     * @return Collection<int, InstructorProfile>
     */
    private function buildTrialInstructors(): Collection
    {
        if (! Schema::hasTable('instructor_profiles')) {
            return collect();
        }

        return InstructorProfile::query()
            ->approved()
            ->whereHas('user', function ($q) {
                $q->whereIn('role', ['instructor', 'teacher'])
                    ->where('is_active', true);
            })
            ->with(['user:id,name,role,is_active,bio,profile_image'])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(16)
            ->get();
    }

    /**
     * @return Collection<int, ServicePackage>
     */
    private function buildHomePackages(): Collection
    {
        if (! Schema::hasTable('service_packages')) {
            return collect();
        }

        // نفس كتالوج الإدارة النشط المعروض للطالب وصفحة التسعير
        return ServicePackage::storefrontCatalog(12);
    }

    /**
     * @return Collection<int, array{name: string, url: string}>
     */
    private function buildHomeCategories(): Collection
    {
        if (Schema::hasTable('academic_subjects')) {
            $subjects = AcademicSubject::query()
                ->active()
                ->where(function ($q) {
                    $q->where('code', 'like', 'HSK-%')
                        ->orWhere('code', 'like', 'SCH-%');
                })
                ->ordered()
                ->limit(10)
                ->get(['id', 'name', 'slug']);

            if ($subjects->isEmpty()) {
                $subjects = AcademicSubject::query()
                    ->active()
                    ->ordered()
                    ->limit(10)
                    ->get(['id', 'name', 'slug']);
            }

            if ($subjects->isNotEmpty()) {
                return $subjects->map(fn (AcademicSubject $subject) => [
                    'name' => $subject->name,
                    'url' => route('public.instructors.index', ['q' => $subject->name]),
                ]);
            }
        }

        if (Schema::hasTable('course_categories')) {
            $categories = CourseCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(10)
                ->get(['id', 'name']);

            if ($categories->isNotEmpty()) {
                return $categories->map(fn (CourseCategory $category) => [
                    'name' => $category->name,
                    'url' => route('public.courses', ['category' => $category->id]),
                ]);
            }
        }

        return collect();
    }

    /**
     * أرقام ثقة من قاعدة البيانات فقط (بدون حد أدنى تسويقي ثابت).
     *
     * @return list<array{num: string, label: string, suffix?: string}>
     */
    private function buildHomeTrustStats(int $homeInstructorsLoaded): array
    {
        $instructors = Schema::hasTable('instructor_profiles')
            ? InstructorProfile::query()
                ->approved()
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['instructor', 'teacher'])->where('is_active', true))
                ->count()
            : $homeInstructorsLoaded;

        $subjects = Schema::hasTable('academic_subjects')
            ? AcademicSubject::query()->active()->count()
            : 0;

        if ($subjects < 1 && Schema::hasTable('course_categories')) {
            $subjects = CourseCategory::query()->count();
        }

        $students = User::query()->where('role', 'student')->where('is_active', true)->count();
        $courses = Schema::hasTable('advanced_courses')
            ? AdvancedCourse::query()->where('is_active', true)->count()
            : 0;
        $services = Schema::hasTable('site_services')
            ? SiteService::active()->count()
            : 0;

        $locale = app()->getLocale();
        $ar = $locale === 'ar';

        $stats = [
            [
                'num' => (string) max(0, $instructors),
                'label' => $ar ? 'معلمون معتمدون' : 'Certified teachers',
            ],
            [
                'num' => (string) max(0, $subjects),
                'label' => $ar ? 'مواد ومناهج' : 'Subjects & curricula',
            ],
            [
                'num' => (string) max(0, $courses > 0 ? $courses : $services),
                'label' => $ar ? 'كورسات نشطة' : 'Active courses',
            ],
            [
                'num' => (string) max(0, $students),
                'label' => $ar ? 'طلاب مسجّلون' : 'Registered students',
            ],
        ];

        return $stats;
    }
}
