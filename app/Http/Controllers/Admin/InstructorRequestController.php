<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructorRequest;
use App\Models\User;
use Illuminate\Http\Request;

class InstructorRequestController extends Controller
{
    /**
     * قائمة طلبات المدربين
     */
    public function index(Request $request)
    {
        $query = InstructorRequest::with(['instructor', 'repliedByUser'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhereHas('instructor', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $requests = $query->paginate(20);

        $stats = [
            'total' => InstructorRequest::count(),
            'pending' => InstructorRequest::pending()->count(),
            'approved' => InstructorRequest::where('status', 'approved')->count(),
            'rejected' => InstructorRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.instructor-requests.index', compact('requests', 'stats'));
    }

    /**
     * نموذج إنشاء طلب مدرب
     */
    public function create()
    {
        $instructors = User::query()
            ->whereIn('role', ['instructor', 'teacher'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.instructor-requests.create', compact('instructors'));
    }

    /**
     * حفظ طلب مدرب جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $instructor = User::query()->findOrFail($validated['instructor_id']);
        if (! $instructor->isInstructor()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس مدرباً.');
        }

        InstructorRequest::create([
            'instructor_id' => $validated['instructor_id'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
        ]);

        return redirect()->route('admin.instructor-requests.index')
            ->with('success', 'تم إنشاء الطلب بنجاح');
    }

    /**
     * عرض تفاصيل الطلب والرد عليه
     */
    public function show(InstructorRequest $instructorRequest)
    {
        $instructorRequest->load(['instructor', 'repliedByUser']);
        return view('admin.instructor-requests.show', compact('instructorRequest'));
    }

    /**
     * حفظ رد الإدارة على الطلب
     */
    public function respond(Request $request, InstructorRequest $instructorRequest)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_reply' => 'required|string|max:5000',
        ]);

        $instructorRequest->update([
            'status' => $validated['status'],
            'admin_reply' => $validated['admin_reply'],
            'replied_at' => now(),
            'replied_by' => auth()->id(),
        ]);

        $statusLabel = $validated['status'] === 'approved' ? 'موافقة' : 'رفض';
        return redirect()->route('admin.instructor-requests.show', $instructorRequest)
            ->with('success', "تم تسجيل الرد بنجاح ($statusLabel)");
    }
}
