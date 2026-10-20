<?php

return [
    'mode' => env('STRIPE_MODE', 'test'),
    'timeout' => (int) env('STRIPE_HTTP_TIMEOUT', 30),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'test' => [
        'publishable_key' => env('STRIPE_TEST_PUBLISHABLE_KEY', env('STRIPE_PUBLISHABLE_KEY', '')),
        'secret_key' => env('STRIPE_TEST_SECRET_KEY', env('STRIPE_SECRET_KEY', '')),
    ],
    'live' => [
        'publishable_key' => env('STRIPE_LIVE_PUBLISHABLE_KEY', ''),
        'secret_key' => env('STRIPE_LIVE_SECRET_KEY', ''),
    ],
];
