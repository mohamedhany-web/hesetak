<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\CourseLesson;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;

class CourseLessonController extends Controller
{
    /**
     * عرض دروس الكورس
     */
    public function index(AdvancedCourse $course)
    {
        $lessons = $course->lessons()->ordered()->get();
        
        return view('admin.course-lessons.index', compact('course', 'lessons'));
    }

    /**
     * عرض صفحة إضافة درس جديد
     */
    public function create(AdvancedCourse $course)
    {
        return view('admin.course-lessons.create', compact('course'));
    }

    /**
     * حفظ درس جديد
     */
    public function store(Request $request, AdvancedCourse $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,document,quiz,assignment',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',

            'attachments.*' => 'nullable|file|max:'.config('upload_limits.max_upload_kb'), // حتى 40 ميجابايت لكل ملف
            'duration_minutes' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
        ], [
            'title.required' => 'عنوان الدرس مطلوب',
            'title.max' => 'عنوان الدرس لا يجب أن يتجاوز 255 حرف',
            'type.required' => 'نوع الدرس مطلوب',
            'type.in' => 'نوع الدرس غير صحيح',
            'video_url.url' => 'رابط الفيديو غير صحيح',

            'attachments.*.max' => 'حجم المرفق لا يجب أن يتجاوز 40 ميجابايت',
            'duration_minutes.min' => 'مدة الدرس يجب أن تكون دقيقة واحدة على الأقل',
        ]);

        $data = $request->all();
        $data['advanced_course_id'] = $course->id;
        $data['is_free'] = $request->has('is_free');
        $data['is_active'] = $request->has('is_active');
        
        // تحديد ترتيب الدرس إذا لم يتم تحديده
        if (!isset($data['order'])) {
            $data['order'] = $course->lessons()->max('order') + 1;
        }
        
        // تحديد مدة الدرس الافتراضية
        if (!isset($data['duration_minutes']) || $data['duration_minutes'] === null || $data['duration_minutes'] === '') {
            $data['duration_minutes'] = 0;
        }

        // التحقق من صحة رابط الفيديو
        if ($data['type'] === 'video' && !empty($data['video_url'])) {
            if (!\App\Helpers\VideoHelper::isValidVideoUrl($data['video_url'])) {
                return back()->withErrors(['video_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
        }

        // رفع المرفقات
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = PublicMediaStorage::storeFile($file, 'course-attachments');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => PublicMediaStorage::publicUrl($path) ?? $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $data['attachments'] = json_encode($attachments);
        }

        CourseLesson::create($data);

        return redirect()->route('admin.courses.lessons.index', $course)
            ->with('success', 'تم إضافة الدرس بنجاح');
    }

    /**
     * عرض تفاصيل الدرس
     */
    public function show(AdvancedCourse $course, CourseLesson $lesson)
    {
        return view('admin.course-lessons.show', compact('course', 'lesson'));
    }

    /**
     * عرض صفحة تعديل الدرس
     */
    public function edit(AdvancedCourse $course, CourseLesson $lesson)
    {
        return view('admin.course-lessons.edit', compact('course', 'lesson'));
    }

    /**
     * تحديث بيانات الدرس
     */
    public function update(Request $request, AdvancedCourse $course, CourseLesson $lesson)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,document,quiz,assignment',
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',

            'attachments.*' => 'nullable|file|max:'.config('upload_limits.max_upload_kb'),
            'duration_minutes' => 'nullable|integer|min:1',
            'order' => 'nullable|integer|min:0',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['is_free'] = $request->has('is_free');
        $data['is_active'] = $request->has('is_active');

        // التحقق من صحة رابط الفيديو
        if ($data['type'] === 'video' && !empty($data['video_url'])) {
            if (!\App\Helpers\VideoHelper::isValidVideoUrl($data['video_url'])) {
                return back()->withErrors(['video_url' => 'يسمح فقط بروابط Bunny Stream (mediadelivery.net).'])->withInput();
            }
        }

        // رفع المرفقات الجديدة
        if ($request->hasFile('attachments')) {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                $path = PublicMediaStorage::storeFile($file, 'course-attachments');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => PublicMediaStorage::publicUrl($path) ?? $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
            $data['attachments'] = json_encode($attachments);
        }

        $lesson->update($data);

        return redirect()->route('admin.courses.lessons.index', $course)
            ->with('success', 'تم تحديث الدرس بنجاح');
    }

    /**
     * حذف الدرس
     */
    public function destroy(AdvancedCourse $course, CourseLesson $lesson)
    {
        // حذف المرفقات فقط (الفيديوهات روابط خارجية)
        if ($lesson->attachments) {
            $attachments = json_decode($lesson->attachments, true);
            foreach ($attachments as $attachment) {
                $raw = (string) ($attachment['path'] ?? '');
                if ($raw === '') {
                    continue;
                }
                if (PublicMediaStorage::isExternalUrl($raw)) {
                    $relative = ltrim((string) (parse_url($raw, PHP_URL_PATH) ?: ''), '/');
                } else {
                    $relative = ltrim(str_replace('/storage/', '', $raw), '/');
                }
                PublicMediaStorage::delete($relative);
            }
        }

        // حذف تقدم الطلاب في هذا الدرس
        \App\Models\LessonProgress::where('course_lesson_id', $lesson->id)->delete();

        $lesson->delete();

        return redirect()->route('admin.courses.lessons.index', $course)
            ->with('success', 'تم حذف الدرس بنجاح');
    }

    /**
     * إعادة ترتيب الدروس
     */
    public function reorder(Request $request, AdvancedCourse $course)
    {
        $request->validate([
            'lessons' => 'required|array',
            'lessons.*.id' => 'required|exists:course_lessons,id',
            'lessons.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->lessons as $lessonData) {
            CourseLesson::where('id', $lessonData['id'])
                ->where('advanced_course_id', $course->id)
                ->update(['order' => $lessonData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة ترتيب الدروس بنجاح'
        ]);
    }

    /**
     * تغيير حالة الدرس (تفعيل/إلغاء تفعيل)
     */
    public function toggleStatus(AdvancedCourse $course, CourseLesson $lesson)
    {
        $lesson->update([
            'is_active' => !$lesson->is_active
        ]);

        $status = $lesson->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';

        return response()->json([
            'success' => true,
            'message' => $status . ' الدرس بنجاح',
            'is_active' => $lesson->is_active
        ]);
    }
}
