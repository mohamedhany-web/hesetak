<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Classroom;
use App\Services\CourseThumbnailStorage;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with(['subject', 'classroom'])
            ->withCount(['lessons', 'enrollments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'requirements' => 'nullable|string',
            'what_you_learn' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:'.config('upload_limits.max_upload_kb'),
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ], [
            'title.required' => 'عنوان الكورس مطلوب',
            'subject_id.required' => 'المادة مطلوبة',
            'subject_id.exists' => 'المادة المحددة غير موجودة',
            'classroom_id.required' => 'الصف الدراسي مطلوب',
            'classroom_id.exists' => 'الصف الدراسي المحدد غير موجود',
            'level.required' => 'مستوى الكورس مطلوب',
            'level.in' => 'مستوى الكورس غير صحيح',
            'thumbnail.image' => 'يجب أن تكون الصورة من نوع صحيح',
            'thumbnail.max' => 'حجم الصورة لا يجب أن يتجاوز 2MB',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        
        // ضمان عدم إرسال null للحقول المطلوبة
        $data['description'] = $data['description'] ?? '';
        $data['objectives'] = $data['objectives'] ?? '';
        $data['requirements'] = $data['requirements'] ?? '';
        $data['what_you_learn'] = $data['what_you_learn'] ?? '';
        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['price'] = $data['price'] ?? 0;

        // رفع الصورة
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = PublicMediaStorage::store(
                $request->file('thumbnail'),
                CourseThumbnailStorage::DIRECTORY
            );
        }

        Course::create($data);

        return redirect()->route('admin.courses.index')
            ->with('success', 'تم إضافة الكورس بنجاح');
    }

    public function show(Course $course)
    {
        $course->load([
            'subject',
            'classroom',
            'lessons' => function($query) {
                $query->orderBy('order');
            },
            'enrollments.user'
        ]);

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'requirements' => 'nullable|string',
            'what_you_learn' => 'nullable|string',
            'subject_id' => 'required|exists:subjects,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'duration_hours' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:'.config('upload_limits.max_upload_kb'),
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ], [
            'title.required' => 'عنوان الكورس مطلوب',
            'subject_id.required' => 'المادة مطلوبة',
            'subject_id.exists' => 'المادة المحددة غير موجودة',
            'classroom_id.required' => 'الصف الدراسي مطلوب',
            'classroom_id.exists' => 'الصف الدراسي المحدد غير موجود',
            'level.required' => 'مستوى الكورس مطلوب',
            'level.in' => 'مستوى الكورس غير صحيح',
            'thumbnail.image' => 'يجب أن تكون الصورة من نوع صحيح',
            'thumbnail.max' => 'حجم الصورة لا يجب أن يتجاوز 2MB',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        
        // ضمان عدم إرسال null للحقول المطلوبة
        $data['description'] = $data['description'] ?? '';
        $data['objectives'] = $data['objectives'] ?? '';
        $data['requirements'] = $data['requirements'] ?? '';
        $data['what_you_learn'] = $data['what_you_learn'] ?? '';
        $data['duration_hours'] = $data['duration_hours'] ?? 0;
        $data['price'] = $data['price'] ?? 0;

        // رفع الصورة الجديدة (يحذف القديمة تلقائياً)
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = PublicMediaStorage::store(
                $request->file('thumbnail'),
                CourseThumbnailStorage::DIRECTORY,
                $course->thumbnail
            );
        }

        $course->update($data);

        return redirect()->route('admin.courses.index')
            ->with('success', 'تم تحديث الكورس بنجاح');
    }

    public function destroy(Course $course)
    {
        if ($course->enrollments()->count() > 0) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'لا يمكن حذف الكورس لأن هناك طلاب مسجلين فيه');
        }

        if ($course->lessons()->count() > 0) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'لا يمكن حذف الكورس لأنه يحتوي على دروس');
        }

        // حذف الصورة
        CourseThumbnailStorage::deleteIfStored($course->thumbnail);

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'تم حذف الكورس بنجاح');
    }
}