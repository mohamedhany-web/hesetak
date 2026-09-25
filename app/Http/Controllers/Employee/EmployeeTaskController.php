<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTask;
use App\Models\EmployeeTaskDeliverable;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeTaskController extends Controller
{
    /**
     * عرض قائمة مهام الموظف
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isEmployee()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $query = $user->employeeTasks()->with(['assigner', 'deliverables']);

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->latest()->paginate(15);

        $stats = [
            'total' => $user->employeeTasks()->count(),
            'pending' => $user->employeeTasks()->where('status', 'pending')->count(),
            'in_progress' => $user->employeeTasks()->where('status', 'in_progress')->count(),
            'completed' => $user->employeeTasks()->where('status', 'completed')->count(),
            'overdue' => $user->employeeTasks()
                ->where('deadline', '<', now())
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
        ];

        return view('employee.tasks.index', compact('tasks', 'stats'));
    }

    /**
     * عرض تفاصيل مهمة
     */
    public function show(EmployeeTask $task)
    {
        $user = Auth::user();
        
        if (!$user->isEmployee() || $task->employee_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $task->load(['assigner', 'deliverables' => function ($q) {
            $q->with('reviewer')->orderByDesc('created_at');
        }]);
        
        return view('employee.tasks.show', compact('task'));
    }

    /**
     * تحديث حالة المهمة
     */
    public function updateStatus(Request $request, EmployeeTask $task)
    {
        $user = Auth::user();
        
        if (!$user->isEmployee() || $task->employee_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,on_hold',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        // تحديث التواريخ بناءً على الحالة
        if ($validated['status'] === 'in_progress' && !$task->started_at) {
            $validated['started_at'] = now();
        }

        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
            $validated['progress'] = 100;
        }

        $task->update($validated);

        return back()->with('success', 'تم تحديث حالة المهمة بنجاح');
    }

    /**
     * تسليم مهمة
     */
    public function submitDeliverable(Request $request, EmployeeTask $task)
    {
        $user = Auth::user();

        if (!$user->isEmployee() || $task->employee_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'delivery_type' => 'required|in:file,image,link',
            'file' => 'nullable|file|max:'.config('upload_limits.max_upload_kb').'|required_if:delivery_type,file,image',
            'link_url' => 'nullable|url|required_if:delivery_type,link',
        ];
        if ($task->isVideoEditing()) {
            $rules['received_from'] = 'nullable|string|max:255';
            $rules['duration_before'] = 'nullable|string|max:64';
            $rules['duration_after'] = 'nullable|string|max:64';
        }
        $validated = $request->validate($rules);

        $filePath = null;
        $fileName = null;
        $fileType = null;
        $fileSize = null;
        $linkUrl = null;
        $deliveryType = $validated['delivery_type'];

        if (in_array($deliveryType, ['file', 'image']) && $request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $folder = $deliveryType === 'image' ? 'employee-deliverables/images' : 'employee-deliverables/files';
            $filePath = PublicMediaStorage::storeFile($file, $folder);
        }
        if ($deliveryType === 'link') {
            $linkUrl = $validated['link_url'];
        }

        EmployeeTaskDeliverable::create([
            'task_id' => $task->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'delivery_type' => $deliveryType,
            'link_url' => $linkUrl,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'received_from' => $task->isVideoEditing() ? ($validated['received_from'] ?? null) : null,
            'duration_before' => $task->isVideoEditing() ? ($validated['duration_before'] ?? null) : null,
            'duration_after' => $task->isVideoEditing() ? ($validated['duration_after'] ?? null) : null,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($task->status !== 'completed') {
            $task->update(['status' => 'in_progress']);
        }

        $message = 'تم تسليم المهمة بنجاح';
        return redirect()->to(route('employee.tasks.show', $task) . '?open=1')
            ->with('success', $message);
    }
}
