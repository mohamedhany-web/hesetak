<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class NeoLeapSettings
{
    public const ENABLED_KEY = 'neoleap_gateway_enabled';

    public const MODE_KEY = 'neoleap_mode';

    public const TRANPORTAL_ID_KEY = 'neoleap_tranportal_id';

    public const PASSWORD_KEY = 'neoleap_password';

    public const ENCRYPTION_KEY_KEY = 'neoleap_encryption_key';

    public const ENCRYPTION_IV_KEY = 'neoleap_encryption_iv';

    public const HOSTED_URL_KEY = 'neoleap_hosted_url';

    public const TRANPORTAL_URL_KEY = 'neoleap_tranportal_url';

    public const CURRENCY_KEY = 'neoleap_currency';

    public const MODES = ['test', 'live'];

    public const CURRENCIES = ['SAR', 'USD', 'EUR', 'AED', 'EGP', 'GBP'];

    public static function isEnabled(): bool
    {
        return Setting::getValue(self::ENABLED_KEY) === '1';
    }

    public static function isConfigured(): bool
    {
        return self::tranportalId() !== ''
            && self::password() !== ''
            && self::encryptionKey() !== ''
            && self::encryptionIv() !== '';
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
        $mode = strtolower(self::storedOrEnv(self::MODE_KEY, (string) config('neoleap.mode', 'test')));

        return in_array($mode, self::MODES, true) ? $mode : 'test';
    }

    public static function isLive(): bool
    {
        return self::mode() === 'live';
    }

    public static function tranportalId(): string
    {
        return trim(self::storedOrEnv(self::TRANPORTAL_ID_KEY, (string) config('neoleap.tranportal_id', '')));
    }

    public static function password(): string
    {
        return self::decryptOrEnv(self::PASSWORD_KEY, (string) config('neoleap.password', ''));
    }

    public static function encryptionKey(): string
    {
        return self::decryptOrEnv(self::ENCRYPTION_KEY_KEY, (string) config('neoleap.encryption_key', ''));
    }

    public static function encryptionIv(): string
    {
        return self::decryptOrEnv(self::ENCRYPTION_IV_KEY, (string) config('neoleap.encryption_iv', ''));
    }

    public static function hostedUrl(): string
    {
        $url = trim(self::storedOrEnv(self::HOSTED_URL_KEY, (string) config('neoleap.hosted_url', '')));

        return $url !== ''
            ? $url
            : 'https://digitalpayments.neoleap.com.sa/pg/payment/hosted.htm';
    }

    public static function tranportalUrl(): string
    {
        $url = trim(self::storedOrEnv(self::TRANPORTAL_URL_KEY, (string) config('neoleap.tranportal_url', '')));

        return $url !== ''
            ? $url
            : 'https://digitalpayments.neoleap.com.sa/pg/payment/tranportal.htm';
    }

    public static function currency(): string
    {
        $currency = strtoupper(self::storedOrEnv(self::CURRENCY_KEY, (string) config('neoleap.default_currency', 'SAR')));

        return in_array($currency, self::CURRENCIES, true) ? $currency : 'SAR';
    }

    public static function hasStoredPassword(): bool
    {
        $raw = Setting::getValue(self::PASSWORD_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function hasStoredEncryptionKey(): bool
    {
        $raw = Setting::getValue(self::ENCRYPTION_KEY_KEY);

        return is_string($raw) && $raw !== '';
    }

    public static function hasStoredEncryptionIv(): bool
    {
        $raw = Setting::getValue(self::ENCRYPTION_IV_KEY);

        return is_string($raw) && $raw !== '';
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     mode?: string,
     *     tranportal_id?: string,
     *     password?: string|null,
     *     encryption_key?: string|null,
     *     encryption_iv?: string|null,
     *     hosted_url?: string,
     *     tranportal_url?: string,
     *     currency?: string
     * }  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::ENABLED_KEY, ! empty($data['enabled']) ? '1' : null);

        $mode = strtolower(trim((string) ($data['mode'] ?? 'test')));
        Setting::setValue(self::MODE_KEY, in_array($mode, self::MODES, true) ? $mode : 'test');

        $id = trim((string) ($data['tranportal_id'] ?? ''));
        Setting::setValue(self::TRANPORTAL_ID_KEY, $id !== '' ? $id : null);

        self::saveSecretIfFilled(self::PASSWORD_KEY, $data['password'] ?? null);
        self::saveSecretIfFilled(self::ENCRYPTION_KEY_KEY, $data['encryption_key'] ?? null);
        self::saveSecretIfFilled(self::ENCRYPTION_IV_KEY, $data['encryption_iv'] ?? null);

        $hosted = trim((string) ($data['hosted_url'] ?? ''));
        Setting::setValue(self::HOSTED_URL_KEY, $hosted !== '' ? $hosted : null);

        $portal = trim((string) ($data['tranportal_url'] ?? ''));
        Setting::setValue(self::TRANPORTAL_URL_KEY, $portal !== '' ? $portal : null);

        $currency = strtoupper(trim((string) ($data['currency'] ?? 'SAR')));
        Setting::setValue(self::CURRENCY_KEY, in_array($currency, self::CURRENCIES, true) ? $currency : 'SAR');
    }

    private static function saveSecretIfFilled(string $settingKey, mixed $value): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        Setting::setValue($settingKey, Crypt::encryptString(trim($value)));
    }

    private static function decryptOrEnv(string $settingKey, string $fallback): string
    {
        $raw = Setting::getValue($settingKey);
        if (is_string($raw) && $raw !== '') {
            try {
                return trim(Crypt::decryptString($raw));
            } catch (Throwable) {
                return trim($raw);
            }
        }

        return trim($fallback);
    }

    private static function storedOrEnv(string $key, string $fallback): string
    {
        $stored = Setting::getValue($key);

        return (is_string($stored) && $stored !== '') ? $stored : $fallback;
    }
}
