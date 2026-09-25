<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\CourseLesson;
use App\Services\CourseVideoStorage;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * عرض قائمة الدروس للكورس
     */
    public function index($courseId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lessons = $course->lessons()
            ->orderBy('order', 'asc')
            ->paginate(20);
        
        return view('instructor.lessons.index', compact('course', 'lessons'));
    }

    /**
     * عرض صفحة إنشاء درس جديد
     */
    public function create($courseId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        // جلب آخر ترتيب
        $lastOrder = $course->lessons()->max('order') ?? 0;
        
        return view('instructor.lessons.create', compact('course', 'lastOrder'));
    }

    /**
     * حفظ درس جديد
     */
    public function store(Request $request, $courseId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:video,text,document,quiz',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:512000', // 500MB
            'duration_minutes' => 'nullable|integer|min:0',
            'order' => 'required|integer|min:0',
            'is_active' => 'nullable',
            'is_free' => 'nullable',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:'.config('upload_limits.max_upload_kb'), // حتى 40 ميجابايت (Cloudflare)
        ], [
            'title.required' => 'عنوان الدرس مطلوب',
            'type.required' => 'نوع الدرس مطلوب',
            'video_file.max' => 'حجم الفيديو يجب ألا يتجاوز 500 ميجابايت',
        ]);
        
        // معالجة رفع الفيديو → Cloudflare R2 (أو public حسب PUBLIC_MEDIA_DISK)
        if ($request->hasFile('video_file')) {
            $validated['video_url'] = CourseVideoStorage::store($request->file('video_file'));
        }
        
        // معالجة المرفقات
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = $this->storeLessonAttachment($file);
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $validated['attachments'] = json_encode($attachments);
        }
        
        $validated['advanced_course_id'] = $course->id;
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_free'] = $request->has('is_free') ? true : false;
        
        $lesson = CourseLesson::create($validated);
        
        return redirect()
            ->route('instructor.courses.lessons.index', $course->id)
            ->with('success', 'تم إضافة الدرس بنجاح');
    }

    /**
     * عرض تفاصيل درس
     */
    public function show($courseId, $lessonId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lesson = $course->lessons()->findOrFail($lessonId);
        
        return view('instructor.lessons.show', compact('course', 'lesson'));
    }

    /**
     * عرض صفحة تعديل درس
     */
    public function edit($courseId, $lessonId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lesson = $course->lessons()->findOrFail($lessonId);
        
        return view('instructor.lessons.edit', compact('course', 'lesson'));
    }

    /**
     * تحديث درس
     */
    public function update(Request $request, $courseId, $lessonId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lesson = $course->lessons()->findOrFail($lessonId);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:video,text,document,quiz',
            'video_url' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,webm,ogg|max:512000',
            'duration_minutes' => 'nullable|integer|min:0',
            'order' => 'required|integer|min:0',
            'is_active' => 'nullable',
            'is_free' => 'nullable',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:'.config('upload_limits.max_upload_kb'),
        ]);
        
        // معالجة رفع الفيديو → Cloudflare R2 (أو public حسب PUBLIC_MEDIA_DISK)
        if ($request->hasFile('video_file')) {
            $validated['video_url'] = CourseVideoStorage::store(
                $request->file('video_file'),
                $lesson->video_url
            );
        }
        
        // معالجة المرفقات
        if ($request->hasFile('attachments')) {
            $attachments = json_decode($lesson->attachments ?? '[]', true);
            if (! is_array($attachments)) {
                $attachments = [];
            }
            foreach ($request->file('attachments') as $file) {
                $path = $this->storeLessonAttachment($file);
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $validated['attachments'] = json_encode($attachments);
        }
        
        $validated['is_active'] = $request->has('is_active') ? true : false;
        $validated['is_free'] = $request->has('is_free') ? true : false;
        
        $lesson->update($validated);
        
        return redirect()
            ->route('instructor.courses.lessons.index', $course->id)
            ->with('success', 'تم تحديث الدرس بنجاح');
    }

    /**
     * حذف درس
     */
    public function destroy($courseId, $lessonId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lesson = $course->lessons()->findOrFail($lessonId);
        $lesson->delete();
        
        return redirect()
            ->route('instructor.courses.lessons.index', $course->id)
            ->with('success', 'تم حذف الدرس بنجاح');
    }

    /**
     * تبديل حالة الدرس (نشط/غير نشط)
     */
    public function toggleStatus($courseId, $lessonId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $lesson = $course->lessons()->findOrFail($lessonId);
        $lesson->is_active = !$lesson->is_active;
        $lesson->save();
        
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة الدرس بنجاح',
            'is_active' => $lesson->is_active,
        ]);
    }

    /**
     * إعادة ترتيب الدروس
     */
    public function reorder(Request $request, $courseId)
    {
        $instructor = Auth::user();
        
        // التحقق من أن الكورس يخص المدرب
        $course = AdvancedCourse::where('id', $courseId)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();
        
        $validated = $request->validate([
            'lessons' => 'required|array',
            'lessons.*.id' => 'required|exists:course_lessons,id',
            'lessons.*.order' => 'required|integer',
        ]);
        
        foreach ($validated['lessons'] as $lessonData) {
            CourseLesson::where('id', $lessonData['id'])
                ->where('advanced_course_id', $course->id)
                ->update(['order' => $lessonData['order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'تم إعادة ترتيب الدروس بنجاح',
        ]);
    }

    private function storeLessonAttachment(UploadedFile $file): string
    {
        return PublicMediaStorage::storeFile($file, 'lesson-attachments');
    }
}
