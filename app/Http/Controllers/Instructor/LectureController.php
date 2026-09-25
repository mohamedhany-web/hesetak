<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Lecture;
use App\Models\LectureMaterial;
use App\Models\AdvancedCourse;
use App\Models\AttendanceRecord;
use App\Models\TeamsAttendanceFile;
use App\Services\LectureMaterialStorage;
use App\Services\PublicMediaStorage;
use App\Services\TeamsAttendanceImportService;
use App\Support\AppTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LectureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $instructor = Auth::user();
        
        // جلب الكورسات التي يدرسها المدرب مع إحصائيات
        $query = AdvancedCourse::where('instructor_id', $instructor->id)
            ->withCount([
                'lectures',
                'enrollments',
                'sections'
            ])
            ->with(['academicYear', 'academicSubject']);
        
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
        
        // إحصائيات عامة
        $stats = [
            'total_courses' => AdvancedCourse::where('instructor_id', $instructor->id)->count(),
            'active_courses' => AdvancedCourse::where('instructor_id', $instructor->id)->where('is_active', true)->count(),
            'total_lectures' => Lecture::where('instructor_id', $instructor->id)->count(),
            'scheduled_lectures' => Lecture::where('instructor_id', $instructor->id)->where('status', 'scheduled')->count(),
            'total_students' => \App\Models\StudentCourseEnrollment::whereHas('course', function($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })->where('status', 'active')->count(),
        ];
        
        return view('instructor.lectures.index', compact('courses', 'stats'));
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
        
        // جلب دروس الكورس المحدد (إذا كان موجوداً)
        $lessons = collect();
        if (request()->filled('course_id')) {
            $lessons = \App\Models\CourseLesson::where('advanced_course_id', request('course_id'))
                ->where('is_active', true)
                ->orderBy('order')
                ->get();
        }
        
        return view('instructor.lectures.create', compact('courses', 'lessons'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $instructor = Auth::user();
        
        if ($request->filled('video_platform')) {
            $request->merge(['video_platform' => strtolower(trim($request->input('video_platform')))]);
        }
        $minWatch = $request->input('min_watch_percent_to_unlock_next');
        if ($minWatch === '' || (is_string($minWatch) && trim($minWatch) === '')) {
            $request->merge(['min_watch_percent_to_unlock_next' => null]);
        }
        
        $validated = $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
            'course_lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'timezone' => AppTimezone::inputRules(),
            'duration_minutes' => 'required|integer|min:15|max:480',
            'min_watch_percent_to_unlock_next' => 'nullable|integer|min:0|max:100',
            'teams_registration_link' => 'nullable|url',
            'teams_meeting_link' => 'nullable|url',
            'recording_url' => 'nullable|url',
            'video_platform' => 'nullable|in:bunny',
            'notes' => 'nullable|string',
            'has_attendance_tracking' => 'boolean',
            'has_assignment' => 'boolean',
            'has_evaluation' => 'boolean',
            'material_files' => 'nullable|array',
            'material_files.*' => 'nullable|file|max:'.config('upload_limits.max_upload_kb'), // حتى 40 ميجابايت — nullable لتفادي فشل التحقق عند صفوف بدون ملف
            'material_titles' => 'nullable|array',
            'material_titles.*' => 'nullable|string|max:255',
            'material_visible' => 'nullable|array',
            'material_visible.*' => 'in:0,1',
        ], [
            'course_id.required' => 'يجب اختيار الكورس',
            'course_id.exists' => 'الكورس المحدد غير موجود',
            'title.required' => 'عنوان المحاضرة مطلوب',
            'scheduled_at.required' => 'موعد المحاضرة مطلوب',
            'scheduled_at.date' => 'موعد المحاضرة يجب أن يكون تاريخ صحيح',
            'duration_minutes.required' => 'مدة المحاضرة مطلوبة',
            'duration_minutes.min' => 'مدة المحاضرة يجب أن تكون 15 دقيقة على الأقل',
            'duration_minutes.max' => 'مدة المحاضرة يجب ألا تتجاوز 480 دقيقة (8 ساعات)',
        ]);
        $validated = AppTimezone::shiftRequestDateTime($request, $validated, 'scheduled_at');
        unset($validated['timezone']);
        
        // التحقق من أن الكورس يخص هذا المدرب
        $course = AdvancedCourse::where('id', $validated['course_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $validated['instructor_id'] = $instructor->id;
        $validated['status'] = 'scheduled';
        $validated['has_attendance_tracking'] = $request->has('has_attendance_tracking');
        $validated['has_assignment'] = $request->has('has_assignment');
        $validated['has_evaluation'] = $request->has('has_evaluation');
        $rawMin = $request->get('min_watch_percent_to_unlock_next');
        $minWatchValue = null;
        if ($rawMin !== null && $rawMin !== '' && is_numeric($rawMin)) {
            $minWatchValue = (int) min(100, max(0, (float) $rawMin));
        }
        $validated['min_watch_percent_to_unlock_next'] = $minWatchValue;

        // التأكد من حفظ recording_url و video_platform بشكل صريح
        if ($request->has('recording_url')) {
            $validated['recording_url'] = $request->input('recording_url');
        } else {
            $validated['recording_url'] = null;
        }
        
        // إذا كان recording_url موجوداً و video_platform غير موجود، حاول اكتشافه
        if (isset($validated['recording_url']) && $validated['recording_url']) {
            if (strpos($validated['recording_url'], 'mediadelivery.net') === false) {
                return back()->withErrors(['recording_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
            $validated['video_platform'] = 'bunny';
        }
        
        // التأكد من حفظ video_platform إذا كان موجوداً في الطلب
        if ($request->has('video_platform') && $request->input('video_platform')) {
            $validated['video_platform'] = $request->input('video_platform');
        }
        
        \Log::info('Creating lecture', [
            'recording_url' => $validated['recording_url'] ?? 'not set',
            'video_platform' => $validated['video_platform'] ?? 'not set',
            'all_validated' => $validated
        ]);
        
        $lecture = Lecture::create($validated);
        if (array_key_exists('min_watch_percent_to_unlock_next', $validated)) {
            $lecture->min_watch_percent_to_unlock_next = $validated['min_watch_percent_to_unlock_next'];
            $lecture->save();
        }

        // حفظ مواد المحاضرة (ملفات مرفوعة)
        $materialFiles = $request->file('material_files');
        if ($materialFiles && is_array($materialFiles)) {
            $titles = $request->input('material_titles', []);
            $visible = $request->input('material_visible', []);
            $sortOrder = 0;
            foreach ($materialFiles as $index => $file) {
                if (!$file || !$file->isValid()) continue;
                $path = LectureMaterialStorage::store($file, (int) $lecture->id);
                if (!$path) continue;
                // دعم كلا النموذجين: منهج (قيمة واحدة لكل ملف) أو إضافة محاضرة (قيمتان لكل صف: hidden ثم checkbox)
                $visibleVal = $visible[$index] ?? $visible[2 * $index + 1] ?? $visible[2 * $index] ?? 1;
                LectureMaterial::create([
                    'lecture_id' => $lecture->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'storage_disk' => LectureMaterialStorage::resolvedDisk(),
                    'title' => $titles[$index] ?? null,
                    'is_visible_to_student' => (int)$visibleVal === 1,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
        
        // إذا كان الطلب AJAX (من popup)، أرجع JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المحاضرة بنجاح',
                'lecture' => $lecture,
            ]);
        }
        
        return redirect()->route('instructor.lectures.show', $lecture)
            ->with('success', 'تم إنشاء المحاضرة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بالوصول لهذه المحاضرة');
        }
        
        $lecture->load(['course', 'instructor', 'attendanceRecords.user']);
        
        // إذا كان الطلب AJAX، أرجع JSON
        if (request()->ajax() || request()->wantsJson()) {
            // إعادة تحميل المحاضرة من قاعدة البيانات للتأكد من أحدث البيانات
            $lecture->refresh();
            
            \Log::info('Returning lecture JSON', [
                'lecture_id' => $lecture->id,
                'recording_url' => $lecture->recording_url,
                'video_platform' => $lecture->video_platform,
            ]);
            
            $videoPlatform = $lecture->video_platform ? strtolower(trim($lecture->video_platform)) : '';
            return response()->json([
                'id' => $lecture->id,
                'title' => $lecture->title,
                'description' => $lecture->description,
                'course_id' => $lecture->course_id,
                'course_lesson_id' => $lecture->course_lesson_id,
                'scheduled_at' => $lecture->scheduled_at ? $lecture->scheduled_at->toIso8601String() : null,
                'duration_minutes' => $lecture->duration_minutes,
                'min_watch_percent_to_unlock_next' => $lecture->min_watch_percent_to_unlock_next,
                'recording_url' => $lecture->recording_url ?? '',
                'video_platform' => $videoPlatform,
                'teams_registration_link' => $lecture->teams_registration_link ?? '',
                'teams_meeting_link' => $lecture->teams_meeting_link ?? '',
                'notes' => $lecture->notes ?? '',
                'has_attendance_tracking' => $lecture->has_attendance_tracking ?? false,
                'has_assignment' => $lecture->has_assignment ?? false,
                'has_evaluation' => $lecture->has_evaluation ?? false,
                'status' => $lecture->status ?? 'scheduled',
            ]);
        }
        
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
        
        return view('instructor.lectures.show', compact('lecture', 'enrollments', 'attendanceRecords', 'attendanceStats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتعديل هذه المحاضرة');
        }
        
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();
        
        // جلب دروس الكورس
        $lessons = \App\Models\CourseLesson::where('advanced_course_id', $lecture->course_id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
        
        return view('instructor.lectures.edit', compact('lecture', 'courses', 'lessons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتعديل هذه المحاضرة');
        }
        
        // تطبيع video_platform لأحرف صغيرة حتى يمر التحقق (القيم المسموحة: youtube, vimeo, ...)
        if ($request->filled('video_platform')) {
            $request->merge(['video_platform' => strtolower(trim($request->input('video_platform')))]);
        }
        $minWatch = $request->input('min_watch_percent_to_unlock_next');
        if ($minWatch === '' || (is_string($minWatch) && trim($minWatch) === '')) {
            $request->merge(['min_watch_percent_to_unlock_next' => null]);
        }
        
        $validated = $request->validate([
            'course_id' => 'required|exists:advanced_courses,id',
            'course_lesson_id' => 'nullable|exists:course_lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'timezone' => AppTimezone::inputRules(),
            'duration_minutes' => 'required|integer|min:15|max:480',
            'min_watch_percent_to_unlock_next' => 'nullable|integer|min:0|max:100',
            'teams_registration_link' => 'nullable|url',
            'teams_meeting_link' => 'nullable|url',
            'recording_url' => 'nullable|string|max:2000',
            'video_platform' => 'nullable|in:bunny',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
            'has_attendance_tracking' => 'boolean',
            'has_assignment' => 'boolean',
            'has_evaluation' => 'boolean',
            'material_visible_old' => 'nullable|array',
            'material_visible_old.*' => 'in:0,1',
            'material_delete_old' => 'nullable|array',
            'material_delete_old.*' => 'exists:lecture_materials,id',
            'material_files' => 'nullable|array',
            'material_files.*' => 'nullable|file|max:'.config('upload_limits.max_upload_kb'),
            'material_titles' => 'nullable|array',
            'material_titles.*' => 'nullable|string|max:255',
            'material_visible' => 'nullable|array',
            'material_visible.*' => 'in:0,1',
        ]);
        
        // التحقق من أن الكورس يخص هذا المدرب
        $course = AdvancedCourse::where('id', $validated['course_id'])
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $validated['has_attendance_tracking'] = $request->has('has_attendance_tracking');
        $validated['has_assignment'] = $request->has('has_assignment');
        $validated['has_evaluation'] = $request->has('has_evaluation');
        $validated['status'] = $validated['status'] ?? $lecture->status;
        $rawMin = $request->get('min_watch_percent_to_unlock_next');
        $minWatchValue = null;
        if ($rawMin !== null && $rawMin !== '' && is_numeric($rawMin)) {
            $minWatchValue = (int) min(100, max(0, (float) $rawMin));
        }
        $validated['min_watch_percent_to_unlock_next'] = $minWatchValue;
        // تطبيع recording_url: إذا كان فارغاً استخدم null، وإلا تحقق من صحة الرابط
        $recordingUrl = $request->input('recording_url');
        if ($recordingUrl === null || trim((string) $recordingUrl) === '') {
            $validated['recording_url'] = null;
        } else {
            $validated['recording_url'] = trim($recordingUrl);
            if (!filter_var($validated['recording_url'], FILTER_VALIDATE_URL)) {
                unset($validated['recording_url']);
            }
        }
        
        // إذا تم تغيير recording_url دون video_platform، اكتشاف المنصة تلقائياً
        if (!empty($validated['recording_url'])) {
            if (strpos($validated['recording_url'], 'mediadelivery.net') === false) {
                return back()->withErrors(['recording_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
            $validated['video_platform'] = 'bunny';
        } else {
            $validated['video_platform'] = null;
        }

        $validated = AppTimezone::shiftRequestDateTime($request, $validated, 'scheduled_at');
        unset($validated['timezone']);
        
        $lecture->update($validated);
        $lecture->min_watch_percent_to_unlock_next = $validated['min_watch_percent_to_unlock_next'];
        $lecture->save();

        // مواد المحاضرة: حذف المحددة
        $deleteIds = $request->input('material_delete_old', []);
        if (!empty($deleteIds)) {
            $toDelete = LectureMaterial::where('lecture_id', $lecture->id)->whereIn('id', $deleteIds)->get();
            foreach ($toDelete as $m) {
                LectureMaterialStorage::delete($m->file_path, $m->storage_disk);
                $m->delete();
            }
        }
        // تحديث ظهور المواد الحالية
        $visibleOld = $request->input('material_visible_old', []);
        foreach ($visibleOld as $matId => $val) {
            $visible = is_array($val) ? in_array('1', $val, true) : ((int)$val === 1);
            LectureMaterial::where('lecture_id', $lecture->id)->where('id', $matId)->update(['is_visible_to_student' => $visible]);
        }
        // إضافة مواد جديدة
        $materialFiles = $request->file('material_files');
        if ($materialFiles && is_array($materialFiles)) {
            $titles = $request->input('material_titles', []);
            $visible = $request->input('material_visible', []);
            $sortStart = (int) $lecture->materials()->max('sort_order') + 1;
            $sortOrder = $sortStart;
            foreach ($materialFiles as $index => $file) {
                if (!$file || !$file->isValid()) continue;
                $path = LectureMaterialStorage::store($file, (int) $lecture->id);
                if (!$path) continue;
                $visibleVal = $visible[$index] ?? $visible[2 * $index + 1] ?? $visible[2 * $index] ?? 1;
                LectureMaterial::create([
                    'lecture_id' => $lecture->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'storage_disk' => LectureMaterialStorage::resolvedDisk(),
                    'title' => $titles[$index] ?? null,
                    'is_visible_to_student' => (int)$visibleVal === 1,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
        
        // إذا كان الطلب AJAX (من popup)، أرجع JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المحاضرة بنجاح',
                'lecture' => $lecture->fresh(),
            ]);
        }
        
        return redirect()->route('instructor.lectures.show', $lecture)
            ->with('success', 'تم تحديث المحاضرة بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بحذف هذه المحاضرة');
        }
        
        $lecture->delete();
        
        // إذا كان الطلب AJAX (من popup)، أرجع JSON
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المحاضرة بنجاح',
            ]);
        }
        
        return redirect()->route('instructor.lectures.index')
            ->with('success', 'تم حذف المحاضرة بنجاح');
    }

    /**
     * تحديث حالة الحضور لطالب
     */
    public function updateAttendance(Request $request, Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتحديث الحضور لهذه المحاضرة');
        }
        
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'status' => 'required|in:present,late,absent,partial',
            'joined_at' => 'nullable|date',
            'left_at' => 'nullable|date',
            'attendance_minutes' => 'nullable|integer|min:0',
        ]);
        
        $attendanceRecord = AttendanceRecord::updateOrCreate(
            [
                'lecture_id' => $lecture->id,
                'student_id' => $validated['student_id'],
            ],
            [
                'status' => $validated['status'],
                'joined_at' => $validated['joined_at'] ?? now(),
                'left_at' => $validated['left_at'] ?? null,
                'attendance_minutes' => $validated['attendance_minutes'] ?? 0,
                'total_minutes' => $lecture->duration_minutes,
                'attendance_percentage' => isset($validated['attendance_minutes']) && $validated['attendance_minutes'] > 0
                    ? ($validated['attendance_minutes'] / $lecture->duration_minutes) * 100 
                    : 0,
                'source' => 'manual',
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحضور بنجاح',
            'record' => $attendanceRecord->load('student'),
        ]);
    }

    /**
     * تحديث حالة المحاضرة
     */
    public function updateStatus(Request $request, Lecture $lecture)
    {
        $instructor = Auth::user();
        
        // التحقق من أن المحاضرة تخص هذا المدرب
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بتحديث حالة هذه المحاضرة');
        }
        
        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);
        
        $lecture->update(['status' => $validated['status']]);
        
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة المحاضرة بنجاح',
            'lecture' => $lecture,
        ]);
    }

    public function syncTeamsAttendance(Request $request, Lecture $lecture)
    {
        $instructor = Auth::user();
        if ($lecture->instructor_id !== $instructor->id) {
            abort(403, 'غير مسموح لك بمزامنة حضور هذه المحاضرة');
        }

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
            'uploaded_by' => $instructor->id,
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
            \Log::error('Instructor Teams attendance sync failed', [
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
