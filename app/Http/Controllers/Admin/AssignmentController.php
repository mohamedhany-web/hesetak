<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AdvancedCourse;
use App\Models\CourseLesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AssignmentController extends Controller
{
    /**
     * عرض قائمة الكورسات لاختيار واحد منها وعرض واجباته
     */
    public function index(): View
    {
        $courses = AdvancedCourse::where('is_active', true)
            ->withCount('assignments')
            ->with('academicSubject')
            ->orderBy('title')
            ->get();

        return view('admin.assignments.index', compact('courses'));
    }

    /**
     * عرض واجبات كورس معين مع روابط CRUD
     */
    public function indexByCourse(AdvancedCourse $course): View
    {
        $course->loadCount('assignments')->load('academicSubject');
        $assignments = $course->assignments()
            ->withCount('submissions')
            ->with(['lesson', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.assignments.by-course', compact('course', 'assignments'));
    }

    /**
     * نموذج إنشاء واجب جديد
     */
    public function create(Request $request): View
    {
        $courses = AdvancedCourse::where('is_active', true)->with(['instructor:id,name', 'lessons:id,advanced_course_id,title,order'])->orderBy('title')->get();
        $selectedCourse = $request->get('course_id');
        return view('admin.assignments.create', compact('courses', 'selectedCourse'));
    }

    /**
     * حفظ واجب جديد
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1|max:1000',
            'allow_late_submission' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ], [
            'advanced_course_id.required' => 'يجب اختيار الكورس',
            'title.required' => 'عنوان الواجب مطلوب',
        ]);

        if (!empty($validated['lesson_id'])) {
            CourseLesson::where('id', $validated['lesson_id'])
                ->where('advanced_course_id', $validated['advanced_course_id'])
                ->firstOrFail();
        }

        $course = AdvancedCourse::find($validated['advanced_course_id']);
        $validated['teacher_id'] = $course->instructor_id ?? null;
        // course_id كان يشير لجدول courses القديم — لا نكتب معرف advanced_courses فيه
        $validated['course_id'] = null;
        $validated['allow_late_submission'] = $request->has('allow_late_submission');

        $assignment = Assignment::create($validated);
        $courseId = $validated['advanced_course_id'];

        return redirect()->route('admin.assignments.by-course', $courseId)
            ->with('success', 'تم إنشاء الواجب بنجاح');
    }

    /**
     * عرض تفاصيل واجب
     */
    public function show(Assignment $assignment): View
    {
        $assignment->load(['course.instructor', 'lesson', 'teacher', 'submissions.student']);
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;

        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->with(['student', 'grader'])
            ->orderBy('submitted_at', 'desc')
            ->paginate(15);

        $submissionStats = [
            'total' => AssignmentSubmission::where('assignment_id', $assignment->id)->count(),
            'submitted' => AssignmentSubmission::where('assignment_id', $assignment->id)->where('status', 'submitted')->count(),
            'graded' => AssignmentSubmission::where('assignment_id', $assignment->id)->where('status', 'graded')->count(),
            'returned' => AssignmentSubmission::where('assignment_id', $assignment->id)->where('status', 'returned')->count(),
        ];

        return view('admin.assignments.show', compact('assignment', 'submissions', 'submissionStats'));
    }

    /**
     * نموذج تعديل واجب
     */
    public function edit(Assignment $assignment): View
    {
        $assignment->load(['course', 'lesson']);
        $courses = AdvancedCourse::where('is_active', true)->orderBy('title')->get();
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $lessons = CourseLesson::where('advanced_course_id', $courseId)->orderBy('order')->get();

        return view('admin.assignments.edit', compact('assignment', 'courses', 'lessons'));
    }

    /**
     * تحديث واجب
     */
    public function update(Request $request, Assignment $assignment): RedirectResponse
    {
        $validated = $request->validate([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'due_date' => 'nullable|date',
            'max_score' => 'required|integer|min:1|max:1000',
            'allow_late_submission' => 'boolean',
            'status' => 'required|in:draft,published,archived',
        ]);

        if (!empty($validated['lesson_id'])) {
            CourseLesson::where('id', $validated['lesson_id'])
                ->where('advanced_course_id', $validated['advanced_course_id'])
                ->firstOrFail();
        }

        $validated['course_id'] = null;
        $validated['allow_late_submission'] = $request->has('allow_late_submission');
        $course = AdvancedCourse::find($validated['advanced_course_id']);
        $validated['teacher_id'] = $course->instructor_id ?? $assignment->teacher_id;

        $assignment->update($validated);
        $courseId = (int) $validated['advanced_course_id'];

        return redirect()->route('admin.assignments.by-course', $courseId)
            ->with('success', 'تم تحديث الواجب بنجاح');
    }

    /**
     * حذف واجب
     */
    public function destroy(Assignment $assignment): RedirectResponse
    {
        $courseId = $assignment->advanced_course_id ?? $assignment->course_id;
        $assignment->delete();
        return redirect()->route('admin.assignments.by-course', $courseId)
            ->with('success', 'تم حذف الواجب بنجاح');
    }

    /**
     * عرض تسليمات الواجب
     */
    public function submissions(Assignment $assignment): View
    {
        $assignment->load(['course.instructor']);
        $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->with(['student', 'grader'])
            ->orderBy('submitted_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $gradeSubmission = null;
        if ($assignment->id && request()->filled('grade')) {
            $gradeSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('id', request('grade'))
                ->with('student')
                ->first();
        }

        return view('admin.assignments.submissions', compact('assignment', 'submissions', 'gradeSubmission'));
    }

    /**
     * تقييم تسليم واجب
     */
    public function grade(Request $request, Assignment $assignment, AssignmentSubmission $submission): RedirectResponse
    {
        if ($submission->assignment_id != $assignment->id) {
            abort(404, 'التسليم غير موجود');
        }

        $validated = $request->validate([
            'score' => 'required|integer|min:0|max:' . (int) $assignment->max_score,
            'feedback' => 'nullable|string',
            'status' => 'required|in:submitted,graded,returned',
        ]);

        $validated['graded_by'] = auth()->id();
        $validated['graded_at'] = now();

        $submission->update($validated);

        return back()->with('success', 'تم تقييم التسليم بنجاح');
    }
}
