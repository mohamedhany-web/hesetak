<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Support\OneToOneReportGate;
use App\Models\WithdrawalRequest;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class WithdrawalRequestController extends Controller
{
    protected function userCanManageWithdrawals(): bool
    {
        $user = Auth::user();

        return $user && ($user->isSuperAdmin() || $user->hasPermission('manage.withdrawals'));
    }

    /**
     * عرض قائمة طلبات السحب
     * محمي من: Unauthorized Access, SQL Injection, XSS
     */
    public function index(Request $request)
    {
        if (! $this->userCanManageWithdrawals()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        try {
            // Sanitization
            $instructorId = filter_var($request->input('instructor_id'), FILTER_VALIDATE_INT);
            $status = strip_tags(trim($request->input('status', '')));
            $search = SearchInput::sanitizeForLike((string) $request->input('search', ''));

            $query = WithdrawalRequest::with(['instructor', 'processedBy', 'payment']);

            if ($status && in_array($status, ['pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled'])) {
                $query->where('status', $status);
            }

            if ($instructorId && $instructorId > 0) {
                $query->where('instructor_id', $instructorId);
            }

            if ($search !== '') {
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', '%' . $search . '%')
                      ->orWhereHas('instructor', function($q) use ($search) {
                          $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                      });
                });
            }

            $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20);
            $instructors = \App\Models\User::whereIn('role', ['instructor', 'teacher'])->orderBy('name')->get();

            $overdueReportCounts = OneToOneReportGate::overdueCountsByInstructorIds(
                $withdrawals->pluck('instructor_id')->filter()->unique()->all()
            );

            $stats = [
                'total' => WithdrawalRequest::count(),
                'pending' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_PENDING)->count(),
                'approved' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_APPROVED)->count(),
                'completed' => WithdrawalRequest::where('status', WithdrawalRequest::STATUS_COMPLETED)->sum('amount'),
            ];

            return view('admin.withdrawals.index', compact('withdrawals', 'instructors', 'stats', 'overdueReportCounts'));
        } catch (\Exception $e) {
            Log::error('Error loading withdrawals: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل طلبات السحب');
        }
    }

    public function show(WithdrawalRequest $withdrawal)
    {
        if (! $this->userCanManageWithdrawals()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $withdrawal->load(['instructor', 'processedBy', 'payment']);
        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * الموافقة على طلب السحب
     * محمي من: Unauthorized Access, Brute Force, SQL Injection
     */
    public function approve(Request $request, WithdrawalRequest $withdrawal)
    {
        if (! $this->userCanManageWithdrawals()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        // Rate Limiting
        $key = 'approve_withdrawal_' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return redirect()->back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        RateLimiter::hit($key, 60);

        // التحقق من حالة الطلب
        if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'لا يمكن الموافقة على هذا الطلب');
        }

        try {
            // Sanitization
            $adminNotes = strip_tags(trim($request->input('admin_notes', '')));
            if (strlen($adminNotes) > 1000) {
                $adminNotes = mb_substr($adminNotes, 0, 1000);
            }

            DB::beginTransaction();
            
            $withdrawal->refresh();
            if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
                DB::rollBack();
                return redirect()->back()->with('error', 'تم تغيير حالة الطلب أثناء المعالجة');
            }

            $withdrawal->update([
                'status' => WithdrawalRequest::STATUS_APPROVED,
                'admin_notes' => $adminNotes,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Activity Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'withdrawal_approved',
                'description' => 'تم الموافقة على طلب سحب رقم: ' . ($withdrawal->request_number ?? $withdrawal->id),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'تم الموافقة على طلب السحب بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::clear($key);
            Log::error('Error approving withdrawal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء الموافقة على الطلب');
        }
    }

    /**
     * رفض طلب السحب
     * محمي من: Unauthorized Access, Brute Force, SQL Injection
     */
    public function reject(Request $request, WithdrawalRequest $withdrawal)
    {
        if (! $this->userCanManageWithdrawals()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        // Rate Limiting
        $key = 'reject_withdrawal_' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return redirect()->back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        RateLimiter::hit($key, 60);

        // التحقق من حالة الطلب
        if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'لا يمكن رفض هذا الطلب');
        }

        try {
            // Sanitization
            $adminNotes = strip_tags(trim($request->input('admin_notes', '')));
            if (strlen($adminNotes) > 1000) {
                $adminNotes = mb_substr($adminNotes, 0, 1000);
            }

            DB::beginTransaction();
            
            $withdrawal->refresh();
            if ($withdrawal->status !== WithdrawalRequest::STATUS_PENDING) {
                DB::rollBack();
                return redirect()->back()->with('error', 'تم تغيير حالة الطلب أثناء المعالجة');
            }

            $withdrawal->update([
                'status' => WithdrawalRequest::STATUS_REJECTED,
                'admin_notes' => $adminNotes,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Activity Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'withdrawal_rejected',
                'description' => 'تم رفض طلب سحب رقم: ' . ($withdrawal->request_number ?? $withdrawal->id),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'تم رفض طلب السحب');
        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::clear($key);
            Log::error('Error rejecting withdrawal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء رفض الطلب');
        }
    }

    /**
     * إكمال طلب السحب
     * محمي من: Unauthorized Access, Brute Force, SQL Injection
     */
    public function complete(Request $request, WithdrawalRequest $withdrawal)
    {
        if (! $this->userCanManageWithdrawals()) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        // Rate Limiting
        $key = 'complete_withdrawal_' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return redirect()->back()->with('error', "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.");
        }
        RateLimiter::hit($key, 60);

        // التحقق من حالة الطلب
        if ($withdrawal->status !== WithdrawalRequest::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'يجب الموافقة على الطلب أولاً');
        }

        try {
            // Sanitization
            $adminNotes = strip_tags(trim($request->input('admin_notes', '')));
            if (strlen($adminNotes) > 1000) {
                $adminNotes = mb_substr($adminNotes, 0, 1000);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();
            
            $withdrawal->refresh();
            if ($withdrawal->status !== WithdrawalRequest::STATUS_APPROVED) {
                DB::rollBack();
                return redirect()->back()->with('error', 'تم تغيير حالة الطلب أثناء المعالجة');
            }

            // إنشاء سجل دفع في النظام المحاسبي
            $payment = Payment::create([
                'user_id' => $withdrawal->instructor_id,
                'order_id' => null,
                'amount' => $withdrawal->amount,
                'payment_method' => $withdrawal->payment_method,
                'status' => 'completed',
                'paid_at' => now(),
                'notes' => 'سحب ماديات - ' . ($withdrawal->request_number ?? $withdrawal->id),
            ]);

            $withdrawal->update([
                'status' => WithdrawalRequest::STATUS_COMPLETED,
                'payment_id' => $payment->id,
                'admin_notes' => $adminNotes,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            // Activity Log
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'withdrawal_completed',
                'description' => 'تم إكمال طلب سحب رقم: ' . ($withdrawal->request_number ?? $withdrawal->id) . ' - المبلغ: ' . number_format($withdrawal->amount, 2) . ' ' . currency_symbol(),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'تم إكمال طلب السحب بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::clear($key);
            Log::error('Error completing withdrawal: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ أثناء إكمال الطلب');
        }
    }
}
