<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayPal REST (Orders v2)
    |--------------------------------------------------------------------------
    | القيم هنا احتياطية من .env. المصدر الأساسي بعد الربط هو إعدادات النظام
    | (/admin/system-settings) عبر App\Services\PayPalSettings.
    */
    'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    'currency' => env('PAYPAL_CURRENCY', 'SAR'),
    'timeout' => (int) env('PAYPAL_HTTP_TIMEOUT', 30),
];
