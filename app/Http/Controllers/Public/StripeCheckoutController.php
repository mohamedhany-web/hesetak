<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Services\CourseCheckoutPricingService;
use App\Services\CourseSubscriptionService;
use App\Services\StripeService;
use App\Services\StripeSettings;
use App\Services\StudentEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class StripeCheckoutController extends Controller
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
            'currency' => 'nullable|in:SAR,EGP,USD,EUR,GBP,AED,sar,egp,usd,eur,gbp,aed',
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

        return $this->sendToStripe($request, $order, (string) ($course->title ?? 'كورس'));
    }

    public function startPackage(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        $gate = $this->guardReady();
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        $user = Auth::user();
        $yearId = $request->filled('academic_year_id') ? $request->integer('academic_year_id') : null;
        $subjectId = $request->filled('academic_subject_id') ? $request->integer('academic_subject_id') : null;
        $curriculumType = $request->input('curriculum_type');
        $quote = app(\App\Services\ServiceSessionRateService::class)->quotePackage(
            $servicePackage,
            $yearId,
            is_string($curriculumType) ? $curriculumType : null,
        );

        if ((float) $quote['total'] < 0.01) {
            return back()->with('error', 'هذه الباقة لا تتطلب دفعاً عبر البوابة.');
        }

        $existing = Order::query()
            ->where('user_id', $user->id)
            ->where('service_package_id', $servicePackage->id)
            ->where('order_type', Order::TYPE_SERVICE_PACKAGE)
            ->where('status', Order::STATUS_PENDING)
            ->latest('id')
            ->get()
            ->first(function (Order $order) {
                $meta = is_array($order->custom_package_data) ? $order->custom_package_data : [];

                return empty($meta['is_gift']);
            });

        if ($existing) {
            if ($existing->payment_method !== 'online' || $existing->payment_proof !== null) {
                return back()->with('error', 'لديك طلب قيد المراجعة لهذه الباقة.');
            }
            $meta = is_array($existing->custom_package_data) ? $existing->custom_package_data : [];
            $meta = array_merge($meta, [
                'quoted_unit_price' => $quote['unit'],
                'quoted_total' => $quote['total'],
                'curriculum_type' => $quote['curriculum_type'],
                'academic_year_id' => $quote['academic_year_id'] ?? $yearId,
                'academic_subject_id' => $subjectId ?: ($meta['academic_subject_id'] ?? $servicePackage->academic_subject_id),
                'quote_source' => $quote['source'],
                'is_gift' => false,
            ]);
            $original = $servicePackage->original_price !== null
                ? (float) $servicePackage->original_price
                : (float) $quote['total'];
            $existing->update([
                'original_amount' => $original,
                'discount_amount' => max(0, $original - (float) $quote['total']),
                'amount' => $quote['total'],
                'currency' => $this->normalizeChargeCurrency($quote['currency'] ?? $servicePackage->currency ?? null),
                'academic_year_id' => $quote['academic_year_id'] ?? $yearId,
                'custom_package_data' => $meta,
                'wallet_id' => null,
                'payment_method' => 'online',
            ]);
            $order = $existing->fresh();
        } else {
            $order = StudentEntitlementService::createOrder($user, $servicePackage, 'online', null, [
                'academic_year_id' => $yearId,
                'academic_subject_id' => $subjectId,
                'curriculum_type' => is_string($curriculumType) ? $curriculumType : null,
            ]);
        }

        return $this->sendToStripe($request, $order, (string) ($servicePackage->name ?? 'باقة'));
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

        return $this->sendToStripe($request, $order, (string) $title);
    }

    public function returnFromStripe(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول لإكمال تأكيد الدفع.');
        }

        $sessionId = trim((string) $request->query('session_id', ''));
        if ($sessionId === '') {
            return redirect()->route('orders.index')->with('error', 'لم يصل رقم جلسة Stripe.');
        }

        $order = Order::query()->where('stripe_checkout_session_id', $sessionId)->first();
        if (! $order) {
            return redirect()->route('orders.index')->with('error', 'لم يُعثر على الطلب المرتبط بـ Stripe.');
        }
        if ((int) $order->user_id !== (int) Auth::id()) {
            return redirect()->route('orders.index')->with('error', 'طلب غير صالح.');
        }
        if ($order->status === Order::STATUS_APPROVED) {
            return redirect()->to($this->successUrl($order))
                ->with('success', 'تم الدفع مسبقاً وتم تفعيل الطلب.');
        }

        try {
            $this->captureAndApprove($order, $sessionId);
        } catch (Throwable $e) {
            Log::warning('Stripe return confirm failed', [
                'order_id' => $order->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to($this->retryUrl($order))
                ->with('error', 'تعذّر تأكيد الدفع: '.$e->getMessage());
        }

        return redirect()->to($this->successUrl($order->fresh()))
            ->with('success', 'تم الدفع عبر Stripe بنجاح وتم تفعيل الطلب.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $sessionId = trim((string) $request->query('session_id', ''));
        $order = $sessionId !== ''
            ? Order::query()->where('stripe_checkout_session_id', $sessionId)->first()
            : null;
        if (! $order && $request->filled('order_id')) {
            $order = Order::query()
                ->whereKey((int) $request->query('order_id'))
                ->where('user_id', Auth::id())
                ->first();
        }
        $url = $order ? $this->retryUrl($order) : route('orders.index');

        return redirect()->to($url)->with('info', 'تم إلغاء الدفع من Stripe. يمكنك المحاولة مرة أخرى.');
    }

    public function captureAndApprove(Order $order, string $sessionId): void
    {
        $stripe = app(StripeService::class);
        $session = $stripe->retrieveSession($sessionId);
        if (! $stripe->isSessionPaid($session)) {
            throw new RuntimeException('الدفع لم يكتمل لدى Stripe بعد.');
        }

        DB::transaction(function () use ($order, $sessionId, $session, $stripe) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== Order::STATUS_PENDING) {
                return;
            }

            $ref = $stripe->orderIdFromSession($session);
            if ((string) $ref !== (string) $locked->id) {
                throw new RuntimeException('رقم الطلب لا يطابق جلسة Stripe.');
            }

            $stripe->assertMatchesOrder(
                $session,
                (float) $locked->amount,
                $locked->currencyCode() ?: StripeSettings::currency()
            );

            app(CheckoutController::class)->approveOrderAfterOnlinePaymentPublic(
                $locked,
                'stripe',
                $stripe->paymentIntentId($session) ?: $sessionId,
                $session,
                'Stripe'
            );
        });
    }

    private function sendToStripe(Request $request, Order $order, string $itemTitle): RedirectResponse
    {
        $email = trim((string) (Auth::user()?->email ?? ''));
        $currency = $this->chargeCurrencyForOrder($order);
        $amount = (float) $order->amount;

        try {
            $created = app(StripeService::class)->createCheckoutSession(
                $amount,
                $currency,
                route('public.checkout.stripe.return'),
                route('public.checkout.stripe.cancel', ['order_id' => $order->id]),
                (string) $order->id,
                $itemTitle,
                $email !== '' ? $email : null
            );
        } catch (Throwable $e) {
            Log::error('Stripe create session failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        $order->update(['stripe_checkout_session_id' => $created['id']]);
        $request->session()->put('stripe_order_id', $order->id);

        return redirect()->away($created['url']);
    }

    private function guardReady(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول أولاً لإتمام عملية الشراء');
        }
        if (! StripeSettings::isEnabled()) {
            return back()->with('error', 'الدفع عبر Stripe غير مفعّل حالياً.');
        }
        if (! StripeSettings::isConfigured()) {
            return back()->with('error', 'بيانات اتصال Stripe غير مكتملة في إعدادات النظام.');
        }

        return null;
    }

    private function normalizeChargeCurrency(?string $input): string
    {
        $currency = strtoupper(trim((string) $input));
        if (in_array($currency, StripeSettings::CURRENCIES, true)) {
            return $currency;
        }

        return StripeSettings::currency();
    }

    private function chargeCurrencyForOrder(Order $order): string
    {
        $fromOrder = strtoupper((string) ($order->currencyCode() ?: ''));
        if (in_array($fromOrder, StripeSettings::CURRENCIES, true)) {
            return $fromOrder;
        }

        return StripeSettings::currency();
    }

    private function successUrl(Order $order): string
    {
        if ($order->advanced_course_id && \Illuminate\Support\Facades\Route::has('public.course.show')) {
            return route('public.course.show', $order->advanced_course_id);
        }
        if ($order->service_package_id && \Illuminate\Support\Facades\Route::has('public.service-packages.show')) {
            return route('public.service-packages.checkout', $order->service_package_id);
        }

        return route('orders.index');
    }

    private function retryUrl(Order $order): string
    {
        if ($order->advanced_course_id) {
            return route('public.course.checkout', $order->advanced_course_id);
        }
        if ($order->service_package_id) {
            return route('public.service-packages.checkout', $order->service_package_id);
        }

        return route('orders.index');
    }
}
