<?php

/**
 * إعدادات اتصال مزوّد التوليد النصي (ذكاء حصتك).
 * أسماء متغيرات البيئة التالية تبقى للتوافق مع الإصدارات السابقة.
 */
return [
    'enabled' => filter_var(env('GEMINI_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'api_key' => env('GEMINI_API_KEY'),

    /** معرّف النموذج في مسار REST: models/{model}:generateContent */
    'model' => env('GEMINI_MODEL', 'gemini-flash-latest'),

    /** قاعدة REST (بدون / في النهاية) */
    'base_url' => rtrim((string) env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),

    /** مهلة HTTP بالثواني */
    'http_timeout' => (int) env('GEMINI_HTTP_TIMEOUT', 60),

    /** حد مخرجات تقريبي */
    'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 8192),

    /** حد أعلى لملف لعبة HTML (ألعاب تعليمية) عند الحاجة */
    'max_output_tokens_educational_game' => (int) env('GEMINI_MAX_OUTPUT_TOKENS_GAME', 16384),
];
