<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * NeoLeap hosted checkout + tranportal (purchase / inquiry / refund / void).
 *
 * Spec pattern (merchant docs + public NeoLeap Laravel packages):
 * - AES-256-CBC encrypt JSON trandata (PKCS7)
 * - Hosted initiate → status=1 + result "paymentId:redirectUrl"
 * - Callback posts encrypted trandata; CAPTURED = success
 */
class NeoLeapService
{
    public const ACTION_PURCHASE = 1;

    public const ACTION_AUTH = 2;

    public const ACTION_REFUND = 3;

    public const ACTION_VOID = 4;

    public const ACTION_INQUIRY = 8;

    /**
     * Initiate hosted payment. Returns paymentId + redirect URL.
     *
     * @param  array{udf1?:string,udf2?:string,udf3?:string,udf4?:string,udf5?:string}  $udf
     * @return array{payment_id: string, redirect_url: string, track_id: string, raw: array}
     */
    public function initiateHostedPayment(
        float $amount,
        string $trackId,
        string $responseUrl,
        string $errorUrl,
        string $currency = 'SAR',
        string $lang = 'AR',
        array $udf = [],
    ): array {
        $this->assertConfigured();

        $trackId = trim($trackId);
        if ($trackId === '' || $amount < 0.01) {
            throw new RuntimeException('مبلغ أو رقم تتبع الدفع غير صالح.');
        }

        $payload = $this->baseTranPayload(
            amount: $amount,
            action: self::ACTION_PURCHASE,
            trackId: $trackId,
            currency: $currency,
            responseUrl: $responseUrl,
            errorUrl: $errorUrl,
            lang: $lang,
            udf: $udf,
        );

        $trandata = $this->encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $body = [[
            'id' => NeoLeapSettings::tranportalId(),
            'trandata' => $trandata,
            'responseURL' => $responseUrl,
            'errorURL' => $errorUrl,
        ]];

        $raw = $this->postJson(NeoLeapSettings::hostedUrl(), $body);
        $row = $this->firstRow($raw);
        $status = (string) ($row['status'] ?? '');
        $result = (string) ($row['result'] ?? '');

        if ($status !== '1' || $result === '' || ! str_contains($result, ':')) {
            $errorText = $result !== '' ? $result : json_encode($raw, JSON_UNESCAPED_UNICODE);
            Log::warning('NeoLeap hosted initiate failed', ['track_id' => $trackId, 'response' => $raw]);
            throw new RuntimeException('تعذّر بدء الدفع عبر NeoLeap: '.$errorText);
        }

        [$paymentId, $redirectUrl] = explode(':', $result, 2);
        $paymentId = trim($paymentId);
        $redirectUrl = trim($redirectUrl);
        if ($paymentId === '' || $redirectUrl === '' || ! filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('استجابة NeoLeap لا تحتوي رابط دفع صالح.');
        }

        return [
            'payment_id' => $paymentId,
            'redirect_url' => $redirectUrl,
            'track_id' => $trackId,
            'raw' => $raw,
        ];
    }

    /**
     * Decrypt + parse callback trandata from NeoLeap (responseURL / errorURL).
     *
     * @return array{
     *   success: bool,
     *   result: string,
     *   track_id: ?string,
     *   payment_id: ?string,
     *   auth: ?string,
     *   ref: ?string,
     *   amt: ?float,
     *   currency: ?string,
     *   raw: array
     * }
     */
    public function parseCallbackTrandata(string $trandata): array
    {
        $this->assertConfigured();

        $trandata = trim(urldecode($trandata));
        if ($trandata === '') {
            throw new RuntimeException('trandata فارغ من NeoLeap.');
        }

        $plain = $this->decrypt($trandata);
        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) {
            // بعض الردود تُرجع مصفوفة داخل مصفوفة
            throw new RuntimeException('تعذّر قراءة trandata بعد فك التشفير.');
        }

        $row = $decoded;
        if (array_is_list($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
            $row = $decoded[0];
        }

        $result = strtoupper(trim((string) ($row['result'] ?? $row['Result'] ?? '')));
        $amtRaw = $row['amt'] ?? $row['amount'] ?? null;
        $currencyCode = (string) ($row['currencyCode'] ?? $row['currencycode'] ?? '');
        $currency = $this->currencyFromCode($currencyCode) ?: NeoLeapSettings::currency();

        return [
            'success' => in_array($result, ['CAPTURED', 'SUCCESS', 'APPROVED'], true),
            'result' => $result !== '' ? $result : (string) ($row['result'] ?? ''),
            'track_id' => isset($row['trackId']) ? (string) $row['trackId'] : (isset($row['trackid']) ? (string) $row['trackid'] : null),
            'payment_id' => isset($row['paymentId']) ? (string) $row['paymentId'] : (isset($row['paymentid']) ? (string) $row['paymentid'] : null),
            'auth' => isset($row['auth']) ? (string) $row['auth'] : null,
            'ref' => isset($row['ref']) ? (string) $row['ref'] : (isset($row['tranid']) ? (string) $row['tranid'] : null),
            'amt' => is_numeric($amtRaw) ? round((float) $amtRaw, 2) : null,
            'currency' => $currency,
            'raw' => $row,
        ];
    }

