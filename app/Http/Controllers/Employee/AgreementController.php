<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAgreement;
use App\Services\EmployeeAgreementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AgreementController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user->isEmployee()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $agreements = EmployeeAgreement::where('employee_id', $user->id)
            ->with(['creator', 'deductions', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        $activeAgreement = $agreements->where('status', 'active')->first();

        return view('employee.agreements.index', compact('agreements', 'activeAgreement'));
    }

    public function show(EmployeeAgreement $agreement)
    {
        $user = Auth::user();

        if (! $user->isEmployee() || $agreement->employee_id !== $user->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $agreement->load(['creator', 'deductions', 'payments', 'versions']);

        $stats = [
            'total_deductions' => $agreement->deductions()->where('status', 'applied')->sum('amount'),
            'total_payments' => $agreement->payments()->where('status', 'paid')->sum('net_salary'),
            'pending_payments' => $agreement->payments()->where('status', 'pending')->count(),
        ];

        return view('employee.agreements.show', compact('agreement', 'stats'));
    }

    public function downloadPdf(EmployeeAgreement $agreement, EmployeeAgreementService $agreements): Response
    {
        $user = Auth::user();
        if (! $user->isEmployee() || $agreement->employee_id !== $user->id) {
            abort(403);
        }

        $binary = $agreements->pdfBinary($agreement, true);
        $filename = $agreement->agreement_number.'-v'.$agreement->version.'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function requestAcceptanceCode(EmployeeAgreement $agreement, EmployeeAgreementService $agreements)
    {
        $user = Auth::user();
        if (! $user->isEmployee() || $agreement->employee_id !== $user->id) {
            abort(403);
        }

        try {
            $agreements->sendAcceptanceOtp($agreement);

            return back()->with('success', 'تم إرسال رمز التأكيد إلى بريدك الإلكتروني.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }
    }

    public function accept(Request $request, EmployeeAgreement $agreement, EmployeeAgreementService $agreements)
    {
        $user = Auth::user();
        if (! $user->isEmployee() || $agreement->employee_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'otp' => 'required|string|size:6',
            'signer_name' => 'required|string|min:3|max:120',
        ], [
            'otp.required' => 'أدخل رمز التأكيد.',
            'otp.size' => 'رمز التأكيد يجب أن يكون 6 أرقام.',
            'signer_name.required' => 'اكتب اسمك للموافقة.',
        ]);

        try {
            $agreements->acceptWithOtp(
                $agreement,
                $data['otp'],
                $data['signer_name'],
                (string) $request->ip(),
                (string) $request->userAgent(),
            );

            return redirect()->route('employee.agreements.show', $agreement)
                ->with('success', 'تم توقيع الاتفاقية بنجاح. وصلت نسخة PDF إلى بريدك.');
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }
    }
}
