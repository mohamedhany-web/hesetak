<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class StripeSettings
{
    public const ENABLED_KEY = 'stripe_gateway_enabled';

    public const MODE_KEY = 'stripe_mode';

    public const PUBLISHABLE_KEY = 'stripe_publishable_key';

    public const SECRET_KEY = 'stripe_secret_key';

    public const WEBHOOK_SECRET_KEY = 'stripe_webhook_secret';

    public const CURRENCY_KEY = 'stripe_currency';

    public const MODES = ['test', 'live'];

    public const CURRENCIES = ['SAR', 'USD', 'EUR', 'GBP', 'AED', 'EGP'];

    public static function isEnabled(): bool
    {
        return Setting::getValue(self::ENABLED_KEY) === '1';
    }

    public static function isConfigured(): bool
    {
        return self::secretKey() !== '' && self::publishableKey() !== '';
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
        $mode = strtolower(self::storedOrEnv(self::MODE_KEY, (string) config('stripe.mode', 'test')));

        return in_array($mode, self::MODES, true) ? $mode : 'test';
    }

    public static function isLive(): bool
    {
        return self::mode() === 'live';
    }

    public static function publishableKey(): string
    {
        return trim(self::storedOrEnv(self::PUBLISHABLE_KEY, (string) config('stripe.'.self::mode().'.publishable_key', '')));
    }

    public static function secretKey(): string
    {
        $raw = Setting::getValue(self::SECRET_KEY);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim((string) config('stripe.'.self::mode().'.secret_key', ''));
    }

    public static function hasStoredSecret(): bool
    {
        $raw = Setting::getValue(self::SECRET_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function webhookSecret(): string
    {
        $raw = Setting::getValue(self::WEBHOOK_SECRET_KEY);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim((string) config('stripe.webhook_secret', ''));
    }

    public static function hasStoredWebhookSecret(): bool
    {
        $raw = Setting::getValue(self::WEBHOOK_SECRET_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function currency(): string
    {
        $currency = strtoupper(self::storedOrEnv(self::CURRENCY_KEY, (string) config('currency.code', 'SAR')));

        return in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency();
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     mode?: string,
     *     publishable_key?: string,
     *     secret_key?: string|null,
     *     webhook_secret?: string|null,
     *     currency?: string
     * }  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::ENABLED_KEY, ! empty($data['enabled']) ? '1' : null);

        $mode = strtolower(trim((string) ($data['mode'] ?? 'test')));
        Setting::setValue(self::MODE_KEY, in_array($mode, self::MODES, true) ? $mode : 'test');

        $pk = trim((string) ($data['publishable_key'] ?? ''));
        Setting::setValue(self::PUBLISHABLE_KEY, $pk !== '' ? $pk : null);

        $secret = $data['secret_key'] ?? null;
        if (is_string($secret) && trim($secret) !== '') {
            Setting::setValue(self::SECRET_KEY, Crypt::encryptString(trim($secret)));
        }

        $wh = $data['webhook_secret'] ?? null;
        if (is_string($wh) && trim($wh) !== '') {
            Setting::setValue(self::WEBHOOK_SECRET_KEY, Crypt::encryptString(trim($wh)));
        }

        $currency = strtoupper(trim((string) ($data['currency'] ?? platform_currency())));
        Setting::setValue(self::CURRENCY_KEY, in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency());
    }

    private static function storedOrEnv(string $key, string $fallback): string
    {
        $stored = Setting::getValue($key);

        return (is_string($stored) && $stored !== '') ? $stored : $fallback;
    }
}
