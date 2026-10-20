<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\FawaterakApiService;
use App\Services\FawaterakService;
use App\Services\KashierSettings;
use App\Services\PayPalService;
use App\Services\PayPalSettings;
use App\Services\PaymentGatewaySettings;
use App\Services\StripeService;
use App\Services\StripeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PaymentGatewaysController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage.system-settings');
    }

    public function index(): View
    {
        $fawaterakEnabled = PaymentGatewaySettings::isFawaterakEnabled();
        $fawaterakApi = app(FawaterakApiService::class);
        $fawaterakIframe = app(FawaterakService::class);
        $fawaterakIntegration = $fawaterakApi->integrationMode();
        $fawaterakConfigured = $fawaterakIntegration === 'api'
            ? $fawaterakApi->isConfigured()
            : $fawaterakIframe->isConfigured();
        $paymentGatewayFeePercent = Setting::getValue(PaymentGatewaySettings::FEE_PERCENT_SETTING_KEY) ?? '';

        return view('admin.payment-gateways.index', [
            'fawaterakEnabled' => $fawaterakEnabled,
            'fawaterakConfigured' => $fawaterakConfigured,
            'fawaterakIntegration' => $fawaterakIntegration,
            'paymentGatewayFeePercent' => $paymentGatewayFeePercent,
            'allowManualWithGateways' => PaymentGatewaySettings::allowsManualWithGateways(),
            'blocksManual' => PaymentGatewaySettings::blocksManualCheckout(),
            'enabledCount' => PaymentGatewaySettings::hubEnabledCount(),
            'paypalEnabled' => PayPalSettings::isEnabled(),
            'paypalConfigured' => PayPalSettings::isConfigured(),
            'paypalMode' => PayPalSettings::mode(),
            'paypalClientId' => PayPalSettings::clientId(),
            'paypalHasSecret' => PayPalSettings::hasStoredSecret() || PayPalSettings::clientSecret() !== '',
            'paypalWebhookId' => PayPalSettings::webhookId(),
            'paypalCurrency' => PayPalSettings::currency(),
            'stripeEnabled' => StripeSettings::isEnabled(),
            'stripeConfigured' => StripeSettings::isConfigured(),
            'stripeMode' => StripeSettings::mode(),
            'stripePublishableKey' => StripeSettings::publishableKey(),
            'stripeHasSecret' => StripeSettings::hasStoredSecret() || StripeSettings::secretKey() !== '',
            'stripeHasWebhookSecret' => StripeSettings::hasStoredWebhookSecret() || StripeSettings::webhookSecret() !== '',
            'stripeCurrency' => StripeSettings::currency(),
            'kashierEnabled' => KashierSettings::isEnabled(),
            'kashierConfigured' => KashierSettings::isConfigured(),
            'kashierMode' => KashierSettings::mode(),
            'kashierMid' => KashierSettings::mid(),
            'kashierHasApiKey' => KashierSettings::hasStoredApiKey() || KashierSettings::apiKey() !== '',
            'kashierHasSecret' => KashierSettings::hasStoredSecret() || KashierSettings::secret() !== '',
            'kashierCurrency' => KashierSettings::currency(),
            'kashierRedirectUrl' => KashierSettings::merchantRedirectUrl(),
            'paypalWebhookUrl' => url('/webhooks/paypal'),
            'stripeWebhookUrl' => url('/webhooks/stripe'),
            'kashierCallbackUrl' => url()->route('public.checkout.kashier.callback'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // PayPal مؤرشف: يُحفظ الربط للسجلات فقط ولا يُفعَّل من المركز.
        $paypalEnabled = false;
        $paypalClientId = trim((string) $request->input('paypal_client_id', ''));
        $paypalSecret = trim((string) $request->input('paypal_client_secret', ''));

        $stripeEnabled = $request->boolean('stripe_gateway_enabled');
        $stripePk = trim((string) $request->input('stripe_publishable_key', ''));
        $stripeSecret = trim((string) $request->input('stripe_secret_key', ''));
        $stripeWh = trim((string) $request->input('stripe_webhook_secret', ''));
        if ($stripeEnabled && $stripePk === '') {
            return back()->withErrors(['stripe_publishable_key' => 'Publishable Key مطلوب لتفعيل Stripe.'])->withInput();
        }
        if ($stripeEnabled && $stripeSecret === '' && ! StripeSettings::isConfigured()) {
            return back()->withErrors(['stripe_secret_key' => 'Secret Key مطلوب لتفعيل Stripe في أول ربط.'])->withInput();
        }

        $kashierEnabled = $request->boolean('kashier_gateway_enabled');
        $kashierMid = trim((string) $request->input('kashier_mid', ''));
        $kashierApiKey = trim((string) $request->input('kashier_api_key', ''));
        $kashierSecret = trim((string) $request->input('kashier_secret', ''));
        if ($kashierEnabled && $kashierMid === '') {
            return back()->withErrors(['kashier_mid' => 'MID مطلوب لتفعيل كاشير.'])->withInput();
        }
        if ($kashierEnabled && $kashierApiKey === '' && KashierSettings::apiKey() === '') {
            return back()->withErrors(['kashier_api_key' => 'API Key مطلوب لتفعيل كاشير في أول ربط.'])->withInput();
        }
        if ($kashierEnabled && $kashierSecret === '' && ! KashierSettings::isConfigured()) {
            return back()->withErrors(['kashier_secret' => 'Secret مطلوب لتفعيل كاشير في أول ربط.'])->withInput();
        }

        $redirectRaw = trim((string) $request->input('kashier_merchant_redirect_url', ''));
        if ($redirectRaw !== '' && ! filter_var($redirectRaw, FILTER_VALIDATE_URL)) {
            return back()->withErrors(['kashier_merchant_redirect_url' => 'رابط عودة كاشير غير صالح.'])->withInput();
        }

        Setting::setValue(
            PaymentGatewaySettings::SETTING_KEY,
            $request->boolean('fawaterak_gateway_enabled') ? '1' : null
        );

        PaymentGatewaySettings::setAllowManualWithGateways(
            $request->boolean('allow_manual_checkout_with_gateways')
        );

        $feeRaw = trim((string) $request->input('payment_gateway_fee_percent', ''));
        if ($feeRaw === '') {
            Setting::setValue(PaymentGatewaySettings::FEE_PERCENT_SETTING_KEY, null);
        } else {
            $feeVal = (float) str_replace(',', '.', $feeRaw);
            if ($feeVal < 0 || $feeVal > 100) {
                return back()->withErrors(['payment_gateway_fee_percent' => 'نسبة العمولة يجب أن تكون بين 0 و 100.'])->withInput();
            }
            Setting::setValue(PaymentGatewaySettings::FEE_PERCENT_SETTING_KEY, (string) round($feeVal, 4));
        }

        PayPalSettings::save([
            'enabled' => $paypalEnabled,
            'mode' => (string) $request->input('paypal_mode', 'sandbox'),
            'client_id' => $paypalClientId,
            'client_secret' => $paypalSecret !== '' ? $paypalSecret : null,
            'webhook_id' => (string) $request->input('paypal_webhook_id', ''),
            'currency' => (string) $request->input('paypal_currency', platform_currency()),
        ]);

        StripeSettings::save([
            'enabled' => $stripeEnabled,
            'mode' => (string) $request->input('stripe_mode', 'test'),
            'publishable_key' => $stripePk,
            'secret_key' => $stripeSecret !== '' ? $stripeSecret : null,
            'webhook_secret' => $stripeWh !== '' ? $stripeWh : null,
            'currency' => (string) $request->input('stripe_currency', platform_currency()),
        ]);

        KashierSettings::save([
            'enabled' => $kashierEnabled,
            'mode' => (string) $request->input('kashier_mode', 'test'),
            'mid' => $kashierMid,
            'api_key' => $kashierApiKey !== '' ? $kashierApiKey : null,
            'secret' => $kashierSecret !== '' ? $kashierSecret : null,
            'currency' => (string) $request->input('kashier_currency', platform_currency()),
            'merchant_redirect_url' => $redirectRaw,
        ]);

        return redirect()->route('admin.payment-gateways.index')
            ->with('success', 'تم حفظ تفعيل بوابات الدفع وبيانات الاتصال.');
    }

    public function testPaypal(Request $request): RedirectResponse
    {
        if (! PayPalSettings::isConfigured()) {
            return redirect()->route('admin.payment-gateways.index')
                ->withErrors(['paypal_client_id' => 'احفظ Client ID و Secret أولاً ثم اختبر الاتصال.']);
        }

        try {
            $result = app(PayPalService::class)->ping();

            return redirect()->route('admin.payment-gateways.index')
                ->with('success', 'اتصال PayPal ناجح (وضع '.($result['mode'] === 'live' ? 'Live' : 'Sandbox').') — البوابة مؤرشفة ولن تظهر للطالب.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.payment-gateways.index')
                ->withErrors(['paypal_client_secret' => $e->getMessage()]);
        }
    }

    public function testStripe(Request $request): RedirectResponse
    {
        if (! StripeSettings::isConfigured()) {
            return redirect()->route('admin.payment-gateways.index')
                ->withErrors(['stripe_secret_key' => 'احفظ Publishable + Secret أولاً ثم اختبر الاتصال.']);
        }

        try {
            $result = app(StripeService::class)->ping();

            return redirect()->route('admin.payment-gateways.index')
                ->with('success', 'اتصال Stripe ناجح (وضع '.($result['mode'] === 'live' ? 'Live' : 'Test').').');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.payment-gateways.index')
                ->withErrors(['stripe_secret_key' => $e->getMessage()]);
        }
    }
}
