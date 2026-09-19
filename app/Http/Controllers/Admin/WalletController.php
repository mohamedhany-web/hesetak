<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SearchInput;
use App\Models\Wallet;
use App\Models\WalletReport;
use App\Models\WalletTransaction;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    private function ownedWalletsQuery()
    {
        return Wallet::query()->where('user_id', Auth::id());
    }

    private function ensureWalletOwnership(Wallet $wallet): void
    {
        if ((int) $wallet->user_id !== (int) Auth::id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المحفظة');
        }
    }

    /**
     * عرض قائمة المحافظ
     * محمي من: XSS, SQL Injection, Brute Force
     */
    public function index(Request $request)
    {
        try {
            $query = $this->ownedWalletsQuery()
                ->with('user')
                ->orderBy('created_at', 'desc');

            // فلترة حسب الحالة - حماية من SQL Injection
            if ($request->filled('status')) {
                $status = strip_tags(trim($request->status));
                $status = preg_replace('/[^a-z]/', '', $status);
                if (in_array($status, ['active', 'inactive'])) {
                    $query->where('is_active', $status === 'active');
                }
            }

            // البحث - حماية من XSS و SQL Injection
            if ($request->filled('search')) {
                $search = SearchInput::sanitizeForLike((string) $request->search);
                if ($search !== '') {
                    $query->where(function ($walletQuery) use ($search) {
                        $walletQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('account_number', 'like', "%{$search}%")
                            ->orWhere('account_holder', 'like', "%{$search}%");
                    });
                }
            }

            $wallets = $query->paginate(12);

            $baseOwnedWallets = $this->ownedWalletsQuery();
            $stats = [
                'total' => (clone $baseOwnedWallets)->count(),
                'active' => (clone $baseOwnedWallets)->where('is_active', true)->count(),
                'inactive' => (clone $baseOwnedWallets)->where('is_active', false)->count(),
                'total_balance' => (float) (clone $baseOwnedWallets)->sum('balance'),
                'pending_balance' => (float) (clone $baseOwnedWallets)->sum('pending_balance'),
            ];

            $ownedWalletIds = (clone $baseOwnedWallets)->pluck('id');
            $totalTransactions = WalletTransaction::whereIn('wallet_id', $ownedWalletIds)->count();

            $currentMonthRange = [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ];

            $currentMonthDeposits = WalletTransaction::where('type', 'deposit')
                ->whereIn('wallet_id', $ownedWalletIds)
                ->whereBetween('created_at', $currentMonthRange)
                ->sum('amount');

            $currentMonthWithdrawals = WalletTransaction::where('type', 'withdrawal')
                ->whereIn('wallet_id', $ownedWalletIds)
                ->whereBetween('created_at', $currentMonthRange)
                ->sum('amount');

            $typeDistribution = collect();
            if (Schema::hasColumn('wallets', 'type')) {
                $typeDistribution = (clone $baseOwnedWallets)
                    ->selectRaw('type, COUNT(*) as wallets_count, SUM(balance) as total_balance')
                    ->groupBy('type')
                    ->get()
                    ->map(function ($row) {
                        return [
                            'type' => $row->type,
                            'label' => Wallet::typeLabel($row->type),
                            'wallets_count' => (int) $row->wallets_count,
                            'total_balance' => (float) $row->total_balance,
                        ];
                    });
            }

            $recentWallets = $this->ownedWalletsQuery()
                ->with('user')
                ->latest()
                ->take(5)
                ->get();

            $transferWallets = $this->ownedWalletsQuery()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'balance', 'currency']);

            return view('admin.wallets.index', compact(
                'wallets',
                'stats',
                'totalTransactions',
                'currentMonthDeposits',
                'currentMonthWithdrawals',
                'typeDistribution',
                'recentWallets',
                'transferWallets'
            ));
        } catch (\Exception $e) {
            Log::error('Error in WalletController@index: ' . $e->getMessage());
            abort(500, 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    public function show(Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $wallet->load(['user']);

        $transactionsQuery = $wallet->transactions();

        $totalDeposits = (clone $transactionsQuery)->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (clone $transactionsQuery)->where('type', 'withdrawal')->sum('amount');
        $transactionsCount = (clone $transactionsQuery)->count();
        $lastTransaction = (clone $transactionsQuery)->latest()->first();

        $currentMonthRange = [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ];

        $currentMonthDeposits = (clone $transactionsQuery)
            ->where('type', 'deposit')
            ->whereBetween('created_at', $currentMonthRange)
            ->sum('amount');

        $currentMonthWithdrawals = (clone $transactionsQuery)
            ->where('type', 'withdrawal')
            ->whereBetween('created_at', $currentMonthRange)
            ->sum('amount');

        $recentTransactions = (clone $transactionsQuery)
            ->latest()
            ->take(8)
            ->get();

        $walletPayments = $wallet->payments()
            ->with(['invoice', 'user'])
            ->latest('paid_at')
            ->take(30)
            ->get();

        $netFlow = $totalDeposits - $totalWithdrawals;

        $metrics = [
            'total_deposits' => (float) $totalDeposits,
            'total_withdrawals' => (float) $totalWithdrawals,
            'net_flow' => (float) $netFlow,
            'transactions_count' => $transactionsCount,
            'current_month_deposits' => (float) $currentMonthDeposits,
            'current_month_withdrawals' => (float) $currentMonthWithdrawals,
            'last_transaction_at' => $lastTransaction?->created_at,
            'last_transaction_type' => $lastTransaction?->type,
        ];

        return view('admin.wallets.show', [
            'wallet' => $wallet,
            'metrics' => $metrics,
            'recentTransactions' => $recentTransactions,
            'walletPayments' => $walletPayments,
        ]);
    }

    public function transactions(Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $wallet->load(['user', 'transactions' => function ($query) {
            $query->latest();
        }]);

        return view('admin.wallets.transactions', [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions,
        ]);
    }

    public function reports(Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $wallet->load(['user', 'reports' => function ($query) {
            $query->latest();
        }]);

        return view('admin.wallets.reports', [
            'wallet' => $wallet,
            'reports' => $wallet->reports,
        ]);
    }

    public function generateReport(Request $request, Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $defaultStart = $wallet->transactions()->oldest('created_at')->value('created_at');
        $from = isset($data['from'])
            ? Carbon::parse($data['from'])->startOfDay()
            : ($defaultStart ? Carbon::parse($defaultStart)->startOfDay() : Carbon::now()->startOfMonth());

        $to = isset($data['to'])
            ? Carbon::parse($data['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $transactions = $wallet->transactions()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $previousTransaction = $wallet->transactions()
            ->where('created_at', '<', $from)
            ->latest('created_at')
            ->first();

        $openingBalance = $previousTransaction?->balance_after;

        if (is_null($openingBalance) && $transactions->isNotEmpty()) {
            $first = $transactions->first();
            $openingBalance = $first->type === 'deposit'
                ? $first->balance_after - $first->amount
                : $first->balance_after + $first->amount;
        }

        $openingBalance ??= (float) $wallet->balance;

        $totalDeposits = (float) $transactions->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');
        $transactionsCount = $transactions->count();

        $latestInRange = $transactions->last();
        $latestUpToPeriod = $wallet->transactions()
            ->where('created_at', '<=', $to)
            ->latest('created_at')
            ->first();

        $closingBalance = $latestInRange?->balance_after
            ?? $latestUpToPeriod?->balance_after
            ?? (float) $wallet->balance;

        $expectedClosing = $openingBalance + $totalDeposits - $totalWithdrawals;
        $difference = round($expectedClosing - $closingBalance, 2);

        $reportKey = $from->copy()->format('Y-m');
        if ($from->format('Y-m') !== $to->format('Y-m')) {
            $reportKey .= '_'.$to->format('Y-m');
        }

        WalletReport::updateOrCreate(
            [
                'wallet_id' => $wallet->id,
                'report_month' => $reportKey,
            ],
            [
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'total_deposits' => $totalDeposits,
                'total_withdrawals' => $totalWithdrawals,
                'transactions_count' => $transactionsCount,
                'expected_amounts' => null,
                'actual_amounts' => null,
                'difference' => $difference,
                'notes' => "تقرير عن الفترة من {$from->format('Y-m-d')} إلى {$to->format('Y-m-d')}",
            ]
        );

        return back()->with('success', 'تم إنشاء التقرير بنجاح!');
    }

    public function create()
    {
        return view('admin.wallets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:vodafone_cash,instapay,bank_transfer,cash,other',
            'account_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'account_holder' => 'nullable|string|max:255',
            'balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم المحفظة مطلوب',
            'type.required' => 'نوع المحفظة مطلوب',
        ]);

        Wallet::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'type' => $validated['type'],
            'account_number' => $validated['account_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'balance' => (float) ($validated['balance'] ?? 0),
            'pending_balance' => 0,
            'currency' => platform_currency(),
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.wallets.index')
            ->with('success', 'تم إنشاء المحفظة بنجاح');
    }

    public function edit(Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        return view('admin.wallets.edit', compact('wallet'));
    }

    public function update(Request $request, Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:vodafone_cash,instapay,bank_transfer,cash,other',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_holder' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $wallet->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'account_number' => $validated['account_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.wallets.show', $wallet)
            ->with('success', 'تم تحديث المحفظة بنجاح');
    }

    public function destroy(Wallet $wallet)
    {
        $this->ensureWalletOwnership($wallet);
        $wallet->delete();
        return redirect()->route('admin.wallets.index')
            ->with('success', 'تم حذف المحفظة بنجاح');
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|integer|different:to_wallet_id',
            'to_wallet_id' => 'required|integer|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ], [
            'from_wallet_id.required' => 'اختر المحفظة المحوّل منها',
            'to_wallet_id.required' => 'اختر المحفظة المحوّل إليها',
            'from_wallet_id.different' => 'لا يمكن التحويل لنفس المحفظة',
            'to_wallet_id.different' => 'لا يمكن التحويل لنفس المحفظة',
            'amount.required' => 'المبلغ مطلوب',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
        ]);

        $amount = round((float) $validated['amount'], 2);

        try {
            DB::transaction(function () use ($validated, $amount) {
                $fromWallet = $this->ownedWalletsQuery()
                    ->where('id', $validated['from_wallet_id'])
                    ->lockForUpdate()
                    ->first();

                $toWallet = $this->ownedWalletsQuery()
                    ->where('id', $validated['to_wallet_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$fromWallet || !$toWallet) {
                    throw new \Exception('المحفظة غير موجودة أو لا تملك صلاحية الوصول إليها');
                }

                if (!$fromWallet->is_active || !$toWallet->is_active) {
                    throw new \Exception('يجب أن تكون المحافظتان نشطتين لإتمام التحويل');
                }

                if ((float) $fromWallet->balance < $amount) {
                    throw new \Exception('الرصيد غير كافٍ في المحفظة المحوّل منها');
                }

                $baseNote = 'تحويل بين المحافظ';
                if (!empty($validated['notes'])) {
                    $baseNote .= ' - ' . $validated['notes'];
                }

                $fromWallet->withdraw($amount, $baseNote . ' (إلى: ' . ($toWallet->name ?? ('#' . $toWallet->id)) . ')');
                $toWallet->deposit($amount, null, null, $baseNote . ' (من: ' . ($fromWallet->name ?? ('#' . $fromWallet->id)) . ')');
            });

            return redirect()
                ->route('admin.wallets.index')
                ->with('success', 'تم التحويل بين المحافظ بنجاح');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'تعذر تنفيذ التحويل: ' . $e->getMessage())
                ->withInput();
        }
    }
}
