<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AdvancedCourse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::with(['academicSubjects'])
            ->withCount('academicSubjects')
            ->ordered()
            ->get();

        $allCourses = AdvancedCourse::where('is_active', true)
            ->select([
                'id',
                'title',
                'description',
                'category',
                'programming_language',
                'framework',
                'level',
                'duration_hours',
                'duration_minutes',
                'price',
                'is_free',
                'rating',
                'skills',
                'created_at',
            ])
            ->get();

        $tracks = $academicYears->map(function (AcademicYear $year) use ($allCourses) {
            return $this->hydrateTrack($year, $allCourses);
        });

        $summary = [
            'total_tracks' => $tracks->count(),
            'active_tracks' => $tracks->where('is_active', true)->count(),
            'skill_clusters' => $tracks->sum('academic_subjects_count'),
            'courses' => $tracks->sum(fn($track) => optional($track->track_metrics)['courses_count'] ?? 0),
            'languages' => $tracks->flatMap(fn($track) => optional($track->track_metrics)['languages'] ?? [])->filter()->unique()->values(),
            'frameworks' => $tracks->flatMap(fn($track) => optional($track->track_metrics)['frameworks'] ?? [])->filter()->unique()->values(),
        ];

        return view('admin.academic-years.index', compact('tracks', 'summary'));
    }

    public function create()
    {
        return view('admin.academic-years.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:academic_years',
            'code' => 'required|string|max:10|unique:academic_years',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:academic_years,slug',
            'tagline' => 'nullable|string|max:255',
            'level_number' => 'nullable|integer|min:1|max:20|unique:academic_years,level_number',
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'price' => 'nullable|numeric|min:0',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ], [
            'name.required' => 'اسم سنة المدرسة مطلوب',
            'name.unique' => 'اسم سنة المدرسة موجود مسبقاً',
            'code.required' => 'رمز سنة المدرسة مطلوب',
            'code.unique' => 'رمز سنة المدرسة موجود مسبقاً',
            'thumbnail.image' => 'يجب أن يكون الملف صورة',
            'thumbnail.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png أو jpg',
            'thumbnail.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        $data = $request->only([
            'name', 'code', 'slug', 'tagline', 'level_number', 'description', 'video_url', 'price', 'icon', 'color', 'order',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_public'] = $request->boolean('is_public');
        $data['order'] = (int) ($data['order'] ?? 0);
        $data['price'] = $request->input('price', 0) ?: 0;
        $data['icon'] = $data['icon'] ?? 'fas fa-graduation-cap';
        $data['color'] = $data['color'] ?? '#0B3D91';
        $data['slug'] = ! empty($data['slug'])
            ? AcademicYear::uniqueSlug($data['slug'])
            : AcademicYear::uniqueSlug($data['name']);
        if (empty($data['level_number'])) {
            $data['level_number'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = \App\Services\PublicMediaStorage::store(
                $request->file('thumbnail'),
                'academic-years',
                null
            );
        }

        AcademicYear::create($data);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'تم إضافة سنة المدرسة بنجاح');
    }

    public function show(AcademicYear $academicYear)
    {
        $academicYear->load(['academicSubjects']);

        $academicYear->academicSubjects->each(function ($subject) {
            $subject->advanced_courses_count = 0;
            $subject->setRelation('advancedCourses', collect());
        });

        return view('admin.academic-years.show', compact('academicYear'));
    }

    public function edit(AcademicYear $academicYear)
    {
        $academicYear->load([
            'academicSubjects' => function ($query) {
                $query->orderBy('order')->orderBy('name');
            },
            'linkedCourses' => function($query) {
                $query->where('is_active', true)
                      ->with(['academicSubject', 'academicYear', 'instructor'])
                      ->withCount('lessons');
            },
            'instructors'
        ]);

        $allCourses = AdvancedCourse::where('is_active', true)
            ->select([
                'id',
                'title',
                'description',
                'category',
                'programming_language',
                'framework',
                'level',
                'duration_hours',
                'duration_minutes',
                'price',
                'is_free',
                'rating',
                'skills',
                'instructor_id',
                'created_at',
            ])
            ->with(['instructor', 'academicSubject', 'academicYear'])
            ->get();

        $track = $this->hydrateTrack($academicYear, $allCourses);

        $clusters = $academicYear->academicSubjects->map(function ($subject) use ($allCourses) {
            return $this->hydrateClusterForTrack($subject, $allCourses);
        });

        $trackSummary = [
            'courses_count' => optional($track->track_metrics)['courses_count'] ?? 0,
            'languages' => collect(optional($track->track_metrics)['languages'] ?? []),
            'frameworks' => collect(optional($track->track_metrics)['frameworks'] ?? []),
            'levels' => collect(optional($track->track_metrics)['levels'] ?? []),
            'avg_duration' => optional($track->track_metrics)['avg_duration'] ?? null,
            'avg_rating' => optional($track->track_metrics)['avg_rating'] ?? null,
        ];

        // جلب المدربين المتاحين
        $availableInstructors = \App\Models\User::where('role', 'instructor')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.academic-years.edit', [
            'academicYear' => $academicYear,
            'track' => $track,
            'clusters' => $clusters,
            'trackSummary' => $trackSummary,
            'allCourses' => $allCourses,
            'availableInstructors' => $availableInstructors,
        ]);
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years')->ignore($academicYear->id),
            ],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('academic_years')->ignore($academicYear->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('academic_years', 'slug')->ignore($academicYear->id),
            ],
            'tagline' => 'nullable|string|max:255',
            'level_number' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
                Rule::unique('academic_years', 'level_number')->ignore($academicYear->id),
            ],
            'description' => 'nullable|string',
            'video_url' => 'nullable|url|max:500',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'price' => 'nullable|numeric|min:0',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
        ], [
            'name.required' => 'اسم سنة المدرسة مطلوب',
            'name.unique' => 'اسم سنة المدرسة موجود مسبقاً',
            'code.required' => 'رمز سنة المدرسة مطلوب',
            'code.unique' => 'رمز سنة المدرسة موجود مسبقاً',
            'thumbnail.image' => 'يجب أن يكون الملف صورة',
            'thumbnail.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png أو jpg',
            'thumbnail.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
        ]);

        $data = $request->only([
            'name', 'code', 'slug', 'tagline', 'level_number', 'description', 'video_url', 'price', 'icon', 'color', 'order',
        ]);
        $data['is_active'] = $request->has('is_active');
        $data['is_public'] = $request->boolean('is_public');
        $data['order'] = $data['order'] ?? 0;
        $data['price'] = $request->input('price', $academicYear->price ?? 0);
        $data['slug'] = ! empty($data['slug'])
            ? AcademicYear::uniqueSlug($data['slug'], $academicYear->id)
            : AcademicYear::uniqueSlug($data['name'], $academicYear->id);
        if (empty($data['level_number'])) {
            $data['level_number'] = null;
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = \App\Services\PublicMediaStorage::store(
                $request->file('thumbnail'),
                'academic-years',
                $academicYear->thumbnail
            );
        }

        $academicYear->update($data);

        return redirect()->route('admin.academic-years.edit', $academicYear)
            ->with('success', 'تم تحديث سنة المدرسة بنجاح');
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->academicSubjects()->count() > 0) {
            return redirect()->route('admin.academic-years.index')
                ->with('error', 'لا يمكن حذف المسار لأنه يحتوي على مجموعات مهارية');
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'تم حذف مسار التعلم بنجاح');
    }

    /**
     * إضافة كورس للمسار
     */
    public function addCourse(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
        ]);

        // التحقق من عدم وجود الكورس بالفعل
        if ($academicYear->linkedCourses()->where('advanced_course_id', $request->course_id)->exists()) {
            return back()->withErrors(['error' => 'هذا الكورس مرتبط بالفعل بالمسار']);
        }

        $academicYear->linkedCourses()->attach($request->course_id, [
            'order' => $request->order ?? 0,
            'is_required' => $request->has('is_required'),
        ]);

        return back()->with('success', 'تم إضافة الكورس للمسار بنجاح');
    }

    /**
     * إزالة كورس من المسار
     */
    public function removeCourse(AcademicYear $academicYear, AdvancedCourse $course)
    {
        $academicYear->linkedCourses()->detach($course->id);
        return back()->with('success', 'تم إزالة الكورس من المسار بنجاح');
    }

    /**
     * إضافة مدرب للمسار
     */
    public function addInstructor(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'assigned_courses' => 'nullable|array',
            'assigned_courses.*' => 'exists:advanced_courses,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من أن المستخدم مدرب
        $instructor = \App\Models\User::findOrFail($request->instructor_id);
        if ($instructor->role !== 'instructor') {
            return back()->withErrors(['error' => 'المستخدم المحدد ليس مدرب']);
        }

        // التحقق من عدم وجود المدرب بالفعل
        if ($academicYear->instructors()->where('instructor_id', $request->instructor_id)->exists()) {
            return back()->withErrors(['error' => 'هذا المدرب مرتبط بالفعل بالمسار']);
        }

        $academicYear->instructors()->attach($request->instructor_id, [
            'assigned_courses' => json_encode($request->assigned_courses ?? []),
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'تم إضافة المدرب للمسار بنجاح');
    }

    /**
     * إزالة مدرب من المسار
     */
    public function removeInstructor(AcademicYear $academicYear, User $instructor)
    {
        $academicYear->instructors()->detach($instructor->id);
        return back()->with('success', 'تم إزالة المدرب من المسار بنجاح');
    }

    public function toggleStatus(Request $request, AcademicYear $academicYear)
    {
        $academicYear->update([
            'is_active' => !$academicYear->is_active
        ]);

        $status = $academicYear->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $status . ' المسار بنجاح',
                'is_active' => $academicYear->is_active
            ]);
        }

        return redirect()->back()->with('success', $status . ' المسار بنجاح');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:academic_years,id',
            'items.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            AcademicYear::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث ترتيب المسارات التعليمية بنجاح'
        ]);
    }

    private function hydrateTrack(AcademicYear $year, Collection $courses): AcademicYear
    {
        $matchedCourses = $this->filterCourses($courses, [$year->code, $year->name, $year->description]);
        if ($matchedCourses->isEmpty()) {
            $matchedCourses = $courses;
        }

        $languages = $matchedCourses->pluck('programming_language')->filter()->unique()->values();
        $frameworks = $matchedCourses->pluck('framework')->filter()->unique()->values();
        $levels = $matchedCourses->pluck('level')->filter()->unique()->values();
        $minutes = $matchedCourses->sum(function ($course) {
            return ((int) ($course->duration_hours ?? 0) * 60) + (int) ($course->duration_minutes ?? 0);
        });
        $avgMinutes = $matchedCourses->count() > 0 ? (int) round($minutes / $matchedCourses->count()) : 0;

        $year->setAttribute('track_metrics', [
            'courses_count' => $matchedCourses->count(),
            'languages' => $languages->take(8),
            'frameworks' => $frameworks->take(8),
            'levels' => $levels,
            'avg_duration' => $this->formatDurationMinutes($avgMinutes),
            'avg_rating' => $matchedCourses->count() > 0 ? round((float) ($matchedCourses->avg('rating') ?? 0), 1) : null,
        ]);

        $year->setRelation('preview_courses', $matchedCourses->sortByDesc('created_at')->take(3));

        return $year;
    }

    private function hydrateClusterForTrack($subject, Collection $courses)
    {
        $track = $subject->academicYear;

        $matchedCourses = $this->filterCourses($courses, [
            optional($track)->code,
            optional($track)->name,
            $subject->code,
            $subject->name,
            $subject->description,
        ]);

        if ($matchedCourses->isEmpty()) {
            $matchedCourses = $courses;
        }

        $languages = $matchedCourses->pluck('programming_language')->filter()->unique()->values();
        $frameworks = $matchedCourses->pluck('framework')->filter()->unique()->values();
        $levels = $matchedCourses->pluck('level')->filter()->unique()->values();
        $minutes = $matchedCourses->sum(function ($course) {
            return ((int) ($course->duration_hours ?? 0) * 60) + (int) ($course->duration_minutes ?? 0);
        });
        $avgMinutes = $matchedCourses->count() > 0 ? (int) round($minutes / $matchedCourses->count()) : 0;

        $subject->setAttribute('cluster_metrics', [
            'courses_count' => $matchedCourses->count(),
            'languages' => $languages->take(8),
            'frameworks' => $frameworks->take(8),
            'levels' => $levels,
            'avg_duration' => $this->formatDurationMinutes($avgMinutes),
            'avg_rating' => $matchedCourses->count() > 0 ? round((float) ($matchedCourses->avg('rating') ?? 0), 1) : null,
        ]);

        $subject->setRelation('preview_courses', $matchedCourses->sortByDesc('created_at')->take(3));

        return $subject;
    }

    private function filterCourses(Collection $courses, array $identifiers): Collection
    {
        $needles = collect($identifiers)
            ->filter()
            ->map(fn($value) => Str::of($value)->lower()->replace(['-', '_'], ' ')->squish())
            ->filter(fn($value) => $value->isNotEmpty());

        if ($needles->isEmpty()) {
            return collect();
        }

        return $courses->filter(function (AdvancedCourse $course) use ($needles) {
            $fields = collect([
                $course->category,
                $course->programming_language,
                $course->framework,
                $course->level,
                $course->title,
                $course->description,
            ])->merge((array) ($course->skills ?? []));

            return $fields->contains(function ($field) use ($needles) {
                if (empty($field)) {
                    return false;
                }

                $value = Str::of($field)->lower()->replace(['-', '_'], ' ')->squish();

                foreach ($needles as $needle) {
                    if ($needle->isNotEmpty() && Str::contains($value, $needle)) {
                        return true;
                    }
                }

                return false;
            });
        })->values();
    }

    private function formatDurationMinutes(int $minutes): ?string
    {
        if ($minutes <= 0) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours === 0) {
            return $remaining . ' د';
        }

        if ($remaining === 0) {
            return $hours . ' س';
        }

        return $hours . ' س ' . $remaining . ' د';
    }
}