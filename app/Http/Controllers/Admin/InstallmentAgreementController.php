<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallmentAgreement;
use App\Models\InstallmentPayment;
use App\Models\InstallmentPlan;
use App\Models\StudentCourseEnrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallmentAgreementController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage.installments');
    }

    public function index(Request $request): View
    {
        $agreements = InstallmentAgreement::with(['student', 'course', 'plan'])
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statuses = $this->statusOptions();

        return view('admin.installments.agreements.index', compact('agreements', 'statuses'));
    }

    public function create(Request $request): View
    {
        $plans = InstallmentPlan::active()->with('course')->orderBy('name')->get();
        $statuses = $this->statusOptions();
        $selectedPlanId = $request->integer('plan_id');

        $enrollmentsQuery = StudentCourseEnrollment::with(['student', 'course'])->latest();

        if ($request->filled('student')) {
            $term = $request->string('student');
            $enrollmentsQuery->whereHas('student', function (Builder $query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        if ($request->filled('course')) {
            $term = $request->string('course');
            $enrollmentsQuery->whereHas('course', function (Builder $query) use ($term) {
                $query->where('title', 'like', "%{$term}%");
            });
        }

        $enrollments = $enrollmentsQuery->limit(50)->get();

        return view('admin.installments.agreements.create', compact('plans', 'enrollments', 'statuses', 'selectedPlanId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $plan = InstallmentPlan::findOrFail($data['installment_plan_id']);
        $enrollment = StudentCourseEnrollment::with(['student', 'course'])->findOrFail($data['student_course_enrollment_id']);

        if (InstallmentAgreement::where('student_course_enrollment_id', $enrollment->id)
            ->whereIn('status', ['active', 'overdue'])
            ->exists()) {
            return back()->withErrors(['student_course_enrollment_id' => 'هناك خطة تقسيط نشطة بالفعل لهذا التسجيل.'])->withInput();
        }

        $totalAmount = $data['total_amount'] ?? $plan->total_amount ?? $enrollment->course?->price ?? 0;
        $depositAmount = $data['deposit_amount'] ?? $plan->deposit_amount ?? 0;

        if ($totalAmount < $depositAmount) {
            return back()->withErrors(['deposit_amount' => 'الدفعة المقدمة أكبر من إجمالي المبلغ.'])->withInput();
        }

        $agreement = InstallmentAgreement::create([
            'installment_plan_id' => $plan->id,
            'student_course_enrollment_id' => $enrollment->id,
            'user_id' => $enrollment->user_id,
            'advanced_course_id' => $enrollment->advanced_course_id,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'installments_count' => $data['installments_count'] ?? $plan->installments_count,
            'start_date' => $data['start_date'],
            'status' => $data['status'] ?? InstallmentAgreement::STATUS_ACTIVE,
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        $agreement->payments()->delete();
        $agreement->generateSchedule(Carbon::parse($agreement->start_date));

        return redirect()->route('admin.installments.agreements.show', $agreement)->with('success', 'تم إنشاء اتفاقية التقسيط وجدول السداد بنجاح.');
    }

    public function show(InstallmentAgreement $agreement): View
    {
        $agreement->load(['student', 'course', 'plan', 'payments' => function ($query) {
            $query->orderBy('sequence_number');
        }]);

        $statuses = $this->statusOptions();
        $paymentStatuses = $this->paymentStatuses();
        $frequencyUnits = $this->frequencyUnits();

        return view('admin.installments.agreements.show', compact('agreement', 'statuses', 'paymentStatuses', 'frequencyUnits'));
    }

    public function edit(InstallmentAgreement $agreement): View
    {
        $plans = InstallmentPlan::active()->with('course')->orderBy('name')->get();
        $statuses = $this->statusOptions();

        return view('admin.installments.agreements.edit', compact('agreement', 'plans', 'statuses'));
    }

    public function update(Request $request, InstallmentAgreement $agreement): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys($this->statusOptions()))],
            'notes' => ['nullable', 'string'],
        ]);

        $agreement->update($data);

        return back()->with('success', 'تم تحديث حالة الاتفاقية بنجاح.');
    }

    public function destroy(InstallmentAgreement $agreement): RedirectResponse
    {
        $agreement->update(['status' => InstallmentAgreement::STATUS_CANCELLED]);
        $agreement->payments()->update(['status' => InstallmentPayment::STATUS_SKIPPED]);

        return redirect()->route('admin.installments.agreements.index')->with('success', 'تم إلغاء اتفاقية التقسيط.');
    }

    public function markPayment(Request $request, InstallmentPayment $payment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys($this->paymentStatuses()))],
            'paid_at' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'in:cash,card,bank_transfer,online,wallet,other'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            DB::beginTransaction();

            if ($data['status'] === InstallmentPayment::STATUS_PAID) {
                // التحقق من عدم وجود Payment مرتبط بالفعل
                if (!$payment->payment_id) {
                    $agreement = $payment->agreement;
                    
                    // إنشاء أو الحصول على Invoice للتقسيط
                    $invoice = $agreement->enrollment->invoice_id 
                        ? Invoice::find($agreement->enrollment->invoice_id)
                        : null;
                    
                    // إذا لم يكن هناك Invoice، أنشئ واحدة
                    if (!$invoice) {
                        $invoiceNumber = 'INV-' . str_pad(Invoice::count() + 1, 8, '0', STR_PAD_LEFT);
                        $invoice = Invoice::create([
                            'invoice_number' => $invoiceNumber,
                            'user_id' => $agreement->user_id,
                            'type' => 'course',
                            'description' => 'فاتورة تقسيط - ' . ($agreement->course->title ?? 'كورس'),
                            'subtotal' => $payment->amount,
                            'tax_amount' => 0,
                            'discount_amount' => 0,
                            'total_amount' => $payment->amount,
                            'status' => 'paid',
                            'due_date' => $payment->due_date,
                            'paid_at' => $data['paid_at'] ? Carbon::parse($data['paid_at']) : now(),
                            'notes' => 'فاتورة قسط تقسيط رقم: ' . $payment->sequence_number,
                            'items' => [
                                [
                                    'description' => 'قسط تقسيط - ' . ($agreement->course->title ?? 'كورس'),
                                    'quantity' => 1,
                                    'price' => $payment->amount,
                                    'total' => $payment->amount,
                                ]
                            ],
                        ]);
                    }

                    // إنشاء Payment
                    $paymentNumber = Payment::generateUniquePaymentNumber();
                    $paymentRecord = Payment::create([
                        'payment_number' => $paymentNumber,
                        'invoice_id' => $invoice->id,
                        'user_id' => $agreement->user_id,
                        'payment_method' => $data['payment_method'] ?? 'cash',
                        'amount' => $payment->amount,
                        'currency' => platform_currency(),
                        'status' => 'completed',
                        'paid_at' => $data['paid_at'] ? Carbon::parse($data['paid_at']) : now(),
                        'processed_by' => auth()->id(),
                        'installment_payment_id' => $payment->id,
                        'notes' => 'دفعة قسط تقسيط رقم: ' . $payment->sequence_number . ($data['notes'] ? ' - ' . $data['notes'] : ''),
                    ]);

                    // إنشاء Transaction
                    Transaction::create([
                        'transaction_number' => Transaction::generateUniqueTransactionNumber(),
                        'user_id' => $agreement->user_id,
                        'payment_id' => $paymentRecord->id,
                        'invoice_id' => $invoice->id,
                        'expense_id' => null,
                        'subscription_id' => null,
                        'type' => 'credit', // دائن (إيراد)
                        'category' => 'course_payment',
                        'amount' => $payment->amount,
                        'currency' => platform_currency(),
                        'description' => 'دفعة قسط تقسيط - ' . ($agreement->course->title ?? 'كورس') . ' - قسط رقم: ' . $payment->sequence_number,
                        'status' => 'completed',
                        'metadata' => [
                            'installment_agreement_id' => $agreement->id,
                            'installment_payment_id' => $payment->id,
                            'sequence_number' => $payment->sequence_number,
                        ],
                        'created_by' => auth()->id(),
                    ]);

                    // ربط InstallmentPayment بالPayment
                    $payment->payment_id = $paymentRecord->id;
                }

                $payment->status = InstallmentPayment::STATUS_PAID;
                $payment->paid_at = $data['paid_at'] ? Carbon::parse($data['paid_at']) : now();
            } else {
                $payment->status = $data['status'];
                $payment->paid_at = null;
            }

            $payment->notes = $data['notes'] ?? null;
            $payment->save();

            // تحديث حالة الاتفاقية
            $payment->agreement->refreshStatus();

            DB::commit();

            return back()->with('success', 'تم تحديث حالة القسط بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء تحديث حالة القسط: ' . $e->getMessage());
        }
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'installment_plan_id' => ['required', 'exists:installment_plans,id'],
            'student_course_enrollment_id' => ['required', 'exists:student_course_enrollments,id'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'installments_count' => ['nullable', 'integer', 'min:1', 'max:60'],
            'start_date' => ['required', 'date'],
            'status' => ['nullable', 'in:' . implode(',', array_keys($this->statusOptions()))],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function statusOptions(): array
    {
        return [
            InstallmentAgreement::STATUS_ACTIVE => 'نشط',
            InstallmentAgreement::STATUS_OVERDUE => 'متأخر',
            InstallmentAgreement::STATUS_COMPLETED => 'مكتمل',
            InstallmentAgreement::STATUS_CANCELLED => 'ملغى',
        ];
    }

    protected function paymentStatuses(): array
    {
        return [
            InstallmentPayment::STATUS_PENDING => 'قيد الانتظار',
            InstallmentPayment::STATUS_PAID => 'مدفوع',
            InstallmentPayment::STATUS_OVERDUE => 'متأخر',
            InstallmentPayment::STATUS_SKIPPED => 'متجاوز',
        ];
    }

    protected function frequencyUnits(): array
    {
        return [
            'month' => 'شهري',
            'week' => 'أسبوعي',
            'day' => 'يومي',
            'year' => 'سنوي',
        ];
    }
}
