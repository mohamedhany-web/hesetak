<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponCommissionAccrual;
use App\Models\Expense;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CouponCommissionService
{
    /**
     * عند اعتماد طلب: تسجيل مستحق عمولة التسويق إن وُجدت.
     */
    public function accrueFromApprovedOrder(Order $order, ?int $invoiceId = null, ?int $paymentId = null): ?CouponCommissionAccrual
    {
        if (! $order->coupon_id) {
            return null;
        }

        $coupon = Coupon::find($order->coupon_id);
        if (! $coupon || ! $coupon->beneficiary_user_id || $coupon->commission_percent === null || (float) $coupon->commission_percent <= 0) {
            return null;
        }

        if (CouponCommissionAccrual::where('order_id', $order->id)->exists()) {
            return null;
        }

        $base = $coupon->commission_on === 'original_price'
            ? (float) ($order->original_amount ?? 0)
            : (float) ($order->amount ?? 0);

        if ($base <= 0) {
            return null;
        }

        $percent = (float) $coupon->commission_percent;
        $amount = round($base * ($percent / 100), 2);
        if ($amount <= 0) {
            return null;
        }

        try {
            return CouponCommissionAccrual::create([
                'coupon_id' => $coupon->id,
                'beneficiary_user_id' => $coupon->beneficiary_user_id,
                'order_id' => $order->id,
                'invoice_id' => $invoiceId,
                'payment_id' => $paymentId,
                'base_amount_egp' => $base,
                'commission_percent' => $percent,
                'commission_amount_egp' => $amount,
                'status' => CouponCommissionAccrual::STATUS_PENDING,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Coupon commission accrual failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * إنشاء مصروف تسويق (معلق) وربطه بالمستحق.
     */
    public function createPendingExpense(CouponCommissionAccrual $accrual): Expense
    {
        if ($accrual->status !== CouponCommissionAccrual::STATUS_PENDING) {
            throw new \InvalidArgumentException('لا يمكن إنشاء مصروف لهذه الحالة.');
        }

        if ($accrual->expense_id) {
            throw new \InvalidArgumentException('يوجد مصروف مرتبط مسبقاً.');
        }

        return DB::transaction(function () use ($accrual) {
            $accrual->load(['coupon', 'beneficiary', 'order.course']);
            $coupon = $accrual->coupon;
            $beneficiary = $accrual->beneficiary;
            $title = 'عمولة كوبون تسويق: ' . ($coupon->code ?? '') . ' — ' . ($beneficiary->name ?? 'مستفيد');
            $desc = 'مستحق عمولة بنسبة ' . $accrual->commission_percent . '% على قاعدة ' . number_format($accrual->base_amount_egp, 2) . ' $ — طلب #' . $accrual->order_id;

            $expenseNumber = 'EXP-' . str_pad(Expense::count() + 1, 8, '0', STR_PAD_LEFT);

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'title' => $title,
                'description' => $desc,
                'category' => 'marketing',
                'amount' => $accrual->commission_amount_egp,
                'currency' => platform_currency(),
                'expense_date' => now()->toDateString(),
                'payment_method' => 'other',
                'wallet_id' => null,
                'reference_number' => 'COUP-COMM-' . $accrual->id,
                'status' => 'pending',
                'notes' => 'مستحق عمولة كوبون #' . $accrual->coupon_id . ' — accrual #' . $accrual->id,
                'metadata' => [
                    'coupon_commission_accrual_id' => $accrual->id,
                    'coupon_id' => $accrual->coupon_id,
                    'order_id' => $accrual->order_id,
                ],
                'created_by' => auth()->id(),
            ]);

            $accrual->update([
                'expense_id' => $expense->id,
                'status' => CouponCommissionAccrual::STATUS_EXPENSE_PENDING,
            ]);

            return $expense;
        });
    }

    public function markSettledFromExpense(Expense $expense): void
    {
        $id = $expense->metadata['coupon_commission_accrual_id'] ?? null;
        if (! $id) {
            return;
        }

        $accrual = CouponCommissionAccrual::find($id);
        if (! $accrual
            || $accrual->status === CouponCommissionAccrual::STATUS_SETTLED
            || $accrual->status === CouponCommissionAccrual::STATUS_CANCELLED) {
            return;
        }

        $accrual->update([
            'status' => CouponCommissionAccrual::STATUS_SETTLED,
            'paid_at' => now(),
        ]);
    }
}
