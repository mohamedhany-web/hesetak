<?php

namespace App\Services;

use App\Models\CouponUsage;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class OrderWalletAndCouponFinalizer
{
    /**
     * بعد إتمام الطلب (موافقة أدمن أو دفع أونلاين): خصم رصيد محفظة الطالب وتسجيل استخدام الكوبون.
     *
     * @throws \Throwable
     */
    public static function run(Order $order): void
    {
        $credit = (float) ($order->wallet_credit_amount ?? 0);
        if ($credit > 0) {
            $wallet = Wallet::where('user_id', $order->user_id)->lockForUpdate()->first();
            if (! $wallet) {
                throw new \RuntimeException('لا توجد محفظة للطالب لخصم الرصيد.');
            }
            if ((float) $wallet->balance + 0.00001 < $credit) {
                throw new \RuntimeException('رصيد محفظة الطالب غير كافٍ لإتمام الطلب (مطلوب خصم '.format_money($credit).').');
            }
            $wallet->withdraw(
                $credit,
                'خصم لشراء كورس — طلب #'.$order->id
            );
        }

        if ($order->coupon_id && (float) ($order->discount_amount ?? 0) > 0) {
            $exists = CouponUsage::where('order_id', $order->id)->exists();
            if (! $exists) {
                $coupon = \App\Models\Coupon::whereKey($order->coupon_id)->lockForUpdate()->first();
                if ($coupon) {
                    CouponUsage::create([
                        'coupon_id' => $coupon->id,
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'discount_amount' => $order->discount_amount,
                        'order_amount' => $order->original_amount ?? $order->amount,
                        'final_amount' => $order->amount,
                    ]);
                    $coupon->incrementUsage();
                }
            }
        }

        self::notifyPaymentSuccess($order);
    }

    /**
     * إشعار الطالب بإتمام الدفع (داخل التطبيق + بريد) — مرة واحدة لكل طلب.
     */
    protected static function notifyPaymentSuccess(Order $order): void
    {
        try {
            if (! $order->user_id) {
                return;
            }

            $dedupe = 'order_paid:'.$order->id;
            $already = Notification::query()
                ->where('user_id', $order->user_id)
                ->where('type', 'system')
                ->where('data->dedupe', $dedupe)
                ->exists();

            if ($already) {
                return;
            }

            $amount = format_money((float) ($order->amount ?? 0));
            $title = 'تم تأكيد الدفع — طلب #'.$order->id;
            $message = "تم تأكيد دفع طلبك بنجاح بمبلغ {$amount}. يمكنك متابعة اشتراكاتك ومواعيدك من لوحة الطالب.";

            app(PlatformNotifier::class)->notify((int) $order->user_id, [
                'title' => $title,
                'message' => $message,
                'type' => 'system',
                'priority' => 'high',
                'action_url' => '/orders',
                'action_text' => 'عرض طلباتي',
                'sender_id' => null,
                'data' => [
                    'dedupe' => $dedupe,
                    'order_id' => $order->id,
                    'source' => 'order_paid',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Order payment notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
