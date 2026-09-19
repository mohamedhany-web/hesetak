<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Package;
use App\Models\TutoringGroupPackage;
use App\Services\TutoringGroupPackagePricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage.packages');
    }

    /**
     * مركز الباقات والأسعار: برامج مسجّلة + أسعار البرامج + باقات الحصص.
     */
    public function index(Request $request)
    {
        $packagesQuery = Package::withCount('courses')
            ->orderBy('order')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $packagesQuery->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $packagesQuery->where('is_active', false);
            }
        }

        if ($request->filled('track')) {
            $packagesQuery->where('track', $request->track);
        }

        if ($request->filled('search') && $request->tab !== 'courses' && $request->tab !== 'tutoring') {
            $search = $request->search;
            $packagesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('card_summary', 'like', "%{$search}%");
            });
        }

        $packages = $packagesQuery->paginate(20, ['*'], 'packages_page');

        $coursesQuery = AdvancedCourse::with(['instructor'])
            ->withCount('lessons')
            ->orderBy('created_at', 'desc');

        if ($request->filled('course_status')) {
            if ($request->course_status === 'free') {
                $coursesQuery->where(function ($q) {
                    $q->where('is_free', true)->orWhere('price', 0);
                });
            } elseif ($request->course_status === 'paid') {
                $coursesQuery->where('is_free', false)->where('price', '>', 0);
            }
        }

        if ($request->filled('course_level')) {
            $coursesQuery->where('level', $request->course_level);
        }

        if ($request->filled('course_language')) {
            $coursesQuery->where('programming_language', $request->course_language);
        }

        if ($request->filled('course_category')) {
            $coursesQuery->where('category', $request->course_category);
        }

        if ($request->filled('course_active')) {
            $coursesQuery->where('is_active', $request->course_active === '1');
        }

        if ($request->filled('course_search') && $request->tab === 'courses') {
            $search = $request->course_search;
            $coursesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('programming_language', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $courses = $coursesQuery->paginate(12, ['*'], 'courses_page');

        $programmingLanguages = AdvancedCourse::whereNotNull('programming_language')
            ->distinct()
            ->pluck('programming_language')
            ->sort()
            ->values();

        $categories = AdvancedCourse::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        $packageStats = [
            'total' => Package::count(),
            'active' => Package::where('is_active', true)->count(),
            'inactive' => Package::where('is_active', false)->count(),
            'featured' => Package::where('is_featured', true)->count(),
        ];

        $courseStats = [
            'total' => AdvancedCourse::count(),
            'free' => AdvancedCourse::where(function ($q) {
                $q->where('is_free', true)->orWhere('price', 0);
            })->count(),
            'paid' => AdvancedCourse::where('is_free', false)->where('price', '>', 0)->count(),
            'total_revenue' => AdvancedCourse::where('is_free', false)->sum('price'),
        ];

        $tutoringPackagesQuery = TutoringGroupPackage::query()
            ->with(['tutoringGroup.instructor:id,name'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('duration_months');

        if ($request->filled('tutoring_status')) {
            if ($request->tutoring_status === 'active') {
                $tutoringPackagesQuery->where('is_active', true);
            } elseif ($request->tutoring_status === 'inactive') {
                $tutoringPackagesQuery->where('is_active', false);
            }
        }

        if ($request->filled('tutoring_search') && $request->tab === 'tutoring') {
            $search = $request->tutoring_search;
            $tutoringPackagesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('tutoringGroup', function ($gq) use ($search) {
                        $gq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        $tutoringPackages = $tutoringPackagesQuery->paginate(20, ['*'], 'tutoring_page');

        $tutoringStats = [
            'total' => TutoringGroupPackage::count(),
            'active' => TutoringGroupPackage::where('is_active', true)->count(),
            'featured' => TutoringGroupPackage::where('is_featured', true)->count(),
            'avg_savings' => (int) round((float) (TutoringGroupPackage::query()
                ->whereNotNull('original_price')
                ->where('original_price', '>', 0)
                ->whereColumn('original_price', '>', 'price')
                ->selectRaw('AVG(((original_price - price) / NULLIF(original_price, 0)) * 100) as avg_pct')
                ->value('avg_pct') ?? 0)),
        ];

        $pricingTiers = [
            ['months' => 1, 'discount' => 0],
            ['months' => 3, 'discount' => 16.7],
            ['months' => 6, 'discount' => 20],
            ['months' => 12, 'discount' => 25],
        ];

        $exampleCalc = TutoringGroupPackagePricingService::calculate(10, 8, 3, 200);

        return view('admin.packages.index', compact(
            'packages',
            'courses',
            'packageStats',
            'courseStats',
            'programmingLanguages',
            'categories',
            'tutoringPackages',
            'tutoringStats',
            'pricingTiers',
            'exampleCalc'
        ));
    }

    public function create()
    {
        $courses = AdvancedCourse::where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'price']);

        return view('admin.packages.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePackage($request);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated = $this->normalizePackagePayload($validated, $request);

        $package = Package::create($validated);
        $this->syncCourses($package, $validated['courses'] ?? []);

        return redirect()->route('admin.packages.index')
            ->with('success', 'تم إنشاء الباقة بنجاح');
    }

    public function show(Package $package)
    {
        $package->load('courses');

        return view('admin.packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        $package->load('courses');
        $courses = AdvancedCourse::where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'price']);

        return view('admin.packages.edit', compact('package', 'courses'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $this->validatePackage($request, $package);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated = $this->normalizePackagePayload($validated, $request, $package);

        $package->update($validated);
        $this->syncCourses($package, $validated['courses'] ?? []);

        return redirect()->route('admin.packages.index')
            ->with('success', 'تم تحديث الباقة بنجاح');
    }

    public function destroy(Package $package)
    {
        if ($package->thumbnail) {
            \App\Services\PublicMediaStorage::delete($package->thumbnail);
        }

        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', 'تم حذف الباقة بنجاح');
    }

    public function updatePrice(Request $request, AdvancedCourse $course)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
            'is_free' => 'boolean',
        ]);

        $course->update([
            'price' => $validated['price'],
            'is_free' => $validated['is_free'] ?? ($validated['price'] == 0),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السعر بنجاح',
                'course' => $course->fresh(),
            ]);
        }

        return redirect()->route('admin.packages.index', ['tab' => 'courses'])
            ->with('success', 'تم تحديث السعر بنجاح');
    }

    public function updateBulkPrices(Request $request)
    {
        $validated = $request->validate([
            'courses' => 'required|array',
            'courses.*.id' => 'required|exists:advanced_courses,id',
            'courses.*.price' => 'required|numeric|min:0',
            'courses.*.is_free' => 'boolean',
        ]);

        foreach ($validated['courses'] as $courseData) {
            AdvancedCourse::where('id', $courseData['id'])->update([
                'price' => $courseData['price'],
                'is_free' => $courseData['is_free'] ?? ($courseData['price'] == 0),
            ]);
        }

        return redirect()->route('admin.packages.index', ['tab' => 'courses'])
            ->with('success', 'تم تحديث الأسعار بنجاح');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackage(Request $request, ?Package $package = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('packages', 'slug')->ignore($package?->id),
            ],
            'description' => 'nullable|string',
            'card_summary' => 'nullable|string',
            'features' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'track' => ['nullable', 'string', Rule::in([Package::TRACK_ISLAMIC, Package::TRACK_ENGLISH])],
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:'.config('upload_limits.max_upload_kb'),
            'duration_days' => 'nullable|integer|min:0',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'courses' => 'required|array|min:1',
            'courses.*' => 'exists:advanced_courses,id',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePackagePayload(array $validated, Request $request, ?Package $package = null): array
    {
        $validated['card_summary'] = $validated['card_summary'] ?? null;
        if ($validated['card_summary'] !== null) {
            $validated['card_summary'] = trim($validated['card_summary']) ?: null;
        }

        $validated['currency'] = strtoupper(trim((string) ($validated['currency'] ?? platform_currency()))) ?: platform_currency();
        $validated['track'] = $validated['track'] ?? null;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_popular'] = $request->boolean('is_popular');

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = \App\Services\PublicMediaStorage::store(
                $request->file('thumbnail'),
                'packages',
                $package?->thumbnail
            );
        }

        return $validated;
    }

    /**
     * @param  array<int, int|string>  $courseIds
     */
    private function syncCourses(Package $package, array $courseIds): void
    {
        $coursesData = [];
        foreach ($courseIds as $index => $courseId) {
            $coursesData[$courseId] = ['order' => $index];
        }
        $package->courses()->sync($coursesData);
    }
}