    /**
     * Transaction inquiry via tranportal.htm
     *
     * @return array<string, mixed>
     */
    public function inquire(string $trackId, ?string $paymentId = null, ?string $transId = null): array
    {
        $this->assertConfigured();

        $payload = [
            'id' => NeoLeapSettings::tranportalId(),
            'password' => NeoLeapSettings::password(),
            'action' => (string) self::ACTION_INQUIRY,
            'trackId' => $trackId,
            'udf5' => 'TrackID',
        ];
        if ($paymentId) {
            $payload['paymentId'] = $paymentId;
        }
        if ($transId) {
            $payload['transId'] = $transId;
            $payload['udf5'] = 'TranID';
        }

        return $this->postEncryptedTranportal($payload);
    }

    /**
     * Refund (action 3) — amount optional (full if omitted depends on gateway).
     *
     * @return array<string, mixed>
     */
    public function refund(string $trackId, float $amount, string $transId, string $currency = 'SAR'): array
    {
        $this->assertConfigured();

        $payload = $this->baseTranPayload(
            amount: $amount,
            action: self::ACTION_REFUND,
            trackId: $trackId,
            currency: $currency,
            responseUrl: url('/checkout/neoleap/return'),
            errorUrl: url('/checkout/neoleap/error'),
            lang: 'AR',
        );
        $payload['transId'] = $transId;
        $payload['udf5'] = 'TranID';

        return $this->postEncryptedTranportal($payload);
    }

    /**
     * Void (action 4) — same-day reversal before settlement.
     *
     * @return array<string, mixed>
     */
    public function void(string $trackId, string $transId, string $currency = 'SAR'): array
    {
        $this->assertConfigured();

        $payload = [
            'id' => NeoLeapSettings::tranportalId(),
            'password' => NeoLeapSettings::password(),
            'action' => (string) self::ACTION_VOID,
            'currencyCode' => $this->currencyCode($currency),
            'trackId' => $trackId,
            'transId' => $transId,
            'udf5' => 'TranID',
        ];

        return $this->postEncryptedTranportal($payload);
    }

    /**
     * Light connectivity check — validates credentials shape + endpoint reachability.
     *
     * @return array{ok: bool, mode: string, hosted_status: int|null, message: string}
     */
    public function ping(): array
    {
        $this->assertConfigured();

        try {
            $response = Http::timeout((int) config('neoleap.http_timeout', 45))
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->post(NeoLeapSettings::hostedUrl(), [[
                    'id' => NeoLeapSettings::tranportalId(),
                    'trandata' => 'ping',
                    'responseURL' => url('/'),
                    'errorURL' => url('/'),
                ]]);

            return [
                'ok' => $response->status() > 0,
                'mode' => NeoLeapSettings::mode(),
                'hosted_status' => $response->status(),
                'message' => 'تم الوصول لـ hosted.htm (HTTP '.$response->status().'). ضع بيانات التاجر الصحيحة ثم جرّب دفعة تجريبية.',
            ];
        } catch (Throwable $e) {
            throw new RuntimeException('تعذّر الاتصال بـ NeoLeap: '.$e->getMessage(), 0, $e);
        }
    }

    public function assertMatchesOrder(float $orderAmount, string $orderCurrency, ?float $paidAmount, ?string $paidCurrency): void
    {
        if ($paidAmount === null || $paidCurrency === null || $paidCurrency === '') {
            throw new RuntimeException('استجابة NeoLeap بلا مبلغ/عملة للتحقق.');
        }

        if (! PaymentGatewaySettings::paidMatchesOrder($orderAmount, $orderCurrency, $paidAmount, $paidCurrency)) {
            throw new RuntimeException('المبلغ أو العملة من NeoLeap لا يطابقان الطلب.');
        }
    }

    public function encrypt(string $plain): string
    {
        $key = $this->normalizeBinary(NeoLeapSettings::encryptionKey(), 32, 'Encryption Key');
        $iv = $this->normalizeBinary(NeoLeapSettings::encryptionIv(), 16, 'Encryption IV');

        $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('فشل تشفير trandata (AES-256-CBC).');
        }

