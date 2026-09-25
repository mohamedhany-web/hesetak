<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Models\Expense;
use App\Models\Wallet;
use App\Models\ActivityLog;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ExpenseController extends Controller
{
    /**
     * عرض قائمة المصروفات
     * محمي من: XSS, SQL Injection, Brute Force
     */
    public function index(Request $request)
    {
        try {
            $query = Expense::with(['wallet', 'approvedBy', 'createdBy'])
                ->orderBy('created_at', 'desc');

            // فلترة حسب الحالة - حماية من SQL Injection
            if ($request->filled('status')) {
                $status = strip_tags(trim($request->status));
                $status = preg_replace('/[^a-z_]/', '', $status);
                if (in_array($status, ['pending', 'approved', 'rejected'])) {
                    $query->where('status', $status);
                }
            }

            // فلترة حسب الفئة - حماية من SQL Injection
            if ($request->filled('category')) {
                $category = strip_tags(trim($request->category));
                $category = preg_replace('/[^a-z_]/', '', $category);
                if (in_array($category, ['operational', 'marketing', 'salaries', 'utilities', 'equipment', 'maintenance', 'other'])) {
                    $query->where('category', $category);
                }
            }

            // فلترة حسب التاريخ - حماية من SQL Injection
            if ($request->filled('date_from')) {
                $dateFrom = strip_tags(trim($request->date_from));
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
                    $query->whereDate('expense_date', '>=', $dateFrom);
                }
            }
            if ($request->filled('date_to')) {
                $dateTo = strip_tags(trim($request->date_to));
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
                    $query->whereDate('expense_date', '<=', $dateTo);
                }
            }

            // البحث - حماية من XSS و SQL Injection
            if ($request->filled('search')) {
                $search = SearchInput::sanitizeForLike((string) $request->search);
                if ($search !== '') {
                    $query->where(function($q) use ($search) {
                        $q->where('expense_number', 'like', "%{$search}%")
                          ->orWhere('title', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('reference_number', 'like', "%{$search}%");
                    });
                }
            }

            $expenses = $query->paginate(20);

            // إحصائيات
            $stats = [
                'total' => Expense::count(),
                'pending' => Expense::pending()->count(),
                'approved' => Expense::approved()->count(),
                'rejected' => Expense::rejected()->count(),
                'total_amount' => Expense::approved()->sum('amount'),
                'pending_amount' => Expense::pending()->sum('amount'),
                'this_month' => Expense::whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->approved()
                    ->sum('amount'),
            ];

            return view('admin.expenses.index', compact('expenses', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error in ExpenseController@index: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    /**
     * عرض نموذج إنشاء مصروف جديد
     */
    public function create()
    {
        $wallets = Wallet::where('is_active', true)
            ->whereNotNull('type')
            ->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.expenses.create', compact('wallets'));
    }

    /**
     * حفظ مصروف جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:operational,marketing,salaries,utilities,equipment,maintenance,other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,wallet,other',
            'wallet_id' => 'nullable|exists:wallets,id',
            'reference_number' => 'nullable|string|max:255',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string',
        ], [
            'title.required' => 'عنوان المصروف مطلوب',
            'category.required' => 'فئة المصروف مطلوبة',
            'amount.required' => 'المبلغ مطلوب',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'expense_date.required' => 'تاريخ المصروف مطلوب',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
        ]);

        try {
            // إنشاء رقم المصروف
            $expenseNumber = 'EXP-' . str_pad(Expense::count() + 1, 8, '0', STR_PAD_LEFT);

            // رفع المرفق إذا كان موجوداً
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = PublicMediaStorage::storeFile($request->file('attachment'), 'expenses');
            }

            // إنشاء المصروف
            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'currency' => platform_currency(),
                'expense_date' => $validated['expense_date'],
                'payment_method' => $validated['payment_method'],
                'wallet_id' => $validated['wallet_id'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'attachment' => $attachmentPath,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.expenses.index')
                ->with('success', 'تم إنشاء المصروف بنجاح وانتظار الموافقة');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء إنشاء المصروف: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل المصروف
     */
    public function show(Expense $expense)
    {
        $expense->load(['wallet', 'approvedBy', 'createdBy', 'transaction', 'invoice']);
        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * عرض نموذج تعديل المصروف
     */
    public function edit(Expense $expense)
    {
        $wallets = Wallet::where('is_active', true)
            ->whereNotNull('type')
            ->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer'])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.expenses.edit', compact('expense', 'wallets'));
    }

    /**
     * تحديث المصروف
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:operational,marketing,salaries,utilities,equipment,maintenance,other',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,card,wallet,other',
            'wallet_id' => 'nullable|exists:wallets,id',
            'reference_number' => 'nullable|string|max:255',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string',
        ]);

        try {
            // رفع المرفق الجديد إذا كان موجوداً
            if ($request->hasFile('attachment')) {
                $attachmentPath = PublicMediaStorage::storeFile(
                    $request->file('attachment'),
                    'expenses',
                    $expense->attachment
                );
                $validated['attachment'] = $attachmentPath;
            }

            $expense->update($validated);

            return redirect()->route('admin.expenses.index')
                ->with('success', 'تم تحديث المصروف بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تحديث المصروف: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * الموافقة على المصروف
     */
    public function approve(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'لا يمكن الموافقة على هذا المصروف');
        }

        try {
            DB::beginTransaction();

            // تحديث حالة المصروف
            $expense->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // خصم المبلغ من المحفظة إذا كانت موجودة
            $wallet = null;
            if ($expense->wallet_id) {
                $wallet = \App\Models\Wallet::find($expense->wallet_id);
                if ($wallet) {
                    try {
                        // التحقق من وجود رصيد كافي
                        if ($wallet->balance < $expense->amount) {
                            DB::rollback();
                            return back()->with('error', 'رصيد المحفظة غير كافي. الرصيد الحالي: ' . number_format($wallet->balance, 2) . ' ' . currency_symbol());
                        }

                        // خصم المبلغ من المحفظة
                        $wallet->withdraw(
                            $expense->amount,
                            'سحب من المصروف رقم: ' . $expense->expense_number . ' - ' . $expense->title
                        );
                    } catch (\Exception $e) {
                        DB::rollback();
                        return back()->with('error', 'حدث خطأ أثناء خصم المبلغ من المحفظة: ' . $e->getMessage());
                    }
                }
            }

            // إنشاء معاملة مالية (مصروف)
            $transaction = \App\Models\Transaction::create([
                'user_id' => $expense->created_by ?? auth()->id(),
                'payment_id' => null,
                'invoice_id' => null,
                'expense_id' => $expense->id,
                'subscription_id' => null,
                'type' => 'debit', // مدين (مصروف)
                'category' => 'expense',
                'amount' => $expense->amount,
                'currency' => $expense->currency,
                'description' => 'مصروف: ' . $expense->title . ' - رقم المصروف: ' . $expense->expense_number . ($wallet ? ' - محفظة: ' . $wallet->name : ''),
                'status' => 'completed',
                'metadata' => [
                    'expense_id' => $expense->id,
                    'expense_number' => $expense->expense_number,
                    'category' => $expense->category,
                    'wallet_id' => $expense->wallet_id,
                ],
                'created_by' => auth()->id(),
            ]);

            // ربط المصروف بالمعاملة المالية
            $expense->update([
                'transaction_id' => $transaction->id,
            ]);

            // ربط معاملة المحفظة بالمعاملة المالية إذا كانت موجودة
            if ($wallet) {
                $walletTransaction = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'withdrawal')
                    ->where('amount', $expense->amount)
                    ->latest()
                    ->first();
                
                if ($walletTransaction) {
                    $walletTransaction->update([
                        'transaction_id' => $transaction->id,
                        'notes' => 'سحب من المصروف رقم: ' . $expense->expense_number . ' - ' . $expense->title,
                    ]);
                }
            }

            DB::commit();

            try {
                app(\App\Services\CouponCommissionService::class)->markSettledFromExpense($expense->fresh());
            } catch (\Throwable $e) {
                Log::warning('Coupon commission settle from expense failed: ' . $e->getMessage(), ['expense_id' => $expense->id]);
            }

            return back()->with('success', 'تمت الموافقة على المصروف ' . ($wallet ? 'وتم خصم المبلغ من المحفظة' : '') . ' بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'حدث خطأ أثناء الموافقة على المصروف: ' . $e->getMessage());
        }

        return back()->with('success', 'تمت الموافقة على المصروف بنجاح');
    }

    /**
     * رفض المصروف
     */
    public function reject(Request $request, Expense $expense)
    {
        if ($expense->status !== 'pending') {
            return back()->with('error', 'لا يمكن رفض هذا المصروف');
        }

        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => ($expense->notes ? $expense->notes . "\n" : '') . 'سبب الرفض: ' . ($request->rejection_reason ?? 'غير محدد'),
        ]);

        return back()->with('success', 'تم رفض المصروف');
    }

    /**
     * حذف المصروف
     */
    public function destroy(Expense $expense)
    {
        try {
            // حذف المرفق
            PublicMediaStorage::delete($expense->attachment);

            $expense->delete();

            return redirect()->route('admin.expenses.index')
                ->with('success', 'تم حذف المصروف بنجاح');

        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء حذف المصروف: ' . $e->getMessage());
        }
    }
}
