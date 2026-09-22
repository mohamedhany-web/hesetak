<?php

return [

    /*
    |--------------------------------------------------------------------------
    | الشهادات — سطح المنتج + الإصدار التلقائي
    |--------------------------------------------------------------------------
    */
    /** إخفاء سطح الشهادات مؤقتاً (طالب + إدارة + إصدار تلقائي) */
    'enabled' => filter_var(env('CERTIFICATES_ENABLED', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | إصدار شهادة المنصة تلقائياً عند اكتمال الكورس
    |--------------------------------------------------------------------------
    */
    'platform_auto_issue' => filter_var(
        env('CERTIFICATE_PLATFORM_AUTO_ON_COMPLETE', false),
        FILTER_VALIDATE_BOOL
    ),

    /** اسم الأكاديمية الظاهر على الشهادة */
    'academy_name' => env('CERTIFICATE_ACADEMY_NAME', ''),

    /** ألوان موحّدة مع الصفحة الرئيسية (مقاربة لـ welcome) */
    'primary' => env('CERTIFICATE_COLOR_PRIMARY', '#283593'),
    'secondary' => env('CERTIFICATE_COLOR_SECONDARY', '#FB5607'),
    'cream' => env('CERTIFICATE_COLOR_CREAM', '#FDFBF7'),
    'accent_light' => env('CERTIFICATE_COLOR_ACCENT', '#FFE5F7'),

    'director_name' => env('CERTIFICATE_DIRECTOR_NAME', 'المدير العام'),
    'director_title' => env('CERTIFICATE_DIRECTOR_TITLE', 'الإدارة التنفيذية'),
];
