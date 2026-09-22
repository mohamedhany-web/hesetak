<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $instructor = Auth::user();
        $teachingIds = $instructor->teachingAdvancedCourseIds();

        if ($teachingIds->isEmpty()) {
            abort(403, 'لم يتم تعيين أي كورس عادي لك بعد.');
        }

        // جلب الكورسات التي تم تعيينها لهذا المدرس (مباشرة أو عبر المسار)
        $query = AdvancedCourse::whereIn('id', $teachingIds)
            ->with(['academicYear', 'academicSubject'])
            ->withCount(['lectures', 'enrollments']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(15);

        // إحصائيات
        $stats = [
            'total' => $teachingIds->count(),
            'active' => AdvancedCourse::whereIn('id', $teachingIds)->where('is_active', true)->count(),
            'inactive' => AdvancedCourse::whereIn('id', $teachingIds)->where('is_active', false)->count(),
            'total_students' => \App\Models\StudentCourseEnrollment::whereIn('advanced_course_id', $teachingIds)
                ->where('status', 'active')
                ->count(),
        ];

        return view('instructor.courses.index', compact('courses', 'stats'));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $instructor = Auth::user();
        $teachingIds = $instructor->teachingAdvancedCourseIds();

        if ($teachingIds->isEmpty() || ! $teachingIds->contains((int) $id)) {
            abort(403, 'لم يتم تعيين هذا الكورس لك.');
        }

        $course = AdvancedCourse::whereIn('id', $teachingIds)
            ->with(['academicYear', 'academicSubject', 'instructor'])
            ->withCount(['lectures', 'enrollments'])
            ->findOrFail($id);

        // المحاضرات
        $lectures = \App\Models\Lecture::where('course_id', $course->id)
            ->with(['instructor'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(10, ['*'], 'lectures_page');

        // الاختبارات
        $exams = \App\Models\AdvancedExam::where('advanced_course_id', $course->id)
            ->with(['lesson', 'advancedCourse'])
            ->withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'exams_page');

        // الواجبات
        $assignments = \App\Models\Assignment::where(function($q) use ($course) {
                $q->where('advanced_course_id', $course->id)
                  ->orWhere('course_id', $course->id);
            })
            ->with(['lesson', 'teacher'])
            ->withCount('submissions')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'assignments_page');

        // الطلاب المسجلون (نشطون ولهم وصول)
        $enrollments = \App\Models\StudentCourseEnrollment::where('advanced_course_id', $course->id)
            ->with('student')
            ->grantingAccess()
            ->latest('enrolled_at')
            ->paginate(20, ['*'], 'students_page');

        // إحصائيات شاملة (محاضرات فقط — تم إلغاء الدروس؛ بدون حضور للكورسات المسجّلة)
        $stats = [
            'total_lectures' => \App\Models\Lecture::where('course_id', $course->id)->count(),
            'upcoming_lectures' => \App\Models\Lecture::where('course_id', $course->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->count(),
            'total_exams' => \App\Models\AdvancedExam::where('advanced_course_id', $course->id)->count(),
            'active_exams' => \App\Models\AdvancedExam::where('advanced_course_id', $course->id)
                ->where('is_active', true)
                ->count(),
            'total_assignments' => \App\Models\Assignment::where(function($q) use ($course) {
                    $q->where('advanced_course_id', $course->id)
                      ->orWhere('course_id', $course->id);
                })->count(),
            'pending_submissions' => \App\Models\AssignmentSubmission::whereHas('assignment', function($q) use ($course) {
                    $q->where(function($q2) use ($course) {
                        $q2->where('advanced_course_id', $course->id)
                           ->orWhere('course_id', $course->id);
                    });
                })
                ->whereNull('graded_at')
                ->count(),
            'total_students' => $enrollments->total(),
        ];

        return view('instructor.courses.show', compact(
            'course', 
            'enrollments', 
            'lectures', 
            'exams', 
            'assignments', 
            'stats'
        ));
    }
}
