<?php

/**
 * Legacy private-lessons facet keys (Quran-centric).
 *
 * Matching for Hesetak is now:
 *   stage (academic_years) + subject (academic_subjects) + curriculum type (hesetak_curriculum_types)
 *
 * Keep genders + lesson_duration_minutes for optional UX / booking defaults.
 * Do not use subjects/age_groups/languages/specializations for public directory filters.
 */
return [
    'lesson_duration_minutes' => 50,

    'genders' => [
        'female' => ['en' => 'Female', 'ar' => 'معلمة'],
        'male' => ['en' => 'Male', 'ar' => 'معلم'],
    ],

    // Deprecated catalogs — retained only so old stored private_teaching_meta keys do not break readers.
    'subjects' => [
        'quran' => ['en' => 'Quran', 'ar' => 'قرآن'],
        'arabic' => ['en' => 'Arabic', 'ar' => 'العربية'],
        'islamic_studies' => ['en' => 'Islamic Studies', 'ar' => 'دراسات إسلامية'],
        'tajweed' => ['en' => 'Tajweed', 'ar' => 'تجويد'],
        'aqeedah' => ['en' => 'Aqeedah', 'ar' => 'عقيدة'],
        'fiqh' => ['en' => 'Fiqh', 'ar' => 'فقه'],
    ],

    'age_groups' => [
        '4-6' => ['en' => '4–6', 'ar' => '4–6'],
        '7-9' => ['en' => '7–9', 'ar' => '7–9'],
        '10-12' => ['en' => '10–12', 'ar' => '10–12'],
        '13-15' => ['en' => '13–15', 'ar' => '13–15'],
        'teens' => ['en' => 'Teens', 'ar' => 'مراهقون'],
    ],

    'languages' => [
        'english' => ['en' => 'English', 'ar' => 'إنجليزية'],
        'arabic' => ['en' => 'Arabic', 'ar' => 'عربية'],
        'bilingual' => ['en' => 'Bilingual', 'ar' => 'ثنائي اللغة'],
    ],

    'specializations' => [
        'quran_non_arabic' => ['en' => 'Quran for non-Arabic speakers', 'ar' => 'قرآن لغير الناطقين بالعربية'],
        'arabic_non_arabic' => ['en' => 'Arabic for non-speakers', 'ar' => 'عربية لغير الناطقين'],
        'children' => ['en' => 'Children', 'ar' => 'الأطفال'],
        'new_muslims' => ['en' => 'New Muslims', 'ar' => 'مسلمون جدد'],
    ],

    'availability' => [
        'morning' => ['en' => 'Morning', 'ar' => 'صباح'],
        'afternoon' => ['en' => 'Afternoon', 'ar' => 'ظهر'],
        'evening' => ['en' => 'Evening', 'ar' => 'مساء'],
        'weekend' => ['en' => 'Weekend', 'ar' => 'نهاية الأسبوع'],
    ],
];
