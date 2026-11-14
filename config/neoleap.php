<?php

/**
 * NeoLeap (Al Rajhi) Payment Gateway — Hosted Checkout + Tranportal.
 *
 * Purchase:  https://digitalpayments.neoleap.com.sa/pg/payment/hosted.htm
 * Support:   https://digitalpayments.neoleap.com.sa/pg/payment/tranportal.htm
 *
 * Credentials (Tranportal ID / Password / AES key+IV) come from NeoLeap merchant portal.
 */
return [
    'mode' => env('NEOLEAP_MODE', 'test'),

    'tranportal_id' => env('NEOLEAP_TRANPORTAL_ID', ''),
    'password' => env('NEOLEAP_PASSWORD', ''),

    /** 32-byte key (raw) or 64-char hex */
    'encryption_key' => env('NEOLEAP_ENCRYPTION_KEY', ''),
    /** 16-byte IV (raw) or 32-char hex */
    'encryption_iv' => env('NEOLEAP_ENCRYPTION_IV', ''),

    'hosted_url' => env(
        'NEOLEAP_HOSTED_URL',
        'https://digitalpayments.neoleap.com.sa/pg/payment/hosted.htm'
    ),
    'tranportal_url' => env(
        'NEOLEAP_TRANPORTAL_URL',
        'https://digitalpayments.neoleap.com.sa/pg/payment/tranportal.htm'
    ),

    /** ISO numeric currency → NeoLeap currencyCode (682 = SAR) */
    'currency_codes' => [
        'SAR' => '682',
        'USD' => '840',
        'EUR' => '978',
        'AED' => '784',
        'EGP' => '818',
        'GBP' => '826',
    ],

    'default_currency' => env('NEOLEAP_CURRENCY', 'SAR'),
    'http_timeout' => (int) env('NEOLEAP_HTTP_TIMEOUT', 45),
];
