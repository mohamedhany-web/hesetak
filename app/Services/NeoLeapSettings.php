<?php

namespace App\Services;

use App\Models\Setting;

/**
 * NeoLeap: الأسرار من .env / config فقط.
 * الأدمن يتحكم فقط في التفعيل (neoleap_gateway_enabled).
 */
class NeoLeapSettings
{
    public const ENABLED_KEY = 'neoleap_gateway_enabled';

    /** مفاتيح قديمة كانت تُحفظ في DB — تُمسح عند الحفظ حتى لا تبقى أسرار. */
    public const LEGACY_SECRET_KEYS = [
        'neoleap_mode',
        'neoleap_tranportal_id',
        'neoleap_password',
        'neoleap_encryption_key',
        'neoleap_encryption_iv',
        'neoleap_hosted_url',
        'neoleap_tranportal_url',
        'neoleap_currency',
    ];

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
        $mode = strtolower(trim((string) config('neoleap.mode', 'test')));

        return in_array($mode, self::MODES, true) ? $mode : 'test';
    }

    public static function isLive(): bool
    {
        return self::mode() === 'live';
    }

    public static function tranportalId(): string
    {
        return trim((string) config('neoleap.tranportal_id', ''));
    }

    public static function password(): string
    {
        return trim((string) config('neoleap.password', ''));
    }

    public static function encryptionKey(): string
    {
        return trim((string) config('neoleap.encryption_key', ''));
    }

    public static function encryptionIv(): string
    {
        return trim((string) config('neoleap.encryption_iv', ''));
    }

    public static function hostedUrl(): string
    {
        $url = trim((string) config('neoleap.hosted_url', ''));

        return $url !== ''
            ? $url
            : 'https://digitalpayments.neoleap.com.sa/pg/payment/hosted.htm';
    }

    public static function tranportalUrl(): string
    {
        $url = trim((string) config('neoleap.tranportal_url', ''));

        return $url !== ''
            ? $url
            : 'https://digitalpayments.neoleap.com.sa/pg/payment/tranportal.htm';
    }

    public static function currency(): string
    {
        $currency = strtoupper(trim((string) config('neoleap.default_currency', 'SAR')));

        return in_array($currency, self::CURRENCIES, true) ? $currency : 'SAR';
    }

    /** هل الأسرار موجودة في البيئة (بدون كشف قيمها). */
    public static function envSecretsPresent(): array
    {
        return [
            'tranportal_id' => self::tranportalId() !== '',
            'password' => self::password() !== '',
            'encryption_key' => self::encryptionKey() !== '',
            'encryption_iv' => self::encryptionIv() !== '',
        ];
    }

    /**
     * @param  array{enabled?: bool}  $data
     */
    public static function save(array $data): void
    {
        Setting::setValue(self::ENABLED_KEY, ! empty($data['enabled']) ? '1' : null);
        self::purgeLegacyDbSecrets();
    }

    /** إزالة أي أسرار NeoLeap كانت مخزّنة سابقاً في settings. */
    public static function purgeLegacyDbSecrets(): void
    {
        foreach (self::LEGACY_SECRET_KEYS as $key) {
            Setting::setValue($key, null);
        }
    }
}
