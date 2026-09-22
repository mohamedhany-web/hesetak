<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\AdvancedCourse;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request)
    {
        if (! instructor_ui('show_attendance', false)) {
            abort(404);
        }

        $instructor = Auth::user();
        
        // جلب الكورسات التي يدرسها المدرب
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
        
        // جلب المحاضرات مع سجلات الحضور
        $query = Lecture::where('instructor_id', $instructor->id)
            ->with(['course', 'attendanceRecords'])
            ->withCount('attendanceRecords');
        
        // فلترة حسب الكورس
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        
        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('scheduled_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('scheduled_at', '<=', $request->date_to);
        }
        
        $lectures = $query->orderBy('scheduled_at', 'desc')->paginate(20);
        
        // إحصائيات عامة
        $stats = [
            'total_lectures' => Lecture::where('instructor_id', $instructor->id)->count(),
            'total_attendance_records' => AttendanceRecord::whereHas('lecture', function($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->count(),
            'present_count' => AttendanceRecord::whereHas('lecture', function($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->where('status', 'present')->count(),
            'absent_count' => AttendanceRecord::whereHas('lecture', function($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->where('status', 'absent')->count(),
        ];
        
        return view('instructor.attendance.index', compact('lectures', 'courses', 'stats'));
    }

    /**
     * Display attendance for a specific lecture.
     */
    public function showLecture(Lecture $lecture)
    {
        if (! instructor_ui('show_attendance', false)) {
            abort(404);
        }

        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بالوصول لهذه المحاضرة');
        }
        
        $lecture->load(['course', 'instructor', 'attendanceRecords.student']);
        
        // جلب الطلاب المسجلين في الكورس
        $enrollments = \App\Models\StudentCourseEnrollment::where('advanced_course_id', $lecture->course_id)
            ->where('status', 'active')
            ->with('user')
            ->get();
        
        // جلب سجلات الحضور
        $attendanceRecords = AttendanceRecord::where('lecture_id', $lecture->id)
            ->with('student')
            ->get()
            ->keyBy('student_id');
        
        // إحصائيات الحضور
        $attendanceStats = [
            'total_students' => $enrollments->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'partial' => $attendanceRecords->where('status', 'partial')->count(),
        ];
        
        return view('instructor.attendance.show-lecture', compact('lecture', 'enrollments', 'attendanceRecords', 'attendanceStats'));
    }
}
