<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\ServicePackagePricingRule;
use App\Models\Wallet;
use App\Services\CustomServicePackagePricingService;
use App\Services\FawaterakApiService;
use App\Services\FawaterakService;
use App\Services\PaymentGatewaySettings;
use App\Services\PublicMediaStorage;
use App\Services\StudentEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServicePackageCheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Canonical public catalog is /pricing (navbar). Keep checkout routes here.
        if (! $request->filled('year') && ! $request->filled('subject') && ! $request->boolean('catalog')) {
            return redirect()->route('public.pricing');
        }

        $yearId = $request->filled('year') ? (int) $request->query('year') : null;
        $subjectId = $request->filled('subject') ? (int) $request->query('subject') : null;

        $selectedYear = null;
        $selectedSubject = null;
        if ($yearId && Schema::hasTable('academic_years')) {
            $selectedYear = AcademicYear::query()->active()->find($yearId);
            $yearId = $selectedYear?->id;
        }
        if ($subjectId && Schema::hasTable('academic_subjects')) {
            $selectedSubject = AcademicSubject::query()->active()->find($subjectId);
            $subjectId = $selectedSubject?->id;
            if ($selectedSubject?->academic_year_id && ! $yearId) {
                $yearId = (int) $selectedSubject->academic_year_id;
                $selectedYear = AcademicYear::query()->find($yearId);
            }
        }

        $commercialPackages = ServicePackage::query()
            ->commercial()
            ->forSchoolProgram($yearId, $subjectId)
            ->with(['academicYear:id,name,slug', 'academicSubject:id,name'])
            ->ordered()
            ->get();

        $planMatrix = ServicePackage::commercialCatalogMatrix($commercialPackages);

        $privateRule = ServicePackagePricingRule::query()
            ->active()
            ->where('scope', ServicePackage::SCOPE_PRIVATE_LESSONS)
            ->ordered()
            ->first()
            ?: ServicePackagePricingRule::query()
                ->active()
                ->where('scope', ServicePackage::SCOPE_TUTORING_INDIVIDUAL)
                ->ordered()
                ->first();

        $years = Schema::hasTable('academic_years')
            ? AcademicYear::query()->active()->ordered()->get(['id', 'name', 'slug', 'level_number'])
            : collect();

        [$fawaterakUseGateway, $fawaterakMisconfigured, $fawaterakIntegration] = $this->fawaterakFlags();
        [$paypalUseGateway, $paypalMisconfigured] = $this->paypalFlags();
        [$stripeUseGateway, $stripeMisconfigured] = $this->stripeFlags();

        return view('public.service-packages', [
            'planMatrix' => $planMatrix,
            'packages' => $commercialPackages,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedSubject' => $selectedSubject,
            'yearId' => $yearId,
            'subjectId' => $subjectId,
            'privateRule' => $privateRule,
            'privateTermMonths' => CustomServicePackagePricingService::PRIVATE_TERM_MONTHS,
            'privateWeeklyOptions' => CustomServicePackagePricingService::PRIVATE_WEEKLY_OPTIONS,
            'pricingRules' => collect(),
            'fawaterakUseGateway' => $fawaterakUseGateway,
            'fawaterakMisconfigured' => $fawaterakMisconfigured,
            'fawaterakIntegration' => $fawaterakIntegration,
            'paypalUseGateway' => $paypalUseGateway,
            'paypalMisconfigured' => $paypalMisconfigured,
            'stripeUseGateway' => $stripeUseGateway,
            'stripeMisconfigured' => $stripeMisconfigured,
        ]);
    }

    public function checkout(ServicePackage $servicePackage): View|RedirectResponse
    {
        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        if (! auth()->check()) {
            return redirect()
                ->guest(route('login'))
                ->with('info', 'سجّل الدخول لشراء الباقة — سنعيدك مباشرة لإتمام الدفع.');
        }

        $servicePackage->load(['academicYear:id,name', 'academicSubject:id,name']);
        [$fawaterakUseGateway, $fawaterakMisconfigured, $fawaterakIntegration] = $this->fawaterakFlags();
        [$paypalUseGateway, $paypalMisconfigured] = $this->paypalFlags();
        [$stripeUseGateway, $stripeMisconfigured] = $this->stripeFlags();

        $yearId = request()->filled('year')
            ? request()->integer('year')
            : ($servicePackage->academic_year_id ? (int) $servicePackage->academic_year_id : null);
        $subjectId = request()->filled('subject')
            ? request()->integer('subject')
            : ($servicePackage->academic_subject_id ? (int) $servicePackage->academic_subject_id : null);

        $relatedPackages = ServicePackage::query()
            ->storefront()
            ->where('id', '!=', $servicePackage->id)
            ->when($yearId, function ($q) use ($yearId) {
                $q->where(function ($inner) use ($yearId) {
                    $inner->where('academic_year_id', $yearId)->orWhereNull('academic_year_id');
                });
            })
            ->when($subjectId, function ($q) use ($subjectId) {
                $q->where(function ($inner) use ($subjectId) {
                    $inner->where('academic_subject_id', $subjectId)->orWhereNull('academic_subject_id');
                });
            })
            ->orderByDesc('units_count')
            ->limit(6)
            ->get(['id', 'name', 'slug', 'units_count', 'price', 'currency', 'academic_year_id', 'academic_subject_id']);

        $manualCheckoutAllowed = ! PaymentGatewaySettings::blocksManualCheckout();
        $wallets = $manualCheckoutAllowed
            ? Wallet::query()
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereIn('type', ['vodafone_cash', 'instapay', 'bank_transfer', 'other'])
                        ->orWhereNull('type')
                        ->orWhere('type', '');
                })
                ->where(function ($query) {
                    $query->whereNotNull('account_number')
                        ->orWhereNotNull('name');
                })
                ->orderBy('name')
                ->get()
            : collect();

        $isUrgent = request()->boolean('urgent') || request()->query('urgent') === '1';

        return view('public.service-package-checkout', [
            'package' => $servicePackage,
            'indexable' => false,
            'isUrgent' => $isUrgent,
            'fawaterakUseGateway' => $fawaterakUseGateway,
            'fawaterakMisconfigured' => $fawaterakMisconfigured,
            'fawaterakIntegration' => $fawaterakIntegration,
            'paypalUseGateway' => $paypalUseGateway,
            'paypalMisconfigured' => $paypalMisconfigured,
            'stripeUseGateway' => $stripeUseGateway,
            'stripeMisconfigured' => $stripeMisconfigured,
            'relatedPackages' => $relatedPackages,
            'manualCheckoutAllowed' => $manualCheckoutAllowed,
            'wallets' => $wallets,
            'backPricingUrl' => route('public.pricing', array_filter([
                'year' => $yearId,
                'subject' => $subjectId,
            ])).'#mc-pricing-storefront',
        ]);
    }

    public function store(Request $request, ServicePackage $servicePackage): RedirectResponse
    {
        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        if (! Auth::check()) {
            return redirect()
                ->guest(route('login'))
                ->with('info', 'سجّل الدخول لشراء الباقة — سنعيدك مباشرة لإتمام الدفع.');
        }

        if (PaymentGatewaySettings::blocksManualCheckout()) {
            return redirect()
                ->route('public.service-packages.checkout', $servicePackage)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'الدفع اليدوي غير متاح حالياً لأن بوابة إلكترونية مفعّلة. استخدم زر الدفع الإلكتروني أعلاه.'
                    : 'Manual payment is disabled while an online gateway is enabled. Use the online pay button above.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,cash,other',
            'wallet_id' => [
                'nullable',
                'required_if:payment_method,bank_transfer',
                Rule::exists('wallets', 'id')->where('is_active', true),
            ],
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:'.config('upload_limits.max_upload_kb'),
            'notes' => 'nullable|string|max:1000',
            'academic_year_id' => 'nullable|integer',
            'academic_subject_id' => 'nullable|integer',
            'curriculum_type' => 'nullable|string|max:40',
        ], [
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'wallet_id.required_if' => 'اختر حساب المنصة الذي حوّلت إليه',
            'payment_proof.required' => 'صورة إيصال الدفع مطلوبة',
            'payment_proof.image' => 'يجب أن يكون الملف صورة',
        ]);

        $existingOrder = Order::query()
            ->where('user_id', Auth::id())
            ->where('service_package_id', $servicePackage->id)
            ->where('order_type', Order::TYPE_SERVICE_PACKAGE)
            ->where('status', Order::STATUS_PENDING)
            ->whereNotNull('payment_proof')
            ->latest('id')
            ->first();
        if ($existingOrder) {
            return redirect()
                ->route('public.service-packages.checkout', $servicePackage)
                ->with('info', app()->getLocale() === 'ar'
                    ? 'لديك طلب قيد المراجعة لهذه الباقة.'
                    : 'You already have a pending review order for this package.');
        }

        try {
            $proofPath = PublicMediaStorage::storeFile($request->file('payment_proof'), 'payment-proofs');
            $order = StudentEntitlementService::createOrder(
                Auth::user(),
                $servicePackage,
                $validated['payment_method'] === 'bank_transfer' ? 'bank_transfer' : $validated['payment_method'],
                $validated['payment_method'] === 'bank_transfer' ? (int) $validated['wallet_id'] : null,
                [
                    'academic_year_id' => $request->integer('academic_year_id') ?: null,
                    'academic_subject_id' => $request->integer('academic_subject_id') ?: null,
                    'curriculum_type' => $request->input('curriculum_type'),
                ],
            );
            $extra = trim((string) ($validated['notes'] ?? ''));
            $order->update([
                'payment_proof' => $proofPath,
                'notes' => trim((string) $order->notes.($extra !== '' ? "\n".$extra : '')),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('success', app()->getLocale() === 'ar'
                    ? 'تم استلام طلب شراء الباقة. سنراجع الإيصال ونفعّل رصيد الحصص بعد التأكيد.'
                    : 'Package order received. We will review the receipt and activate session credits after confirmation.');
        } catch (\Throwable $e) {
            report($e);
            Log::error('Service package manual checkout failed', [
                'user_id' => Auth::id(),
                'package_id' => $servicePackage->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', app()->getLocale() === 'ar'
                    ? 'تعذر إرسال طلب الدفع. حاول مرة أخرى أو تواصل مع الدعم.'
                    : 'Could not submit payment order. Try again or contact support.')
                ->withInput();
        }
    }

    public function customQuote(Request $request)
    {
        $data = $request->validate([
            'pricing_rule_id' => ['required', 'integer', 'exists:service_package_pricing_rules,id'],
            'term_months' => ['required', 'integer', 'in:1,3'],
            'weekly_sessions' => ['required', 'integer', 'in:1,2,3,4'],
        ]);

        $rule = ServicePackagePricingRule::query()->active()->findOrFail($data['pricing_rule_id']);

        return response()->json(CustomServicePackagePricingService::calculatePrivateWeekly(
            $rule,
            (int) $data['term_months'],
            (int) $data['weekly_sessions'],
        ));
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            return redirect()
                ->guest(route('login'))
                ->with('info', 'سجّل الدخول لشراء الباقة الخاصة — سنعيدك لإتمام الدفع.');
        }

        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return redirect()
                ->route('public.pricing')
                ->with('error', 'بوابة الدفع الإلكترونية (فواتيرك) غير مفعّلة حالياً. تواصل مع الدعم.');
        }

        [$useGateway, $misconfigured] = $this->fawaterakFlags();
        if ($misconfigured || ! $useGateway) {
            return redirect()
                ->route('public.pricing')
                ->with('error', 'تم تفعيل فواتيرك لكن الربط غير مكتمل على الخادم.');
        }

        $data = $request->validate([
            'pricing_rule_id' => ['required', 'integer', 'exists:service_package_pricing_rules,id'],
            'term_months' => ['required', 'integer', 'in:1,3'],
            'weekly_sessions' => ['required', 'integer', 'in:1,2,3,4'],
        ]);

        $rule = ServicePackagePricingRule::query()->active()->findOrFail($data['pricing_rule_id']);

        $order = StudentEntitlementService::createPrivateWeeklyOrder(
            $request->user(),
            $rule,
            (int) $data['term_months'],
            (int) $data['weekly_sessions'],
            'online',
            null,
        );

        $request->session()->put('fawaterak_order_id', $order->id);

        return redirect()->route('public.service-packages.custom.pay', $order);
    }

    public function customPay(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless($request->user() && (int) $order->user_id === (int) $request->user()->id, 403);
        abort_unless($order->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE, 404);
        abort_unless($order->status === Order::STATUS_PENDING && $order->payment_method === 'online', 404);

        [$fawaterakUseGateway, $fawaterakMisconfigured, $fawaterakIntegration] = $this->fawaterakFlags();
        [$paypalUseGateway, $paypalMisconfigured] = $this->paypalFlags();
        [$stripeUseGateway, $stripeMisconfigured] = $this->stripeFlags();
        $request->session()->put('fawaterak_order_id', $order->id);

        $isRtl = app()->getLocale() === 'ar';

        return view('public.service-package-pay', [
            'order' => $order,
            'packageTitle' => $order->custom_package_data['name'] ?? ($isRtl ? 'باقة خاصة' : 'Private pack'),
            'fawaterakUseGateway' => $fawaterakUseGateway,
            'fawaterakMisconfigured' => $fawaterakMisconfigured,
            'fawaterakIntegration' => $fawaterakIntegration,
            'paypalUseGateway' => $paypalUseGateway,
            'paypalMisconfigured' => $paypalMisconfigured,
            'stripeUseGateway' => $stripeUseGateway,
            'stripeMisconfigured' => $stripeMisconfigured,
            'prepareRoute' => route('public.service-packages.custom.fawaterak.prepare', $order),
            'methodsRoute' => route('public.service-packages.custom.fawaterak.methods', $order),
            'payRoute' => route('public.service-packages.custom.fawaterak.pay', $order),
            'paypalRoute' => route('public.service-packages.custom.paypal', $order),
            'stripeRoute' => route('public.service-packages.custom.stripe', $order),
        ]);
    }

    public function fawaterakPrepare(Request $request, ServicePackage $servicePackage): JsonResponse
    {
        abort_unless($servicePackage->is_active && ! $servicePackage->tutoring_group_id, 404);

        $gate = $this->fawaterakGateJson();
        if ($gate instanceof JsonResponse) {
            return $gate;
        }

        if (! Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
        }

        $user = Auth::user();
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل الدفع.'], 422);
        }

        $yearId = $request->filled('academic_year_id') ? $request->integer('academic_year_id') : null;
        $subjectId = $request->filled('academic_subject_id') ? $request->integer('academic_subject_id') : null;
        $curriculumType = $request->input('curriculum_type');
        $quote = app(\App\Services\ServiceSessionRateService::class)->quotePackage(
            $servicePackage,
            $yearId,
            is_string($curriculumType) ? $curriculumType : null,
        );

        if ((float) $quote['total'] < 0.01) {
            return response()->json(['message' => 'هذه الباقة لا تتطلب دفعاً عبر البوابة.'], 422);
        }

        $existingOrder = Order::query()
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

        if ($existingOrder) {
            if ($existingOrder->payment_method !== 'online' || $existingOrder->payment_proof !== null) {
                return response()->json(['message' => 'لديك طلب قيد المراجعة لهذه الباقة.'], 409);
            }
            $meta = is_array($existingOrder->custom_package_data) ? $existingOrder->custom_package_data : [];
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
            $existingOrder->update([
                'original_amount' => $original,
                'discount_amount' => max(0, $original - (float) $quote['total']),
                'amount' => $quote['total'],
                'currency' => $quote['currency'],
                'academic_year_id' => $quote['academic_year_id'] ?? $yearId,
                'custom_package_data' => $meta,
                'wallet_id' => null,
                'payment_method' => 'online',
            ]);
            $order = $existingOrder->fresh();
        } else {
            $order = StudentEntitlementService::createOrder($user, $servicePackage, 'online', null, [
                'academic_year_id' => $yearId,
                'academic_subject_id' => $subjectId,
                'curriculum_type' => is_string($curriculumType) ? $curriculumType : null,
            ]);
        }

        $request->session()->put('fawaterak_order_id', $order->id);

        return $this->fawaterakPrepareResponse(
            $order,
            Str::limit($servicePackage->name ?? 'باقة', 120),
            [
                'order_id' => (string) $order->id,
                'user_id' => (string) $user->id,
                'service_package_id' => (string) $servicePackage->id,
            ]
        );
    }

    public function fawaterakPrepareCustom(Request $request, Order $order): JsonResponse
    {
        $gate = $this->fawaterakGateJson();
        if ($gate instanceof JsonResponse) {
            return $gate;
        }

        if (! Auth::check() || (int) $order->user_id !== (int) Auth::id()) {
            return response()->json(['message' => 'طلب غير صالح.'], 403);
        }

        if ($order->order_type !== Order::TYPE_CUSTOM_SERVICE_PACKAGE
            || $order->status !== Order::STATUS_PENDING
            || $order->payment_method !== 'online') {
            return response()->json(['message' => 'هذا الطلب غير متاح للدفع الإلكتروني.'], 422);
        }

        if ((float) $order->amount < 0.01) {
            return response()->json(['message' => 'هذه الباقة لا تتطلب دفعاً عبر البوابة.'], 422);
        }

        $user = Auth::user();
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'يرجى إضافة بريد إلكتروني صالح في ملفك الشخصي قبل الدفع.'], 422);
        }

        $request->session()->put('fawaterak_order_id', $order->id);

        $title = Str::limit((string) ($order->custom_package_data['name'] ?? 'باقة خاصة'), 120);

        return $this->fawaterakPrepareResponse(
            $order,
            $title,
            [
                'order_id' => (string) $order->id,
                'user_id' => (string) $user->id,
                'order_type' => Order::TYPE_CUSTOM_SERVICE_PACKAGE,
            ]
        );
    }

    public function fawaterakPaymentMethods(Request $request, ServicePackage $servicePackage): JsonResponse
    {
        return $this->fawaterakMethodsForPackageOrder($request, function (Order $order) use ($servicePackage) {
            return (int) $order->service_package_id === (int) $servicePackage->id
                && $order->order_type === Order::TYPE_SERVICE_PACKAGE;
        });
    }

    public function fawaterakPaymentMethodsCustom(Request $request, Order $order): JsonResponse
    {
        return $this->fawaterakMethodsForPackageOrder($request, function (Order $sessionOrder) use ($order) {
            return (int) $sessionOrder->id === (int) $order->id
                && $sessionOrder->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE;
        });
    }

    public function fawaterakPay(Request $request, ServicePackage $servicePackage): JsonResponse
    {
        return $this->fawaterakInvoicePay(
            $request,
            function (Order $order) use ($servicePackage) {
                return (int) $order->service_package_id === (int) $servicePackage->id
                    && $order->order_type === Order::TYPE_SERVICE_PACKAGE;
            },
            Str::limit($servicePackage->name ?? 'باقة', 120),
            [
                'service_package_id' => (string) $servicePackage->id,
            ]
        );
    }

    public function fawaterakPayCustom(Request $request, Order $order): JsonResponse
    {
        $title = Str::limit((string) ($order->custom_package_data['name'] ?? 'باقة خاصة'), 120);

        return $this->fawaterakInvoicePay(
            $request,
            function (Order $sessionOrder) use ($order) {
                return (int) $sessionOrder->id === (int) $order->id
                    && $sessionOrder->order_type === Order::TYPE_CUSTOM_SERVICE_PACKAGE;
            },
            $title,
            [
                'order_type' => Order::TYPE_CUSTOM_SERVICE_PACKAGE,
            ]
        );
    }

    /**
     * @return array{0: bool, 1: bool, 2: string}
     */
    private function fawaterakFlags(): array
    {
        $gatewayOn = PaymentGatewaySettings::isFawaterakEnabled();
        $api = app(FawaterakApiService::class);
        $iframe = app(FawaterakService::class);
        $integration = $api->integrationMode();
        $ready = $integration === 'api'
            ? $api->isConfigured()
            : $iframe->isConfigured();

        return [$gatewayOn && $ready, $gatewayOn && ! $ready, $integration];
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function paypalFlags(): array
    {
        return [
            \App\Services\PayPalSettings::isReady(),
            \App\Services\PayPalSettings::isMisconfigured(),
        ];
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function stripeFlags(): array
    {
        return [
            \App\Services\StripeSettings::isReady(),
            \App\Services\StripeSettings::isMisconfigured(),
        ];
    }

    private function fawaterakGateJson(): ?JsonResponse
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

        return null;
    }

    /**
     * @param  array<string, string>  $payLoad
     */
    private function fawaterakPrepareResponse(Order $order, string $itemName, array $payLoad): JsonResponse
    {
        $api = app(FawaterakApiService::class);
        $iframe = app(FawaterakService::class);
        $integration = $api->integrationMode();
        $user = Auth::user();

        if ($integration === 'api') {
            return response()->json([
                'mode' => 'api',
                'methodsUrl' => null,
                'payUrl' => null,
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
        $cartTotal = number_format((float) $order->amount, 2, '.', '');

        $bearer = trim((string) config('fawaterak.plugin_bearer_token', ''));
        if ($bearer === '') {
            $bearer = trim((string) config('fawaterak.vendor_key', ''));
        }

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
                    'email' => trim((string) ($user->email ?? '')),
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
                        'name' => $itemName,
                        'price' => $cartTotal,
                        'quantity' => '1',
                    ],
                ],
                'payLoad' => $payLoad,
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
     * @param  callable(Order): bool  $matches
     */
    private function fawaterakMethodsForPackageOrder(Request $request, callable $matches): JsonResponse
    {
        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return response()->json(['message' => 'بوابة الدفع الإلكترونية غير مفعّلة.'], 403);
        }

        $api = app(FawaterakApiService::class);
        if ($api->integrationMode() !== 'api' || ! $api->isConfigured()) {
            return response()->json(['message' => 'وضع API غير مفعّل أو الرمز غير مضبوط.'], 503);
        }

        $order = $this->resolveSessionOrder($request, $matches);
        if ($order instanceof JsonResponse) {
            return $order;
        }

        $result = $api->getPaymentMethods();
        if (! $result['ok'] || ! is_array($result['json'])) {
            Log::warning('Fawaterak getPaymentmethods failed (package)', [
                'status' => $result['status'],
                'body' => Str::limit($result['body'], 500),
            ]);

            return response()->json(['message' => 'تعذّر جلب وسائل الدفع من فواتيرك. حاول لاحقاً أو راجع الإعدادات.'], 502);
        }

        return response()->json($result['json']);
    }

    /**
     * @param  callable(Order): bool  $matches
     * @param  array<string, string>  $extraPayLoad
     */
    private function fawaterakInvoicePay(Request $request, callable $matches, string $itemName, array $extraPayLoad = []): JsonResponse
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

        $order = $this->resolveSessionOrder($request, $matches);
        if ($order instanceof JsonResponse) {
            return $order;
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
                    'name' => $itemName,
                    'price' => $cartTotal,
                    'quantity' => '1',
                ],
            ],
            'payLoad' => array_merge([
                'order_id' => (string) $order->id,
                'user_id' => (string) $user->id,
            ], $extraPayLoad),
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
            Log::warning('Fawaterak invoiceInitPay failed (package)', [
                'status' => $result['status'],
                'body' => Str::limit($result['body'], 800),
            ]);

            return response()->json([
                'message' => 'تعذّر بدء الدفع عبر فواتيرك. حاول مرة أخرى أو اختر وسيلة أخرى.',
            ], 502);
        }

        $extInvoiceId = data_get($result['json'], 'data.invoice_id')
            ?? data_get($result['json'], 'invoice_id');
        if ($extInvoiceId) {
            $order->update(['fawaterak_invoice_id' => (string) $extInvoiceId]);
        }

        return response()->json($result['json']);
    }

    /**
     * @param  callable(Order): bool  $matches
     */
    private function resolveSessionOrder(Request $request, callable $matches): Order|JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'يجب تسجيل الدخول.'], 401);
        }

        $orderId = (int) $request->session()->get('fawaterak_order_id');
        if ($orderId < 1) {
            return response()->json(['message' => 'جلسة الدفع غير جاهزة. أعد تحميل الصفحة.'], 409);
        }

        $order = Order::query()->find($orderId);
        if (! $order
            || (int) $order->user_id !== (int) Auth::id()
            || $order->status !== Order::STATUS_PENDING
            || $order->payment_method !== 'online'
            || ! $matches($order)
        ) {
            return response()->json(['message' => 'طلب الدفع غير صالح لهذه الباقة.'], 422);
        }

        return $order;
    }
}