        return strtoupper(bin2hex($cipher));
    }

    public function decrypt(string $hexOrCipher): string
    {
        $key = $this->normalizeBinary(NeoLeapSettings::encryptionKey(), 32, 'Encryption Key');
        $iv = $this->normalizeBinary(NeoLeapSettings::encryptionIv(), 16, 'Encryption IV');

        $raw = $hexOrCipher;
        if (ctype_xdigit($hexOrCipher) && (strlen($hexOrCipher) % 2) === 0) {
            $bin = @hex2bin($hexOrCipher);
            if ($bin !== false) {
                $raw = $bin;
            }
        } else {
            $decoded = base64_decode($hexOrCipher, true);
            if ($decoded !== false) {
                $raw = $decoded;
            }
        }

        $plain = openssl_decrypt($raw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new RuntimeException('فشل فك تشفير trandata من NeoLeap.');
        }

        return $plain;
    }

    /**
     * @param  array{udf1?:string,udf2?:string,udf3?:string,udf4?:string,udf5?:string}  $udf
     * @return array<string, string>
     */
    private function baseTranPayload(
        float $amount,
        int $action,
        string $trackId,
        string $currency,
        string $responseUrl,
        string $errorUrl,
        string $lang,
        array $udf = [],
    ): array {
        $payload = [
            'id' => NeoLeapSettings::tranportalId(),
            'password' => NeoLeapSettings::password(),
            'action' => (string) $action,
            'currencyCode' => $this->currencyCode($currency),
            'amt' => $this->formatAmount($amount),
            'responseURL' => $responseUrl,
            'errorURL' => $errorUrl,
            'trackId' => $trackId,
            'langid' => strtoupper($lang) === 'EN' ? 'EN' : 'AR',
        ];

        foreach (['udf1', 'udf2', 'udf3', 'udf4', 'udf5'] as $key) {
            if (! empty($udf[$key])) {
                $payload[$key] = (string) $udf[$key];
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function postEncryptedTranportal(array $payload): array
    {
        $trandata = $this->encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $body = [[
            'id' => NeoLeapSettings::tranportalId(),
            'trandata' => $trandata,
        ]];

        $raw = $this->postJson(NeoLeapSettings::tranportalUrl(), $body);
        $row = $this->firstRow($raw);

        // إن رجعت trandata مشفّرة في الرد، نفكها
        if (! empty($row['trandata']) && is_string($row['trandata'])) {
            try {
                $inner = json_decode($this->decrypt($row['trandata']), true);
                if (is_array($inner)) {
                    return array_is_list($inner) && isset($inner[0]) && is_array($inner[0])
                        ? $inner[0]
                        : $inner;
                }
            } catch (Throwable) {
                // أعد الصف كما هو
            }
        }

        return $row;
    }

    /**
     * @param  array<int|string, mixed>  $body
     * @return array<int|string, mixed>
     */
    private function postJson(string $url, array $body): array
    {
        $response = Http::timeout((int) config('neoleap.http_timeout', 45))
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->post($url, $body);

        if (! $response->successful() && $response->status() >= 500) {
            throw new RuntimeException('NeoLeap HTTP '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            $trim = trim($response->body());
            $json = json_decode($trim, true);
        }
        if (! is_array($json)) {
            Log::warning('NeoLeap non-JSON response', ['url' => $url, 'body' => substr($response->body(), 0, 500)]);
            throw new RuntimeException('استجابة NeoLeap ليست JSON صالحاً.');
        }

        return $json;
    }

    /**
     * @param  array<int|string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function firstRow(array $raw): array
    {
        if (array_is_list($raw) && isset($raw[0]) && is_array($raw[0])) {
            return $raw[0];
        }

        return $raw;
    }

    private function formatAmount(float $amount): string
    {
        return number_format(round($amount, 3), 3, '.', '');
    }

    private function currencyCode(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        $map = (array) config('neoleap.currency_codes', []);

        return (string) ($map[$currency] ?? $map['SAR'] ?? '682');
    }

    private function currencyFromCode(string $code): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }
        $map = (array) config('neoleap.currency_codes', []);
        $flip = array_flip($map);

        return isset($flip[$code]) ? (string) $flip[$code] : null;
    }

    private function normalizeBinary(string $value, int $bytes, string $label): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new RuntimeException($label.' مطلوب لـ NeoLeap.');
        }

        if (ctype_xdigit($value) && strlen($value) === ($bytes * 2)) {
            $bin = hex2bin($value);
            if ($bin !== false && strlen($bin) === $bytes) {
                return $bin;
            }
        }

        if (strlen($value) === $bytes) {
            return $value;
        }

        throw new RuntimeException($label.' يجب أن يكون '.$bytes.' بايت (أو '.($bytes * 2).' حرف hex).');
    }

    private function assertConfigured(): void
    {
        if (! NeoLeapSettings::isConfigured()) {
            throw new RuntimeException('إعدادات NeoLeap غير مكتملة (Tranportal ID / Password / Key / IV).');
        }
    }
}
