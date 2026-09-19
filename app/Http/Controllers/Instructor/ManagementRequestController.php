<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManagementRequestController extends Controller
{
    /**
     * قائمة طلبات المدرب للإدارة
     */
    public function index(Request $request)
    {
        $base = InstructorRequest::query()->where('instructor_id', Auth::id());

        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $query = (clone $base)->with('repliedByUser')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15);

        return view('instructor.management-requests.index', compact('requests', 'stats'));
    }

    /**
     * نموذج تقديم طلب جديد
     */
    public function create()
    {
        return view('instructor.management-requests.create');
    }

    /**
     * حفظ الطلب
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $validated['instructor_id'] = Auth::id();
        $validated['status'] = 'pending';

        InstructorRequest::create($validated);

        return redirect()->route('instructor.management-requests.index')
            ->with('success', 'تم إرسال الطلب للإدارة بنجاح. سيتم الرد عليه قريباً.');
    }

    /**
     * عرض تفاصيل الطلب
     */
    public function show(InstructorRequest $managementRequest)
    {
        if ($managementRequest->instructor_id !== Auth::id()) {
            abort(403);
        }
        $managementRequest->load('repliedByUser');
        return view('instructor.management-requests.show', ['request' => $managementRequest]);
    }
}
