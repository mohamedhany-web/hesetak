<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AdvancedCourse;
use App\Models\CourseCategory;
use App\Models\User;
use App\Services\CourseThumbnailStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdvancedCourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage.courses');
    }

    /**
     * سعر العرض الاختياري: يُخزَّن فقط إذا كان أقل من السعر الأساسي وبشكل صالح.
     */
    private function normalizeOptionalSalePrice(mixed $raw, float $listPrice): ?float
    {
        if ($listPrice <= 0) {
            return null;
        }
        if ($raw === null || $raw === '') {
            return null;
        }
        $s = round((float) $raw, 2);
        if ($s <= 0 || $s >= $listPrice) {
            return null;
        }

        return $s;
    }

    /**
     * عرض قائمة الكورسات
     */
    public function index(Request $request)
    {
        $query = AdvancedCourse::with(['instructor'])
            ->withCount(['lessons', 'enrollments', 'orders']);

        // فلترة حسب مسار الكورس (الجدول course_categories)
        if ($request->filled('course_category_id')) {
            $query->where('course_category_id', (int) $request->course_category_id);
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // فلترة حسب نوع التعلّم (جماعي / فردي)
        if ($request->filled('delivery_type')) {
            $delivery = $request->input('delivery_type');
            if ($delivery === 'one_to_one') {
                $query->where('delivery_type', 'one_to_one');
            } elseif ($delivery === 'group') {
                $query->where(function ($q) {
                    $q->whereNull('delivery_type')
                        ->orWhere('delivery_type', 'group');
                });
            }
        }

        // البحث في العنوان والوصف
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(15);

        $courseCategoryOptions = CourseCategory::query()->orderBy('sort_order')->orderBy('name')->get();

        $instructors = User::where('role', 'instructor')->where('is_active', true)->get();

        try {
            return view('admin.advanced-courses.index', compact(
                'courses',
                'courseCategoryOptions',
                'instructors'
            ));
        } catch (\Throwable $e) {
            Log::error('AdvancedCourse index view error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * عرض صفحة إنشاء كورس جديد
     */
    public function create(Request $request): View
    {
        $trackOptions = [];

        $instructors = User::whereIn('role', ['instructor', 'teacher'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $courseCategories = CourseCategory::active()->ordered()->get(['id', 'name']);
        $skills = AdvancedCourse::selectRaw('DISTINCT JSON_EXTRACT(skills, "$[*]") as skill')
            ->whereNotNull('skills')
            ->pluck('skill')
            ->filter()
            ->flatMap(function ($json) {
                $decoded = json_decode($json, true);

                return is_array($decoded) ? $decoded : [$json];
            })
            ->unique()
            ->values();

        return view('admin.advanced-courses.create', compact(
            'trackOptions',
            'instructors',
            'courseCategories',
            'skills'
        ));
    }

    public function getSubjectsByYear(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $subjects = AcademicSubject::where('academic_year_id', $validated['academic_year_id'])
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json($subjects);
    }

    /**
     * حفظ كورس جديد
     */
    public function store(Request $request)
    {
        $request->merge([
            'academic_year_id' => $request->filled('academic_year_id') ? $request->academic_year_id : null,
            'academic_subject_id' => $request->filled('academic_subject_id') ? $request->academic_subject_id : null,
            'course_category_id' => $request->filled('course_category_id') ? $request->course_category_id : null,
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_subject_id' => 'nullable|exists:academic_subjects,id',
            'course_category_id' => 'nullable|exists:course_categories,id',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'objectives' => 'nullable|string',
            'instructor_id' => 'required|exists:users,id',
            'programming_language' => 'nullable|string|max:100',
            'framework' => 'nullable|string|max:100',
            'duration_hours' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'price' => 'nullable|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0',
            'price_egp' => 'nullable|numeric|min:0',
            'price_egp_after_discount' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'price_usd_after_discount' => 'nullable|numeric|min:0',
            'delivery_type' => 'required|in:group,one_to_one',
            'billing_mode' => 'nullable|in:one_time,monthly',
            'monthly_price' => 'nullable|numeric|min:0',
            'monthly_price_after_discount' => 'nullable|numeric|min:0',
            'requirements' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'what_you_learn' => 'nullable|string',
            'skills' => 'nullable|array',
            'skills.*' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.config('upload_limits.max_upload_kb'),
            'thumbnail_link' => 'nullable|url|max:500',
            'remove_thumbnail' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ], [
            'title.required' => 'عنوان الكورس مطلوب',
            'academic_year_id.exists' => 'المسار التعليمي المحدد غير موجود',
            'academic_subject_id.exists' => 'مجموعة المهارات المحددة غير موجودة',
            'course_category_id.exists' => 'التصنيف المحدد غير موجود',
            'instructor_id.exists' => 'المدرب المحدد غير موجود',
            'instructor_id.required' => 'يجب تعيين مدرّس مسؤول للكورس',
            'delivery_type.required' => 'اختر نوع التعلّم (جماعي أو فردي)',
            'duration_hours.numeric' => 'مدة الكورس يجب أن تكون رقم',
            'duration_hours.min' => 'مدة الكورس لا يمكن أن تكون أقل من صفر',
            'duration_minutes.integer' => 'المدة الإضافية يجب أن تكون رقم صحيح',
            'duration_minutes.max' => 'الدقائق يجب ألا تتجاوز 59 دقيقة',
            'price.numeric' => 'السعر يجب أن يكون رقم',
            'price.min' => 'السعر لا يمكن أن يكون أقل من صفر',
            'price_after_discount.numeric' => 'سعر بعد الخصم يجب أن يكون رقماً',
            'price_after_discount.min' => 'سعر بعد الخصم لا يمكن أن يكون سالباً',
            'thumbnail.image' => 'يجب أن تكون صورة صحيحة',
            'thumbnail.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png, jpg أو webp',
            'thumbnail_link.url' => 'رابط الصورة غير صالح',
            'thumbnail.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'ends_at.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
        ]);

        $data = array_merge(
            $request->only([
                'title',
                'academic_year_id',
                'academic_subject_id',
                'course_category_id',
                'description',
                'video_url',
                'objectives',
                'instructor_id',
                'programming_language',
                'framework',
                'duration_hours',
                'duration_minutes',
                'price',
                'price_egp',
                'price_egp_after_discount',
                'price_usd',
                'price_usd_after_discount',
                'delivery_type',
                'billing_mode',
                'monthly_price',
                'requirements',
                'prerequisites',
                'what_you_learn',
                'language',
                'starts_at',
                'ends_at',
            ]),
            [
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'students_count' => 0,
                'rating' => 0,
                'reviews_count' => 0,
            ]
        );
        $data['academic_year_id'] = $request->filled('academic_year_id') ? (int) $data['academic_year_id'] : null;
        $data['academic_subject_id'] = $request->filled('academic_subject_id') ? (int) $data['academic_subject_id'] : null;
        $data['course_category_id'] = $request->filled('course_category_id') ? (int) $data['course_category_id'] : null;
        $data['instructor_id'] = isset($data['instructor_id']) && $data['instructor_id'] !== '' ? (int) $data['instructor_id'] : null;

        if ($data['course_category_id']) {
            $data['category'] = CourseCategory::whereKey($data['course_category_id'])->value('name');
        } else {
            $data['category'] = null;
        }

        $data['level'] = 'beginner';
        $data['price'] = $data['price'] ?? 0;
        if ($request->filled('price_usd')) {
            $data['price'] = $data['price_usd'];
            $data['price_after_discount'] = $request->input('price_usd_after_discount');
            $data['price_egp'] = $data['price_usd'];
            $data['price_egp_after_discount'] = $request->input('price_usd_after_discount');
        } elseif ($request->filled('price_egp')) {
            $data['price'] = $data['price_egp'];
            $data['price_after_discount'] = $request->input('price_egp_after_discount');
        }
        $list = (float) $data['price'];
        if ($list > 0
            && $request->filled('price_after_discount')
            && (float) $request->input('price_after_discount') >= $list) {
            return back()->withErrors(['price_after_discount' => 'سعر بعد الخصم يجب أن يكون أقل من السعر الأساسي.'])->withInput();
        }
        if ($list <= 0 && $request->filled('price_after_discount')) {
            return back()->withErrors(['price_after_discount' => 'لا يُستخدم سعر بعد الخصم للكورس المجاني.'])->withInput();
        }
        $data['price_after_discount'] = $this->normalizeOptionalSalePrice($request->input('price_after_discount'), $list);
        $monthlyList = (float) ($request->input('monthly_price') ?? 0);
        $data['delivery_type'] = in_array($request->input('delivery_type'), ['group', 'one_to_one'], true)
            ? $request->input('delivery_type') : 'group';
        $data['billing_mode'] = in_array($request->input('billing_mode'), ['one_time', 'monthly'], true)
            ? $request->input('billing_mode') : 'one_time';
        $data['monthly_price'] = $monthlyList > 0 ? $monthlyList : null;
        $data['monthly_price_after_discount'] = $monthlyList > 0
            ? $this->normalizeOptionalSalePrice($request->input('monthly_price_after_discount'), $monthlyList)
            : null;
        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;
        $data['language'] = $data['language'] ?? 'ar';
        $data['skills'] = $request->filled('skills')
            ? array_values(array_filter($request->input('skills', [])))
            : null;

        $data['thumbnail'] = CourseThumbnailStorage::resolveFromRequest($request);

        try {
            AdvancedCourse::create($data);
        } catch (\Throwable $e) {
            Log::error('AdvancedCourse::store create failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return redirect()->route('admin.advanced-courses.index')
            ->with('success', 'تم إنشاء الكورس بنجاح');
    }

    /**
     * عرض تفاصيل كورس محدد
     */
    public function show(AdvancedCourse $advancedCourse)
    {
        $advancedCourse->load([
            'instructor',
            'lessons' => function ($query) {
                $query->ordered();
            },
            'enrollments.student',
            'orders' => function ($query) {
                $query->with(['user'])->orderBy('created_at', 'desc');
            },
        ]);

        // إحصائيات
        $stats = [
            'total_lessons' => $advancedCourse->lessons->count(),
            'active_lessons' => $advancedCourse->lessons->where('is_active', true)->count(),
            'total_students' => $advancedCourse->enrollments->count(),
            'active_students' => $advancedCourse->enrollments->where('status', 'active')->count(),
            'pending_orders' => $advancedCourse->orders->where('status', 'pending')->count(),
            'total_duration' => $advancedCourse->lessons->sum('duration_minutes'),
        ];

        return view('admin.advanced-courses.show', compact('advancedCourse', 'stats'));
    }

    /**
     * عرض صفحة تعديل كورس
     */
    public function edit(AdvancedCourse $advancedCourse)
    {
        $instructors = User::where('role', 'instructor')->where('is_active', true)->orderBy('name')->get();

        $courseCategories = CourseCategory::query()
            ->where(function ($q) use ($advancedCourse) {
                $q->where('is_active', true);
                if ($advancedCourse->course_category_id) {
                    $q->orWhere('id', $advancedCourse->course_category_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $trackOptions = [];

        $selectedSkills = old('skills');
        if ($selectedSkills === null) {
            $skills = $advancedCourse->skills;
            $selectedSkills = is_array($skills) ? $skills : (is_string($skills) ? json_decode($skills, true) : []);
        }
        $selectedSkills = is_array($selectedSkills) ? $selectedSkills : [];

        return view('admin.advanced-courses.edit', compact(
            'advancedCourse',
            'instructors',
            'courseCategories',
            'trackOptions',
            'selectedSkills'
        ));
    }

    /**
     * تحديث بيانات كورس
     */
    public function update(Request $request, AdvancedCourse $advancedCourse)
    {
        // تحويل القيم الفارغة لـ المسار ومجموعة المهارات إلى null (اختياريان)
        $request->merge([
            'academic_year_id' => $request->filled('academic_year_id') ? $request->academic_year_id : null,
            'academic_subject_id' => $request->filled('academic_subject_id') ? $request->academic_subject_id : null,
            'course_category_id' => $request->filled('course_category_id') ? $request->course_category_id : null,
        ]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'academic_subject_id' => 'nullable|exists:academic_subjects,id',
            'course_category_id' => 'nullable|exists:course_categories,id',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'objectives' => 'nullable|string',
            'instructor_id' => 'required|exists:users,id',
            'programming_language' => 'nullable|string|max:100',
            'framework' => 'nullable|string|max:100',
            'duration_hours' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0|max:59',
            'price' => 'nullable|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0',
            'price_egp' => 'nullable|numeric|min:0',
            'price_egp_after_discount' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'price_usd_after_discount' => 'nullable|numeric|min:0',
            'delivery_type' => 'required|in:group,one_to_one',
            'billing_mode' => 'nullable|in:one_time,monthly',
            'monthly_price' => 'nullable|numeric|min:0',
            'monthly_price_after_discount' => 'nullable|numeric|min:0',
            'requirements' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'what_you_learn' => 'nullable|string',
            'skills' => 'nullable|array',
            'skills.*' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.config('upload_limits.max_upload_kb'),
            'thumbnail_link' => 'nullable|url|max:500',
            'remove_thumbnail' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ], [
            'title.required' => 'عنوان الكورس مطلوب',
            'academic_year_id.exists' => 'المسار التعليمي المحدد غير موجود',
            'academic_subject_id.exists' => 'مجموعة المهارات المحددة غير موجودة',
            'course_category_id.exists' => 'التصنيف المحدد غير موجود',
            'instructor_id.exists' => 'المدرب المحدد غير موجود',
            'instructor_id.required' => 'يجب تعيين مدرّس مسؤول للكورس',
            'delivery_type.required' => 'اختر نوع التعلّم (جماعي أو فردي)',
            'duration_minutes.max' => 'الدقائق يجب ألا تتجاوز 59 دقيقة',
            'price_after_discount.numeric' => 'سعر بعد الخصم يجب أن يكون رقماً',
            'price_after_discount.min' => 'سعر بعد الخصم لا يمكن أن يكون سالباً',
            'thumbnail.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'ends_at.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
        ]);

        $data = $request->only([
            'title',
            'academic_year_id',
            'academic_subject_id',
            'course_category_id',
            'description',
            'video_url',
            'objectives',
            'instructor_id',
            'programming_language',
            'framework',
            'duration_hours',
            'duration_minutes',
            'price',
            'price_egp',
            'price_egp_after_discount',
            'price_usd',
            'price_usd_after_discount',
            'delivery_type',
            'billing_mode',
            'monthly_price',
            'requirements',
            'prerequisites',
            'what_you_learn',
            'language',
            'starts_at',
            'ends_at',
        ]);

        $data['level'] = 'beginner';
        $data['price'] = $data['price'] ?? 0;
        if ($request->filled('price_usd')) {
            $data['price'] = $data['price_usd'];
            $data['price_after_discount'] = $request->input('price_usd_after_discount');
            $data['price_egp'] = $data['price_usd'];
            $data['price_egp_after_discount'] = $request->input('price_usd_after_discount');
        } elseif ($request->filled('price_egp')) {
            $data['price'] = $data['price_egp'];
            $data['price_after_discount'] = $request->input('price_egp_after_discount');
        }
        $list = (float) $data['price'];
        if ($list > 0
            && $request->filled('price_after_discount')
            && (float) $request->input('price_after_discount') >= $list) {
            return back()->withErrors(['price_after_discount' => 'سعر بعد الخصم يجب أن يكون أقل من السعر الأساسي.'])->withInput();
        }
        if ($list <= 0 && $request->filled('price_after_discount')) {
            return back()->withErrors(['price_after_discount' => 'لا يُستخدم سعر بعد الخصم للكورس المجاني.'])->withInput();
        }
        $data['price_after_discount'] = $this->normalizeOptionalSalePrice($request->input('price_after_discount'), $list);
        $monthlyList = (float) ($request->input('monthly_price') ?? 0);
        $data['delivery_type'] = in_array($request->input('delivery_type'), ['group', 'one_to_one'], true)
            ? $request->input('delivery_type') : 'group';
        $data['billing_mode'] = in_array($request->input('billing_mode'), ['one_time', 'monthly'], true)
            ? $request->input('billing_mode') : 'one_time';
        $data['monthly_price'] = $monthlyList > 0 ? $monthlyList : null;
        $data['monthly_price_after_discount'] = $monthlyList > 0
            ? $this->normalizeOptionalSalePrice($request->input('monthly_price_after_discount'), $monthlyList)
            : null;
        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['duration_minutes'] = $data['duration_minutes'] ?? 0;
        $data['language'] = $data['language'] ?? 'ar';
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['skills'] = $request->filled('skills')
            ? array_values(array_filter($request->input('skills', [])))
            : null;

        // المسار ومجموعة المهارات اختياريان في التعديل
        $data['academic_year_id'] = $request->filled('academic_year_id') ? $data['academic_year_id'] : null;
        $data['academic_subject_id'] = $request->filled('academic_subject_id') ? $data['academic_subject_id'] : null;
        $data['course_category_id'] = $request->filled('course_category_id') ? (int) $data['course_category_id'] : null;
        if ($data['course_category_id']) {
            $data['category'] = CourseCategory::whereKey($data['course_category_id'])->value('name');
        } else {
            $data['category'] = null;
        }

        if ($request->hasFile('thumbnail') || $request->filled('thumbnail_link') || $request->boolean('remove_thumbnail')) {
            $data['thumbnail'] = CourseThumbnailStorage::resolveFromRequest($request, $advancedCourse->thumbnail);
        }

        $advancedCourse->update($data);

        return redirect()->route('admin.advanced-courses.index')
            ->with('success', 'تم تحديث الكورس بنجاح');
    }

    /**
     * حذف كورس
     * الحذف يتم باستعلامات مباشرة دون أحداث النموذج لتجنب خطأ 500.
     */
    public function destroy(AdvancedCourse $advancedCourse)
    {
        $courseId = $advancedCourse->id;
        $title = $advancedCourse->title;

        try {
            Log::info('بدء حذف الكورس', ['course_id' => $courseId, 'title' => $title]);

            DB::transaction(function () use ($courseId) {
                AdvancedCourse::deleteRelatedRecords($courseId);
                DB::table('advanced_courses')->where('id', $courseId)->delete();
            });

            Log::info('تم حذف الكورس بنجاح', ['course_id' => $courseId]);

            return redirect()->route('admin.advanced-courses.index')
                ->with('success', 'تم حذف الكورس بنجاح');
        } catch (\Throwable $e) {
            Log::error('حذف الكورس فشل', [
                'course_id' => $courseId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.advanced-courses.index')
                ->with('error', 'حدث خطأ أثناء حذف الكورس. قد يكون الكورس مرتبطاً ببيانات أخرى.');
        }
    }

    /**
     * تفعيل طالب في الكورس
     */
    public function activateStudent(Request $request, AdvancedCourse $advancedCourse)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // التحقق من عدم وجود الطالب مسبقاً
        if ($advancedCourse->enrollments()->where('user_id', $request->user_id)->exists()) {
            return back()->with('error', 'الطالب مسجل بالفعل في هذا الكورس');
        }

        $advancedCourse->enrollments()->create([
            'user_id' => $request->user_id,
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        return back()->with('success', 'تم تفعيل الطالب في الكورس بنجاح');
    }

    /**
     * عرض طلاب الكورس
     */
    public function students(AdvancedCourse $advancedCourse)
    {
        $advancedCourse->load(['enrollments.user']);
        $availableStudents = User::where('role', 'student')
            ->whereNotIn('id', $advancedCourse->enrollments->pluck('user_id'))
            ->get();

        return view('admin.advanced-courses.students', compact('advancedCourse', 'availableStudents'));
    }

    /**
     * تغيير حالة الكورس (تفعيل/إلغاء تفعيل)
     */
    public function toggleStatus(AdvancedCourse $advancedCourse)
    {
        $advancedCourse->update([
            'is_active' => ! $advancedCourse->is_active,
        ]);

        $status = $advancedCourse->is_active ? 'تم تفعيل' : 'تم إيقاف';

        return response()->json([
            'success' => true,
            'message' => $status.' الكورس بنجاح',
            'is_active' => $advancedCourse->is_active,
        ]);
    }

    /**
     * تغيير حالة الترشيح للكورس
     */
    public function toggleFeatured(AdvancedCourse $advancedCourse)
    {
        $advancedCourse->update([
            'is_featured' => ! $advancedCourse->is_featured,
        ]);

        $status = $advancedCourse->is_featured ? 'تم ترشيح' : 'تم إلغاء ترشيح';

        return response()->json([
            'success' => true,
            'message' => $status.' الكورس بنجاح',
            'is_featured' => $advancedCourse->is_featured,
        ]);
    }

    /**
     * عرض الطلبات الخاصة بالكورس
     */
    public function orders(AdvancedCourse $advancedCourse)
    {
        $orders = $advancedCourse->orders()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.advanced-courses.orders', compact('advancedCourse', 'orders'));
    }

    /**
     * إحصائيات الكورس
     */
    public function statistics(AdvancedCourse $advancedCourse)
    {
        $advancedCourse->loadMissing(['enrollments', 'lessons', 'orders']);

        $stats = [
            // إحصائيات الطلاب
            'students' => [
                'total' => $advancedCourse->enrollments->count(),
                'active' => $advancedCourse->enrollments->where('status', 'active')->count(),
                'completed' => $advancedCourse->enrollments->where('status', 'completed')->count(),
                'pending' => $advancedCourse->enrollments->where('status', 'pending')->count(),
            ],

            // إحصائيات الدروس
            'lessons' => [
                'total' => $advancedCourse->lessons->count(),
                'active' => $advancedCourse->lessons->where('is_active', true)->count(),
                'video' => $advancedCourse->lessons->where('type', 'video')->count(),
                'document' => $advancedCourse->lessons->where('type', 'document')->count(),
                'quiz' => $advancedCourse->lessons->where('type', 'quiz')->count(),
                'total_duration' => $advancedCourse->lessons->sum('duration_minutes'),
            ],

            // إحصائيات الطلبات
            'orders' => [
                'total' => $advancedCourse->orders->count(),
                'pending' => $advancedCourse->orders->where('status', 'pending')->count(),
                'approved' => $advancedCourse->orders->where('status', 'approved')->count(),
                'rejected' => $advancedCourse->orders->where('status', 'rejected')->count(),
            ],

            // التقدم العام
            'progress' => [
                'average' => $advancedCourse->enrollments->where('status', 'active')->avg('progress') ?? 0,
                'completion_rate' => $advancedCourse->enrollments->count() > 0
                    ? ($advancedCourse->enrollments->where('status', 'completed')->count() / $advancedCourse->enrollments->count()) * 100
                    : 0,
            ],
        ];

        return view('admin.advanced-courses.statistics', compact('advancedCourse', 'stats'));
    }

    /**
     * تصدير بيانات الكورس
     */
    public function export(AdvancedCourse $advancedCourse)
    {
        // يمكن تطوير هذه الوظيفة لتصدير بيانات الكورس إلى Excel أو PDF
        return response()->json([
            'message' => 'سيتم تطوير وظيفة التصدير قريباً',
        ]);
    }

    /**
     * نسخ الكورس
     */
    public function duplicate(AdvancedCourse $advancedCourse)
    {
        $newCourse = $advancedCourse->replicate();
        $newCourse->title = $advancedCourse->title.' - نسخة';
        $newCourse->is_active = false;
        $newCourse->is_featured = false;
        $newCourse->save();

        // نسخ الدروس
        foreach ($advancedCourse->lessons as $lesson) {
            $newLesson = $lesson->replicate();
            $newLesson->advanced_course_id = $newCourse->id;
            $newLesson->save();
        }

        return redirect()->route('admin.advanced-courses.edit', $newCourse)
            ->with('success', 'تم نسخ الكورس بنجاح. يمكنك الآن تعديل البيانات حسب الحاجة.');
    }
}
