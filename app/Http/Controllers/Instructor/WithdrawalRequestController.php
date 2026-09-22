<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Models\AgreementPayment;
use App\Support\OneToOneReportGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalRequestController extends Controller
{
    public function index()
    {
        $instructor = auth()->user();
        
        $withdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->with(['payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // حساب الماديات المتاحة
        $totalEarned = AgreementPayment::where('instructor_id', $instructor->id)
            ->where('status', AgreementPayment::STATUS_PAID)
            ->sum('amount');
        
        $totalWithdrawn = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_COMPLETED, WithdrawalRequest::STATUS_PROCESSING])
            ->sum('amount');
        
        $pendingWithdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_PENDING, WithdrawalRequest::STATUS_APPROVED])
            ->sum('amount');

        $availableAmount = $totalEarned - $totalWithdrawn - $pendingWithdrawals;

        $stats = [
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalWithdrawn,
            'pending_withdrawals' => $pendingWithdrawals,
            'available_amount' => max(0, $availableAmount),
        ];

        return view('instructor.withdrawals.index', compact('withdrawals', 'stats'));
    }

    public function create()
    {
        $instructor = auth()->user();

        if (OneToOneReportGate::instructorHasOverdueReports((int) $instructor->id)) {
            $count = OneToOneReportGate::overdueCountForInstructor((int) $instructor->id);

            return redirect()
                ->route('instructor.withdrawals.index')
                ->with('error', 'الصرف موقوف حتى إكمال تقارير الحصص. لديك '.$count.' حصة بانتظار التقرير.');
        }

        $totalEarned = AgreementPayment::where('instructor_id', $instructor->id)
            ->where('status', AgreementPayment::STATUS_PAID)
            ->sum('amount');

        $totalWithdrawn = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_COMPLETED, WithdrawalRequest::STATUS_PROCESSING])
            ->sum('amount');

        $pendingWithdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_PENDING, WithdrawalRequest::STATUS_APPROVED])
            ->sum('amount');

        $availableAmount = max(0, $totalEarned - $totalWithdrawn - $pendingWithdrawals);

        $stats = [
            'total_earned' => $totalEarned,
            'total_withdrawn' => $totalWithdrawn,
            'pending_withdrawals' => $pendingWithdrawals,
            'available_amount' => $availableAmount,
        ];

        return view('instructor.withdrawals.create', compact('stats'));
    }

    public function store(Request $request)
    {
        $instructor = auth()->user();

        if (OneToOneReportGate::instructorHasOverdueReports((int) $instructor->id)) {
            $count = OneToOneReportGate::overdueCountForInstructor((int) $instructor->id);

            return redirect()->back()
                ->withInput()
                ->with('error', 'الصرف موقوف حتى التقرير. أكمل تقارير '.$count.' حصة أولاً.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank_transfer,wallet,cash,other',
            'bank_name' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'account_number' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'account_holder_name' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // حساب الماديات المتاحة
        $totalEarned = AgreementPayment::where('instructor_id', $instructor->id)
            ->where('status', AgreementPayment::STATUS_PAID)
            ->sum('amount');
        
        $totalWithdrawn = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_COMPLETED, WithdrawalRequest::STATUS_PROCESSING])
            ->sum('amount');
        
        $pendingWithdrawals = WithdrawalRequest::where('instructor_id', $instructor->id)
            ->whereIn('status', [WithdrawalRequest::STATUS_PENDING, WithdrawalRequest::STATUS_APPROVED])
            ->sum('amount');

        $availableAmount = max(0, $totalEarned - $totalWithdrawn - $pendingWithdrawals);

        if ($request->amount > $availableAmount) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'المبلغ المطلوب يتجاوز الماديات المتاحة. المبلغ المتاح: ' . number_format($availableAmount, 2) . ' ' . currency_symbol());
        }

        WithdrawalRequest::create([
            'instructor_id' => $instructor->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'iban' => $request->iban,
            'notes' => $request->notes,
            'status' => WithdrawalRequest::STATUS_PENDING,
        ]);

        return redirect()->route('instructor.withdrawals.index')
            ->with('success', 'تم تقديم طلب السحب بنجاح. سيتم مراجعته من قبل الإدارة');
    }

    public function show(WithdrawalRequest $withdrawal)
    {
        if ($withdrawal->instructor_id !== auth()->id()) {
            abort(403);
        }

        $withdrawal->load(['payment']);
        return view('instructor.withdrawals.show', compact('withdrawal'));
    }

    public function cancel(WithdrawalRequest $withdrawal)
    {
        if ($withdrawal->instructor_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($withdrawal->status, [WithdrawalRequest::STATUS_PENDING, WithdrawalRequest::STATUS_APPROVED])) {
            return redirect()->back()->with('error', 'لا يمكن إلغاء هذا الطلب');
        }

        $withdrawal->update([
            'status' => WithdrawalRequest::STATUS_CANCELLED,
        ]);

        return redirect()->route('instructor.withdrawals.index')
            ->with('success', 'تم إلغاء طلب السحب بنجاح');
    }
}
