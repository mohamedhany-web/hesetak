<?php

return [
    /*
    |--------------------------------------------------------------------------
    | مزود البث الافتراضي للمنصة — LiveKit فقط
    |--------------------------------------------------------------------------
    */
    'provider' => 'livekit',

    'livekit' => [
        // عنوان WebSocket العام للمتصفح (نطاق خادم LiveKit لحصتك — عبر .env)
        'url' => env('LIVEKIT_URL', 'wss://live.glottical.com'),
        // النطاق العام بدون بروتوكول (لوحة الإدارة / فحص الاتصال)
        'host' => env('LIVEKIT_PUBLIC_HOST', 'live.glottical.com'),
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        // مدة صلاحية توكن الانضمام بالثواني
        'token_ttl' => (int) env('LIVEKIT_TOKEN_TTL', 21600),
        // عنوان HTTP داخلي/مباشر لفحص الصحة (قبل اكتمال DNS)
        'http_url' => env('LIVEKIT_HTTP_URL', 'http://187.124.36.228:7880'),
    ],
];
