<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeService
{
    public function isReady(): bool
    {
        return StripeSettings::isReady();
    }

    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(
        float $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        string $orderId,
        string $itemTitle,
        ?string $customerEmail = null
    ): array {
        if (! $this->isReady()) {
            throw new RuntimeException('Stripe غير جاهز (مفعّل + مفاتيح).');
        }

        $currency = strtolower(trim($currency));
        $unitAmount = (int) round($amount * 100);
        if ($unitAmount < 1) {
            throw new RuntimeException('مبلغ Stripe غير صالح.');
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $orderId,
            'metadata[order_id]' => (string) $orderId,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => $unitAmount,
            'line_items[0][price_data][product_data][name]' => mb_substr($itemTitle !== '' ? $itemTitle : ('Order #'.$orderId), 0, 120),
        ];

        if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $payload['customer_email'] = $customerEmail;
        }

        $response = $this->request('post', '/v1/checkout/sessions', $payload);
        $id = (string) ($response['id'] ?? '');
        $url = (string) ($response['url'] ?? '');
        if ($id === '' || $url === '') {
            throw new RuntimeException('استجابة Stripe ناقصة (session id/url).');
        }

        return ['id' => $id, 'url' => $url];
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array
    {
        return $this->request('get', '/v1/checkout/sessions/'.rawurlencode($sessionId));
    }

    public function isSessionPaid(array $session): bool
    {
        $status = strtolower((string) ($session['payment_status'] ?? ''));
        $sessionStatus = strtolower((string) ($session['status'] ?? ''));

        return $status === 'paid' || ($status === 'no_payment_required' && $sessionStatus === 'complete');
    }

    public function assertMatchesOrder(array $session, float $orderAmount, string $orderCurrency): void
    {
        $paidAmount = isset($session['amount_total']) ? ((float) $session['amount_total']) / 100 : null;
        $paidCurrency = strtoupper((string) ($session['currency'] ?? ''));
        if ($paidAmount === null || $paidCurrency === '') {
            throw new RuntimeException('تعذّر قراءة مبلغ/عملة جلسة Stripe.');
        }
        if (! PaymentGatewaySettings::paidMatchesOrder($orderAmount, $orderCurrency, $paidAmount, $paidCurrency)) {
            throw new RuntimeException('مبلغ أو عملة Stripe لا يطابقان الطلب.');
        }
    }

    public function paymentIntentId(array $session): ?string
    {
        $pi = $session['payment_intent'] ?? null;
        if (is_string($pi) && $pi !== '') {
            return $pi;
        }
        if (is_array($pi) && ! empty($pi['id'])) {
            return (string) $pi['id'];
        }

        return null;
    }

    public function orderIdFromSession(array $session): ?string
    {
        $ref = $session['client_reference_id'] ?? null;
        if (is_string($ref) && $ref !== '') {
            return $ref;
        }
        $meta = $session['metadata']['order_id'] ?? null;

        return is_string($meta) && $meta !== '' ? $meta : null;
    }

    /**
     * التحقق من توقيع Webhook الرسمي لـ Stripe.
     *
     * @return array<string, mixed>
     */
    public function assertValidWebhook(Request $request): array
    {
        $secret = StripeSettings::webhookSecret();
        if ($secret === '') {
            throw new RuntimeException('STRIPE webhook secret غير مضبوط.');
        }

        $sigHeader = (string) $request->header('Stripe-Signature', '');
        if ($sigHeader === '') {
            throw new RuntimeException('توقيع Stripe مفقود.');
        }

        $payload = $request->getContent();
        $parts = [];
        foreach (explode(',', $sigHeader) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, null);
            if ($k && $v !== null) {
                $parts[$k][] = $v;
            }
        }
        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];
        if (! $timestamp || $signatures === []) {
            throw new RuntimeException('صيغة توقيع Stripe غير صالحة.');
        }
        if (abs(time() - (int) $timestamp) > 300) {
            throw new RuntimeException('توقيع Stripe منتهي الصلاحية.');
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);
        $ok = false;
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            throw new RuntimeException('توقيع Stripe غير مطابق.');
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Payload Stripe غير صالح.');
        }

        return $decoded;
    }

    public function ping(): array
    {
        $this->request('get', '/v1/balance');

        return ['mode' => StripeSettings::mode()];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $form = []): array
    {
        $secret = StripeSettings::secretKey();
        if ($secret === '') {
            throw new RuntimeException('مفتاح Stripe السري فارغ.');
        }

        $pending = Http::timeout((int) config('stripe.timeout', 30))
            ->withToken($secret)
            ->asForm()
            ->acceptJson();

        $url = 'https://api.stripe.com'.$path;
        $response = strtolower($method) === 'get'
            ? $pending->get($url, $form)
            : $pending->post($url, $form);

        if (! $response->successful()) {
            $message = data_get($response->json(), 'error.message')
                ?: ('Stripe HTTP '.$response->status());
            throw new RuntimeException((string) $message);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('استجابة Stripe غير متوقعة.');
        }

        return $json;
    }
}
