<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\AdvancedCourse;
use App\Models\CourseLesson;
use App\Models\TeamsAttendanceFile;
use App\Models\User;
use App\Services\PublicMediaStorage;
use App\Services\TeamsAttendanceImportService;
use App\Support\AppTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LectureController extends Controller
{
    /**
     * عرض قائمة الكورسات (الدخول للكورس يعرض محاضراته).
     */
    public function index()
    {
        $courses = AdvancedCourse::where('is_active', true)
            ->withCount('lectures')
            ->orderBy('title')
            ->get();

        return view('admin.lectures.index', compact('courses'));
    }

    /**
     * عرض محاضرات كورس معين مع روابط CRUD.
     */
    public function indexByCourse(AdvancedCourse $course)
    {
        $course->loadCount('lectures');
        $lectures = $course->lectures()
            ->with('instructor')
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);

        return view('admin.lectures.by-course', compact('course', 'lectures'));
    }

    public function create(Request $request)
    {
        $courses = AdvancedCourse::where('is_active', true)->get();
        $instructors = User::where('role', 'instructor')->where('is_active', true)->get();
        $preselectedCourseId = $request->query('course_id');

        return view('admin.lectures.create', compact('courses', 'instructors', 'preselectedCourseId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
            'instructor_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'teams_registration_link' => 'nullable|url',
            'teams_meeting_link' => 'nullable|url',
            'recording_url' => 'nullable|url',
            'video_platform' => 'nullable|in:bunny',
            'scheduled_at' => 'required|date',
            'timezone' => AppTimezone::inputRules(),
            'duration_minutes' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'has_attendance_tracking' => 'boolean',
            'has_assignment' => 'boolean',
            'has_evaluation' => 'boolean',
        ]);

        if (!empty($validated['recording_url'])) {
            $validated['video_platform'] = 'bunny';
            if (!\App\Helpers\VideoHelper::isValidVideoUrl($validated['recording_url'])) {
                return back()->withErrors(['recording_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
        } else {
            $validated['video_platform'] = null;
        }

        $validated = AppTimezone::shiftRequestDateTime($request, $validated, 'scheduled_at');
        unset($validated['timezone']);

        $lecture = Lecture::create($validated);

        return redirect()->route('admin.lectures.by-course', $lecture->course_id)
            ->with('success', 'تم إنشاء المحاضرة بنجاح');
    }

    public function show(Lecture $lecture)
    {
        $lecture->load([
            'course', 'instructor', 'lesson',
            'materials', 'assignments', 'attendanceRecords', 'evaluations',
        ]);

        return view('admin.lectures.show', compact('lecture'));
    }

    public function edit(Lecture $lecture)
    {
        $courses = AdvancedCourse::where('is_active', true)->get();
        $instructors = User::where('role', 'instructor')->where('is_active', true)->get();
        $lessons = CourseLesson::where('advanced_course_id', $lecture->course_id)->orderBy('order')->get();

        return view('admin.lectures.edit', compact('lecture', 'courses', 'instructors', 'lessons'));
    }

    public function update(Request $request, Lecture $lecture)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
            'instructor_id' => 'required|exists:users,id',
            'course_lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'recording_url' => 'nullable|url',
            'video_platform' => 'nullable|in:bunny',
            'scheduled_at' => 'required|date',
            'timezone' => AppTimezone::inputRules(),
            'duration_minutes' => 'required|integer|min:1',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
            'has_attendance_tracking' => 'boolean',
            'has_assignment' => 'boolean',
            'has_evaluation' => 'boolean',
        ]);

        if (!empty($validated['recording_url'])) {
            $validated['video_platform'] = 'bunny';
            if (!\App\Helpers\VideoHelper::isValidVideoUrl($validated['recording_url'])) {
                return back()->withErrors(['recording_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
        } else {
            $validated['video_platform'] = null;
        }

        $validated['course_lesson_id'] = $validated['course_lesson_id'] ?? null;
        $validated = AppTimezone::shiftRequestDateTime($request, $validated, 'scheduled_at');
        unset($validated['timezone']);
        $lecture->update($validated);

        return redirect()->route('admin.lectures.by-course', $lecture->course_id)
            ->with('success', 'تم تحديث المحاضرة بنجاح');
    }

    public function destroy(Lecture $lecture)
    {
        $courseId = $lecture->course_id;
        $lecture->delete();

        return redirect()->route('admin.lectures.by-course', $courseId)
            ->with('success', 'تم حذف المحاضرة بنجاح');
    }

    public function syncTeamsAttendance(Request $request, Lecture $lecture)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:'.config('upload_limits.max_upload_kb'),
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $localImportPath = $file->getRealPath();
        $filePath = PublicMediaStorage::storeFileAs($file, 'attendance/teams', $fileName);

        $teamsFile = TeamsAttendanceFile::create([
            'lecture_id' => $lecture->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getClientOriginalExtension(),
            'status' => 'processing',
            'uploaded_by' => auth()->id(),
        ]);

        try {
            $result = app(TeamsAttendanceImportService::class)->importFromFile($lecture, $localImportPath);
            $teamsFile->update([
                'status' => 'completed',
                'total_records' => (int) ($result['total'] ?? 0),
                'processed_records' => (int) ($result['processed'] ?? 0),
                'error_message' => !empty($result['errors']) ? implode(' | ', array_slice($result['errors'], 0, 5)) : null,
            ]);
            return back()->with('success', "تمت مزامنة الحضور. مطابق: {$result['matched']} | غير مطابق: {$result['unmatched']}");
        } catch (\Throwable $e) {
            Log::error('Admin Teams attendance sync failed', [
                'lecture_id' => $lecture->id,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            $teamsFile->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return back()->with('error', 'تعذرت مزامنة ملف الحضور.');
        }
    }
}
