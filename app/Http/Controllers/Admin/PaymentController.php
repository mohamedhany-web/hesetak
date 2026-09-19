<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * عرض قائمة المدفوعات
     * محمي من: XSS, SQL Injection, Brute Force
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['user', 'invoice', 'wallet', 'installmentPayment', 'transactions'])
                ->orderBy('created_at', 'desc');

            // فلترة حسب الحالة - حماية من SQL Injection
            if ($request->filled('status')) {
                $status = strip_tags(trim($request->status));
                $status = preg_replace('/[^a-z_]/', '', $status);
                if (in_array($status, ['pending', 'completed', 'processing', 'failed', 'cancelled', 'refunded'])) {
                    $query->where('status', $status);
                }
            }

            // البحث - حماية من XSS و SQL Injection
            if ($request->filled('search')) {
                $search = SearchInput::sanitizeForLike((string) $request->search);
                if ($search !== '') {
                    $query->where(function($q) use ($search) {
                        $q->where('payment_number', 'like', "%{$search}%")
                          ->orWhere('reference_number', 'like', "%{$search}%")
                          ->orWhereHas('user', function($uq) use ($search) {
                              $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                          });
                    });
                }
            }

            $payments = $query->paginate(20);

            // إحصائيات سريعة
            $stats = [
                'total' => Payment::count(),
                'completed' => Payment::where('status', 'completed')->count(),
                'pending' => Payment::where('status', 'pending')->count(),
                'failed' => Payment::where('status', 'failed')->count(),
                'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            ];

            return view('admin.payments.index', compact('payments', 'stats'));
        } catch (\Exception $e) {
            Log::error('Error in PaymentController@index: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    public function create()
    {
        $invoices = Invoice::with(['user', 'payments' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get()
            ->filter(fn ($invoice) => $invoice->remaining_amount > 0)
            ->values();

        $users = User::where('role', 'student')->where('is_active', true)->orderBy('name')->get();
        return view('admin.payments.create', compact('invoices', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'required|in:cash,card,bank_transfer,online,wallet,other',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::with('user', 'payments')->findOrFail($validated['invoice_id']);

        if ((int) $invoice->user_id !== (int) $validated['user_id']) {
            return back()->withErrors(['invoice_id' => 'هذه الفاتورة لا تتبع الطالب المحدد.'])->withInput();
        }

        $remainingAmount = $invoice->remaining_amount;
        if ($remainingAmount <= 0) {
            return back()->withErrors(['invoice_id' => 'تم سداد هذه الفاتورة بالفعل.'])->withInput();
        }

        if ($validated['amount'] > $remainingAmount) {
            return back()->withErrors([
                'amount' => 'لا يمكن دفع مبلغ أكبر من المتبقي (' . number_format($remainingAmount, 2) . ' $).' ,
            ])->withInput();
        }

        $payment = Payment::create([
            'payment_number' => Payment::generateUniquePaymentNumber(),
            'invoice_id' => $invoice->id,
            'user_id' => $validated['user_id'],
            'payment_method' => $validated['payment_method'],
            'wallet_id' => $request->wallet_id ?? null,
            'amount' => $validated['amount'],
            'currency' => platform_currency(),
            'status' => 'completed',
            'paid_at' => now(),
            'processed_by' => auth()->id(),
            'notes' => $validated['notes'],
        ]);

        // إنشاء معاملة مالية تلقائياً (إيراد)
        \App\Models\Transaction::create([
            'transaction_number' => \App\Models\Transaction::generateUniqueTransactionNumber(),
            'user_id' => $validated['user_id'],
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'expense_id' => null,
            'subscription_id' => null,
            'type' => 'credit', // دائن (إيراد)
            'category' => $invoice->type === 'subscription' ? 'subscription' : 'course_payment',
            'amount' => $validated['amount'],
            'currency' => platform_currency(),
            'description' => 'دفعة للفاتورة: ' . $invoice->invoice_number . ' - ' . $invoice->description,
            'status' => 'completed',
            'metadata' => [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'payment_method' => $validated['payment_method'],
            ],
            'created_by' => auth()->id(),
        ]);

        $invoice->refresh();
        if ($invoice->remaining_amount <= 0 && !$invoice->isPaid()) {
                $invoice->markAsPaid();
        }

        return redirect()->route('admin.payments.index')
            ->with('success', 'تم إنشاء الدفعة بنجاح');
    }

    public function show(Payment $payment)
    {
        $payment->load('user', 'invoice', 'processedBy');
        return view('admin.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $payment->load('invoice');

        $invoices = Invoice::with(['user', 'payments' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->get()
            ->filter(function ($invoice) use ($payment) {
                return $invoice->remaining_amount > 0 || $invoice->id === $payment->invoice_id;
            })
            ->values();

        $users = User::where('role', 'student')->where('is_active', true)->orderBy('name')->get();
        return view('admin.payments.edit', compact('payment', 'invoices', 'users'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'user_id' => 'required|exists:users,id',
            'payment_method' => 'required|in:cash,card,bank_transfer,online,wallet,other',
            'amount' => 'required|numeric|min:0.01',
            'status' => 'required|in:pending,completed,failed,cancelled,refunded',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::with('payments')->findOrFail($validated['invoice_id']);

        if ((int) $invoice->user_id !== (int) $validated['user_id']) {
            return back()->withErrors(['invoice_id' => 'هذه الفاتورة لا تتبع الطالب المحدد.'])->withInput();
        }

        $currentRemaining = $invoice->remaining_amount + ($payment->status === 'completed' ? $payment->amount : 0);

        if ($validated['status'] === 'completed' && $validated['amount'] > $currentRemaining) {
            return back()->withErrors([
                'amount' => 'لا يمكن دفع مبلغ أكبر من المتبقي (' . number_format(max($currentRemaining, 0), 2) . ' $).' ,
            ])->withInput();
        }

        $payment->update([
            'invoice_id' => $invoice->id,
            'user_id' => $validated['user_id'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'status' => $validated['status'],
            'notes' => $validated['notes'],
        ]);

        $invoice->refresh();
        if ($invoice->remaining_amount <= 0 && !$invoice->isPaid()) {
            $invoice->markAsPaid();
        }

        return redirect()->route('admin.payments.index')
            ->with('success', 'تم تحديث الدفعة بنجاح');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('admin.payments.index')
            ->with('success', 'تم حذف الدفعة بنجاح');
    }
}
