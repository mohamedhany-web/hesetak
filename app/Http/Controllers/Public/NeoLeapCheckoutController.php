<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Services\CourseCheckoutPricingService;
use App\Services\CourseSubscriptionService;
use App\Services\NeoLeapService;
use App\Services\NeoLeapSettings;
use App\Services\StudentEntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NeoLeapCheckoutController extends Controller
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

        return $this->sendToNeoLeap($request, $order, (string) ($course->title ?? 'كورس'));
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

        return $this->sendToNeoLeap($request, $order, (string) ($servicePackage->name ?? 'باقة'));
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

        return $this->sendToNeoLeap($request, $order, (string) $title);
    }

    public function returnFromNeoLeap(Request $request): RedirectResponse
    {
        return $this->handleCallback($request, expectSuccess: true);
    }

    public function errorFromNeoLeap(Request $request): RedirectResponse
    {
        return $this->handleCallback($request, expectSuccess: false);
    }

    public function captureAndApprove(Order $order, array $parsed): void
    {
        $neoleap = app(NeoLeapService::class);

        DB::transaction(function () use ($order, $parsed, $neoleap) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== Order::STATUS_PENDING) {
                return;
            }

            $track = (string) ($parsed['track_id'] ?? '');
            $storedTrack = (string) ($locked->neoleap_track_id ?? '');
            if ($track !== '' && $storedTrack !== '' && ! hash_equals($storedTrack, $track)) {
                throw new RuntimeException('رقم التتبع لا يطابق طلب NeoLeap.');
            }

            $currency = $this->chargeCurrencyForOrder($locked);
            $neoleap->assertMatchesOrder(
                (float) $locked->amount,
                $currency,
                $parsed['amt'] ?? null,
                $parsed['currency'] ?? $currency
            );

            $txn = (string) ($parsed['ref'] ?? $parsed['payment_id'] ?? $track ?: ('neoleap-'.$locked->id));

            $updates = [];
            if (Schema::hasColumn('orders', 'neoleap_payment_id') && ! empty($parsed['payment_id'])) {
                $updates['neoleap_payment_id'] = (string) $parsed['payment_id'];
            }
            if (Schema::hasColumn('orders', 'neoleap_tran_id') && ! empty($parsed['ref'])) {
                $updates['neoleap_tran_id'] = (string) $parsed['ref'];
            }
            if ($updates !== []) {
                $locked->update($updates);
            }

            app(CheckoutController::class)->approveOrderAfterOnlinePaymentPublic(
                $locked->fresh(),
                'neoleap',
                $txn,
                $parsed['raw'] ?? $parsed,
                'NeoLeap'
            );
        });
    }

    private function handleCallback(Request $request, bool $expectSuccess): RedirectResponse
    {
        $trandata = (string) ($request->input('trandata') ?: $request->query('trandata', ''));
        if ($trandata === '') {
            return redirect()->route('orders.index')
                ->with('error', 'لم تصل بيانات تأكيد الدفع من NeoLeap.');
        }

        try {
            $parsed = app(NeoLeapService::class)->parseCallbackTrandata($trandata);
        } catch (Throwable $e) {
            Log::warning('NeoLeap callback decrypt failed', ['error' => $e->getMessage()]);

            return redirect()->route('orders.index')
                ->with('error', 'تعذّر قراءة رد NeoLeap: '.$e->getMessage());
        }

        $order = $this->resolveOrderFromCallback($parsed, $request);
        if (! $order) {
            return redirect()->route('orders.index')
                ->with('error', 'لم يُعثر على الطلب المرتبط بدفعة NeoLeap.');
        }

        if ($order->status === Order::STATUS_APPROVED) {
            return redirect()->to($this->successUrl($order))
                ->with('success', 'تم الدفع مسبقاً وتم تفعيل الطلب.');
        }

        if (! ($parsed['success'] ?? false)) {
            return redirect()->to($this->retryUrl($order))
                ->with('error', 'فشل الدفع عبر NeoLeap: '.($parsed['result'] ?: 'رفضت البوابة العملية'));
        }

        if (! $expectSuccess && ! ($parsed['success'] ?? false)) {
            return redirect()->to($this->retryUrl($order))
                ->with('error', 'أُلغيت أو فشلت عملية الدفع.');
        }

        try {
            $this->captureAndApprove($order, $parsed);
        } catch (Throwable $e) {
            Log::warning('NeoLeap capture failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to($this->retryUrl($order))
                ->with('error', 'تعذّر تأكيد الدفع: '.$e->getMessage());
        }

        return redirect()->to($this->successUrl($order->fresh()))
            ->with('success', 'تم الدفع عبر NeoLeap بنجاح وتم تفعيل الطلب.');
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function resolveOrderFromCallback(array $parsed, Request $request): ?Order
    {
        $track = trim((string) ($parsed['track_id'] ?? ''));
        if ($track !== '' && Schema::hasColumn('orders', 'neoleap_track_id')) {
            $byTrack = Order::query()->where('neoleap_track_id', $track)->first();
            if ($byTrack) {
                return $byTrack;
            }
        }

        // trackId format: HST-{orderId}-{random}
        if (preg_match('/^HST-(\d+)-/', $track, $m)) {
            $byId = Order::query()->find((int) $m[1]);
            if ($byId) {
                return $byId;
            }
        }

        $paymentId = trim((string) ($parsed['payment_id'] ?? $request->input('paymentid', $request->query('paymentId', ''))));
        if ($paymentId !== '' && Schema::hasColumn('orders', 'neoleap_payment_id')) {
            return Order::query()->where('neoleap_payment_id', $paymentId)->first();
        }

        $sessionOrderId = (int) $request->session()->get('neoleap_order_id', 0);
        if ($sessionOrderId > 0) {
            return Order::query()->find($sessionOrderId);
        }

        return null;
    }

    private function sendToNeoLeap(Request $request, Order $order, string $itemTitle): RedirectResponse
    {
        $currency = $this->chargeCurrencyForOrder($order);
        $amount = (float) $order->amount;
        $trackId = 'HST-'.$order->id.'-'.Str::upper(Str::random(8));

        $responseUrl = route('public.checkout.neoleap.return');
        $errorUrl = route('public.checkout.neoleap.error');

        try {
            $created = app(NeoLeapService::class)->initiateHostedPayment(
                amount: $amount,
                trackId: $trackId,
                responseUrl: $responseUrl,
                errorUrl: $errorUrl,
                currency: $currency,
                lang: app()->getLocale() === 'en' ? 'EN' : 'AR',
                udf: [
                    'udf1' => (string) $order->id,
                    'udf2' => Str::limit($itemTitle, 50, ''),
                    'udf3' => (string) ($order->user_id ?? ''),
                ],
            );
        } catch (Throwable $e) {
            Log::error('NeoLeap initiate failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $e->getMessage());
        }

        $updates = [];
        if (Schema::hasColumn('orders', 'neoleap_track_id')) {
            $updates['neoleap_track_id'] = $trackId;
        }
        if (Schema::hasColumn('orders', 'neoleap_payment_id')) {
            $updates['neoleap_payment_id'] = $created['payment_id'];
        }
        if ($updates !== []) {
            $order->update($updates);
        }

        $request->session()->put('neoleap_order_id', $order->id);

        return redirect()->away($created['redirect_url']);
    }

    private function guardReady(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول أولاً لإتمام عملية الشراء');
        }
        if (! NeoLeapSettings::isEnabled()) {
            return back()->with('error', 'الدفع عبر NeoLeap غير مفعّل حالياً.');
        }
        if (! NeoLeapSettings::isConfigured()) {
            return back()->with('error', 'بيانات اتصال NeoLeap غير مكتملة في إعدادات بوابات الدفع.');
        }

        return null;
    }

    private function normalizeChargeCurrency(?string $input): string
    {
        $currency = strtoupper(trim((string) $input));
        if (in_array($currency, NeoLeapSettings::CURRENCIES, true)) {
            return $currency;
        }

        return NeoLeapSettings::currency();
    }

    private function chargeCurrencyForOrder(Order $order): string
    {
        $fromOrder = strtoupper((string) ($order->currencyCode() ?: ''));
        if (in_array($fromOrder, NeoLeapSettings::CURRENCIES, true)) {
            return $fromOrder;
        }

        return NeoLeapSettings::currency();
    }

    private function successUrl(Order $order): string
    {
        if ($order->advanced_course_id && \Illuminate\Support\Facades\Route::has('public.course.show')) {
            return route('public.course.show', $order->advanced_course_id);
        }
        if ($order->service_package_id && \Illuminate\Support\Facades\Route::has('public.service-packages.checkout')) {
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
