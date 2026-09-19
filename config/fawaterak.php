<?php

return [
    /*
    |--------------------------------------------------------------------------
    | وضع التكامل: iframe (الإضافة + HMAC) أو api (Bearer + REST كما في وثائق Gateway)
    |--------------------------------------------------------------------------
    */
    'integration' => env('FAWATERAK_INTEGRATION', 'iframe'), // iframe | api

    /*
    |--------------------------------------------------------------------------
    | Fawaterak API (Gateway) — عند FAWATERAK_INTEGRATION=api
    |--------------------------------------------------------------------------
    | نفس رمز Bearer من لوحة فواتيرك (مثال الوثائق: Authorization: Bearer ...).
    | يمكن ضبط FAWATERAK_API_BASE_URL يدوياً؛ وإلا يُبنى من FAWATERAK_ENV (staging / app).
    */
    'api' => [
        'token' => env('FAWATERAK_API_TOKEN', ''),
        'base_url' => env('FAWATERAK_API_BASE_URL', ''),
        'timeout' => (int) env('FAWATERAK_API_TIMEOUT', 45),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fawaterak IFrame
    |--------------------------------------------------------------------------
    | مفاتيح من لوحة فواتيرك: Integrations → Fawaterak (API Key = Vendor، Provider Key).
    | نطاق الـ Domain في الـ HMAC يجب أن يطابق ما أدخلته في لوحة فواتيرك (HTTPS بدون / في النهاية).
    */
    'env' => env('FAWATERAK_ENV', 'test'), // test | live

    'vendor_key' => env('FAWATERAK_VENDOR_KEY', ''),
    'provider_key' => env('FAWATERAK_PROVIDER_KEY', ''),

    /**
     * قيمة حقل Domain في HMAC = نفس FAWATERAK-DOMAIN التي يرسلها المتصفح (https://النطاق).
     * إن تركتها فارغة: يُبنى https:// + host من APP_URL.
     * إن زار المستخدم www والموقع بدون www (أو العكس) عيّن القيمة يدوياً لتطابق ما في لوحة فواتيرك.
     */
    'iframe_domain' => env('FAWATERAK_IFRAME_DOMAIN', ''),

    /*
    | رمز Bearer لطلبات الإضافة (getPaymentmethods…) إن كان مختلفاً عن API Key — وإلا يُستخدم vendor_key
    */
    'plugin_bearer_token' => env('FAWATERAK_PLUGIN_BEARER_TOKEN', ''),

    'version' => env('FAWATERAK_VERSION', '0'),

    'currency' => env('FAWATERAK_CURRENCY', 'SAR'),

    'test' => [
        'plugin_url' => env('FAWATERAK_TEST_PLUGIN_URL', 'https://staging.fawaterk.com/fawaterkPlugin/fawaterkPlugin.min.js?v=1.2'),
    ],
    'live' => [
        'plugin_url' => env('FAWATERAK_LIVE_PLUGIN_URL', 'https://app.fawaterk.com/fawaterkPlugin/fawaterkPlugin.min.js'),
    ],
];
