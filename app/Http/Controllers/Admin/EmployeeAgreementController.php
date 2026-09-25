<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Models\EmployeeAgreement;
use App\Models\EmployeeSalaryDeduction;
use App\Models\EmployeeSalaryPayment;
use App\Models\User;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmployeeAgreementController extends Controller
{
    /**
     * عرض قائمة اتفاقيات الموظفين
     */
    public function index(Request $request)
    {
        try {
            // Sanitization
            $employeeId = filter_var($request->input('employee_id'), FILTER_VALIDATE_INT);
            $status = strip_tags(trim($request->input('status', '')));
            $search = SearchInput::sanitizeForLike((string) $request->input('search', ''));

            $query = EmployeeAgreement::with(['employee', 'creator'])
                ->withCount(['deductions', 'payments']);

            if ($employeeId && $employeeId > 0) {
                $query->where('employee_id', $employeeId);
            }

            if ($status && in_array($status, ['draft', 'active', 'suspended', 'terminated', 'completed'])) {
                $query->where('status', $status);
            }

            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('agreement_number', 'like', '%' . $search . '%')
                      ->orWhere('title', 'like', '%' . $search . '%')
                      ->orWhereHas('employee', function($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                      });
                });
            }

            $agreements = $query->orderBy('created_at', 'desc')->paginate(20);
            
            // جلب جميع الموظفين
            $employees = User::where('is_employee', true)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $stats = [
                'total' => EmployeeAgreement::count(),
                'active' => EmployeeAgreement::where('status', 'active')->count(),
                'draft' => EmployeeAgreement::where('status', 'draft')->count(),
                'total_salary' => EmployeeAgreement::where('status', 'active')->sum('salary'),
            ];

            return view('admin.employee-agreements.index', compact('agreements', 'employees', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error loading employee agreements: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل اتفاقيات الموظفين');
        }
    }

    /**
     * عرض صفحة إنشاء اتفاقية جديدة
     */
    public function create()
    {
        $employees = User::where('is_employee', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.employee-agreements.create', compact('employees'));
    }

    /**
     * حفظ اتفاقية جديدة
     */
    public function store(Request $request)
    {
        try {
            $endDateRaw = $request->input('end_date');
            $endDateNormalized = (is_string($endDateRaw) && trim($endDateRaw) !== '') ? trim($endDateRaw) : null;
            $request->merge(['end_date' => $endDateNormalized]);

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'salary' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'status' => 'required|in:draft,active,suspended,terminated,completed',
                'contract_terms' => 'nullable|string',
                'agreement_terms' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return redirect()->route('admin.employee-agreements.create')
                    ->withInput()
                    ->withErrors($validator);
            }

            $validated = $validator->validated();

            if (!empty($validated['end_date']) && $validated['end_date'] < $validated['start_date']) {
                return redirect()->route('admin.employee-agreements.create')
                    ->withInput()
                    ->withErrors(['end_date' => ['تاريخ الانتهاء يجب أن يكون في نفس تاريخ البدء أو بعده.']]);
            }

            DB::beginTransaction();

            $agreement = EmployeeAgreement::create([
                'employee_id' => $validated['employee_id'],
                'agreement_number' => EmployeeAgreement::generateAgreementNumber(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'salary' => $validated['salary'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'status' => $validated['status'],
                'contract_terms' => $validated['contract_terms'] ?? null,
                'agreement_terms' => $validated['agreement_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.employee-agreements.index')
                ->with('success', 'تم إنشاء الاتفاقية بنجاح');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $msg = $e->getMessage();
            $file = $e->getFile();
            $line = $e->getLine();
            Log::error('Employee agreement store FAILED', [
                'class' => get_class($e),
                'message' => $msg,
                'file' => $file,
                'line' => $line,
                'trace' => $e->getTraceAsString(),
            ]);
            $safeMsg = mb_substr($msg, 0, 500);
            try {
                return redirect()->to(url('/admin/employee-agreements/create'))
                    ->with('error', 'خطأ عند الحفظ: ' . $safeMsg);
            } catch (\Throwable $redirectEx) {
                Log::error('Redirect failed after store error', [
                    'message' => $redirectEx->getMessage(),
                    'original' => $msg,
                ]);
                abort(500, 'خطأ عند الحفظ: ' . $safeMsg);
            }
        }
    }

    /**
     * عرض تفاصيل اتفاقية
     */
    public function show(EmployeeAgreement $employeeAgreement)
    {
        $employeeAgreement->load([
            'employee',
            'creator',
            'deductions' => function($q) {
                $q->orderBy('deduction_date', 'desc');
            },
            'payments' => function($q) {
                $q->orderBy('payment_date', 'desc');
            }
        ]);

        $stats = [
            'total_deductions' => $employeeAgreement->deductions()->where('status', 'applied')->sum('amount'),
            'total_payments' => $employeeAgreement->payments()->where('status', 'paid')->sum('net_salary'),
            'pending_payments' => $employeeAgreement->payments()->where('status', 'pending')->count(),
            'total_paid_amount' => $employeeAgreement->payments()->where('status', 'paid')->sum('net_salary'),
        ];

        return view('admin.employee-agreements.show', compact('employeeAgreement', 'stats'));
    }

    /**
     * عرض صفحة تعديل اتفاقية
     */
    public function edit(EmployeeAgreement $employeeAgreement)
    {
        $employees = User::where('is_employee', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.employee-agreements.edit', compact('employeeAgreement', 'employees'));
    }

    /**
     * تحديث اتفاقية
     */
    public function update(Request $request, EmployeeAgreement $employeeAgreement)
    {
        $request->merge(['end_date' => trim((string) $request->input('end_date', '')) !== '' ? $request->input('end_date') : null]);

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'salary' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:draft,active,suspended,terminated,completed',
            'contract_terms' => 'nullable|string',
            'agreement_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->withErrors($validator);
        }

        $validated = $validator->validated();

        if (!empty($validated['end_date']) && $validated['end_date'] < $validated['start_date']) {
            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->withErrors(['end_date' => ['تاريخ الانتهاء يجب أن يكون في نفس تاريخ البدء أو بعده.']]);
        }

        try {
            DB::beginTransaction();
            $employeeAgreement->update($validated);
            DB::commit();

            return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
                ->with('success', 'تم تحديث الاتفاقية بنجاح');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee agreement update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->with('error', 'خطأ عند الحفظ: ' . $e->getMessage());
        }
    }

    /**
     * حذف اتفاقية
     */
    public function destroy(EmployeeAgreement $employeeAgreement)
    {
        try {
            if ($employeeAgreement->payments()->where('status', 'paid')->exists()) {
                return redirect()->route('admin.employee-agreements.index')
                    ->with('error', 'لا يمكن حذف الاتفاقية لأنها تحتوي على مدفوعات مكتملة');
            }

            $employeeAgreement->delete();

            return redirect()->route('admin.employee-agreements.index')
                ->with('success', 'تم حذف الاتفاقية بنجاح');
        } catch (\Throwable $e) {
            Log::error('Error deleting employee agreement: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect()->route('admin.employee-agreements.index')
                ->with('error', 'حدث خطأ أثناء حذف الاتفاقية');
        }
    }

    /**
     * إنشاء دفعة راتب جديدة للاتفاقية
     */
    public function storePayment(Request $request, EmployeeAgreement $employeeAgreement)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'total_deductions' => 'nullable|numeric|min:0',
        ]);

        $baseSalary = (float) $employeeAgreement->salary;
        $totalDeductions = (float) ($request->total_deductions ?? 0);
        $netSalary = $baseSalary - $totalDeductions;

        $payment = EmployeeSalaryPayment::create([
            'employee_id' => $employeeAgreement->employee_id,
            'agreement_id' => $employeeAgreement->id,
            'payment_number' => EmployeeSalaryPayment::generatePaymentNumber(),
            'base_salary' => $baseSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'payment_date' => $request->payment_date,
            'status' => 'pending',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
            ->with('success', 'تم إنشاء دفعة الراتب. قم بالتحويل للموظف ثم ارفع إيصال التحويل من جدول المدفوعات.');
    }

    /**
     * تسجيل دفع الراتب ورفع إيصال التحويل
     */
    public function markPaymentPaid(Request $request, EmployeeSalaryPayment $payment)
    {
        if ($payment->status !== 'pending' && $payment->status !== 'overdue') {
            return back()->with('error', 'هذه الدفعة ليست قيد الانتظار للدفع.');
        }

        $request->validate([
            'transfer_receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string|max:500',
        ]);

        $file = $request->file('transfer_receipt');
        $path = PublicMediaStorage::storeFile($file, 'receipts/employee-salary-payments');

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'transfer_receipt_path' => $path,
            'notes' => $request->filled('notes')
                ? trim(($payment->notes ?? '') . "\n" . '[تحويل] ' . $request->notes)
                : $payment->notes,
        ]);

        $agreement = $payment->agreement;
        return redirect()->route('admin.employee-agreements.show', $agreement)
            ->with('success', 'تم تسجيل الدفع ورفع إيصال التحويل. ستظهر المدفوعة في قسم المحاسبة للموظف.');
    }
}
