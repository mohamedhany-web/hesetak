<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseSection;
use App\Models\CurriculumItem;
use App\Services\AssignmentFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $instructor = Auth::user();

        // جلب الكورسات التي يدرسها المدرب
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        // جلب الواجبات
        $query = Assignment::where(function ($q) use ($instructor) {
            $q->whereHas('course', function ($q2) use ($instructor) {
                $q2->where('instructor_id', $instructor->id);
            })->orWhere('teacher_id', $instructor->id);
        })
            ->with(['course', 'lesson', 'teacher'])
            ->withCount('submissions');

        // فلترة حسب الكورس
        if ($request->filled('course_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('advanced_course_id', $request->course_id)
                    ->orWhere('course_id', $request->course_id);
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(20);

        // إحصائيات
        $stats = [
            'total' => Assignment::whereHas('course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->count(),
            'published' => Assignment::whereHas('course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->where('status', 'published')->count(),
            'draft' => Assignment::whereHas('course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->where('status', 'draft')->count(),
            'total_submissions' => AssignmentSubmission::whereHas('assignment.course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->count(),
        ];

        return view('instructor.assignments.index', compact('assignments', 'courses', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $instructor = Auth::user();

        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        return view('instructor.assignments.create', compact('courses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $instructor = Auth::user();

        $validated = $request->validate(array_merge([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1|max:1000',
            'allow_late_submission' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ], $this->resourceFileValidationRules()), [
            'advanced_course_id.required' => 'يجب اختيار الكورس',
            'advanced_course_id.exists' => 'الكورس المحدد غير موجود',
            'title.required' => 'عنوان الواجب مطلوب',
            'max_score.min' => 'الحد الأدنى للدرجة هو 1',
            'max_score.max' => 'الحد الأقصى للدرجة هو 1000',
        ]);

        // التحقق من أن الكورس يخص هذا المدرب
        $course = AdvancedCourse::where('id', $validated['advanced_course_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        // التحقق من أن الدرس يخص الكورس إذا تم تحديده
        if (! empty($validated['lesson_id'])) {
            $lesson = \App\Models\CourseLesson::where('id', $validated['lesson_id'])
                ->where('advanced_course_id', $validated['advanced_course_id'])
                ->firstOrFail();
        }

        $validated['teacher_id'] = $instructor->id;
        $validated['allow_late_submission'] = $request->has('allow_late_submission');
        // استخدام advanced_course_id فقط — course_id يبقى null (يشير لجدول courses القديم)
        unset($validated['course_id']);
        unset($validated['resource_files']);

        $assignment = Assignment::create($validated);

        $uploaded = $this->storeResourceFilesFromRequest($request);
        if ($uploaded !== []) {
            $assignment->update(['resource_attachments' => $uploaded]);
        }

        $this->attachAssignmentToCourseCurriculum($assignment, (int) $course->id);

        return redirect()->route('instructor.assignments.show', $assignment)
            ->with('success', 'تم إنشاء الواجب بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Assignment $assignment)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بالوصول لهذا الواجب');
        }

        $assignment->load(['course', 'lesson', 'teacher', 'submissions.student']);

        // جلب الطلاب المسجلين في الكورس
        $enrollments = \App\Models\StudentCourseEnrollment::where('advanced_course_id', $courseId)
            ->where('status', 'active')
            ->with('user')
            ->get();

        // جلب التسليمات
        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->with(['student', 'grader'])
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);

        // إحصائيات
        $submissionStats = [
            'total' => $submissions->total(),
            'submitted' => AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('status', 'submitted')->count(),
            'graded' => AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('status', 'graded')->count(),
            'returned' => AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('status', 'returned')->count(),
        ];

        return view('instructor.assignments.show', compact('assignment', 'enrollments', 'submissions', 'submissionStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Assignment $assignment)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتعديل هذا الواجب');
        }

        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $lessons = \App\Models\CourseLesson::where('advanced_course_id', $courseId)
            ->orderBy('order')
            ->get();

        return view('instructor.assignments.edit', compact('assignment', 'courses', 'lessons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Assignment $assignment)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتعديل هذا الواجب');
        }

        $validated = $request->validate(array_merge([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1|max:1000',
            'allow_late_submission' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'remove_resource_indices' => 'nullable|array',
            'remove_resource_indices.*' => 'integer|min:0',
        ], $this->resourceFileValidationRules()));

        $course = AdvancedCourse::where('id', $validated['advanced_course_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        if (! empty($validated['lesson_id'])) {
            \App\Models\CourseLesson::where('id', $validated['lesson_id'])
                ->where('advanced_course_id', $validated['advanced_course_id'])
                ->firstOrFail();
        }

        $validated['allow_late_submission'] = $request->has('allow_late_submission');

        $removeIndices = $validated['remove_resource_indices'] ?? [];
        unset($validated['remove_resource_indices'], $validated['resource_files']);

        $currentResources = is_array($assignment->resource_attachments) ? $assignment->resource_attachments : [];
        if (is_array($removeIndices) && $removeIndices !== []) {
            foreach (array_unique(array_map('intval', $removeIndices)) as $idx) {
                if (isset($currentResources[$idx])) {
                    AssignmentFileStorage::deletePath($currentResources[$idx]['path'] ?? null);
                    unset($currentResources[$idx]);
                }
            }
            $currentResources = array_values($currentResources);
        }

        $assignment->update($validated);

        $uploaded = $this->storeResourceFilesFromRequest($request);
        if ($uploaded !== []) {
            $currentResources = array_values(array_merge($currentResources, $uploaded));
        }

        $assignment->update(['resource_attachments' => $currentResources !== [] ? $currentResources : null]);

        return redirect()->route('instructor.assignments.show', $assignment)
            ->with('success', 'تم تحديث الواجب بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assignment $assignment)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بحذف هذا الواجب');
        }

        if (is_array($assignment->resource_attachments)) {
            AssignmentFileStorage::deleteMany($assignment->resource_attachments);
        }

        $assignment->delete();

        return redirect()->route('instructor.assignments.index')
            ->with('success', 'تم حذف الواجب بنجاح');
    }

    /**
     * عرض تسليمات الواجب
     */
    public function submissions(Assignment $assignment)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بالوصول لتسليمات هذا الواجب');
        }

        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->with(['student', 'grader'])
            ->orderBy('submitted_at', 'desc')
            ->paginate(20);

        return view('instructor.assignments.submissions', compact('assignment', 'submissions'));
    }

    /**
     * تقييم تسليم واجب
     */
    public function grade(Request $request, Assignment $assignment, AssignmentSubmission $submission)
    {
        $instructor = Auth::user();

        // التحقق من أن الواجب يخص كورس يدرسه هذا المدرب
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $course = AdvancedCourse::where('id', $courseId)->first();

        if (! $course || $course->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتقييم هذا التسليم');
        }

        // التحقق من أن التسليم يخص هذا الواجب
        if ($submission->assignment_id !== $assignment->id) {
            abort(404, 'التسليم غير موجود');
        }

        $validated = $request->validate([
            'score' => 'nullable|integer|min:0|max:'.$assignment->max_score,
            'feedback' => 'nullable|string',
            'status' => 'required|in:submitted,graded,returned',
        ]);

        $validated['graded_by'] = $instructor->id;
        $validated['graded_at'] = now();
        // إذا لم تُدخل درجة نُبقي على القيمة الحالية
        if (! array_key_exists('score', $validated) || $validated['score'] === null || $validated['score'] === '') {
            $validated['score'] = $submission->score;
        }

        $submission->update($validated);

        return back()->with('success', 'تم تقييم التسليم بنجاح');
    }

    private function attachAssignmentToCourseCurriculum(Assignment $assignment, int $courseId): void
    {
        if (! Schema::hasTable('course_sections') || ! Schema::hasTable('curriculum_items')) {
            return;
        }

        $section = CourseSection::query()
            ->where('advanced_course_id', $courseId)
            ->where('is_active', true)
            ->orderBy('order')
            ->first();

        if (! $section) {
            $section = CourseSection::create([
                'advanced_course_id' => $courseId,
                'title' => 'الواجبات',
                'order' => 1,
                'is_active' => true,
            ]);
        }

        $already = CurriculumItem::query()
            ->where('course_section_id', $section->id)
            ->where('item_type', Assignment::class)
            ->where('item_id', $assignment->id)
            ->exists();

        if ($already) {
            return;
        }

        $lastOrder = (int) ($section->items()->max('order') ?? 0);
        CurriculumItem::create([
            'course_section_id' => $section->id,
            'item_type' => Assignment::class,
            'item_id' => $assignment->id,
            'order' => $lastOrder + 1,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resourceFileValidationRules(): array
    {
        return [
            'resource_files' => 'nullable|array|max:20',
            'resource_files.*' => 'file|max:40960|mimes:pdf,doc,docx,zip,rar,jpg,jpeg,png,gif,webp,ppt,pptx,txt',
        ];
    }

    /**
     * @return list<array{path: string, original_name: string, mime: string|null}>
     */
    private function storeResourceFilesFromRequest(Request $request): array
    {
        $meta = [];
        foreach ($request->file('resource_files', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }
            $path = AssignmentFileStorage::storeResource($file);
            $meta[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
            ];
        }

        return $meta;
    }
}
