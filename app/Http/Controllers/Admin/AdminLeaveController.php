<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLeaveController extends Controller
{
    /**
     * عرض قائمة جميع طلبات الإجازة
     */
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee.employeeJob', 'reviewer'])->latest();

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب النوع
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // فلترة حسب الموظف
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaveRequests = $query->paginate(20);

        $employees = User::employees()->orderBy('name')->get();

        $stats = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        return view('admin.leaves.index', compact('leaveRequests', 'employees', 'stats'));
    }

    /**
     * نموذج إنشاء طلب إجازة لموظف
     */
    public function create()
    {
        $employees = User::employees()->orderBy('name')->get();

        return view('admin.leaves.create', compact('employees'));
    }

    /**
     * حفظ طلب إجازة نيابة عن موظف
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'type' => 'required|in:annual,sick,emergency,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $employee = User::query()->findOrFail($validated['employee_id']);
        if (! $employee->isEmployee()) {
            return back()->withInput()->with('error', 'المستخدم المحدد ليس موظفاً.');
        }

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $validated['days'] = $startDate->diffInDays($endDate) + 1;
        $validated['status'] = 'pending';
        unset($validated['employee_id']);

        LeaveRequest::create(array_merge($validated, ['employee_id' => $employee->id]));

        return redirect()->route('admin.leaves.index')
            ->with('success', 'تم تقديم طلب الإجازة بنجاح');
    }

    /**
     * عرض تفاصيل طلب إجازة
     */
    public function show(LeaveRequest $leave)
    {
        $leave->load(['employee.employeeJob', 'reviewer']);

        return view('admin.leaves.show', compact('leave'));
    }

    /**
     * الموافقة على طلب إجازة
     */
    public function approve(Request $request, LeaveRequest $leave)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($leave->status !== 'pending') {
            return redirect()->back()
                        ->with('error', 'لا يمكن الموافقة على طلب تمت مراجعته بالفعل');
        }

        $leave->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return redirect()->route('admin.leaves.index')
                        ->with('success', 'تم الموافقة على طلب الإجازة بنجاح');
    }

    /**
     * رفض طلب إجازة
     */
    public function reject(Request $request, LeaveRequest $leave)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        if ($leave->status !== 'pending') {
            return redirect()->back()
                        ->with('error', 'لا يمكن رفض طلب تمت مراجعته بالفعل');
        }

        $leave->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'admin_notes' => $validated['admin_notes'],
        ]);

        return redirect()->route('admin.leaves.index')
                        ->with('success', 'تم رفض طلب الإجازة بنجاح');
    }
}
