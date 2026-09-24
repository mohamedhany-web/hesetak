<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PackageGift;
use App\Models\ServicePackage;
use App\Services\FawaterakApiService;
use App\Services\FawaterakService;
use App\Services\PackageCatalogFilterService;
use App\Services\PackageGiftService;
use App\Services\PaymentGatewaySettings;
use App\Services\ServiceSessionRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GiftPackageController extends Controller
{
    public function show(Request $request, PackageCatalogFilterService $catalog, ServiceSessionRateService $rates): View
    {
        $packageId = $request->integer('package') ?: null;
        $catalogData = $catalog->catalog(
            yearId: $request->filled('year') ? $request->integer('year') : null,
            subjectId: $request->filled('subject') ? $request->integer('subject') : null,
            curriculumType: $request->query('curriculum_type'),
        );

        $selectedPackage = null;
        if ($packageId) {
            $selectedPackage = ServicePackage::query()->storefront()->find($packageId);
        }
        if (! $selectedPackage) {
            $selectedPackage = $catalogData['packages']->first()['model'] ?? ServicePackage::query()->storefront()->ordered()->first();
        }

        $quote = $selectedPackage
            ? $rates->quotePackage(
                $selectedPackage,
                $catalogData['selected_year_id'],
                $catalogData['selected_curriculum_type']
            )
            : null;

        return view('public.gift-package', [
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => (app()->getLocale() === 'ar' ? 'إهداء باقة' : 'Gift a package').' — '.__('landing.nav.brand'),
            'pageDescription' => app()->getLocale() === 'ar'
                ? 'أهدِ باقة حصص لصديق — نفتح حسابه ونفعّل الباقة تلقائياً بعد الدفع.'
                : 'Gift lesson credits to a friend — we create their account and activate the package after payment.',
            'catalog' => $catalogData,
            'selectedPackage' => $selectedPackage,
            'quote' => $quote,
            'fawaterakEnabled' => PaymentGatewaySettings::isFawaterakEnabled(),
        ]);
    }

    public function store(Request $request, PackageGiftService $gifts): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'سجّل الدخول لإهداء باقة.')
                ->with('url.intended', route('public.gift-package.show'));
        }

        $data = $request->validate([
            'service_package_id' => ['required', 'integer', 'exists:service_packages,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'academic_subject_id' => ['nullable', 'integer', 'exists:academic_subjects,id'],
            'curriculum_type' => ['nullable', 'string', 'max:64'],
            'recipient_email' => ['required', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:64'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $package = ServicePackage::query()->storefront()->findOrFail($data['service_package_id']);

        if (strtolower($data['recipient_email']) === strtolower((string) $user->email)) {
            return back()->withInput()->with('error', 'لا يمكن إهداء الباقة لنفس بريد حسابك.');
        }

        if (! PaymentGatewaySettings::isFawaterakEnabled()) {
            return back()->withInput()->with('error', 'بوابة الدفع غير مفعّلة حالياً. تواصل مع الدعم.');
        }

        try {
            $created = $gifts->createPendingGiftOrder($user, $package, $data, 'online');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $request->session()->put('fawaterak_order_id', $created['order']->id);

        return redirect()->route('public.gift-package.pay', $created['order']);
    }

    public function pay(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) Auth::id(), 403);
        abort_unless($order->order_type === Order::TYPE_SERVICE_PACKAGE, 404);

        $meta = is_array($order->custom_package_data) ? $order->custom_package_data : [];
        abort_unless(! empty($meta['is_gift']), 404);

        $api = app(FawaterakApiService::class);
        $gatewayOn = PaymentGatewaySettings::isFawaterakEnabled();
        $useGateway = $gatewayOn && $api->isConfigured();
        $misconfigured = $gatewayOn && ! $api->isConfigured();

        return view('public.service-package-pay', [
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => 'دفع هدية الباقة — '.__('landing.nav.brand'),
            'order' => $order,
            'packageTitle' => 'إهداء: '.($order->servicePackage?->name ?? 'باقة'),
            'fawaterakUseGateway' => $useGateway,
            'fawaterakMisconfigured' => $misconfigured,
            'fawaterakIntegration' => $api->integrationMode(),
            'paypalUseGateway' => false,
            'paypalMisconfigured' => false,
            'prepareRoute' => route('public.gift-package.fawaterak.prepare', $order),
            'methodsRoute' => null,
            'payRoute' => null,
        ]);
    }

    public function fawaterakPrepare(Request $request, Order $order): JsonResponse
    {
        abort_unless(Auth::check() && (int) $order->user_id === (int) Auth::id(), 403);
        $meta = is_array($order->custom_package_data) ? $order->custom_package_data : [];
        abort_unless(! empty($meta['is_gift']), 404);
        abort_unless($order->status === Order::STATUS_PENDING, 422);

        $user = Auth::user();
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'أضف بريداً صالحاً في ملفك قبل الدفع.'], 422);
        }

        $request->session()->put('fawaterak_order_id', $order->id);

        $api = app(FawaterakApiService::class);
        $iframe = app(FawaterakService::class);
        $integration = $api->integrationMode();
        $itemName = Str::limit('إهداء: '.($order->servicePackage?->name ?? 'باقة'), 120);

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
        $phone = preg_replace('/\D/', '', (string) ($user->phone ?? '')) ?: '0000000000';
        $currency = $order->currencyCode() ?: (string) config('currency.code', 'SAR');
        $cartTotal = number_format((float) $order->amount, 2, '.', '');
        $bearer = trim((string) config('fawaterak.plugin_bearer_token', ''))
            ?: trim((string) config('fawaterak.vendor_key', ''));

        $pluginConfig = [
            'envType' => $iframe->envType(),
            'hashKey' => $iframe->generateHashKey(),
            'token' => $bearer,
            'style' => ['listing' => 'horizontal'],
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
                'cartItems' => [[
                    'name' => $itemName,
                    'price' => $cartTotal,
                    'quantity' => '1',
                ]],
                'payLoad' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) $user->id,
                    'is_gift' => '1',
                    'service_package_id' => (string) ($order->service_package_id ?? ''),
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

    public function claim(string $token): View
    {
        $gift = PackageGift::query()
            ->with(['servicePackage', 'buyer', 'recipient'])
            ->where('claim_token', $token)
            ->firstOrFail();

        return view('public.gift-package-claim', [
            'mcActive' => 'pricing',
            'bodyClass' => 'mc-body--dir',
            'pageTitle' => 'هدية الباقة — '.__('landing.nav.brand'),
            'gift' => $gift,
        ]);
    }
}
