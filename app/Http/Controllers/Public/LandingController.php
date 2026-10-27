<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use App\Models\InstructorProfile;
use App\Models\ServicePackage;
use App\Models\PopupAd;
use App\Models\SiteService;
use App\Models\SiteTestimonial;
use App\Models\User;
use App\Services\CourseSubscriptionService;
use App\Services\PackageCatalogFilterService;
use App\Support\HesetakMatchCatalog;
use Illuminate\Http\Request;
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
    public function index(Request $request): View
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
            $homeCategories = $this->buildHomeCategories();
            $homeTrustStats = $this->buildHomeTrustStats($homeInstructors->count());

            $homeTestimonials = Schema::hasTable('site_testimonials')
                ? SiteTestimonial::query()->active()->ordered()->limit(24)->get()
                    ->reject(fn (SiteTestimonial $t) => \App\Support\PublicDemoContent::recordLooksLikeDemo([
                        $t->author_name, $t->role_label, $t->body,
                    ]))
                    ->take(12)
                    ->values()
                : collect();

            $schoolYears = Schema::hasTable('academic_years')
                ? AcademicYear::query()->publicCatalog()->ordered()->get(['id', 'name', 'slug', 'level_number', 'tagline', 'icon', 'color'])
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
                'homeCategories',
                'homeTrustStats',
                'homeTestimonials',
                'schoolYears',
                'schoolSubjects'
            );
        };

        $payload = config('app.debug')
            ? $buildHomePayload()
            : Cache::remember('landing.home.v21.'.$locale, 180, $buildHomePayload);

        // متصفح المنهج للرئيسية: منهج → صف → مادة (بدل قسم المعلمين فقط)
        $payload['curriculumBrowserTracks'] = array_values(HesetakMatchCatalog::curriculumTypes());
        $payload['curriculumBrowserStages'] = Schema::hasTable('academic_years')
            ? AcademicYear::query()
                ->publicCatalog()
                ->ordered()
                ->with(['subjects' => fn ($q) => $q->active()->ordered()->select(['id', 'name', 'slug', 'academic_year_id'])])
                ->get(['id', 'name', 'slug'])
            : collect();

        $catalogService = app(PackageCatalogFilterService::class);
        $packageCatalog = Schema::hasTable('service_packages')
            ? $catalogService->catalog(
                yearId: $request->filled('year') ? $request->integer('year') : null,
                subjectId: $request->filled('subject') ? $request->integer('subject') : null,
                curriculumType: $request->query('curriculum_type'),
            )
            : [
                'years' => collect(),
                'subjects' => collect(),
                'tracks' => [],
                'selected_year_id' => null,
                'selected_subject_id' => null,
                'selected_curriculum_type' => null,
                'packages' => collect(),
                'viewer_locked' => false,
            ];

        $payload['packageCatalog'] = $packageCatalog;
        $payload['homePackages'] = Schema::hasTable('service_packages')
            ? $catalogService->homeShowcase(6)
            : collect();

        $viewer = \App\Support\StudentLearningProfile::fromAuth();
        if ($viewer->hasPreferences()) {
            $filteredHome = $viewer->filterInstructorProfiles(collect($payload['homeInstructors'] ?? []));
            $filteredTrial = $viewer->filterInstructorProfiles(collect($payload['trialInstructors'] ?? []));
            if ($filteredHome->isNotEmpty()) {
                $payload['homeInstructors'] = $filteredHome;
            }
            if ($filteredTrial->isNotEmpty()) {
                $payload['trialInstructors'] = $filteredTrial;
            }
        }

        $payload['activePopupAd'] = null;
        if (Schema::hasTable('popup_ads')) {
            $cookieKey = 'popup_ad_seen';
            $seenId = (int) $request->cookie($cookieKey, 0);
            $ad = PopupAd::query()->activeNow()->first();
            if ($ad && \App\Support\PublicDemoContent::recordLooksLikeDemo([$ad->title, $ad->body, $ad->cta_text])) {
                $ad = null;
            }
            if ($ad && (int) $ad->id !== $seenId) {
                $payload['activePopupAd'] = $ad;
                cookie()->queue(cookie($cookieKey, (string) $ad->id, 60 * 24)); // يوم واحد
            }
        }

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
            ->with(['user:'.implode(',', \App\Services\PublicInstructorDirectory::userSelect()), 'user.teachingLearningPaths'])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->filter(fn (InstructorProfile $profile) => \App\Services\PublicInstructorDirectory::isPublishableListing($profile))
            ->reject(fn (InstructorProfile $profile) => \App\Services\PublicInstructorDirectory::profileLooksLikeDemo($profile))
            ->take(4)
            ->values();
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
            ->with(['user:'.implode(',', \App\Services\PublicInstructorDirectory::userSelect())])
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id')
            ->limit(16)
            ->get()
            ->reject(fn (InstructorProfile $profile) => \App\Services\PublicInstructorDirectory::profileLooksLikeDemo($profile))
            ->values();
    }

    /**
     * @deprecated Prefer PackageCatalogFilterService via index().
     * @return Collection<int, ServicePackage>
     */
    private function buildHomePackages(): Collection
    {
        if (! Schema::hasTable('service_packages')) {
            return collect();
        }

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
