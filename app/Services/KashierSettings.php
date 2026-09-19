<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class KashierSettings
{
    public const ENABLED_KEY = 'kashier_gateway_enabled';

    public const MODE_KEY = 'kashier_mode';

    public const MID_KEY = 'kashier_mid';

    public const API_KEY_KEY = 'kashier_api_key';

    public const SECRET_KEY = 'kashier_secret';

    public const CURRENCY_KEY = 'kashier_currency';

    public const REDIRECT_KEY = 'kashier_merchant_redirect_url';

    public const MODES = ['test', 'live'];

    public const CURRENCIES = ['SAR', 'EGP', 'USD', 'EUR', 'GBP'];

    public static function isEnabled(): bool
    {
        return Setting::getValue(self::ENABLED_KEY) === '1';
    }

    public static function isConfigured(): bool
    {
        return self::mid() !== '' && self::apiKey() !== '' && self::secret() !== '';
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
        $mode = strtolower(self::storedOrEnv(self::MODE_KEY, (string) config('kashier.mode', 'test')));

        return in_array($mode, self::MODES, true) ? $mode : 'test';
    }

    public static function isLive(): bool
    {
        return self::mode() === 'live';
    }

    public static function mid(): string
    {
        return trim(self::storedOrEnv(self::MID_KEY, (string) (config('kashier.'.self::mode().'.mid') ?? '')));
    }

    public static function apiKey(): string
    {
        $raw = Setting::getValue(self::API_KEY_KEY);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim((string) (config('kashier.'.self::mode().'.api_key') ?? ''));
    }

    public static function hasStoredApiKey(): bool
    {
        $raw = Setting::getValue(self::API_KEY_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function secret(): string
    {
        $raw = Setting::getValue(self::SECRET_KEY);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim((string) (config('kashier.'.self::mode().'.secret') ?? ''));
    }

    public static function hasStoredSecret(): bool
    {
        $raw = Setting::getValue(self::SECRET_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function currency(): string
    {
        $currency = strtoupper(self::storedOrEnv(self::CURRENCY_KEY, (string) config('currency.code', 'SAR')));

        return in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency();
    }

    public static function merchantRedirectUrl(): string
    {
        return trim(self::storedOrEnv(self::REDIRECT_KEY, (string) config('kashier.merchant_redirect_url', '')));
    }

    public static function apiBaseUrl(): string
    {
        $fromConfig = (string) (config('kashier.'.self::mode().'.api_base_url') ?? '');

        return rtrim($fromConfig !== '' ? $fromConfig : (self::isLive() ? 'https://api.kashier.io' : 'https://test-api.kashier.io'), '/');
    }

    public static function allowedMethods(): string
    {
        return trim((string) config('kashier.allowed_methods', 'card,wallet,bank_installments')) ?: 'card,wallet,bank_installments';
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     mode?: string,
     *     mid?: string,
     *     api_key?: string,
     *     secret?: string|null,
     *     currency?: string,
     *     merchant_redirect_url?: string
     * }  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::ENABLED_KEY, ! empty($data['enabled']) ? '1' : null);

        $mode = strtolower(trim((string) ($data['mode'] ?? 'test')));
        Setting::setValue(self::MODE_KEY, in_array($mode, self::MODES, true) ? $mode : 'test');

        $mid = trim((string) ($data['mid'] ?? ''));
        Setting::setValue(self::MID_KEY, $mid !== '' ? $mid : null);

        $apiKey = $data['api_key'] ?? null;
        if (is_string($apiKey) && trim($apiKey) !== '') {
            Setting::setValue(self::API_KEY_KEY, Crypt::encryptString(trim($apiKey)));
        }

        $secret = $data['secret'] ?? null;
        if (is_string($secret) && trim($secret) !== '') {
            Setting::setValue(self::SECRET_KEY, Crypt::encryptString(trim($secret)));
        }

        $currency = strtoupper(trim((string) ($data['currency'] ?? platform_currency())));
        Setting::setValue(self::CURRENCY_KEY, in_array($currency, self::CURRENCIES, true) ? $currency : platform_currency());

        $redirect = trim((string) ($data['merchant_redirect_url'] ?? ''));
        Setting::setValue(self::REDIRECT_KEY, $redirect !== '' ? $redirect : null);
    }

    private static function storedOrEnv(string $key, string $fallback): string
    {
        $stored = Setting::getValue($key);

        return (is_string($stored) && $stored !== '') ? $stored : $fallback;
    }
}
