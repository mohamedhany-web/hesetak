<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Wallet;
use App\Services\PublicMediaStorage;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * عرض قائمة المصروفات
     */
    public function index(Request $request)
    {
        $query = Expense::with(['wallet', 'approvedBy', 'createdBy'])
            ->orderBy('created_at', 'desc');

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب الفئة
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->date_to);
        }

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
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
        $expense->load(['wallet', 'approvedBy', 'createdBy']);
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

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // إنشاء معاملة مالية (مصروف)
        \App\Models\Transaction::create([
            'transaction_number' => \App\Models\Transaction::generateUniqueTransactionNumber(),
            'user_id' => $expense->created_by ?? auth()->id(),
            'payment_id' => null,
            'type' => 'debit', // مدين (مصروف)
            'category' => 'other',
            'amount' => $expense->amount,
            'currency' => $expense->currency,
            'description' => 'مصروف: ' . $expense->title . ' - رقم المصروف: ' . $expense->expense_number,
            'status' => 'completed',
            'metadata' => [
                'expense_id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'category' => $expense->category,
            ],
            'created_by' => auth()->id(),
        ]);

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
