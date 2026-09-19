<?php

/**
 * Triple match filters for public teacher directory + curricula path.
 * Curriculum types = matching filters (not platform presence / branches).
 */
return [

    'curriculum_types' => [
        'saudi' => [
            'label_ar' => 'منهج سعودي',
            'label_en' => 'Saudi curriculum',
            'aliases' => ['سعودي', 'saudi', 'منهج سعودي', 'ksa'],
        ],
        'egyptian' => [
            'label_ar' => 'منهج مصري',
            'label_en' => 'Egyptian curriculum',
            'aliases' => ['مصري', 'egyptian', 'منهج مصري', 'egypt'],
        ],
        'us_intl' => [
            'label_ar' => 'مسار أمريكي / دولي',
            'label_en' => 'US / international',
            'aliases' => ['أمريكي', 'دولي', 'american', 'international', 'us', 'usa', 'ib'],
        ],
        'emirati' => [
            'label_ar' => 'منهج إماراتي',
            'label_en' => 'Emirati curriculum',
            'aliases' => ['إماراتي', 'اماراتي', 'emirati', 'uae'],
        ],
        'qatari' => [
            'label_ar' => 'منهج قطري',
            'label_en' => 'Qatari curriculum',
            'aliases' => ['قطري', 'qatari', 'qatar'],
        ],
        'kuwaiti' => [
            'label_ar' => 'منهج كويتي',
            'label_en' => 'Kuwaiti curriculum',
            'aliases' => ['كويتي', 'kuwaiti', 'kuwait'],
        ],
        'bahraini' => [
            'label_ar' => 'منهج بحريني',
            'label_en' => 'Bahraini curriculum',
            'aliases' => ['بحريني', 'bahraini', 'bahrain'],
        ],
    ],

];
