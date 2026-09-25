<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskDeliverable;
use App\Models\AdvancedCourse;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $instructor = Auth::user();

        // المهام من الإدارة فقط (المسندة من الأدمن)
        $query = Task::where('user_id', $instructor->id)
            ->whereNotNull('assigned_by')
            ->with(['relatedCourse', 'relatedLecture', 'assigner']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('due_date', 'asc')->paginate(20);

        // إحصائيات (مهام من الإدارة فقط)
        $baseQuery = Task::where('user_id', $instructor->id)->whereNotNull('assigned_by');
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        return view('instructor.tasks.index', compact('tasks', 'stats'));
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
        
        // جلب المحاضرات إذا تم اختيار كورس
        $lectures = collect();
        if (request()->filled('course_id')) {
            $lectures = \App\Models\Lecture::where('course_id', request('course_id'))
                ->where('instructor_id', $instructor->id)
                ->orderBy('scheduled_at', 'desc')
                ->get();
        }
        
        return view('instructor.tasks.create', compact('courses', 'lectures'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'related_course_id' => 'nullable|exists:advanced_courses,id',
            'related_lecture_id' => 'nullable|exists:lectures,id',
            'tags' => 'nullable|array',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['status'] = 'pending';
        $task = Task::create($validated);

        return redirect()->route('instructor.tasks.show', $task)
            ->with('success', 'تم إنشاء المهمة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        $task->load(['relatedCourse', 'relatedLecture', 'assigner', 'deliverables.reviewer']);
        return view('instructor.tasks.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     * المهام المسندة من الإدارة لا يمكن تعديلها من المدرب.
     */
    public function edit(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        if ($task->isAssignedByAdmin()) {
            return redirect()->route('instructor.tasks.show', $task)
                ->with('info', 'لا يمكن تعديل المهمة المسندة من الإدارة. استخدم التسليمات وتحديث التقدم.');
        }

        $instructor = Auth::user();
        $courses = AdvancedCourse::where('instructor_id', $instructor->id)
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        // جلب المحاضرات للكورس المحدد
        $lectures = collect();
        if ($task->related_course_id) {
            $lectures = \App\Models\Lecture::where('course_id', $task->related_course_id)
                ->where('instructor_id', $instructor->id)
                ->orderBy('scheduled_at', 'desc')
                ->get();
        }

        return view('instructor.tasks.edit', compact('task', 'courses', 'lectures'));
    }

    /**
     * Update the specified resource in storage.
     * المهام المسندة من الإدارة لا يمكن تعديلها من المدرب.
     */
    public function update(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        if ($task->isAssignedByAdmin()) {
            return redirect()->route('instructor.tasks.show', $task)
                ->with('info', 'لا يمكن تعديل المهمة المسندة من الإدارة.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date',
            'related_course_id' => 'nullable|exists:advanced_courses,id',
            'related_lecture_id' => 'nullable|exists:lectures,id',
            'tags' => 'nullable|array',
        ]);

        if ($request->status === 'completed' && !$task->completed_at) {
            $validated['completed_at'] = now();
        }

        $task->update($validated);

        return redirect()->route('instructor.tasks.show', $task)
            ->with('success', 'تم تحديث المهمة بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     * المهام المسندة من الإدارة لا يمكن حذفها من المدرب.
     */
    public function destroy(Task $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
        if ($task->isAssignedByAdmin()) {
            return redirect()->route('instructor.tasks.index')
                ->with('info', 'لا يمكن حذف المهمة المسندة من الإدارة.');
        }

        $task->delete();

        return redirect()->route('instructor.tasks.index')
            ->with('success', 'تم حذف المهمة بنجاح');
    }

    /**
     * Get lectures for a course (AJAX)
     */
    public function getLectures(Request $request)
    {
        $instructor = Auth::user();
        
        $lectures = \App\Models\Lecture::where('course_id', $request->course_id)
            ->where('instructor_id', $instructor->id)
            ->orderBy('scheduled_at', 'desc')
            ->get(['id', 'title', 'scheduled_at']);
        
        return response()->json($lectures);
    }

    /**
     * تسليم عمل على مهمة مسندة من الإدارة
     */
    public function submitDeliverable(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id() || !$task->isAssignedByAdmin()) {
            abort(403, 'غير مصرح لك بتسليم هذه المهمة');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'delivery_type' => 'required|in:file,image,link',
            'file' => 'nullable|file|max:'.config('upload_limits.max_upload_kb').'|required_if:delivery_type,file,image',
            'link_url' => 'nullable|url|required_if:delivery_type,link',
        ]);

        $filePath = null;
        $fileName = null;
        $fileType = null;
        $fileSize = null;
        $linkUrl = null;

        if (in_array($validated['delivery_type'], ['file', 'image']) && $request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $folder = $validated['delivery_type'] === 'image' ? 'task-deliverables/images' : 'task-deliverables/files';
            $filePath = PublicMediaStorage::storeFile($file, $folder);
        }
        if ($validated['delivery_type'] === 'link') {
            $linkUrl = $validated['link_url'];
        }

        TaskDeliverable::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'delivery_type' => $validated['delivery_type'],
            'link_url' => $linkUrl,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($task->status === 'pending') {
            $task->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'تم تسليم العمل بنجاح');
    }

    /**
     * تحديث التقدم على مهمة مسندة من الإدارة
     */
    public function updateProgress(Request $request, Task $task)
    {
        if ($task->user_id !== Auth::id() || !$task->isAssignedByAdmin()) {
            abort(403, 'غير مصرح لك بتحديث هذه المهمة');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $data = [
            'status' => $validated['status'],
            'progress' => (int) ($validated['progress'] ?? $task->progress),
        ];
        if ($validated['status'] === 'completed') {
            $data['completed_at'] = $task->completed_at ?: now();
        }
        $task->update($data);

        return back()->with('success', 'تم تحديث التقدم بنجاح');
    }
}
