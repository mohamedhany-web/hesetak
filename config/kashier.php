<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kashier Payment Gateway
    |--------------------------------------------------------------------------
    | بوابة الدفع كاشير - إعدادات التكامل
    | التوثيق: https://developers.kashier.io/
    */

    'mode' => env('KASHIER_MODE', 'test'), // test | live

    'test' => [
        'api_base_url' => env('KASHIER_TEST_API_BASE_URL', 'https://test-api.kashier.io'),
        'mid' => env('KASHIER_TEST_MID', ''),
        'api_key' => env('KASHIER_TEST_API_KEY', ''),
        'secret' => env('KASHIER_TEST_SECRET', ''),
        'base_url' => env('KASHIER_TEST_BASE_URL', 'https://checkout.kashier.io'),
    ],

    'live' => [
        'api_base_url' => env('KASHIER_LIVE_API_BASE_URL', 'https://api.kashier.io'),
        'mid' => env('KASHIER_LIVE_MID', ''),
        'api_key' => env('KASHIER_LIVE_API_KEY', ''),
        'secret' => env('KASHIER_LIVE_SECRET', ''),
        'base_url' => env('KASHIER_LIVE_BASE_URL', 'https://checkout.kashier.io'),
    ],

    'currency' => env('KASHIER_CURRENCY', 'SAR'),

    'allowed_methods' => env('KASHIER_ALLOWED_METHODS', 'card,wallet,bank_installments'),

    /*
     * رابط العودة بعد الدفع. إن تركته فارغاً يُستخدم رابط التطبيق الحالي.
     * كاشير قد يرفض localhost أو http — للإنتاج استخدم https وربطاً ثابتاً.
     */
    'merchant_redirect_url' => env('KASHIER_MERCHANT_REDIRECT_URL', ''),

];
