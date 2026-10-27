<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\AdvancedCourse;
use App\Models\CourseLesson;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * عرض قائمة الكورسات (الدخول للكورس يعرض امتحاناته).
     */
    public function index()
    {
        $courses = AdvancedCourse::active()
            ->withCount('exams')
            ->with(['academicSubject'])
            ->orderBy('title')
            ->get();

        return view('admin.exams.index', compact('courses'));
    }

    /**
     * عرض امتحانات كورس معين مع روابط CRUD.
     */
    public function indexByCourse(AdvancedCourse $course)
    {
        $course->loadCount('exams')->load('academicSubject');
        $exams = $course->exams()
            ->withCount(['questions', 'attempts'])
            ->with(['lesson'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.exams.by-course', compact('course', 'exams'));
    }

    /**
     * عرض صفحة إنشاء امتحان جديد
     */
    public function create(Request $request)
    {
        $courses = AdvancedCourse::active()->with(['academicSubject'])->get();
        $selectedCourse = $request->get('course_id');
        $lessons = $selectedCourse ? CourseLesson::where('advanced_course_id', $selectedCourse)->active()->get() : collect();

        return view('admin.exams.create', compact('courses', 'selectedCourse', 'lessons'));
    }

    /**
     * حفظ امتحان جديد
     */
    public function store(Request $request)
    {
        $request->validate([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'course_lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'attempts_allowed' => 'required|integer|min:0|max:10',
            'passing_marks' => 'required|numeric|min:0|max:100',
            'total_marks' => 'nullable|numeric|min:0',
            'start_time' => 'nullable|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'randomize_questions' => 'boolean',
            'randomize_options' => 'boolean',
            'show_results_immediately' => 'boolean',
            'show_correct_answers' => 'boolean',
            'show_explanations' => 'boolean',
            'allow_review' => 'boolean',
            'require_camera' => 'boolean',
            'require_microphone' => 'boolean',
            'prevent_tab_switch' => 'boolean',
            'auto_submit' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'advanced_course_id.required' => 'الكورس مطلوب',
            'title.required' => 'عنوان الامتحان مطلوب',
            'duration_minutes.required' => 'مدة الامتحان مطلوبة',
            'duration_minutes.min' => 'مدة الامتحان يجب أن تكون 5 دقائق على الأقل',
            'duration_minutes.max' => 'مدة الامتحان لا يجب أن تتجاوز 8 ساعات',
            'attempts_allowed.required' => 'عدد المحاولات المسموحة مطلوب',
            'passing_marks.required' => 'درجة النجاح مطلوبة',
        ]);

        $data = $request->only([
            'advanced_course_id',
            'course_lesson_id',
            'title',
            'description',
            'instructions',
            'duration_minutes',
            'attempts_allowed',
            'passing_marks',
            'total_marks',
            'start_time',
            'end_time',
        ]);

        // تحويل checkboxes
        $booleanFields = [
            'randomize_questions', 'randomize_options', 'show_results_immediately',
            'show_correct_answers', 'show_explanations', 'allow_review',
            'require_camera', 'require_microphone', 'prevent_tab_switch',
            'auto_submit', 'is_active',
        ];

        foreach ($booleanFields as $field) {
            $data[$field] = $request->has($field);
        }

        // لا تكتب course_id من ?course_id= — FK القديمة على جدول courses
        $data['course_id'] = null;
        $data['created_by'] = auth()->id();
        $data['total_marks'] = $data['total_marks'] ?? 0;

        $exam = Exam::create($data);

        return redirect()->route('admin.exams.questions.manage', $exam)
            ->with('success', 'تم إنشاء الامتحان بنجاح. يمكنك الآن إضافة الأسئلة.');
    }

    /**
     * عرض تفاصيل الامتحان
     */
    public function show(Exam $exam)
    {
        $exam->load([
            'course.academicSubject',
            'lesson',
            'examQuestions.question.category',
            'attempts.user'
        ]);

        return view('admin.exams.show', compact('exam'));
    }

    /**
     * عرض صفحة تعديل الامتحان
     */
    public function edit(Exam $exam)
    {
        $courses = AdvancedCourse::active()->with(['academicSubject'])->get();
        $lessons = CourseLesson::where('advanced_course_id', $exam->advanced_course_id)->active()->get();

        return view('admin.exams.edit', compact('exam', 'courses', 'lessons'));
    }

    /**
     * تحديث الامتحان
     */
    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'advanced_course_id' => 'required|exists:advanced_courses,id',
            'course_lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'attempts_allowed' => 'required|integer|min:0|max:10',
            'passing_marks' => 'required|numeric|min:0|max:100',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
        ]);

        $data = $request->all();
        
        $booleanFields = [
            'randomize_questions', 'randomize_options', 'show_results_immediately',
            'show_correct_answers', 'show_explanations', 'allow_review',
            'require_camera', 'require_microphone', 'prevent_tab_switch',
            'auto_submit', 'is_active'
        ];

        foreach ($booleanFields as $field) {
            $data[$field] = $request->has($field);
        }

        $exam->update($data);

        return redirect()->route('admin.exams.by-course', $exam->advanced_course_id)
            ->with('success', 'تم تحديث الامتحان بنجاح');
    }

    /**
     * حذف الامتحان
     */
    public function destroy(Exam $exam)
    {
        // التحقق من عدم وجود محاولات
        if ($exam->attempts()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف الامتحان لأنه يحتوي على محاولات طلاب');
        }

        $courseId = $exam->advanced_course_id;
        $exam->delete();

        return redirect()->route('admin.exams.by-course', $courseId)
            ->with('success', 'تم حذف الامتحان بنجاح');
    }

    /**
     * إدارة أسئلة الامتحان
     */
    public function manageQuestions(Exam $exam)
    {
        $exam->load(['examQuestions.question.category', 'course']);
        
        $categories = QuestionCategory::active()
                                    ->with(['questions' => function($query) {
                                        $query->active();
                                    }])
                                    ->orderBy('name')
                                    ->get();

        return view('admin.exams.questions', compact('exam', 'categories'));
    }

    /**
     * إضافة سؤال للامتحان
     */
    public function addQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'marks' => 'required|numeric|min:0.5|max:100',
            'time_limit' => 'nullable|integer|min:10|max:600',
            'is_required' => 'boolean',
        ]);

        // التحقق من عدم وجود السؤال مسبقاً
        if ($exam->examQuestions()->where('question_id', $request->question_id)->exists()) {
            return back()->with('error', 'السؤال موجود بالفعل في الامتحان');
        }

        $order = $exam->examQuestions()->max('order') + 1;

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $request->question_id,
            'order' => $order,
            'marks' => $request->marks,
            'time_limit' => $request->time_limit,
            'is_required' => $request->has('is_required'),
        ]);

        // تحديث إجمالي الدرجات
        $exam->update([
            'total_marks' => $exam->calculateTotalMarks()
        ]);

        return back()->with('success', 'تم إضافة السؤال للامتحان بنجاح');
    }

    /**
     * إزالة سؤال من الامتحان
     */
    public function removeQuestion(Exam $exam, ExamQuestion $examQuestion)
    {
        $examQuestion->delete();

        // إعادة ترقيم الأسئلة
        $exam->examQuestions()->orderBy('order')->get()->each(function($question, $index) {
            $question->update(['order' => $index + 1]);
        });

        // تحديث إجمالي الدرجات
        $exam->update([
            'total_marks' => $exam->calculateTotalMarks()
        ]);

        return back()->with('success', 'تم إزالة السؤال من الامتحان بنجاح');
    }

    /**
     * إعادة ترتيب أسئلة الامتحان
     */
    public function reorderQuestions(Request $request, Exam $exam)
    {
        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:exam_questions,id',
            'questions.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->questions as $questionData) {
            ExamQuestion::where('id', $questionData['id'])
                      ->where('exam_id', $exam->id)
                      ->update(['order' => $questionData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة ترتيب الأسئلة بنجاح'
        ]);
    }

    /**
     * نشر/إلغاء نشر الامتحان
     */
    public function togglePublish(Exam $exam)
    {
        $exam->update([
            'is_published' => !$exam->is_published
        ]);

        $status = $exam->is_published ? 'تم نشر' : 'تم إلغاء نشر';

        return response()->json([
            'success' => true,
            'message' => $status . ' الامتحان بنجاح',
            'is_published' => $exam->is_published
        ]);
    }

    /**
     * تفعيل/إلغاء تفعيل الامتحان
     */
    public function toggleStatus(Exam $exam)
    {
        $exam->update([
            'is_active' => !$exam->is_active
        ]);

        $status = $exam->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';

        return response()->json([
            'success' => true,
            'message' => $status . ' الامتحان بنجاح',
            'is_active' => $exam->is_active
        ]);
    }

    /**
     * إحصائيات الامتحان
     */
    public function statistics(Exam $exam)
    {
        $exam->load(['attempts.user', 'course', 'examQuestions']);

        $stats = [
            'overview' => $exam->stats,
            'attempts_by_date' => $exam->attempts()
                                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get(),
            'score_distribution' => $exam->attempts()
                                       ->completed()
                                       ->selectRaw('
                                           CASE 
                                               WHEN percentage >= 90 THEN "ممتاز"
                                               WHEN percentage >= 80 THEN "جيد جداً"
                                               WHEN percentage >= 70 THEN "جيد"
                                               WHEN percentage >= 60 THEN "مقبول"
                                               ELSE "ضعيف"
                                           END as grade,
                                           COUNT(*) as count
                                       ')
                                       ->groupBy('grade')
                                       ->get(),
        ];

        return view('admin.exams.statistics', compact('exam', 'stats'));
    }

    /**
     * معاينة الامتحان
     */
    public function preview(Exam $exam)
    {
        $exam->load(['examQuestions.question.category', 'course']);
        
        return view('admin.exams.preview', compact('exam'));
    }

    /**
     * نسخ الامتحان
     */
    public function duplicate(Exam $exam)
    {
        DB::beginTransaction();
        
        try {
            $newExam = $exam->replicate();
            $newExam->title = $exam->title . ' - نسخة';
            $newExam->is_active = false;
            $newExam->is_published = false;
            $newExam->save();

            // نسخ الأسئلة
            foreach ($exam->examQuestions as $examQuestion) {
                ExamQuestion::create([
                    'exam_id' => $newExam->id,
                    'question_id' => $examQuestion->question_id,
                    'order' => $examQuestion->order,
                    'marks' => $examQuestion->marks,
                    'time_limit' => $examQuestion->time_limit,
                    'is_required' => $examQuestion->is_required,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.exams.by-course', $newExam->advanced_course_id)
                ->with('success', 'تم نسخ الامتحان بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'حدث خطأ أثناء نسخ الامتحان');
        }
    }
}