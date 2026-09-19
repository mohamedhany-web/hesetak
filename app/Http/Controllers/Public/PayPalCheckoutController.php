<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Services\CourseCheckoutPricingService;
use App\Services\CourseSubscriptionService;
use App\Services\PayPalService;
use App\Services\PayPalSettings;
use App\Services\StudentEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PayPalCheckoutController extends Controller
{
    public function startCourse(Request $request, int $courseId): RedirectResponse
    {
        $gate = $this->guardReady();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        $course = AdvancedCourse::query()->where('id', $courseId)->where('is_active', true)->firstOrFail();

        if (Auth::user()->isEnrolledIn($course->id)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        $request->validate([
            'coupon_code' => 'nullable|string|max:64',
            'wallet_credit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SAR,EGP,USD,EUR,GBP,sar,egp,usd,eur,gbp',
        ]);

        $pricingCurrency = platform_currency();

        $pricing = CourseCheckoutPricingService::resolve(
            Auth::user(),
            $course,
            $request->input('coupon_code'),
            (float) $request->input('wallet_credit', 0),
            null,
            $pricingCurrency
        );

        if (! $pricing['ok']) {
            return back()->with('error', $pricing['message']);
        }

        if ($pricing['final_amount'] < 0.01) {
            return back()->with('error', 'المبلغ المتبقي صفر — استخدم طريقة الدفع الأخرى أو راجع الكوبون/المحفظة.');
        }

        $payload = [
            'coupon_id' => $pricing['coupon_id'],
            'original_amount' => $pricing['original_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'wallet_credit_amount' => $pricing['wallet_credit_amount'],
            'amount' => $pricing['final_amount'],
            'currency' => $pricingCurrency,
            'billing_mode' => $course->billing_mode ?? CourseSubscriptionService::BILLING_ONE_TIME,
            'payment_method' => 'online',
            'payment_proof' => null,
            'wallet_id' => null,
            'status' => Order::STATUS_PENDING,
            'auto_renew' => $course->isMonthlyBilling() && $request->boolean('auto_renew'),
        ];

        $existing = Order::query()
            ->where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->where('status', Order::STATUS_PENDING)
            ->first();

        if ($existing) {
            if ($existing->payment_method !== 'online' || $existing->payment_proof !== null) {
                return redirect()->route('public.course.show', $course->id)
                    ->with('info', 'لديك طلب قيد المراجعة لهذا الكورس.');
            }
            $existing->update($payload);
            $order = $existing->fresh();
        } else {
            $order = Order::create(array_merge($payload, [
                'user_id' => Auth::id(),
                'advanced_course_id' => $course->id,
            ]));
        }

        return $this->sendToPaypal($request, $order, (string) ($course->title ?? 'كورس'));
    }

    public function startPackage(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        $gate = $this->guardReady();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        $amount = (float) $servicePackage->price;
        if ($amount < 0.01) {
            return back()->with('error', 'هذه الباقة لا تتطلب دفعاً عبر البوابة.');
        }

        $user = Auth::user();
        $existing = Order::query()
            ->where('user_id', $user->id)
            ->where('service_package_id', $servicePackage->id)
            ->where('order_type', Order::TYPE_SERVICE_PACKAGE)
            ->where('status', Order::STATUS_PENDING)
            ->first();

        if ($existing) {
            if ($existing->payment_method !== 'online' || $existing->payment_proof !== null) {
                return back()->with('error', 'لديك طلب قيد المراجعة لهذه الباقة.');
            }
            $existing->update([
                'original_amount' => $servicePackage->original_price ?? $servicePackage->price,
                'discount_amount' => max(0, (float) ($servicePackage->original_price ?? $servicePackage->price) - (float) $servicePackage->price),
                'amount' => $servicePackage->price,
                'currency' => $this->normalizeChargeCurrency($servicePackage->currency ?? null),
                'wallet_id' => null,
                'payment_method' => 'online',
            ]);
            $order = $existing->fresh();
        } else {
            $order = StudentEntitlementService::createOrder($user, $servicePackage, 'online', null);
        }

        return $this->sendToPaypal($request, $order, (string) ($servicePackage->name ?? 'باقة'));
    }

    public function startExistingOrder(Request $request, Order $order): RedirectResponse
    {
        $gate = $this->guardReady();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        abort_unless((int) $order->user_id === (int) Auth::id(), 403);
        abort_unless($order->status === Order::STATUS_PENDING && $order->payment_method === 'online', 404);

        $title = $order->course->title
            ?? $order->servicePackage->name
            ?? data_get($order->custom_package_data, 'name')
            ?? 'طلب #'.$order->id;

        return $this->sendToPaypal($request, $order, (string) $title);
    }

    public function returnFromPaypal(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول لإكمال تأكيد الدفع.');
        }

        $token = trim((string) $request->query('token', ''));
        if ($token === '') {
            return redirect()->route('orders.index')->with('error', 'لم يصل رقم طلب PayPal.');
        }

        $order = Order::query()->where('paypal_order_id', $token)->first();
        if (! $order) {
            return redirect()->route('orders.index')->with('error', 'لم يُعثر على الطلب المرتبط بـ PayPal.');
        }

        if ((int) $order->user_id !== (int) Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'طلب غير صالح.');
        }

        if ($order->status === Order::STATUS_APPROVED) {
            return redirect()->to($this->successUrl($order))
                ->with('success', 'تم الدفع مسبقاً وتم تفعيل الطلب.');
        }

        try {
            $this->captureAndApprove($order, $token);
        } catch (Throwable $e) {
            Log::warning('PayPal return capture failed', [
                'order_id' => $order->id,
                'paypal_order_id' => $token,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to($this->retryUrl($order))
                ->with('error', 'تعذّر تأكيد الدفع: '.$e->getMessage());
        }

        return redirect()->to($this->successUrl($order->fresh()))
            ->with('success', 'تم الدفع عبر PayPal بنجاح وتم تفعيل الطلب.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $token = trim((string) $request->query('token', ''));
        $order = $token !== ''
            ? Order::query()->where('paypal_order_id', $token)->first()
            : null;

        $url = $order ? $this->retryUrl($order) : route('orders.index');

        return redirect()->to($url)->with('info', 'تم إلغاء الدفع من PayPal. يمكنك المحاولة مرة أخرى.');
    }

    public function captureAndApprove(Order $order, string $paypalOrderId): void
    {
        $paypal = app(PayPalService::class);
        $captured = $paypal->captureOrder($paypalOrderId);
        if (! $paypal->isOrderPaid($captured)) {
            throw new RuntimeException('الدفع لم يكتمل لدى PayPal بعد.');
        }

        DB::transaction(function () use ($order, $paypalOrderId, $captured, $paypal) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== Order::STATUS_PENDING) {
                return;
            }

            $customId = $paypal->customId($captured);
            if ((string) $customId !== (string) $locked->id) {
                throw new RuntimeException('رقم الطلب لا يطابق عملية PayPal.');
            }

            $paypal->assertMatchesOrder(
                $captured,
                (float) $locked->amount,
                $locked->currencyCode() ?: PayPalSettings::currency()
            );

            app(CheckoutController::class)->approveOrderAfterOnlinePaymentPublic(
                $locked,
                'paypal',
                $paypal->captureId($captured) ?: $paypalOrderId,
                $captured,
                'PayPal'
            );
        });
    }

    private function sendToPaypal(Request $request, Order $order, string $itemTitle): RedirectResponse
    {
        $email = trim((string) (Auth::user()?->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل الدفع.');
        }

        $currency = $this->chargeCurrencyForOrder($order);
        $amount = (float) $order->amount;

        try {
            $created = app(PayPalService::class)->createOrder(
                $amount,
                $currency,
                route('public.checkout.paypal.return'),
                route('public.checkout.paypal.cancel'),
                (string) $order->id,
                $itemTitle
            );
        } catch (Throwable $e) {
            Log::error('PayPal create order failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        $order->update(['paypal_order_id' => $created['id']]);
        $request->session()->put('paypal_order_id', $order->id);

        return redirect()->away($created['approve_url']);
    }

    private function guardReady(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول أولاً لإتمام عملية الشراء');
        }
        if (! PayPalSettings::isEnabled()) {
            return back()->with('error', 'الدفع عبر PayPal غير مفعّل حالياً.');
        }
        if (! PayPalSettings::isConfigured()) {
            return back()->with('error', 'بيانات اتصال PayPal غير مكتملة في إعدادات النظام.');
        }

        return null;
    }

    private function normalizeChargeCurrency(?string $input): string
    {
        $currency = strtoupper(trim((string) $input));
        if (in_array($currency, PayPalSettings::CURRENCIES, true)) {
            return $currency;
        }

        return PayPalSettings::currency();
    }

    private function chargeCurrencyForOrder(Order $order): string
    {
        $fromOrder = strtoupper((string) ($order->currencyCode() ?: ''));
        if (in_array($fromOrder, PayPalSettings::CURRENCIES, true)) {
            return $fromOrder;
        }

        return PayPalSettings::currency();
    }

    private function successUrl(Order $order): string
    {
        if ($order->advanced_course_id) {
            return route('public.course.show', $order->advanced_course_id);
        }
        if (in_array($order->order_type, [Order::TYPE_SERVICE_PACKAGE, Order::TYPE_CUSTOM_SERVICE_PACKAGE], true)) {
            return route('student.service-entitlements.index');
        }

        return route('orders.index');
    }

    private function retryUrl(Order $order): string
    {
        if ($order->advanced_course_id) {
            return route('public.course.checkout', $order->advanced_course_id);
        }
        if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && $order->service_package_id) {
            return route('public.service-packages.checkout', $order->service_package_id);
        }

        return route('orders.index');
    }
}
