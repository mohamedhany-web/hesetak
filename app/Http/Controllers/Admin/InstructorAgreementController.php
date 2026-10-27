<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Models\InstructorAgreement;
use App\Models\AgreementPayment;
use App\Models\User;
use App\Models\AdvancedCourse;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class InstructorAgreementController extends Controller
{
    /**
     * عرض قائمة الاتفاقيات
     * محمي من: Unauthorized Access, SQL Injection, XSS
     */
    public function index(Request $request)
    {
        try {
            // Sanitization
            $instructorId = filter_var($request->input('instructor_id'), FILTER_VALIDATE_INT);
            $status = strip_tags(trim($request->input('status', '')));
            $type = strip_tags(trim($request->input('type', '')));
            $search = SearchInput::sanitizeForLike((string) $request->input('search', ''));

            $query = InstructorAgreement::with(['instructor', 'createdBy'])
                ->withCount(['payments', 'paidPayments']);

            if ($instructorId && $instructorId > 0) {
                $query->where('instructor_id', $instructorId);
            }

            if ($status && in_array($status, ['draft', 'active', 'suspended', 'terminated', 'completed'])) {
                $query->where('status', $status);
            }

            if ($type && in_array($type, ['course_price', 'hourly_rate', 'monthly_salary', 'consultation_session'])) {
                $query->where('type', $type);
            }

            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('agreement_number', 'like', '%' . $search . '%')
                      ->orWhere('title', 'like', '%' . $search . '%')
                      ->orWhereHas('instructor', function($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                      });
                });
            }

            $agreements = $query->orderBy('created_at', 'desc')->paginate(20);
            // جلب جميع المدربين (instructor أو teacher) من عمود role فقط لتجنب أخطاء علاقة roles
            $instructors = User::whereIn('role', ['instructor', 'teacher'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'role']);

            $stats = [
                'total' => InstructorAgreement::count(),
                'active' => InstructorAgreement::where('status', InstructorAgreement::STATUS_ACTIVE)->count(),
                'draft' => InstructorAgreement::where('status', InstructorAgreement::STATUS_DRAFT)->count(),
                'total_earned' => AgreementPayment::where('status', AgreementPayment::STATUS_PAID)->sum('amount'),
            ];

            return view('admin.agreements.index', compact('agreements', 'instructors', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error loading agreements: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل الاتفاقيات');
        }
    }

    public function create()
    {
        // المدربون الذين لديهم على الأقل كورس أونلاين مُعيَّن لهم (instructor_id)
        $instructorIdsWithCourses = AdvancedCourse::where('is_active', true)
            ->whereNotNull('instructor_id')
            ->distinct()
            ->pluck('instructor_id');
        $instructors = User::where(function($q) {
                $q->where('role', 'instructor')
                  ->orWhere('role', 'teacher')
                  ->orWhereHas('roles', function($roleQuery) {
                      $roleQuery->whereIn('name', ['instructor', 'teacher']);
                  });
            })
            ->where('is_active', true)
            ->whereIn('id', $instructorIdsWithCourses)
            ->orderBy('name')
            ->get();
        $advancedCourses = AdvancedCourse::where('is_active', true)
            ->whereNotNull('instructor_id')
            ->orderBy('title')
            ->get(['id', 'title', 'instructor_id']);
        return view('admin.agreements.create', compact('instructors', 'advancedCourses'));
    }

    public function store(Request $request)
    {
        $rules = [
            'instructor_id' => 'required|exists:users,id',
            'type' => 'required|in:course_price,hourly_rate,monthly_salary,course_percentage,consultation_session',
            'rate' => 'nullable|numeric|min:0',
            'advanced_course_id' => 'required_if:type,course_percentage|nullable|exists:advanced_courses,id',
            'course_percentage' => 'required_if:type,course_percentage|nullable|numeric|min:0|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,active,suspended,terminated,completed',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
        $request->validate($rules);

        $isCoursePercentage = $request->type === 'course_percentage';
        $isConsultation = $request->type === 'consultation_session';
        $agreement = InstructorAgreement::create([
            'instructor_id' => $request->instructor_id,
            // عمود type في DB: course_price|hourly_rate|monthly_salary — الاستشارات عبر billing_type.
            'type' => ($isCoursePercentage || $isConsultation) ? 'course_price' : $request->type,
            'rate' => $isCoursePercentage ? 0 : (float) ($request->rate ?? 0),
            'billing_type' => $isCoursePercentage
                ? InstructorAgreement::BILLING_COURSE_PERCENTAGE
                : ($isConsultation ? InstructorAgreement::BILLING_CONSULTATION : null),
            'advanced_course_id' => $isCoursePercentage ? $request->advanced_course_id : null,
            'course_percentage' => $isCoursePercentage ? (float) $request->course_percentage : null,
            'agreement_number' => InstructorAgreement::generateAgreementNumber(),
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'terms' => $request->terms,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.agreements.show', $agreement)
            ->with('success', 'تم إنشاء الاتفاقية بنجاح');
    }

    public function show(InstructorAgreement $agreement)
    {
        $agreement->load(['instructor', 'createdBy', 'advancedCourse', 'payments.course', 'payments.lecture', 'payments.enrollment.student']);
        
        $stats = [
            'total_earned' => $agreement->paidPayments()->sum('amount'),
            'pending_amount' => $agreement->approvedPayments()->sum('amount'),
            'total_payments' => $agreement->payments()->count(),
            'paid_payments' => $agreement->paidPayments()->count(),
        ];

        return view('admin.agreements.show', compact('agreement', 'stats'));
    }

    public function edit(InstructorAgreement $agreement)
    {
        $instructorIdsWithCourses = AdvancedCourse::where('is_active', true)
            ->whereNotNull('instructor_id')
            ->distinct()
            ->pluck('instructor_id')
            ->push($agreement->instructor_id)
            ->filter()
            ->unique()
            ->values();
        $instructors = User::where(function($q) {
                $q->where('role', 'instructor')
                  ->orWhere('role', 'teacher')
                  ->orWhereHas('roles', function($roleQuery) {
                      $roleQuery->whereIn('name', ['instructor', 'teacher']);
                  });
            })
            ->where('is_active', true)
            ->whereIn('id', $instructorIdsWithCourses)
            ->orderBy('name')
            ->get();
        $advancedCourses = AdvancedCourse::where('is_active', true)
            ->whereNotNull('instructor_id')
            ->orderBy('title')
            ->get(['id', 'title', 'instructor_id']);
        return view('admin.agreements.edit', compact('agreement', 'instructors', 'advancedCourses'));
    }

    public function update(Request $request, InstructorAgreement $agreement)
    {
        $rules = [
            'instructor_id' => 'required|exists:users,id',
            'type' => 'required|in:course_price,hourly_rate,monthly_salary,course_percentage,consultation_session',
            'rate' => 'nullable|numeric|min:0',
            'advanced_course_id' => 'required_if:type,course_percentage|nullable|exists:advanced_courses,id',
            'course_percentage' => 'required_if:type,course_percentage|nullable|numeric|min:0|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:draft,active,suspended,terminated,completed',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
        $request->validate($rules);

        $isCoursePercentage = $request->type === 'course_percentage';
        $isConsultation = $request->type === 'consultation_session';
        $agreement->update([
            'instructor_id' => $request->instructor_id,
            'type' => ($isCoursePercentage || $isConsultation) ? 'course_price' : $request->type,
            'rate' => $isCoursePercentage ? 0 : (float) ($request->rate ?? 0),
            'billing_type' => $isCoursePercentage
                ? InstructorAgreement::BILLING_COURSE_PERCENTAGE
                : ($isConsultation ? InstructorAgreement::BILLING_CONSULTATION : null),
            'advanced_course_id' => $isCoursePercentage ? $request->advanced_course_id : null,
            'course_percentage' => $isCoursePercentage ? (float) $request->course_percentage : null,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
            'terms' => $request->terms,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.agreements.show', $agreement)
            ->with('success', 'تم تحديث الاتفاقية بنجاح');
    }

    public function destroy(InstructorAgreement $agreement)
    {
        if ($agreement->payments()->where('status', AgreementPayment::STATUS_PAID)->exists()) {
            return redirect()->back()
                ->with('error', 'لا يمكن حذف الاتفاقية لأنها تحتوي على مدفوعات مكتملة');
        }

        $agreement->delete();

        return redirect()->route('admin.agreements.index')
            ->with('success', 'تم حذف الاتفاقية بنجاح');
    }
}
