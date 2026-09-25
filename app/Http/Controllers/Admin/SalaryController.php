<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgreementPayment;
use App\Models\InstructorAgreement;
use App\Models\User;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    /**
     * الماليات الخاصة بالمدربين - قائمة كل المدربين الذين لديهم اتفاقية أو مدفوعات
     */
    public function index(Request $request)
    {
        $instructorIdsFromPayments = AgreementPayment::distinct()->pluck('instructor_id');
        $instructorIdsFromAgreements = InstructorAgreement::distinct()->pluck('instructor_id');
        $instructorIds = $instructorIdsFromPayments->merge($instructorIdsFromAgreements)->unique()->values();

        $statsByInstructor = AgreementPayment::whereIn('status', [AgreementPayment::STATUS_APPROVED, AgreementPayment::STATUS_PAID])
            ->selectRaw('instructor_id,
                sum(case when status = ? then amount else 0 end) as pending_total,
                sum(case when status = ? then amount else 0 end) as paid_total,
                count(case when status = ? then 1 end) as pending_count,
                count(case when status = ? then 1 end) as paid_count',
                [
                    AgreementPayment::STATUS_APPROVED,
                    AgreementPayment::STATUS_PAID,
                    AgreementPayment::STATUS_APPROVED,
                    AgreementPayment::STATUS_PAID,
                ]
            )
            ->groupBy('instructor_id')
            ->get()
            ->keyBy('instructor_id');

        $instructors = User::whereIn('id', $instructorIds)->orderBy('name')->get();

        $globalStats = [
            'pending_total' => AgreementPayment::where('status', AgreementPayment::STATUS_APPROVED)->sum('amount'),
            'pending_count' => AgreementPayment::where('status', AgreementPayment::STATUS_APPROVED)->count(),
            'paid_total' => AgreementPayment::where('status', AgreementPayment::STATUS_PAID)->sum('amount'),
            'paid_count' => AgreementPayment::where('status', AgreementPayment::STATUS_PAID)->count(),
        ];

        return view('admin.salaries.index', compact('instructors', 'statsByInstructor', 'globalStats'));
    }

    /**
     * صفحة مدرب واحد: الاتفاقيات كاملة + جميع المدفوعات (قيد المراجعة، مطلوب الدفع، تم الدفع)
     */
    public function instructor(User $instructor)
    {
        $agreements = InstructorAgreement::where('instructor_id', $instructor->id)
            ->orderByDesc('created_at')
            ->get();

        $payments = AgreementPayment::with(['agreement', 'instructor.payoutDetail'])
            ->where('instructor_id', $instructor->id)
            ->whereIn('status', [
                AgreementPayment::STATUS_PENDING,
                AgreementPayment::STATUS_APPROVED,
                AgreementPayment::STATUS_PAID,
            ])
            ->latest()
            ->get();

        $pendingTotal = $payments->where('status', AgreementPayment::STATUS_APPROVED)->sum('amount');
        $paidTotal = $payments->where('status', AgreementPayment::STATUS_PAID)->sum('amount');

        return view('admin.salaries.instructor', compact('instructor', 'agreements', 'payments', 'pendingTotal', 'paidTotal'));
    }

    /**
     * إنشاء مدفوعة من الاتفاقية (موافق عليها) والانتقال لصفحة الدفع — دفع الآن
     */
    public function payNowFromAgreement(Request $request, User $instructor, InstructorAgreement $agreement)
    {
        if ($agreement->instructor_id != $instructor->id) {
            return redirect()->route('admin.salaries.instructor', $instructor)
                ->with('error', 'الاتفاقية لا تخص هذا المدرب.');
        }

        $amount = (float) ($agreement->rate ?? 0);
        if ($amount <= 0) {
            return redirect()->route('admin.salaries.instructor', $instructor)
                ->with('error', 'الاتفاقية لا تحتوي على مبلغ محدد.');
        }

        $paymentType = match ($agreement->type ?? '') {
            'course_price' => AgreementPayment::TYPE_COURSE_COMPLETION,
            'hourly_rate' => AgreementPayment::TYPE_HOURLY_TEACHING,
            'monthly_salary' => AgreementPayment::TYPE_MONTHLY_SALARY,
            'consultation_session' => AgreementPayment::TYPE_CONSULTATION_SESSION,
            default => AgreementPayment::TYPE_MONTHLY_SALARY,
        };

        $payment = AgreementPayment::create([
            'agreement_id' => $agreement->id,
            'instructor_id' => $instructor->id,
            'type' => $paymentType,
            'amount' => $amount,
            'status' => AgreementPayment::STATUS_APPROVED,
            'description' => 'مدفوعة من صفحة الماليات — ' . ($agreement->title ?? ''),
            'payment_date' => now(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.salaries.pay', $payment)
            ->with('success', 'تم إنشاء المدفوعة. قم برفع إيصال التحويل لتسجيل الدفع.');
    }

    /**
     * صفحة تنفيذ الدفع (رفع إيصال التحويل وتأكيد الدفع)
     */
    public function pay(AgreementPayment $payment)
    {
        if ($payment->status !== AgreementPayment::STATUS_APPROVED) {
            $redirect = $payment->instructor_id
                ? redirect()->route('admin.salaries.instructor', $payment->instructor_id)
                : redirect()->route('admin.salaries.index');
            return $redirect->with('error', 'هذه المدفوعة ليست قيد الانتظار للدفع.');
        }

        $payment->load(['agreement', 'instructor.payoutDetail']);
        return view('admin.salaries.pay', compact('payment'));
    }

    /**
     * الرجوع إلى صفحة الماليات (قائمة المدربين أو صفحة المدرب)
     */
    private function redirectAfterPay(AgreementPayment $payment)
    {
        return redirect()->route('admin.salaries.instructor', $payment->instructor_id)
            ->with('success', 'تم تسجيل الدفع ورفع إيصال التحويل بنجاح.');
    }

    /**
     * تنفيذ الدفع: رفع الإيصال وتحديث الحالة
     */
    public function markPaid(Request $request, AgreementPayment $payment)
    {
        if ($payment->status !== AgreementPayment::STATUS_APPROVED) {
            $to = $payment->instructor_id ? route('admin.salaries.instructor', $payment->instructor_id) : route('admin.salaries.index');
            return redirect($to)->with('error', 'هذه المدفوعة ليست قيد الانتظار للدفع.');
        }

        $request->validate([
            'transfer_receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string|max:500',
        ]);

        $file = $request->file('transfer_receipt');
        $path = PublicMediaStorage::storeFile($file, 'receipts/agreement-payments');
        $payment->update([
            'status' => AgreementPayment::STATUS_PAID,
            'paid_at' => now(),
            'transfer_receipt_path' => $path,
            'notes' => $request->filled('notes')
                ? trim($payment->notes . "\n" . '[تحويل] ' . $request->notes)
                : $payment->notes,
        ]);

        return $this->redirectAfterPay($payment);
    }
}
