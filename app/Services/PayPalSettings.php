<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PayPalSettings
{
    public const ENABLED_KEY = 'paypal_gateway_enabled';

    public const MODE_KEY = 'paypal_mode';

    public const CLIENT_ID_KEY = 'paypal_client_id';

    public const SECRET_KEY = 'paypal_client_secret';

    public const WEBHOOK_ID_KEY = 'paypal_webhook_id';

    public const CURRENCY_KEY = 'paypal_currency';

    public const MODES = ['sandbox', 'live'];

    public const CURRENCIES = ['SAR', 'USD', 'EUR', 'GBP', 'EGP'];

    public static function isEnabled(): bool
    {
        return Setting::getValue(self::ENABLED_KEY) === '1';
    }

    public static function isConfigured(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function isReady(): bool
    {
        return self::isEnabled() && self::isConfigured();
    }

    public static function isMisconfigured(): bool
    {
        return self::isEnabled() && ! self::isConfigured();
    }

    public static function mode(): string
    {
        $mode = strtolower(self::storedOrEnv(self::MODE_KEY, (string) config('paypal.mode', 'sandbox')));

        return in_array($mode, self::MODES, true) ? $mode : 'sandbox';
    }

    public static function isLive(): bool
    {
        return self::mode() === 'live';
    }

    public static function clientId(): string
    {
        return trim(self::storedOrEnv(self::CLIENT_ID_KEY, (string) config('paypal.client_id', '')));
    }

    public static function clientSecret(): string
    {
        $raw = Setting::getValue(self::SECRET_KEY);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim((string) config('paypal.client_secret', ''));
    }

    public static function hasStoredSecret(): bool
    {
        $raw = Setting::getValue(self::SECRET_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function webhookId(): string
    {
        return trim(self::storedOrEnv(self::WEBHOOK_ID_KEY, (string) config('paypal.webhook_id', '')));
    }

    public static function currency(): string
    {
        $currency = strtoupper(self::storedOrEnv(self::CURRENCY_KEY, (string) config('paypal.currency', config('currency.code', 'SAR'))));

        return in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency();
    }

    public static function apiBaseUrl(): string
    {
        return self::isLive()
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     mode?: string,
     *     client_id?: string,
     *     client_secret?: string|null,
     *     webhook_id?: string,
     *     currency?: string
     * }  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::ENABLED_KEY, ! empty($data['enabled']) ? '1' : null);

        $mode = strtolower(trim((string) ($data['mode'] ?? 'sandbox')));
        Setting::setValue(self::MODE_KEY, in_array($mode, self::MODES, true) ? $mode : 'sandbox');

        $clientId = trim((string) ($data['client_id'] ?? ''));
        Setting::setValue(self::CLIENT_ID_KEY, $clientId !== '' ? $clientId : null);

        $secret = $data['client_secret'] ?? null;
        if (is_string($secret) && trim($secret) !== '') {
            Setting::setValue(self::SECRET_KEY, Crypt::encryptString(trim($secret)));
        }

        $webhookId = trim((string) ($data['webhook_id'] ?? ''));
        Setting::setValue(self::WEBHOOK_ID_KEY, $webhookId !== '' ? $webhookId : null);

        $currency = strtoupper(trim((string) ($data['currency'] ?? platform_currency())));
        Setting::setValue(self::CURRENCY_KEY, in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency());
    }

    private static function storedOrEnv(string $key, string $fallback): string
    {
        $stored = Setting::getValue($key);

        return (is_string($stored) && $stored !== '') ? $stored : $fallback;
    }
}
