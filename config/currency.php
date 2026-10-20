<?php

return [
    'code' => env('APP_CURRENCY', 'SAR'),
    'symbol' => env('APP_CURRENCY_SYMBOL', 'ر.س'),
    'label' => env('APP_CURRENCY_LABEL', 'ريال'),
    /** Font Awesome class — محايد، ليس دولار */
    'icon' => env('APP_CURRENCY_ICON', 'fas fa-coins'),
    /**
     * قائمة جاهزة لتعدد العملات لاحقاً (الخليج + مصر).
     * العرض والتخزين الافتراضي يبقيان على code أعلاه حتى يُفعّل مسار تحويل صريح.
     */
    'supported' => ['SAR', 'QAR', 'KWD', 'BHD', 'JOD', 'AED', 'EGP', 'USD'],
];
