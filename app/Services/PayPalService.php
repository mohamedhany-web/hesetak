<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayPalService
{
    public function isConfigured(): bool
    {
        return PayPalSettings::isConfigured();
    }

    public function isReady(): bool
    {
        return PayPalSettings::isReady();
    }

    /**
     * طلب توكن OAuth للتأكد أن Client ID / Secret صالحان.
     */
    public function ping(): array
    {
        $token = $this->accessToken(true);

        return [
            'ok' => $token !== '',
            'mode' => PayPalSettings::mode(),
            'base_url' => PayPalSettings::apiBaseUrl(),
        ];
    }

    /**
     * @return array{id: string, approve_url: string, status: string}
     */
    public function createOrder(
        float $amount,
        string $currency,
        string $returnUrl,
        string $cancelUrl,
        string $customId,
        string $description
    ): array {
        $currency = strtoupper($currency);
        if (! in_array($currency, PayPalSettings::CURRENCIES, true)) {
            $currency = PayPalSettings::currency();
        }

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'glottical-'.$customId,
                'custom_id' => $customId,
                'invoice_id' => 'GLOT-'.$customId,
                'description' => mb_substr($description, 0, 120),
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'brand_name' => mb_substr((string) config('app.name', 'حصتك'), 0, 120),
                'locale' => app()->getLocale() === 'ar' ? 'ar-EG' : 'en-US',
                'landing_page' => 'LOGIN',
                'user_action' => 'PAY_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $response = $this->request('post', '/v2/checkout/orders', $payload, [
            'PayPal-Request-Id' => 'glottical-order-'.$customId,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'تعذّر إنشاء طلب PayPal.'));
        }

        $json = $response->json() ?: [];
        $id = (string) ($json['id'] ?? '');
        $approve = collect($json['links'] ?? [])->firstWhere('rel', 'approve');
        $approveUrl = is_array($approve) ? (string) ($approve['href'] ?? '') : '';

        if ($id === '' || $approveUrl === '') {
            throw new RuntimeException('PayPal لم يُرجع رابط الموافقة.');
        }

        return [
            'id' => $id,
            'approve_url' => $approveUrl,
            'status' => (string) ($json['status'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $paypalOrderId): array
    {
        $response = $this->request('get', '/v2/checkout/orders/'.rawurlencode($paypalOrderId));
        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'تعذّر قراءة طلب PayPal.'));
        }

        return $response->json() ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    public function captureOrder(string $paypalOrderId): array
    {
            $response = $this->request('post', '/v2/checkout/orders/'.rawurlencode($paypalOrderId).'/capture');

        if ($response->successful()) {
            return $response->json() ?: [];
        }

        $json = $response->json() ?: [];
        $issue = strtoupper((string) data_get($json, 'details.0.issue', ''));
        if ($response->status() === 422 && str_contains($issue, 'ORDER_ALREADY_CAPTURED')) {
            return $this->getOrder($paypalOrderId);
        }

        throw new RuntimeException($this->errorMessage($response, 'تعذّر تأكيد الدفع من PayPal.'));
    }

    public function isOrderPaid(array $paypalOrder): bool
    {
        $status = strtoupper((string) ($paypalOrder['status'] ?? ''));
        if ($status === 'COMPLETED') {
            return true;
        }

        $captureStatus = strtoupper((string) data_get(
            $paypalOrder,
            'purchase_units.0.payments.captures.0.status',
            ''
        ));

        return $captureStatus === 'COMPLETED';
    }

    public function captureId(array $paypalOrder): ?string
    {
        $id = data_get($paypalOrder, 'purchase_units.0.payments.captures.0.id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function customId(array $paypalOrder): ?string
    {
        $id = data_get($paypalOrder, 'purchase_units.0.custom_id');

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return array{amount: float, currency: string}|null
     */
    public function capturedMoney(array $paypalOrder): ?array
    {
        $value = data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.value');
        $currency = data_get($paypalOrder, 'purchase_units.0.payments.captures.0.amount.currency_code');
        if ($value === null || $currency === null || $currency === '') {
            $value = data_get($paypalOrder, 'purchase_units.0.amount.value');
            $currency = data_get($paypalOrder, 'purchase_units.0.amount.currency_code');
        }
        if ($value === null || $currency === null || trim((string) $currency) === '') {
            return null;
        }

        return [
            'amount' => (float) $value,
            'currency' => strtoupper(trim((string) $currency)),
        ];
    }

    public function assertMatchesOrder(array $paypalOrder, float $orderAmount, string $orderCurrency): void
    {
        $money = $this->capturedMoney($paypalOrder);
        if ($money === null || ! PaymentGatewaySettings::paidMatchesOrder(
            $orderAmount,
            $orderCurrency,
            $money['amount'],
            $money['currency']
        )) {
            throw new RuntimeException('مبلغ أو عملة PayPal لا تطابق الطلب.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function assertValidWebhook(\Illuminate\Http\Request $request, array $payload): void
    {
        $webhookId = PayPalSettings::webhookId();
        if ($webhookId === '') {
            throw new RuntimeException('Webhook ID غير مضبوط؛ لن يُقبل إشعار PayPal.');
        }

        $authAlgo = trim((string) $request->header('PAYPAL-AUTH-ALGO', ''));
        $certUrl = trim((string) $request->header('PAYPAL-CERT-URL', ''));
        $transmissionId = trim((string) $request->header('PAYPAL-TRANSMISSION-ID', ''));
        $transmissionSig = trim((string) $request->header('PAYPAL-TRANSMISSION-SIG', ''));
        $transmissionTime = trim((string) $request->header('PAYPAL-TRANSMISSION-TIME', ''));

        if ($authAlgo === '' || $certUrl === '' || $transmissionId === '' || $transmissionSig === '' || $transmissionTime === '') {
            throw new RuntimeException('ترويسات توقيع PayPal ناقصة.');
        }

        $host = strtolower((string) (parse_url($certUrl, PHP_URL_HOST) ?: ''));
        if ($host === '' || ! str_ends_with($host, '.paypal.com')) {
            throw new RuntimeException('شهادة توقيع PayPal غير صالحة.');
        }

        $response = $this->request('post', '/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $authAlgo,
            'cert_url' => $certUrl,
            'transmission_id' => $transmissionId,
            'transmission_sig' => $transmissionSig,
            'transmission_time' => $transmissionTime,
            'webhook_id' => $webhookId,
            'webhook_event' => $payload,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'فشل التحقق من توقيع PayPal.'));
        }

        $status = strtoupper((string) $response->json('verification_status', ''));
        if ($status !== 'SUCCESS') {
            throw new RuntimeException('توقيع إشعار PayPal غير صالح.');
        }
    }

    private function accessToken(bool $forceRefresh = false): string
    {
        $clientId = PayPalSettings::clientId();
        $secret = PayPalSettings::clientSecret();
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('بيانات اتصال PayPal غير مكتملة.');
        }

        $cacheKey = 'paypal_oauth_'.sha1(PayPalSettings::mode().'|'.$clientId);
        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $response = Http::timeout((int) config('paypal.timeout', 30))
            ->acceptJson()
            ->asForm()
            ->withBasicAuth($clientId, $secret)
            ->post(PayPalSettings::apiBaseUrl().'/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            Log::warning('PayPal OAuth failed', [
                'status' => $response->status(),
                'mode' => PayPalSettings::mode(),
            ]);
            throw new RuntimeException($this->errorMessage($response, 'فشل التحقق من حساب PayPal. راجع Client ID و Secret ووضع Sandbox/Live.'));
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new RuntimeException('PayPal لم يُرجع توكن وصول.');
        }

        $expires = max(60, (int) $response->json('expires_in', 300) - 60);
        Cache::put($cacheKey, $token, now()->addSeconds($expires));

        return $token;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function request(string $method, string $path, mixed $body = null, array $headers = []): Response
    {
        $pending = Http::timeout((int) config('paypal.timeout', 30))
            ->acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->withHeaders(array_merge([
                'PayPal-Partner-Attribution-Id' => 'حصتك_Checkout',
            ], $headers));

        $url = PayPalSettings::apiBaseUrl().$path;

        return match ($method) {
            'get' => $pending->get($url),
            'post' => $body === null
                ? $pending->withBody('{}', 'application/json')->post($url)
                : $pending->post($url, $body),
            default => throw new RuntimeException('Unsupported PayPal HTTP method.'),
        };
    }

    private function errorMessage(Response $response, string $fallback): string
    {
        $message = $response->json('message')
            ?? data_get($response->json(), 'details.0.description')
            ?? $fallback;

        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
