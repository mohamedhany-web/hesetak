<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAgreement;
use App\Models\EmployeeSalaryPayment;
use App\Models\User;
use App\Services\EmployeeAgreementService;
use App\Services\PublicMediaStorage;
use App\Support\EmployeeAgreementTemplate;
use App\Support\SearchInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAgreementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $employeeId = filter_var($request->input('employee_id'), FILTER_VALIDATE_INT);
            $status = strip_tags(trim($request->input('status', '')));
            $search = SearchInput::sanitizeForLike((string) $request->input('search', ''));

            $query = EmployeeAgreement::with(['employee', 'creator'])
                ->withCount(['deductions', 'payments']);

            if ($employeeId && $employeeId > 0) {
                $query->where('employee_id', $employeeId);
            }

            if ($status && in_array($status, ['draft', 'active', 'suspended', 'terminated', 'completed'], true)) {
                $query->where('status', $status);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('agreement_number', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhereHas('employee', function ($q) use ($search) {
                            $q->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            }

            $agreements = $query->orderBy('created_at', 'desc')->paginate(20);

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
            Log::error('Error loading employee agreements: '.$e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل اتفاقيات الموظفين');
        }
    }

    public function create()
    {
        $employees = User::where('is_employee', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultContractTerms = old('contract_terms', EmployeeAgreementTemplate::contractTerms());
        $defaultAgreementTerms = old('agreement_terms', EmployeeAgreementTemplate::agreementTerms());
        $defaultDescription = old('description', EmployeeAgreementTemplate::defaultDescription());

        return view('admin.employee-agreements.create', compact(
            'employees',
            'defaultContractTerms',
            'defaultAgreementTerms',
            'defaultDescription',
        ));
    }

    public function store(Request $request, EmployeeAgreementService $agreements)
    {
        try {
            $endDateRaw = $request->input('end_date');
            $endDateNormalized = (is_string($endDateRaw) && trim($endDateRaw) !== '') ? trim($endDateRaw) : null;
            $request->merge(['end_date' => $endDateNormalized]);

            $status = (string) $request->input('status', 'draft');
            $validator = Validator::make(
                $request->all(),
                $agreements->validationRules($status),
                $agreements->validationMessages()
            );

            if ($validator->fails()) {
                return redirect()->route('admin.employee-agreements.create')
                    ->withInput()
                    ->withErrors($validator);
            }

            $validated = $validator->validated();
            $agreements->assertEmployeeIsStaff((int) $validated['employee_id']);

            if (! empty($validated['end_date']) && $validated['end_date'] < $validated['start_date']) {
                return redirect()->route('admin.employee-agreements.create')
                    ->withInput()
                    ->withErrors(['end_date' => ['تاريخ الانتهاء يجب أن يكون في نفس تاريخ البدء أو بعده.']]);
            }

            DB::beginTransaction();

            $agreement = EmployeeAgreement::create([
                'employee_id' => $validated['employee_id'],
                'agreement_number' => EmployeeAgreement::generateAgreementNumber(),
                'version' => 1,
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

            return redirect()->route('admin.employee-agreements.show', $agreement)
                ->with('success', 'تم إنشاء الاتفاقية بنجاح. يمكنك معاينة PDF أو إرسالها لموافقة الموظف.');
        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return redirect()->route('admin.employee-agreements.create')
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Employee agreement store FAILED', [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->to(url('/admin/employee-agreements/create'))
                ->with('error', 'خطأ عند الحفظ: '.mb_substr($e->getMessage(), 0, 500));
        }
    }

    public function show(EmployeeAgreement $employeeAgreement)
    {
        $employeeAgreement->load([
            'employee.employeeJob',
            'creator',
            'versions',
            'deductions' => function ($q) {
                $q->orderBy('deduction_date', 'desc');
            },
            'payments' => function ($q) {
                $q->orderBy('payment_date', 'desc');
            },
        ]);

        $stats = [
            'total_deductions' => $employeeAgreement->deductions()->where('status', 'applied')->sum('amount'),
            'total_payments' => $employeeAgreement->payments()->where('status', 'paid')->sum('net_salary'),
            'pending_payments' => $employeeAgreement->payments()->where('status', 'pending')->count(),
            'total_paid_amount' => $employeeAgreement->payments()->where('status', 'paid')->sum('net_salary'),
        ];

        return view('admin.employee-agreements.show', compact('employeeAgreement', 'stats'));
    }

    public function edit(EmployeeAgreement $employeeAgreement)
    {
        $employees = User::where('is_employee', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.employee-agreements.edit', compact('employeeAgreement', 'employees'));
    }

    public function update(Request $request, EmployeeAgreement $employeeAgreement, EmployeeAgreementService $agreements)
    {
        $request->merge(['end_date' => trim((string) $request->input('end_date', '')) !== '' ? $request->input('end_date') : null]);

        $status = (string) $request->input('status', $employeeAgreement->status);
        $validator = Validator::make(
            $request->all(),
            $agreements->validationRules($status),
            $agreements->validationMessages()
        );

        if ($validator->fails()) {
            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->withErrors($validator);
        }

        $validated = $validator->validated();

        try {
            $agreements->assertEmployeeIsStaff((int) $validated['employee_id']);

            if (! empty($validated['end_date']) && $validated['end_date'] < $validated['start_date']) {
                return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                    ->withInput()
                    ->withErrors(['end_date' => ['تاريخ الانتهاء يجب أن يكون في نفس تاريخ البدء أو بعده.']]);
            }

            DB::beginTransaction();
            $wasSigned = $employeeAgreement->isSigned();
            $previousVersion = (int) $employeeAgreement->version;
            $updated = $agreements->applyUpdate($employeeAgreement, $validated, auth()->user());
            DB::commit();

            $msg = 'تم تحديث الاتفاقية بنجاح';
            if ($wasSigned && (int) $updated->version > $previousVersion) {
                $msg = 'تم إنشاء إصدار جديد بعد تعديل عقد موقّع. الحالة عادت إلى مسودة بانتظار موافقة الموظف مجدداً.';
            }

            return redirect()->route('admin.employee-agreements.show', $updated)
                ->with('success', $msg);
        } catch (ValidationException $e) {
            DB::rollBack();

            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Employee agreement update failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.employee-agreements.edit', $employeeAgreement)
                ->withInput()
                ->with('error', 'خطأ عند الحفظ: '.$e->getMessage());
        }
    }

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
            Log::error('Error deleting employee agreement: '.$e->getMessage());

            return redirect()->route('admin.employee-agreements.index')
                ->with('error', 'حدث خطأ أثناء حذف الاتفاقية');
        }
    }

    public function downloadPdf(EmployeeAgreement $employeeAgreement, EmployeeAgreementService $agreements): Response
    {
        try {
            $binary = $agreements->pdfBinary($employeeAgreement, true);
            $filename = $employeeAgreement->agreement_number.'-v'.$employeeAgreement->version.'.pdf';

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        } catch (\Throwable $e) {
            Log::error('Employee agreement PDF failed: '.$e->getMessage());

            return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
                ->with('error', 'تعذر توليد PDF: '.$e->getMessage());
        }
    }

    public function sendForAcceptance(EmployeeAgreement $employeeAgreement, EmployeeAgreementService $agreements)
    {
        try {
            $agreements->sendAcceptanceOtp($employeeAgreement);

            return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
                ->with('success', 'تم إرسال رمز التأكيد إلى بريد الموظف. يمكنه الموافقة من لوحة الموظف.');
        } catch (ValidationException $e) {
            return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Send employee agreement OTP failed: '.$e->getMessage());

            return redirect()->route('admin.employee-agreements.show', $employeeAgreement)
                ->with('error', 'تعذر إرسال رمز التأكيد: '.$e->getMessage());
        }
    }

    public function storePayment(Request $request, EmployeeAgreement $employeeAgreement)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'total_deductions' => 'nullable|numeric|min:0',
        ]);

        $baseSalary = (float) $employeeAgreement->salary;
        $totalDeductions = (float) ($request->total_deductions ?? 0);
        $netSalary = $baseSalary - $totalDeductions;

        EmployeeSalaryPayment::create([
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
                ? trim(($payment->notes ?? '')."\n".'[تحويل] '.$request->notes)
                : $payment->notes,
        ]);

        $agreement = $payment->agreement;

        return redirect()->route('admin.employee-agreements.show', $agreement)
            ->with('success', 'تم تسجيل الدفع ورفع إيصال التحويل. ستظهر المدفوعة في قسم المحاسبة للموظف.');
    }
}
