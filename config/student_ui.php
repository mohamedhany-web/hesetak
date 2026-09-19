<?php

/**
 * واجهة الطالب — حصتك (فردي 1:1 + كورسات + رصيد).
 * غيّر أي قيمة لإظهار/إخفاء القسم دون حذف بيانات.
 */
return [
    // مسار الكورسات المستقلة
    'show_courses' => true,
    'show_course_progress' => true,
    'show_certificates' => true,

    // مالية الطالب
    'show_wallet' => true,
    'show_invoices' => true,
    'show_orders' => true,
    'show_entitlements' => true,

    // الحصة الفردية (الأساسي)
    'show_private_lessons' => true,

    // دعم وحساب
    'show_support' => true,
    'show_notifications' => true,
    'show_profile' => true,
    'show_settings' => true,
    'show_referrals' => true,

    // موروث مدرسي/جماعي/بث — معطّل (مرجعي)
    'show_school' => false,
    'show_classes' => false,
    'show_live_broadcast' => false,
    'show_libraries' => false,
    'show_assignments' => false,
    'show_exams' => false,
    'show_achievements' => false,
    'show_consultations' => false,
    'show_legacy_calendar' => false,

    // تذكير قبل الموعد (دقائق)
    'reminder_minutes' => 30,
];
