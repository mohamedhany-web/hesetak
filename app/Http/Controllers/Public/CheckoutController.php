<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AdvancedCourse;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StudentCourseEnrollment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\AdminPanelBranding;
use App\Services\CourseCheckoutPricingService;
use App\Services\FawaterakApiService;
use App\Services\FawaterakOrderResolver;
use App\Services\FawaterakService;
use App\Services\InstructorCoursePercentageService;
use App\Services\KashierService;
use App\Services\KashierSettings;
use App\Services\OrderWalletAndCouponFinalizer;
use App\Services\PaymentGatewaySettings;
use App\Services\PublicMediaStorage;
use App\Services\CourseSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    /**
     * عرض صفحة إتمام الطلب
     */
    public function show($courseId)
    {
        // التحقق من تسجيل الدخول - سيتم حفظ URL الحالي تلقائياً
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول أولاً لإتمام عملية الشراء');
        }

        $course = AdvancedCourse::where('id', $courseId)
            ->where('is_active', true)
            ->with(['academicSubject', 'academicYear'])
            ->firstOrFail();

        // التحقق من التسجيل السابق (يشمل انتهاء الاشتراك الشهري)
        $existingEnrollment = StudentCourseEnrollment::where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->first();

        if ($existingEnrollment && CourseSubscriptionService::enrollmentGrantsAccess($existingEnrollment)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        // التحقق من وجود طلب قيد الانتظار (يُستثنى طلب دفع أونلاين قيد إكمال فواتيرك)
        $existingOrder = Order::where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->where('status', Order::STATUS_PENDING)
            ->first();

        if ($existingOrder) {
            $fawaterakRetry = $existingOrder->payment_method === 'online'
                && $existingOrder->payment_proof === null;
            if (! $fawaterakRetry) {
                return redirect()->route('public.course.show', $course->id)
                    ->with('info', 'لديك طلب قيد الانتظار لهذا الكورس');
            }
        }

        // جلب المحافظ الإلكترونية النشطة
        $wallets = \App\Models\Wallet::where('is_active', true)
            ->whereNotNull('type')
            ->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer'])
            ->where(function ($query) {
                $query->whereNotNull('account_number')
                    ->orWhereNotNull('name');
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $fawaterakGatewayOn = PaymentGatewaySettings::isFawaterakEnabled();
        $fawaterakApi = app(FawaterakApiService::class);
        $fawaterakIframe = app(FawaterakService::class);
        $fawaterakIntegration = $fawaterakApi->integrationMode();
        $fawaterakReady = $fawaterakIntegration === 'api'
            ? $fawaterakApi->isConfigured()
            : $fawaterakIframe->isConfigured();
        $fawaterakUseGateway = $fawaterakGatewayOn && $fawaterakReady;
        $fawaterakMisconfigured = $fawaterakGatewayOn && ! $fawaterakReady;
        $paypalUseGateway = \App\Services\PayPalSettings::isReady();
        $paypalMisconfigured = \App\Services\PayPalSettings::isMisconfigured();
        $kashierUseGateway = KashierSettings::isReady();
        $kashierMisconfigured = KashierSettings::isMisconfigured();
        $platformLogoUrl = AdminPanelBranding::logoPublicUrl();

        $studentWallet = Wallet::where('user_id', Auth::id())->first();
        $studentWalletBalance = $studentWallet ? (float) $studentWallet->balance : 0.0;

        return view('public.checkout', compact(
            'course',
            'wallets',
            'fawaterakUseGateway',
            'fawaterakMisconfigured',
            'fawaterakIntegration',
            'paypalUseGateway',
            'paypalMisconfigured',
            'kashierUseGateway',
            'kashierMisconfigured',
            'platformLogoUrl',
            'studentWalletBalance'
        ));
    }

    /**
     * معاينة السعر: كوبون + رصيد محفظة الطالب (JSON).
     */
    public function quoteCourseCheckout(Request $request, int $courseId): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'nullable|string|max:64',
            'wallet_credit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SAR,USD,EGP,sar,usd,egp',
        ]);

        $course = AdvancedCourse::where('id', $courseId)
            ->where('is_active', true)
            ->firstOrFail();

        $currency = platform_currency();

        $pricing = CourseCheckoutPricingService::resolve(
            Auth::user(),
            $course,
            $request->input('coupon_code'),
            (float) $request->input('wallet_credit', 0),
            null,
            $currency
        );

        if (! $pricing['ok']) {
            return response()->json(['ok' => false, 'message' => $pricing['message']], 422);
        }

        return response()->json([
            'ok' => true,
            'original_amount' => $pricing['original_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'wallet_credit_amount' => $pricing['wallet_credit_amount'],
            'final_amount' => $pricing['final_amount'],
            'coupon_id' => $pricing['coupon_id'],
            'student_wallet_balance' => $pricing['student_wallet_balance'],
        ]);
    }

    /**
     * التوجيه لبوابة الدفع كاشير (كورس)
     */
    public function redirectToKashier(Request $request, $courseId)
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'))->with('info', 'يرجى تسجيل الدخول أولاً لإتمام عملية الشراء');
        }

        if (! KashierSettings::isReady()) {
            return back()->with('error', 'بوابة كاشير غير مفعّلة أو بيانات الاتصال ناقصة.');
        }

        $course = AdvancedCourse::where('id', $courseId)->where('is_active', true)->firstOrFail();

        if (Auth::user()->isEnrolledIn($course->id)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        $request->validate([
            'coupon_code' => 'nullable|string|max:64',
            'wallet_credit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SAR,USD,EGP,sar,usd,egp',
        ]);

        $pricing = CourseCheckoutPricingService::resolve(
            Auth::user(),
            $course,
            $request->input('coupon_code'),
            (float) $request->input('wallet_credit', 0),
            null,
            platform_currency()
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
            'currency' => platform_currency(),
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

        try {
            $sessionUrl = app(KashierService::class)->getHppUrl(
                (string) $order->id,
                (float) $order->amount,
                $this->getKashierCallbackUrl(),
                KashierSettings::currency(),
                Auth::user()->email,
                (string) Auth::id(),
                (string) ($course->title ?? 'كورس')
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage() ?: 'تعذّر فتح صفحة كاشير. راجع بيانات الاتصال.');
        }

        return redirect()->away($sessionUrl);
    }

    /**
     * رابط العودة من كاشير بعد الدفع (يجب أن يكون URL صالحًا — كاشير قد يرفض localhost أو غير https).
     */
    private function getKashierCallbackUrl(): string
    {
        $configured = KashierSettings::merchantRedirectUrl();
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return url()->route('public.checkout.kashier.callback');
    }

    /**
     * استقبال callback من بوابة كاشير بعد إتمام الدفع
     */
    public function kashierCallback(Request $request)
    {
        if (! KashierSettings::isConfigured()) {
            return redirect()->route('orders.index')
                ->with('info', 'بوابة كاشير غير مربوطة حالياً. يمكنك متابعة حالة طلباتك من هذه الصفحة.');
        }

        $kashier = app(KashierService::class);
        $query = $request->query();

        if (! $kashier->validateCallback($query)) {
            Log::warning('Kashier callback: invalid signature', ['query_keys' => array_keys($query)]);

            return redirect()->route('public.courses')->with('error', 'فشل التحقق من الدفع. يرجى التواصل مع الدعم.');
        }

        $merchantOrderId = $query['merchantOrderId'] ?? null;
        if (! $merchantOrderId || ! ctype_digit((string) $merchantOrderId)) {
            Log::warning('Kashier callback: invalid merchantOrderId', ['merchantOrderId' => $merchantOrderId]);

            return redirect()->route('public.courses')->with('error', 'بيانات الطلب غير صحيحة.');
        }

        $order = Order::with(['course'])->find($merchantOrderId);
        if (! $order || $order->status !== Order::STATUS_PENDING) {
            Log::warning('Kashier callback: order not found or not pending', ['order_id' => $merchantOrderId]);

            return redirect()->route('public.courses')->with('error', 'الطلب غير موجود أو تم معالجته مسبقاً.');
        }

        if (! $kashier->isPaymentSuccess($query)) {
            if ($order->advanced_course_id) {
                return redirect()->route('public.course.show', $order->advanced_course_id)
                    ->with('error', 'لم يتم إتمام الدفع. يمكنك المحاولة مرة أخرى.');
            }

            return redirect()->route('orders.index')
                ->with('error', 'لم يتم إتمام الدفع. يمكنك المحاولة مرة أخرى.');
        }

        $orderCurrency = $order->currencyCode() ?: KashierSettings::currency();
        if (! $kashier->paidMatchesOrder($query, (float) $order->amount, $orderCurrency)) {
            Log::warning('Kashier callback: amount/currency mismatch', [
                'order_id' => $order->id,
                'expected' => $order->amount,
                'expected_currency' => $orderCurrency,
            ]);

            return redirect()->route('public.courses')->with('error', 'المبلغ المدفوع لا يطابق الطلب. يرجى التواصل مع الدعم.');
        }

        try {
            DB::transaction(function () use ($order, $query, $kashier, $orderCurrency) {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== Order::STATUS_PENDING) {
                    return;
                }

                $lockedCurrency = $locked->currencyCode() ?: $orderCurrency;
                if (! $kashier->paidMatchesOrder($query, (float) $locked->amount, $lockedCurrency)) {
                    throw new \RuntimeException('المبلغ المدفوع لا يطابق الطلب.');
                }

                $this->approveOrderAfterOnlinePayment(
                    $locked,
                    'kashier',
                    isset($query['transactionId']) ? (string) $query['transactionId'] : null,
                    $query,
                    'كاشير'
                );
            });
        } catch (\Throwable $e) {
            Log::error('Kashier callback: approval failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('public.courses')->with('error', 'حدث خطأ أثناء تفعيل الطلب. يرجى التواصل مع الدعم.');
        }

        $fresh = $order->fresh();
        if ($fresh && $fresh->advanced_course_id) {
            return redirect()->route('public.course.show', $fresh->advanced_course_id)
                ->with('success', 'تم الدفع بنجاح! تم تفعيل الكورس على حسابك.');
        }

        return redirect()->route('orders.index')
            ->with('success', 'تم الدفع بنجاح! تمت معالجة الطلب.');
    }

    /**
     * تجهيز جلسة دفع فواتيرك (IFrame): إنشاء طلب معلّق وإرجاع إعدادات الإضافة.
     */
    public function fawaterakPrepare(Request $request, int $courseId): JsonResponse
    {
        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return response()->json(['message' => 'بوابة الدفع الإلكترونية غير مفعّلة.'], 403);
        }

        $api = app(FawaterakApiService::class);
        $iframe = app(FawaterakService::class);
        $integration = $api->integrationMode();

        if ($integration === 'api') {
            if (! $api->isConfigured()) {
                return response()->json(['message' => 'رمز Bearer لفواتيرك (FAWATERAK_API_TOKEN) غير مضبوط في ملف البيئة.'], 503);
            }
        } elseif (! $iframe->isConfigured()) {
            return response()->json(['message' => 'مفاتيح فواتيرك (Vendor/Provider) غير مضبوطة في ملف البيئة.'], 503);
        }

        if (! Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
        }

        $course = AdvancedCourse::where('id', $courseId)
            ->where('is_active', true)
            ->first();
        if (! $course) {
            return response()->json(['message' => 'الكورس غير متاح أو غير مفعّل.'], 404);
        }

        if (Auth::user()->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'أنت مسجل بالفعل في هذا الكورس.'], 422);
        }

        $request->validate([
            'coupon_code' => 'nullable|string|max:64',
            'wallet_credit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SAR,USD,EGP,sar,usd,egp',
        ]);

        $currency = platform_currency();

        $pricing = CourseCheckoutPricingService::resolve(
            Auth::user(),
            $course,
            $request->input('coupon_code'),
            (float) $request->input('wallet_credit', 0),
            null,
            $currency
        );

        if (! $pricing['ok']) {
            return response()->json(['message' => $pricing['message']], 422);
        }

        if ($pricing['original_amount'] <= 0) {
            return response()->json(['message' => 'هذا الكورس لا يتطلب دفعاً عبر البوابة.'], 422);
        }

        $amount = (float) $pricing['final_amount'];

        $orderPayloadBase = [
            'coupon_id' => $pricing['coupon_id'],
            'original_amount' => $pricing['original_amount'],
            'discount_amount' => $pricing['discount_amount'],
            'wallet_credit_amount' => $pricing['wallet_credit_amount'],
            'amount' => $pricing['final_amount'],
            'currency' => $currency,
            'billing_mode' => $course->billing_mode ?? CourseSubscriptionService::BILLING_ONE_TIME,
            'payment_method' => 'online',
            'payment_proof' => null,
            'wallet_id' => null,
            'notes' => '',
            'status' => Order::STATUS_PENDING,
            'auto_renew' => $course->isMonthlyBilling() && $request->boolean('auto_renew'),
        ];

        if ($amount < 0.01) {
            $userZero = Auth::user();
            $emailZero = trim((string) ($userZero->email ?? ''));
            if ($emailZero === '' || ! filter_var($emailZero, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل إتمام الشراء.'], 422);
            }

            DB::beginTransaction();
            try {
                $existingOrder = Order::where('user_id', Auth::id())
                    ->where('advanced_course_id', $course->id)
                    ->where('status', Order::STATUS_PENDING)
                    ->first();

                if ($existingOrder) {
                    if ($existingOrder->payment_method !== 'online' || $existingOrder->payment_proof !== null) {
                        DB::rollBack();

                        return response()->json(['message' => 'لديك طلب قيد المراجعة لهذا الكورس.'], 409);
                    }
                    $existingOrder->update(array_merge($orderPayloadBase, [
                        'notes' => 'دفع بالكامل عبر رصيد المحفظة/الكوبون',
                    ]));
                    $order = $existingOrder->fresh();
                } else {
                    $order = Order::create(array_merge($orderPayloadBase, [
                        'user_id' => Auth::id(),
                        'advanced_course_id' => $course->id,
                        'notes' => 'دفع بالكامل عبر رصيد المحفظة/الكوبون',
                    ]));
                }

                $invoice = $this->approveOrderAfterOnlinePayment(
                    $order,
                    'other',
                    'WALLET_COUPON_FULL',
                    ['checkout' => 'zero_remainder'],
                    'رصيد المحفظة والكوبون'
                );
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Fawaterak prepare zero-remainder checkout failed', [
                    'course_id' => $course->id,
                    'user_id' => Auth::id(),
                    'message' => $e->getMessage(),
                ]);

                return response()->json(['message' => 'تعذّر إتمام الطلب بالرصيد. راجع السجلات أو تواصل مع الدعم.'], 500);
            }

            $request->session()->forget('fawaterak_order_id');

            return response()->json([
                'mode' => 'completed',
                'redirect' => route('public.course.show', $course->id),
                'message' => __('public.checkout_payment_success_with_invoice', [
                    'invoice' => $invoice->invoice_number,
                ]),
            ]);
        }

        $existingOrder = Order::where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->where('status', Order::STATUS_PENDING)
            ->first();

        if ($existingOrder) {
            if ($existingOrder->payment_method !== 'online' || $existingOrder->payment_proof !== null) {
                return response()->json(['message' => 'لديك طلب قيد المراجعة لهذا الكورس.'], 409);
            }
            $existingOrder->update($orderPayloadBase);
            $order = $existingOrder->fresh();
        } else {
            $order = Order::create(array_merge($orderPayloadBase, [
                'user_id' => Auth::id(),
                'advanced_course_id' => $course->id,
            ]));
        }

        $request->session()->put('fawaterak_order_id', $order->id);

        $user = Auth::user();
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل الدفع.'], 422);
        }

        if ($integration === 'api') {
            return response()->json([
                'mode' => 'api',
                'methodsUrl' => route('public.course.checkout.fawaterak.methods', $courseId),
                'payUrl' => route('public.course.checkout.fawaterak.pay', $courseId),
            ]);
        }

        $fullName = trim((string) ($user->name ?? ''));
        $nameParts = preg_split('/\s+/u', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? $firstName;

        $phone = preg_replace('/\D/', '', (string) ($user->phone ?? ''));
        if ($phone === '') {
            $phone = '0000000000';
        }

        $currency = $order->currencyCode() ?: (string) config('currency.code', 'SAR');
        $cartTotal = number_format($amount, 2, '.', '');
        $itemPrice = $cartTotal;

        $bearer = trim((string) config('fawaterak.plugin_bearer_token', ''));
        if ($bearer === '') {
            $bearer = trim((string) config('fawaterak.vendor_key', ''));
        }

        // token: Bearer لطلبات الإضافة؛ الـ HMAC يعتمد على domainForHash() مطابقاً لترويسة FAWATERAK-DOMAIN
        $pluginConfig = [
            'envType' => $iframe->envType(),
            'hashKey' => $iframe->generateHashKey(),
            'token' => $bearer,
            'style' => [
                'listing' => 'horizontal',
            ],
            'requestBody' => [
                'cartTotal' => $cartTotal,
                'currency' => $currency,
                'redirectOutIframe' => true,
                'customer' => [
                    'customer_unique_id' => (string) $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => '',
                ],
                'redirectionUrls' => [
                    'successUrl' => route('public.checkout.fawaterak.return', ['status' => 'success']),
                    'failUrl' => route('public.checkout.fawaterak.return', ['status' => 'fail']),
                    'pendingUrl' => route('public.checkout.fawaterak.return', ['status' => 'pending']),
                ],
                'cartItems' => [
                    [
                        'name' => Str::limit($course->title ?? 'كورس', 120),
                        'price' => $itemPrice,
                        'quantity' => '1',
                    ],
                ],
                'payLoad' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) $user->id,
                    'course_id' => (string) $course->id,
                ],
            ],
        ];

        $version = $iframe->versionString();
        if ($version !== '' && $version !== '0') {
            $pluginConfig['version'] = $version;
        }

        return response()->json([
            'mode' => 'iframe',
            'pluginScriptUrl' => route('public.fawaterk.plugin', [], true),
            'pluginConfig' => $pluginConfig,
        ]);
    }

    /**
     * قائمة وسائل الدفع من واجهة Gateway (GET) — بعد prepare وجلسة الطلب.
     */
    public function fawaterakPaymentMethods(Request $request, int $courseId): JsonResponse
    {
        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return response()->json(['message' => 'بوابة الدفع الإلكترونية غير مفعّلة.'], 403);
        }

        $api = app(FawaterakApiService::class);
        if ($api->integrationMode() !== 'api' || ! $api->isConfigured()) {
            return response()->json(['message' => 'وضع API غير مفعّل أو الرمز غير مضبوط.'], 503);
        }

        $order = $this->fawaterakResolvePendingOrder($request, $courseId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $result = $api->getPaymentMethods();
        if (! $result['ok'] || ! is_array($result['json'])) {
            Log::warning('Fawaterak getPaymentmethods failed', [
                'status' => $result['status'],
                'body' => Str::limit($result['body'], 500),
            ]);

            return response()->json(['message' => 'تعذّر جلب وسائل الدفع من فواتيرك. حاول لاحقاً أو راجع الإعدادات.'], 502);
        }

        return response()->json($result['json']);
    }

    /**
     * تنفيذ invoiceInitPay (POST) وإرجاع بيانات الدفع للواجهة (تحويل، أكواد فوري، إلخ).
     */
    public function fawaterakPay(Request $request, int $courseId): JsonResponse
    {
        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return response()->json(['message' => 'بوابة الدفع الإلكترونية غير مفعّلة.'], 403);
        }

        $api = app(FawaterakApiService::class);
        if ($api->integrationMode() !== 'api' || ! $api->isConfigured()) {
            return response()->json(['message' => 'وضع API غير مفعّل أو الرمز غير مضبوط.'], 503);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|integer|min:1',
            'mobile_wallet_number' => 'nullable|string|max:32',
        ]);

        $order = $this->fawaterakResolvePendingOrder($request, $courseId);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $order->loadMissing('course');
        $course = $order->course;
        if (! $course) {
            return response()->json(['message' => 'الكورس غير مرتبط بالطلب.'], 422);
        }

        $user = Auth::user();
        $fullName = trim((string) ($user->name ?? ''));
        $nameParts = preg_split('/\s+/u', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $nameParts[0] ?? 'Customer';
        $lastName = $nameParts[1] ?? $firstName;
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل الدفع.'], 422);
        }

        $phone = preg_replace('/\D/', '', (string) ($user->phone ?? ''));
        if ($phone === '') {
            $phone = '0000000000';
        }

        $amount = (float) $order->amount;
        $currency = $order->currencyCode() ?: (string) config('currency.code', 'SAR');
        $cartTotal = number_format($amount, 2, '.', '');
        $itemPrice = $cartTotal;

        $payload = [
            'payment_method_id' => (int) $validated['payment_method_id'],
            'cartTotal' => $cartTotal,
            'currency' => $currency,
            'invoice_number' => 'ORD-'.$order->id,
            'customer' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => '',
            ],
            'redirectionUrls' => [
                'successUrl' => route('public.checkout.fawaterak.return', ['status' => 'success']),
                'failUrl' => route('public.checkout.fawaterak.return', ['status' => 'fail']),
                'pendingUrl' => route('public.checkout.fawaterak.return', ['status' => 'pending']),
            ],
            'cartItems' => [
                [
                    'name' => Str::limit($course->title ?? 'كورس', 120),
                    'price' => $itemPrice,
                    'quantity' => '1',
                ],
            ],
            'payLoad' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) $user->id,
                'course_id' => (string) $course->id,
            ],
            'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
        ];

        if ($order->fawaterak_invoice_id !== null && $order->fawaterak_invoice_id !== '') {
            $payload['invoice_id'] = (int) $order->fawaterak_invoice_id;
        }

        $walletRaw = preg_replace('/\D/', '', (string) ($validated['mobile_wallet_number'] ?? ''));
        if ($walletRaw !== '') {
            $payload['mobileWalletNumber'] = $walletRaw;
        }

        $result = $api->invoiceInitPay($payload);
        if (! $result['ok'] || ! is_array($result['json'])) {
            Log::warning('Fawaterak invoiceInitPay failed', [
                'status' => $result['status'],
                'body' => Str::limit($result['body'], 800),
            ]);

            return response()->json([
                'message' => 'تعذّر بدء الدفع لدى فواتيرك. حاول مرة أخرى أو راجع السجلات.',
            ], 502);
        }

        $json = $result['json'];
        if (($json['status'] ?? '') !== 'success') {
            $msg = is_string($json['message'] ?? null) ? $json['message'] : 'رفضت فواتيرك الطلب.';

            return response()->json(['message' => $msg, 'details' => $json], 422);
        }

        $extInvoiceId = data_get($json, 'data.invoice_id');
        if ($extInvoiceId !== null && $extInvoiceId !== '') {
            $order->update(['fawaterak_invoice_id' => (string) $extInvoiceId]);
        }

        return response()->json($json);
    }

    /**
     * @return Order|JsonResponse
     */
    private function fawaterakResolvePendingOrder(Request $request, int $courseId)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
        }

        $orderId = (int) $request->session()->get('fawaterak_order_id');
        if ($orderId < 1) {
            return response()->json(['message' => 'لا يوجد طلب دفع نشط. حدّث الصفحة وأعد فتح الدفع.'], 422);
        }

        $order = Order::query()->whereKey($orderId)->first();
        if (! $order || $order->user_id !== Auth::id()) {
            return response()->json(['message' => 'طلب غير صالح.'], 422);
        }

        if ((int) $order->advanced_course_id !== (int) $courseId) {
            return response()->json(['message' => 'الطلب لا يطابق هذا الكورس.'], 422);
        }

        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'تمت معالجة هذا الطلب مسبقاً.'], 409);
        }

        if ($order->payment_method !== 'online' || $order->payment_proof !== null) {
            return response()->json(['message' => 'هذا الطلب لا يصلح للدفع الإلكتروني.'], 422);
        }

        return $order;
    }

    /**
     * عودة المستخدم من فواتيرك بعد الدفع (داخل نفس الجلسة).
     */
    public function fawaterakReturn(Request $request, string $status): RedirectResponse
    {
        $order = $this->resolveFawaterakReturnOrder($request);
        if (! $order) {
            return redirect()->route('orders.index')
                ->with('error', 'انتهت جلسة الدفع. إن اكتمل الدفع لدى فواتيرك ولم يُفعَّل الطلب، تواصل مع الدعم برقم الطلب أو رقم فاتورة فواتيرك.');
        }

        $orderId = (int) $order->id;

        $isServicePackageOrder = in_array($order->order_type, [
            Order::TYPE_SERVICE_PACKAGE,
            Order::TYPE_CUSTOM_SERVICE_PACKAGE,
        ], true);

        if ($status === 'fail') {
            $request->session()->forget('fawaterak_order_id');

            return redirect()->to($this->fawaterakCheckoutRetryUrl($order))
                ->with('error', 'لم يتم إتمام الدفع. يمكنك المحاولة مرة أخرى.');
        }

        if ($status === 'pending') {
            return redirect()->to($this->fawaterakCheckoutRetryUrl($order))
                ->with('info', $isServicePackageOrder
                    ? 'دفعتك قيد المعالجة لدى فواتيرك. عند اكتمالها سيُفعَّل رصيد الحصص تلقائياً أو يمكنك تحديث الصفحة لاحقاً.'
                    : 'دفعتك قيد المعالجة لدى فواتيرك. عند اكتمالها سيُفعَّل الكورس تلقائياً أو يمكنك تحديث الصفحة لاحقاً.');
        }

        $lockedOrder = null;
        DB::beginTransaction();
        try {
            $lockedOrder = Order::whereKey($orderId)
                ->lockForUpdate()
                ->with(['course', 'servicePackage'])
                ->first();

            if (! $lockedOrder || $lockedOrder->user_id !== Auth::id()) {
                DB::rollBack();
                $request->session()->forget('fawaterak_order_id');

                return redirect()->route('orders.index')->with('error', 'طلب غير صالح.');
            }

            if ($lockedOrder->status !== Order::STATUS_PENDING) {
                DB::rollBack();
                $request->session()->forget('fawaterak_order_id');

                return redirect()->to($this->fawaterakSuccessUrl($lockedOrder))
                    ->with('info', 'تمت معالجة هذا الطلب مسبقاً.');
            }

            try {
                $txRef = app(\App\Services\FawaterakPaymentVerifier::class)
                    ->assertOrderPaid($lockedOrder, $request);
            } catch (\InvalidArgumentException $e) {
                DB::rollBack();

                return redirect()->to($this->fawaterakCheckoutRetryUrl($lockedOrder))
                    ->with('error', $e->getMessage());
            }

            $invoice = $this->approveOrderAfterOnlinePayment(
                $lockedOrder,
                'fawaterak',
                $txRef,
                $request->query(),
                'فواتيرك (Fawaterak)'
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Fawaterak return: approval failed', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            $retryOrder = $lockedOrder ?? Order::query()->whereKey($orderId)->first();
            if ($retryOrder) {
                return redirect()->to($this->fawaterakCheckoutRetryUrl($retryOrder))
                    ->with('error', 'تعذّر تفعيل الطلب بعد الدفع. يرجى التواصل مع الدعم مع رقم الطلب.');
            }

            return redirect()->route('orders.index')
                ->with('error', 'تعذّر تفعيل الطلب بعد الدفع. يرجى التواصل مع الدعم.');
        }

        $request->session()->forget('fawaterak_order_id');

        $successMsg = __('public.checkout_payment_success_with_invoice', [
            'invoice' => $invoice->invoice_number,
        ]);

        return redirect()->to($this->fawaterakSuccessUrl($lockedOrder))
            ->with('success', $successMsg);
    }

    private function fawaterakCheckoutRetryUrl(Order $order): string
    {
        if ($order->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE) {
            return route('public.service-packages.custom.pay', $order);
        }

        if ($order->order_type === Order::TYPE_SERVICE_PACKAGE && $order->service_package_id) {
            return route('public.service-packages.checkout', $order->service_package_id);
        }

        if ($order->advanced_course_id) {
            return route('public.course.checkout', $order->advanced_course_id);
        }

        return route('orders.index');
    }

    private function fawaterakSuccessUrl(Order $order): string
    {
        if (in_array($order->order_type, [Order::TYPE_SERVICE_PACKAGE, Order::TYPE_CUSTOM_SERVICE_PACKAGE], true)) {
            return route('student.service-entitlements.index');
        }

        if ($order->advanced_course_id) {
            return route('public.course.show', $order->advanced_course_id);
        }

        return route('orders.index');
    }

    /**
     * واجهة عامة لتفعيل الطلب بعد الدفع (فواتيرك webhook أو عودة المتصفح).
     */
    public function approveOrderAfterOnlinePaymentPublic(
        Order $order,
        string $paymentGateway,
        ?string $transactionId,
        array $gatewayResponse,
        string $gatewayDisplayName
    ): Invoice {
        return $this->approveOrderAfterOnlinePayment(
            $order,
            $paymentGateway,
            $transactionId,
            $gatewayResponse,
            $gatewayDisplayName
        );
    }

    /**
     * يُنشئ فاتورة مدفوعة، وسجل دفع، ومعاملة محاسبية، ويحدّث الطلب إلى مقبول، ويُفعّل الكورس/الباقة.
     *
     * @param  'kashier'|'fawaterak'|'paypal'|'other'  $paymentGateway
     */
    private function approveOrderAfterOnlinePayment(
        Order $order,
        string $paymentGateway,
        ?string $transactionId,
        array $gatewayResponse,
        string $gatewayDisplayName
    ): Invoice {
        $order->loadMissing(['course', 'servicePackage']);

        $isPackageOrder = in_array($order->order_type, [
            Order::TYPE_SERVICE_PACKAGE,
            Order::TYPE_CUSTOM_SERVICE_PACKAGE,
        ], true);

        if ($isPackageOrder) {
            $orderTitle = $order->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE
                ? (string) ($order->custom_package_data['name'] ?? 'باقة مخصصة')
                : (string) ($order->servicePackage?->name ?? 'باقة حصص');
            $invoiceType = 'subscription';
            $invoiceDescription = 'شراء باقة: '.$orderTitle;
            $itemDescription = 'باقة: '.$orderTitle;
            $transactionCategory = 'subscription';
            $transactionDescription = 'دفع باقة: '.$orderTitle.' - طلب #'.$order->id;
        } else {
            $orderTitle = (string) ($order->course?->title ?? 'كورس');
            $invoiceType = 'course';
            $invoiceDescription = 'تسجيل في الكورس: '.$orderTitle;
            $itemDescription = 'الكورس: '.$orderTitle;
            $transactionCategory = 'course_payment';
            $transactionDescription = 'دفع كورس: '.$orderTitle.' - طلب #'.$order->id;
        }

        $currency = $order->currencyCode() ?: (string) config('currency.code', 'SAR');

        $orig = (float) ($order->original_amount ?? $order->amount);
        $couponDisc = (float) ($order->discount_amount ?? 0);
        $walletDisc = (float) ($order->wallet_credit_amount ?? 0);
        $invDiscount = round($couponDisc + $walletDisc, 2);

        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateUniqueInvoiceNumber(),
            'user_id' => $order->user_id,
            'type' => $invoiceType,
            'description' => $invoiceDescription,
            'subtotal' => $orig,
            'tax_amount' => 0,
            'discount_amount' => $invDiscount,
            'total_amount' => $order->amount,
            'status' => 'paid',
            'due_date' => now(),
            'paid_at' => now(),
            'notes' => 'دفع عبر '.$gatewayDisplayName.' - طلب #'.$order->id,
            'items' => [
                [
                    'description' => $itemDescription,
                    'quantity' => 1,
                    'price' => $orig,
                    'total' => $orig,
                ],
            ],
        ]);

        $gross = (float) $order->amount;
        $split = PaymentGatewaySettings::computeFeeSplit($gross);

        $paymentNumber = Payment::generateUniquePaymentNumber();
        $payment = Payment::create([
            'payment_number' => $paymentNumber,
            'invoice_id' => $invoice->id,
            'user_id' => $order->user_id,
            'payment_method' => 'online',
            'payment_gateway' => $paymentGateway,
            'amount' => $gross,
            'gateway_fee_amount' => $split['fee'],
            'net_after_gateway_fee' => $split['net'],
            'currency' => $currency,
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'gateway_response' => $gatewayResponse,
            'paid_at' => now(),
            'notes' => 'دفع عبر '.$gatewayDisplayName.' - طلب #'.$order->id,
        ]);

        $transactionMetadata = [
            'order_id' => $order->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
        ];
        if ($isPackageOrder) {
            $transactionMetadata['service_package_id'] = $order->service_package_id;
            $transactionMetadata['order_type'] = $order->order_type;
        } else {
            $transactionMetadata['course_id'] = $order->advanced_course_id;
        }

        Transaction::create([
            'transaction_number' => Transaction::generateUniqueTransactionNumber(),
            'user_id' => $order->user_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'expense_id' => null,
            'subscription_id' => null,
            'type' => 'credit',
            'category' => $transactionCategory,
            'amount' => $gross,
            'currency' => $currency,
            'description' => $transactionDescription,
            'status' => 'completed',
            'metadata' => $transactionMetadata,
        ]);

        if ($split['fee'] > 0.0001) {
            Transaction::create([
                'transaction_number' => 'TXN-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
                'user_id' => $order->user_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'expense_id' => null,
                'subscription_id' => null,
                'type' => 'debit',
                'category' => 'fee',
                'amount' => $split['fee'],
                'currency' => $currency,
                'description' => 'عمولة بوابة الدفع — دفع #'.$payment->payment_number,
                'status' => 'completed',
                'metadata' => [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'gateway' => $paymentGateway,
                ],
            ]);
        }

        $order->update([
            'status' => Order::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => null,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
        ]);

        try {
            app(\App\Services\ReferralService::class)
                ->completePendingForUser((int) $order->user_id, $order->amount);
        } catch (\Throwable $e) {
            Log::warning('Referral update failed after online payment: '.$e->getMessage(), ['order_id' => $order->id]);
        }

        if ($order->advanced_course_id) {
            CourseSubscriptionService::syncEnrollmentFromOrder(
                $order,
                $invoice->id,
                $payment->id,
                'online',
                (int) $order->user_id
            );
        }

        if ($order->isTutoringOrder()) {
            try {
                \App\Services\TutoringGroupCheckoutService::fulfillApprovedOrder($order->fresh());
            } catch (\Throwable $e) {
                Log::error('Tutoring fulfill failed after online payment: '.$e->getMessage(), [
                    'order_id' => $order->id,
                    'exception' => $e::class,
                ]);

                try {
                    \App\Services\TutoringGroupCheckoutService::fulfillApprovedOrder($order->fresh());
                } catch (\Throwable $retry) {
                    Log::critical('Tutoring fulfill retry failed after online payment: '.$retry->getMessage(), [
                        'order_id' => $order->id,
                    ]);

                    $marker = '[TUTORING_FULFILL_FAILED] '.$retry->getMessage();
                    $existingNotes = (string) ($order->notes ?? '');
                    if (! str_contains($existingNotes, '[TUTORING_FULFILL_FAILED]')) {
                        $order->update([
                            'notes' => trim($existingNotes !== '' ? $existingNotes."\n".$marker : $marker),
                        ]);
                    }
                }
            }
        }

        OrderWalletAndCouponFinalizer::run($order->fresh());

        return $invoice;
    }

    private function resolveFawaterakReturnOrder(Request $request): ?Order
    {
        $userId = (int) Auth::id();
        if ($userId < 1) {
            return null;
        }

        $orderId = (int) $request->session()->get('fawaterak_order_id');
        if ($orderId > 0) {
            $order = Order::with(['course', 'servicePackage'])->find($orderId);
            if ($order && (int) $order->user_id === $userId) {
                return $order;
            }
        }

        $queryPayload = array_merge(
            $request->query(),
            FawaterakOrderResolver::normalizedPayLoad($request->query())
        );
        $invoiceId = FawaterakOrderResolver::extractInvoiceId($queryPayload);

        if ($invoiceId) {
            $order = Order::with(['course', 'servicePackage'])
                ->where('fawaterak_invoice_id', $invoiceId)
                ->where('user_id', $userId)
                ->first();
            if ($order) {
                $request->session()->put('fawaterak_order_id', $order->id);

                return $order;
            }
        }

        $resolved = FawaterakOrderResolver::resolvePendingOrder($queryPayload, $invoiceId);
        if ($resolved && (int) $resolved->user_id === $userId) {
            $request->session()->put('fawaterak_order_id', $resolved->id);

            return $resolved->loadMissing(['course', 'servicePackage']);
        }

        return null;
    }

    /**
     * إتمام الطلب
     */
    public function complete(Request $request, $courseId)
    {
        // التحقق من تسجيل الدخول
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        if (PaymentGatewaySettings::blocksManualCheckout()) {
            return back()->with('error', 'الدفع اليدوي غير متاح حين تكون بوابة دفع إلكترونية مفعّلة من إعدادات النظام.');
        }

        $course = AdvancedCourse::where('id', $courseId)
            ->where('is_active', true)
            ->firstOrFail();

        if (Auth::user()->isEnrolledIn($course->id)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        // منع طلب مكرر: إذا كان هناك طلب قيد الانتظار لنفس الكورس
        $existingPending = Order::where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->where('status', Order::STATUS_PENDING)
            ->first();
        if ($existingPending) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'لديك طلب قيد الانتظار لهذا الكورس. يرجى انتظار المراجعة.');
        }

        $request->validate([
            'coupon_code' => 'nullable|string|max:64',
            'wallet_credit' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SAR,USD,EGP,sar,usd,egp',
        ]);

        $currency = platform_currency();

        $pricing = CourseCheckoutPricingService::resolve(
            Auth::user(),
            $course,
            $request->input('coupon_code'),
            (float) $request->input('wallet_credit', 0),
            null,
            $currency
        );

        if (! $pricing['ok']) {
            return back()->withErrors(['coupon_code' => $pricing['message']])->withInput();
        }

        $proofRequired = $pricing['final_amount'] > 0.009;

        // التحقق من صحة البيانات (حساب الاستلام على المنصة إلزامي للتحويل حتى يُسجَّل الإيداع عند الموافقة)
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,wallet,cash,other',
            'wallet_id' => [
                'nullable',
                'required_if:payment_method,bank_transfer',
                'required_if:payment_method,wallet',
                Rule::exists('wallets', 'id')->where('is_active', true)->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer']),
            ],
            'payment_proof' => ($proofRequired ? 'required|' : 'nullable|').'image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string|max:1000',
        ], [
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صحيحة',
            'wallet_id.required_if' => 'يجب اختيار حساب التحويل على المنصة حتى يُسجَّل المبلغ على المحفظة الصحيحة عند الموافقة.',
            'wallet_id.exists' => 'المحفظة المختارة غير صالحة أو غير متاحة. يرجى اختيار محفظة من القائمة.',
            'payment_proof.required' => 'صورة إيصال الدفع مطلوبة',
            'payment_proof.image' => 'يجب أن يكون الملف صورة',
            'payment_proof.mimes' => 'يجب أن تكون الصورة بصيغة jpeg, png أو jpg',
            'payment_proof.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
        ]);

        DB::beginTransaction();
        try {
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = PublicMediaStorage::storeFile($request->file('payment_proof'), 'payment-proofs');
            }

            $extraNotes = [];
            if ($pricing['discount_amount'] > 0) {
                $extraNotes[] = 'خصم كوبون: '.number_format($pricing['discount_amount'], 2) . ' ' . currency_symbol();
            }
            if ($pricing['wallet_credit_amount'] > 0) {
                $extraNotes[] = 'خصم من رصيد المحفظة: '.number_format($pricing['wallet_credit_amount'], 2) . ' ' . currency_symbol();
            }
            $notes = trim((string) ($request->notes ?? ''));
            if ($extraNotes !== []) {
                $notes .= ($notes !== '' ? "\n" : '').implode("\n", $extraNotes);
            }

            // إنشاء الطلب
            $order = Order::create([
                'user_id' => Auth::id(),
                'advanced_course_id' => $course->id,
                'coupon_id' => $pricing['coupon_id'],
                'original_amount' => $pricing['original_amount'],
                'discount_amount' => $pricing['discount_amount'],
                'wallet_credit_amount' => $pricing['wallet_credit_amount'],
                'amount' => $pricing['final_amount'],
                'currency' => $currency,
                'billing_mode' => $course->billing_mode ?? CourseSubscriptionService::BILLING_ONE_TIME,
                'payment_method' => $request->payment_method === 'wallet' ? 'bank_transfer' : $request->payment_method,
                'payment_proof' => $paymentProofPath,
                'wallet_id' => $request->payment_method === 'bank_transfer' || $request->payment_method === 'wallet'
                    ? ($request->wallet_id ?: null)
                    : null,
                'notes' => $notes,
                'status' => Order::STATUS_PENDING,
                'auto_renew' => $course->isMonthlyBilling() && $request->boolean('auto_renew'),
            ]);

            DB::commit();

            return redirect()->route('public.course.show', $course->id)
                ->with('success', 'تم استلام طلبك بنجاح. طلبك قيد المراجعة لهذا الكورس وسيتم تفعيله بعد الموافقة.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout complete error: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء إتمام الطلب. يرجى المحاولة مرة أخرى.')
                ->withInput();
        }
    }

    /**
     * تسجيل مجاني للكورسات المجانية
     */
    public function enrollFree($courseId)
    {
        // التحقق من تسجيل الدخول
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        $course = AdvancedCourse::where('id', $courseId)
            ->where('is_active', true)
            ->firstOrFail();

        // التحقق من أن الكورس مجاني
        if ($course->effectiveCheckoutPrice() > 0 && ! ($course->is_free ?? false)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('error', 'هذا الكورس ليس مجانياً');
        }

        $existingEnrollment = StudentCourseEnrollment::where('user_id', Auth::id())
            ->where('advanced_course_id', $course->id)
            ->first();

        if ($existingEnrollment && CourseSubscriptionService::enrollmentGrantsAccess($existingEnrollment)) {
            return redirect()->route('public.course.show', $course->id)
                ->with('info', 'أنت مسجل بالفعل في هذا الكورس');
        }

        DB::beginTransaction();
        try {
            if ($existingEnrollment) {
                CourseSubscriptionService::activateLifetimeEnrollment($existingEnrollment);
                $existingEnrollment->update(['activated_by' => Auth::id()]);
                $enrollment = $existingEnrollment->fresh();
            } else {
                $enrollment = StudentCourseEnrollment::create([
                    'user_id' => Auth::id(),
                    'advanced_course_id' => $course->id,
                    'enrolled_at' => now(),
                    'activated_at' => now(),
                    'activated_by' => Auth::id(),
                    'status' => 'active',
                    'progress' => 0,
                    'enrollment_type' => 'gift',
                    'access_type' => 'lifetime',
                ]);
            }
            InstructorCoursePercentageService::processEnrollmentActivation($enrollment);

            DB::commit();

            return redirect()->route('public.course.show', $course->id)
                ->with('success', 'تم تسجيلك في الكورس بنجاح! يمكنك الآن البدء بالتعلم.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('public.course.show', $course->id)
                ->with('error', 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.');
        }
    }
}
