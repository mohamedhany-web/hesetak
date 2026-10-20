<?php

namespace App\Support;

/**
 * المرجع التشغيلي للوحة إدارة حصتك — يطابق كل روابط السايدبار الظاهرة حالياً.
 * المصدر: resources/views/layouts/admin-sidebar.blade.php
 */
final class AdminOpsGuideCatalog
{
    /**
     * @return list<array{
     *   id: string,
     *   title: string,
     *   icon: string,
     *   summary: string,
     *   audience: string,
     *   decision: string,
     *   steps: list<string>,
     *   screenshot: string,
     *   items: list<array{title: string, route: ?string, body: string, tips: list<string>}>
     * }>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'insights',
                'title' => 'ذكاء الأكاديمية',
                'icon' => 'fas fa-brain',
                'summary' => 'لوحة قرار يومية: أين الاختناق؟ ماذا يحتاج تدخّل الآن؟',
                'audience' => 'مدير عام · مشرف أكاديمي · تشغيل',
                'decision' => 'ابدأ يومك من هنا قبل فتح أي قسم تفصيلي.',
                'steps' => [
                    'افتح «تحليلات وتوجهات» صباحاً.',
                    'حدّد العناصر الحمراء/العاجلة.',
                    'انتقل مباشرة للقسم المسؤول من السايدبار.',
                ],
                'screenshot' => 'insights.png',
                'items' => [
                    [
                        'title' => 'تحليلات وتوجهات',
                        'route' => 'admin.academy-insights.index',
                        'body' => 'رادار يومي: اختناقات الحجوزات والجودة والمالية. افتحه صباحاً وانتقل مباشرة للقسم المسؤول عن اللون الأحمر.',
                        'tips' => array (
  0 => 'حدّث الصفحة مع بداية الوردية',
  1 => 'لا تعتمد على الرقم وحده — افتح الصف المتأثر',
),
                    ],
                ],
            ],
            [
                'id' => 'home',
                'title' => 'الرئيسية',
                'icon' => 'fas fa-home',
                'summary' => 'نقطة الدخول الشخصية: لوحة التحكم، الإشعارات، الملف، ومساعد التشغيل.',
                'audience' => 'كل أدوار الإدارة',
                'decision' => 'استخدم الإشعارات كقائمة مهام قصيرة قبل التعمق.',
                'steps' => [
                    'افتح لوحة التحكم بعد الدخول.',
                    'راجع وارد الإشعارات.',
                    'لو احتجت شرح سريع افتح مساعد التشغيل.',
                ],
                'screenshot' => 'home.png',
                'items' => [
                    [
                        'title' => 'لوحة التحكم',
                        'route' => 'admin.dashboard',
                        'body' => 'نقطة دخولك بعد تسجيل الدخول: ملخص سريع لما يخص حسابك التشغيلي.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'وارد الإشعارات',
                        'route' => 'admin.notifications.inbox',
                        'body' => 'وارد تنبيهاتك الشخصية داخل اللوحة — عاملها كقائمة مهام قصيرة قبل ما تغوص في الأقسام.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الملف الشخصي',
                        'route' => 'admin.profile',
                        'body' => 'بيانات حسابك الإداري وكلمة المرور والتفضيلات الشخصية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مساعد التشغيل',
                        'route' => 'admin.ops-guide.index',
                        'body' => 'المساعد التشغيلي داخل اللوحة: شرح الأقسام بنفس منطق هذا الدليل.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'ops',
                'title' => 'التشغيل',
                'icon' => 'fas fa-headset',
                'summary' => 'الطابور اليومي للتواصل والحصة التجريبية.',
                'audience' => 'علاقات عملاء · تشغيل',
                'decision' => 'كل رسالة تواصل أو حصة مجانية = فرصة تحويل أو ثقة.',
                'steps' => [
                    'راجع الحصص التجريبية الجديدة.',
                    'أغلق أو حوّل رسائل التواصل بسرعة.',
                ],
                'screenshot' => 'ops.png',
                'items' => [
                    [
                        'title' => 'الحصة المجانية',
                        'route' => 'admin.free-trial-bookings.index',
                        'body' => 'طلبات الحصة التجريبية من الموقع. كل طلب = فرصة تحويل لباقة أو بناء ثقة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رسائل التواصل',
                        'route' => 'admin.contact-messages.index',
                        'body' => 'رسائل نموذج التواصل العام. رد بسرعة وحوّل المناسب لـ CRM lead.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'hiring',
                'title' => 'التوظيف',
                'icon' => 'fas fa-user-plus',
                'summary' => 'مسار اعتماد المعلم من التقديم حتى التفعيل.',
                'audience' => 'فريق توظيف · مشرف أكاديمي',
                'decision' => 'لا يُفعَّل معلم قبل اكتمال المراجعة والمطابقة والمقابلة/العقد حسب إعداداتك.',
                'steps' => [
                    'راجع الطلبات الجديدة.',
                    'أكمل المقابلة/العقد.',
                    'فعّل بعد اكتمال ملف المطابقة والاتفاقية.',
                ],
                'screenshot' => 'hiring.png',
                'items' => [
                    [
                        'title' => 'منشئ نموذج التقديم',
                        'route' => 'admin.hiring-form.edit',
                        'body' => 'ابنِ/عدّل حقول نموذج تقديم المعلم الظاهر على صفحة الانضمام.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مواعيد المقابلات',
                        'route' => 'admin.tutor-interview-slots.index',
                        'body' => 'حدد مواعيد المقابلات المتاحة لمرشحي التوظيف.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إعدادات البروسيس',
                        'route' => 'admin.hiring.settings.edit',
                        'body' => 'اضبط خطوات بروسيس التوظيف (مقابلة، عقد، شروط التفعيل).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'لوحة التوظيف',
                        'route' => 'admin.tutor-applications.hub',
                        'body' => 'لوحة مركزية لحالة طلبات التوظيف ومؤشرات الطابور.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مراجعة الطلبات',
                        'route' => 'admin.tutor-applications.index',
                        'body' => 'راجع الطلبات واحداً واحداً: قبول/رفض/استكمال نواقص قبل التفعيل.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المعلمون المفعّلون',
                        'route' => 'admin.tutor-applications.activated',
                        'body' => 'المعلمون اللي اتفعّلوا بعد المسار — تأكد إن ملف المطابقة والاتفاقية جاهزين.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'site',
                'title' => 'الموقع',
                'icon' => 'fas fa-globe',
                'summary' => 'محتوى الواجهة العامة والإعدادات الظاهرة للزائر.',
                'audience' => 'تسويق · إدارة محتوى',
                'decision' => 'أي نص عام يجب أن يطابق براند حصتك.',
                'steps' => [
                    'راجع من نحن وFAQ.',
                    'حدّث الآراء والخدمات بحذر.',
                ],
                'screenshot' => 'site.png',
                'items' => [
                    [
                        'title' => 'خدمات الموقع',
                        'route' => 'admin.site-services.index',
                        'body' => 'بطاقات الخدمات الظاهرة في الصفحات العامة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'آراء الرئيسية',
                        'route' => 'admin.site-testimonials.index',
                        'body' => 'شهادات أولياء الأمور/الطلاب على الرئيسية. فضّل شهادات حقيقية بسياق منهج/مادة.',
                        'tips' => array (
  0 => 'فضّل شهادات حقيقية مع سياق منهج/مادة',
),
                    ],
                    [
                        'title' => 'من نحن',
                        'route' => 'admin.about.index',
                        'body' => 'محتوى صفحة «من نحن» — لازم يطابق براند حصتك (أمريكا · مصر · السعودية فقط).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الأسئلة الشائعة',
                        'route' => 'admin.faq.index',
                        'body' => 'الأسئلة الشائعة للزائر والطالب/المعلم.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إعدادات النظام',
                        'route' => 'admin.system-settings.edit',
                        'body' => 'إعدادات عامة ظاهرة/تشغيلية (عملة، سياسات…). اختبر الأثر على الدفع والواجهة بعد الحفظ.',
                        'tips' => array (
  0 => 'اختبر الأثر على الدفع والواجهة بعد أي حفظ',
),
                    ],
                ],
            ],
            [
                'id' => 'students',
                'title' => 'الطلاب',
                'icon' => 'fas fa-user-graduate',
                'summary' => 'دورة حياة الطالب: حساب، ولي أمر، تسكين، رصيد، حصص، دعم، بريفيت.',
                'audience' => 'تشغيل · علاقات عملاء · مشرف أكاديمي',
                'decision' => 'الحصة لا تُغلق مالياً قبل تقرير المعلم الإلزامي.',
                'steps' => [
                    'تابع الأرصدة والحصص.',
                    'أغلق طابور التقارير المتأخرة.',
                    'عالج تذاكر الدعم.',
                ],
                'screenshot' => 'students.png',
                'items' => [
                    [
                        'title' => 'الطلاب والحسابات',
                        'route' => 'admin.students-accounts.index',
                        'body' => 'إنشاء وتفعيل وتعطيل حسابات الطلاب ومتابعة بياناتهم الأساسية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'أولياء الأمور والأبناء',
                        'route' => 'admin.parents.index',
                        'body' => 'ربط ولي الأمر بأبنائه ومتابعة الحسابات العائلية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التسكين',
                        'route' => 'admin.placement.index',
                        'body' => 'تسكين طالب مع معلم بالمطابقة الثلاثية: مرحلة + مادة + نوع منهج.',
                        'tips' => array (
  0 => 'المطابقة ثلاثية إلزامية',
),
                    ],
                    [
                        'title' => 'أرصدة الطلاب',
                        'route' => 'admin.student-entitlements.index',
                        'body' => 'رصيد باقات الحصص الفردية. تبديل المعلم لا يخصم من الرصيد.',
                        'tips' => array (
  0 => 'تبديل المعلم بلا خصم رصيد',
),
                    ],
                    [
                        'title' => 'حصص 1:1',
                        'route' => 'admin.one-to-one-sessions.index',
                        'body' => 'جدول كل حصص 1:1 وحالاتها (قادمة/مكتملة/ملغاة).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير الحصص (قبل الصرف)',
                        'route' => 'admin.one-to-one-report-queue.index',
                        'body' => 'تقارير معلمين ناقصة تمنع إقفال الأجر — الحق المتأخر فوراً.',
                        'tips' => array (
  0 => 'تابع المتأخر فوراً',
),
                    ],
                    [
                        'title' => 'تنظيف الحصص والتجارب',
                        'route' => 'admin.student-lesson-cleanup.index',
                        'body' => 'صيانة لحصص/تجارب معلّقة أو حالات غير مكتملة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التسجيلات عبر الإنترنت',
                        'route' => 'admin.online-enrollments.index',
                        'body' => 'اشتراكات الطلاب في الكورسات المسجّلة (مسار مستقل عن 1:1).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الحضور والانصراف',
                        'route' => 'admin.attendance.index',
                        'body' => 'سجلات حضور مرتبطة بالمحاضرات/المسارات اللي بتفعل الحضور.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رسائل بريفيت',
                        'route' => 'admin.private-courses.threads',
                        'body' => 'محادثات مسار البريفيت بين الطالب/التشغيل.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'استقبال الطلاب',
                        'route' => 'admin.private-courses.receptions',
                        'body' => 'استقبال وتنظيم طلاب مسار البريفيت.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الدعم الفني',
                        'route' => 'admin.support-tickets.index',
                        'body' => 'تذاكر دعم الطلاب من الفتح حتى الإغلاق.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تصنيفات الدعم',
                        'route' => 'admin.support-inquiry-categories.index',
                        'body' => 'تصنيفات نموذج الدعم عشان التوجيه يكون أوضح.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'استشارات المدربين',
                        'route' => 'admin.consultations.index',
                        'body' => 'طلبات الاستشارة المرتبطة بالمعلمين/التشغيل ومستحقاتها إن وُجدت.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'sales',
                'title' => 'المبيعات',
                'icon' => 'fas fa-chart-pie',
                'summary' => 'CRM وخط الأنابيب والطلبات — من lead إلى طلب مدفوع.',
                'audience' => 'مبيعات · CRM',
                'decision' => 'تأكيد الدفع النهائي من webhook البوابة فقط.',
                'steps' => [
                    'راجع الـ pipeline.',
                    'تابع الليدز.',
                    'افتح الطلبات للمراجعة.',
                ],
                'screenshot' => 'sales.png',
                'items' => [
                    [
                        'title' => 'لوحة CRM',
                        'route' => 'admin.crm.dashboard',
                        'body' => 'مؤشرات فرص المبيعات اليومية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'Pipeline',
                        'route' => 'admin.crm.pipeline',
                        'body' => 'مراحل الفرصة من أول تواصل حتى الإغلاق.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'عملاء CRM',
                        'route' => 'admin.crm.leads.index',
                        'body' => 'إدارة عملاء CRM المحتملين والمتابعة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'عمولات CRM',
                        'route' => 'admin.crm.commissions.index',
                        'body' => 'عمولات فرق المبيعات/المسوقين (مش أجر المعلم).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'سجل CRM',
                        'route' => 'admin.crm.audit.index',
                        'body' => 'سجل تدقيق تغييرات CRM.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'فرق CRM',
                        'route' => 'admin.crm.groups.index',
                        'body' => 'تنظيم فرق ومجموعات المبيعات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'Leads القديمة',
                        'route' => 'admin.sales.leads.index',
                        'body' => 'مسار الليدز الأقدم/الموازي — استخدمه لو لسه شغال عندكم.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تحليلات المبيعات',
                        'route' => 'admin.sales.index',
                        'body' => 'تحليلات ملخصة لعمليات البيع.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الطلبات',
                        'route' => 'admin.orders.index',
                        'body' => 'كل طلبات الشراء (باقات/كورسات) وحالات الاعتماد. الدفع النهائي من webhook البوابة فقط.',
                        'tips' => array (
  0 => 'لا تعتمد طلباً يدوياً قبل تأكيد البوابة',
),
                    ],
                ],
            ],
            [
                'id' => 'finance',
                'title' => 'المالية',
                'icon' => 'fas fa-coins',
                'summary' => 'فواتير، مدفوعات، محافظ، رواتب، مصروفات، تقسيط، وتقارير محاسبية.',
                'audience' => 'محاسب مالي · مدير عام',
                'decision' => 'كل حركة مالية تبقى في السجل الدائم.',
                'steps' => [
                    'راجع المدفوعات المعلّقة.',
                    'تابع المحافظ.',
                    'جهّز الرواتب بعد التقارير.',
                ],
                'screenshot' => 'finance.png',
                'items' => [
                    [
                        'title' => 'الفواتير',
                        'route' => 'admin.invoices.index',
                        'body' => 'إصدار ومتابعة فواتير العملاء.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المدفوعات',
                        'route' => 'admin.payments.index',
                        'body' => 'حركات الدفع المرتبطة بالبوابات والتحويلات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المعاملات',
                        'route' => 'admin.transactions.index',
                        'body' => 'دفتر الحركات المالي الشامل.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المحافظ',
                        'route' => 'admin.wallets.index',
                        'body' => 'محافظ المستخدمين (طلاب/معلمون) والأرصدة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'محافظ المنصة (طلاب/معلمون)',
                        'route' => 'admin.platform-wallets.index',
                        'body' => 'تجميع أرصدة المنصة مقابل الطلاب والمعلمين.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رواتب المدربين',
                        'route' => 'admin.salaries.index',
                        'body' => 'احتساب وصرف مستحقات المعلمين. لا تصرف قبل التقرير الإلزامي حيث ينطبق.',
                        'tips' => array (
  0 => 'لا تصرف قبل اكتمال التقرير الإلزامي',
),
                    ],
                    [
                        'title' => 'حسابات المدربين',
                        'route' => 'admin.accounting.instructor-accounts.index',
                        'body' => 'الحساب المحاسبي المرتبط بكل معلم.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المصروفات',
                        'route' => 'admin.expenses.index',
                        'body' => 'تسجيل مصروفات التشغيل.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'خطط التقسيط',
                        'route' => 'admin.installments.plans.index',
                        'body' => 'قوالب خطط التقسيط المعروضة/المستخدمة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'اتفاقيات التقسيط',
                        'route' => 'admin.installments.agreements.index',
                        'body' => 'اتفاقيات التقسيط الفعّالة مع العملاء.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير المحاسبة',
                        'route' => 'admin.accounting.reports',
                        'body' => 'تقارير دورية للإيراد والمصروف وحركة المحاسبة.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'system',
                'title' => 'النظام',
                'icon' => 'fas fa-cogs',
                'summary' => 'المستخدمون، البوابات، الإشعارات، البريد، السجلات، والإحصاءات.',
                'audience' => 'مدير عام · تقنية',
                'decision' => 'صلاحية تُتحقق عند التنفيذ — لا تعتمد على إخفاء الزر فقط.',
                'steps' => [
                    'راجع المستخدمين عند تعيين موظف.',
                    'اختبر بوابة الدفع قبل الإنتاج.',
                    'راقب السجلات و2FA.',
                ],
                'screenshot' => 'system.png',
                'items' => [
                    [
                        'title' => 'المستخدمون',
                        'route' => 'admin.users.index',
                        'body' => 'حسابات مستخدمي النظام وأدوارهم العامة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'بوابات الدفع',
                        'route' => 'admin.payment-gateways.index',
                        'body' => 'تشغيل Stripe/فواتيرك/PayPal وغيرها. لا تخلط مفاتيح الاختبار بالإنتاج.',
                        'tips' => array (
  0 => 'لا تخلط مفاتيح الاختبار بالإنتاج',
),
                    ],
                    [
                        'title' => 'الإشعارات',
                        'route' => 'admin.notifications.index',
                        'body' => 'إدارة/إرسال إشعارات المنصة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إشعارات الموظفين',
                        'route' => 'admin.employee-notifications.index',
                        'body' => 'تنبيهات داخلية لفريق الإدارة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إشعارات البريد (Gmail)',
                        'route' => 'admin.email-broadcasts.index',
                        'body' => 'بث رسائل بريد جماعي (Gmail/SMTP حسب الإعداد).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'سجل النشاط',
                        'route' => 'admin.activity-log',
                        'body' => 'من فعل ماذا ومتى — مرجع تدقيق.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'سجلات التحقق الثنائي',
                        'route' => 'admin.two-factor-logs.index',
                        'body' => 'محاولات وسجلات التحقق الثنائي.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الإحصاءات',
                        'route' => 'admin.statistics.index',
                        'body' => 'إحصاءات منصة عامة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مراقبة أداء المنصة',
                        'route' => 'admin.performance.index',
                        'body' => 'مؤشرات أداء تشغيلية للمنصة.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'agreements',
                'title' => 'الاتفاقيات',
                'icon' => 'fas fa-file-contract',
                'summary' => 'اتفاقيات معلمين وموظفين وطلبات السحب.',
                'audience' => 'مالي · موارد بشرية تشغيلية · مدير عام',
                'decision' => 'أجر المعلم منفصل عن سعر الطالب.',
                'steps' => [
                    'راجع اتفاقيات المعلمين الجديدة.',
                    'وافق على السحب بعد التحقق.',
                ],
                'screenshot' => 'agreements.png',
                'items' => [
                    [
                        'title' => 'اتفاقيات المدربين',
                        'route' => 'admin.agreements.index',
                        'body' => 'اتفاقيات أجر المعلمين (منفصلة عن سعر الطالب في /pricing).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'اتفاقيات الموظفين',
                        'route' => 'admin.employee-agreements.index',
                        'body' => 'اتفاقيات/رواتب فريق الإدارة والموظفين.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'طلبات السحب',
                        'route' => 'admin.withdrawals.index',
                        'body' => 'طلبات سحب مستحقات المعلمين من المحافظ.',
                        'tips' => array (
  0 => 'اربط الموافقة بحالة التقارير',
),
                    ],
                ],
            ],
            [
                'id' => 'marketing',
                'title' => 'التسويق',
                'icon' => 'fas fa-bullhorn',
                'summary' => 'إعلانات منبثقة، علامة شخصية، كوبونات، إحالات وعمولات.',
                'audience' => 'تسويق · مبيعات',
                'decision' => 'كل عرض لازم يكون قابل للقياس بكود أو إحالة.',
                'steps' => [
                    'أنشئ كوبوناً بحملة واضحة.',
                    'راقب الإحالات والعمولات.',
                ],
                'screenshot' => 'marketing.png',
                'items' => [
                    [
                        'title' => 'العلامة الشخصية / ملفات المطابقة',
                        'route' => 'admin.personal-branding.index',
                        'body' => 'بروفايل المطابقة للمعلم (مرحلة/مادة/منهج) وظهوره في الدليل العام.',
                        'tips' => array (
  0 => 'اكتمال الملف شرط ظهور جيد في الدليل',
),
                    ],
                    [
                        'title' => 'الإعلانات المنبثقة',
                        'route' => 'admin.popup-ads.index',
                        'body' => 'نوافذ منبثقة على الموقع. لا تكثرها خصوصاً على الجوال.',
                        'tips' => array (
  0 => 'لا تكثرها على الجوال',
),
                    ],
                    [
                        'title' => 'الكوبونات والخصومات',
                        'route' => 'admin.coupons.index',
                        'body' => 'أكواد الخصم والحملات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'عمولات كوبونات التسويق',
                        'route' => 'admin.coupon-commissions.index',
                        'body' => 'عمولات المسوقين المرتبطة بالكوبونات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'برامج الإحالة',
                        'route' => 'admin.referral-programs.index',
                        'body' => 'إعداد برامج دعوة الأصدقاء وقواعد المكافأة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الإحالات',
                        'route' => 'admin.referrals.index',
                        'body' => 'سجل الإحالات الفعلية وحالاتها.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'paid',
                'title' => 'مدفوع — الباقات والأسعار',
                'icon' => 'fas fa-credit-card',
                'summary' => 'باقات الحصص، مصفوفة سعر الطالب، وقواعد التسعير في /pricing.',
                'audience' => 'مبيعات · مالي · تشغيل',
                'decision' => 'الباقة = رصيد ساعات للحجز 1:1 — ليست اشتراك كورس مسجّل.',
                'steps' => [
                    'اضبط سعر الحصة (مرحلة×مسار).',
                    'عرّف الباقة وقواعدها.',
                    'امنح باقة يدوياً عند الاستثناء.',
                ],
                'screenshot' => 'paid.png',
                'items' => [
                    [
                        'title' => 'باقات الأسعار',
                        'route' => 'admin.packages.index',
                        'body' => 'باقات تسعير عامة/تسويقية مساعدة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'باقات الحصص',
                        'route' => 'admin.service-packages.index',
                        'body' => 'المصدر الأساسي لباقات الحصص 1:1 في الموقع: الاسم والسعر والشارة والمزايا والظهور.',
                        'tips' => array (
  0 => 'فعّل «الأكثر مبيعًا» لتظهر في الرئيسية',
  1 => 'الباقة النشطة تظهر في صفحة الباقات',
),
                    ],
                    [
                        'title' => 'منح باقة يدوياً',
                        'route' => 'admin.service-packages.grant',
                        'body' => 'منح رصيد باقة لطالب يدوياً بدون checkout — سجّل السبب.',
                        'tips' => array (
  0 => 'سجّل السبب في الملاحظة',
),
                    ],
                    [
                        'title' => 'سعر الحصة (مرحلة×مسار)',
                        'route' => 'admin.service-session-rates.index',
                        'body' => 'مصفوفة سعر الحصة للطالب حسب المرحلة×المسار. دي طبقة سعر الطالب مش أجر المعلم.',
                        'tips' => array (
  0 => 'دي سعر الطالب — مش أجر المعلم',
),
                    ],
                    [
                        'title' => 'قواعد تسعير الباقات المخصصة',
                        'route' => 'admin.service-package-pricing-rules.index',
                        'body' => 'قواعد تسعير/خصم على الباقات تظهر في /pricing وcheckout.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'product',
                'title' => 'المنتج التعليمي',
                'icon' => 'fas fa-graduation-cap',
                'summary' => 'مطابقة المناهج، المكتبات، تشغيل المعلمين، الكورسات المسجّلة، والبث المباشر.',
                'audience' => 'مشرف أكاديمي · محتوى · تشغيل معلمين',
                'decision' => 'مسار 1:1 منفصل عن الكورسات المسجّلة — لا تخلط التسعير أو الحضور.',
                'steps' => [
                    'حدّث كتالوج المطابقة.',
                    'راجع ملفات المعلمين.',
                    'أدِر الكورسات والبث إن لزم.',
                ],
                'screenshot' => 'product.png',
                'items' => [
                    [
                        'title' => 'المراحل الدراسية',
                        'route' => 'admin.academic-years.index',
                        'body' => 'المراحل الدراسية المستخدمة في المطابقة والفلاتر العامة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'المواد / مجموعات المهارات',
                        'route' => 'admin.academic-subjects.index',
                        'body' => 'المواد/مجموعات المهارات المرتبطة بالمطابقة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'أنواع المناهج',
                        'route' => 'admin.curriculum-types.index',
                        'body' => 'أنواع المناهج (مثل سعودي…). صياغة منهج وليس فروعاً جغرافية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'العلامة الشخصية / ملفات المطابقة',
                        'route' => 'admin.personal-branding.index',
                        'body' => 'بروفايل المطابقة للمعلم (مرحلة/مادة/منهج) وظهوره في الدليل العام.',
                        'tips' => array (
  0 => 'اكتمال الملف شرط ظهور جيد في الدليل',
),
                    ],
                    [
                        'title' => 'مركز الملفات',
                        'route' => 'admin.libraries.index',
                        'body' => 'بوابة مركز الملفات والمواد.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'ملفات الماتريال',
                        'route' => 'admin.libraries.materials.index',
                        'body' => 'رفع وتنظيم ملفات الماتريال للطلاب/المعلمين.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مناهج تفاعلية (ملفات)',
                        'route' => 'admin.curriculum-library.index',
                        'body' => 'ملفات مناهج تفاعلية مرتبطة بالمسار الأكاديمي.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مكتبة الفيديوهات',
                        'route' => 'admin.libraries.videos.index',
                        'body' => 'مكتبة فيديوهات المحتوى.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'هيكل الكورسات',
                        'route' => 'admin.libraries.curriculum.index',
                        'body' => 'هيكل تنظيم الكورسات/المناهج داخل المكتبة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مركز التحكم بالمعلمين',
                        'route' => 'admin.teachers.index',
                        'body' => 'مركز تشغيل المعلمين المعتمدين يومياً.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تبديل معلم (بدون خصم رصيد)',
                        'route' => 'admin.teacher-switch.create',
                        'body' => 'بدّل معلم لطالب بدون خصم رصيد المحفظة/الباقة.',
                        'tips' => array (
  0 => 'الرصيد لا يُخصم عند التبديل',
),
                    ],
                    [
                        'title' => 'توصيف الطلاب للمعلمين',
                        'route' => 'admin.academy-instructors.index',
                        'body' => 'توصيف وربط الطلاب بالمعلمين المناسبين أكاديمياً.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إدارة الكورسات',
                        'route' => 'admin.advanced-courses.index',
                        'body' => 'إدارة الكورسات المسجّلة (VOD) — مسار مستقل عن حجز 1:1.',
                        'tips' => array (
  0 => 'لا تخلطها مع حضور الحصص الحية',
),
                    ],
                    [
                        'title' => 'تصنيفات الكورسات',
                        'route' => 'admin.course-categories.index',
                        'body' => 'تصنيفات ظهور وتنظيم الكورسات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'دروس الكورس',
                        'route' => 'admin.lectures.index',
                        'body' => 'دروس/محاضرات داخل الكورسات المسجّلة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الواجبات والمشاريع',
                        'route' => 'admin.assignments.index',
                        'body' => 'واجبات ومشاريع مرتبطة بالمحتوى.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الاختبارات',
                        'route' => 'admin.exams.index',
                        'body' => 'اختبارات الكورسات وإدارتها.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'بنك الأسئلة',
                        'route' => 'admin.question-bank.index',
                        'body' => 'بنك الأسئلة المستخدم في الاختبارات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الإنجازات',
                        'route' => 'admin.achievements.index',
                        'body' => 'إنجازات تحفيزية للطلاب (إن كانت مفعّلة).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الشارات',
                        'route' => 'admin.badges.index',
                        'body' => 'شارات تُمنح حسب قواعد المنصة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التقييمات والمراجعات',
                        'route' => 'admin.reviews.index',
                        'body' => 'مراجعات وتقييمات المحتوى/التجارب.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'جلسات البث المباشر',
                        'route' => 'admin.live-sessions.index',
                        'body' => 'جدولة ومتابعة جلسات البث المباشر.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تسجيلات الجلسات',
                        'route' => 'admin.live-recordings.index',
                        'body' => 'أرشيف تسجيلات الجلسات المباشرة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير n8n',
                        'route' => 'admin.n8n.live-session-reports.index',
                        'body' => 'تقارير آتية من تكامل n8n حول الجلسات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إعداد تكامل n8n',
                        'route' => 'admin.n8n.settings',
                        'body' => 'إعداد ربط n8n (ويب هوك/مفاتيح) مع الحذر في الإنتاج.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تسجيلات Classroom',
                        'route' => 'admin.classroom-recordings.index',
                        'body' => 'تسجيلات Classroom المرتبطة بالحصص.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'سيرفرات البث (VPS)',
                        'route' => 'admin.live-servers.index',
                        'body' => 'إدارة سيرفرات البث (VPS).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'لوحة التحكم بالسيرفرات',
                        'route' => 'admin.live-servers.control',
                        'body' => 'لوحة تشغيل/مراقبة السيرفرات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إعدادات نظام اللايف',
                        'route' => 'admin.live-settings.index',
                        'body' => 'إعدادات نظام اللايف العامة (حدود، جودة، سياسات).',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'team',
                'title' => 'الفريق',
                'icon' => 'fas fa-users-cog',
                'summary' => 'الموظفون، الوظائف، المهام، الإشراف، طلبات المدربين، والإجازات.',
                'audience' => 'مدير عام · موارد بشرية تشغيلية',
                'decision' => 'امنح أقل صلاحية كافية للمهمة.',
                'steps' => [
                    'أنشئ الموظف والوظيفة.',
                    'اربط المهام.',
                    'راجع الإجازات وطلبات المدربين.',
                ],
                'screenshot' => 'team.png',
                'items' => [
                    [
                        'title' => 'الموظفون',
                        'route' => 'admin.employees.index',
                        'body' => 'حسابات موظفي الإدارة وتشغيلهم.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الوظائف',
                        'route' => 'admin.employee-jobs.index',
                        'body' => 'الوظائف/المسميات وربطها بصلاحيات مناسبة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مهام الموظفين',
                        'route' => 'admin.employee-tasks.index',
                        'body' => 'مهام داخلية على فريق الإدارة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الإشراف الأكاديمي',
                        'route' => 'admin.academic-supervision.index',
                        'body' => 'متابعة جودة أكاديمية وإشراف على الأداء التدريسي.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'مهام المدربين',
                        'route' => 'admin.tasks.index',
                        'body' => 'مهام موجهة للمدربين ومتابعة إنجازها.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'طلبات انضمام المدربين',
                        'route' => 'admin.instructor-requests.index',
                        'body' => 'طلبات/تذاكر واردة من المدربين للتشغيل.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'طلبات الإجازة',
                        'route' => 'admin.leaves.index',
                        'body' => 'طلبات إجازة الموظفين واعتمادها.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'quality',
                'title' => 'الرقابة والجودة',
                'icon' => 'fas fa-shield-alt',
                'summary' => 'رقابة على الطلاب والمعلمين والموظفين والعمليات.',
                'audience' => 'مشرف أكاديمي · مدير عام · جودة',
                'decision' => 'استخدمها لاكتشاف الخلل مبكراً قبل ما يوصل للدعم أو المالية.',
                'steps' => [
                    'افتح لوحة الرقابة.',
                    'راجع مسار الطلاب ثم المعلمين ثم العمليات.',
                ],
                'screenshot' => 'quality.png',
                'items' => [
                    [
                        'title' => 'لوحة الرقابة',
                        'route' => 'admin.quality-control.index',
                        'body' => 'لوحة رقابة شاملة: أين الخلل التشغيلي؟',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رقابة الطلاب',
                        'route' => 'admin.quality-control.students',
                        'body' => 'رقابة جودة تجربة ومسار الطلاب.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رقابة المدربين',
                        'route' => 'admin.quality-control.instructors',
                        'body' => 'رقابة أداء والتزام المعلمين.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'رقابة الموظفين',
                        'route' => 'admin.quality-control.employees',
                        'body' => 'رقابة أداء فريق الإدارة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'متابعة العمليات',
                        'route' => 'admin.quality-control.operations',
                        'body' => 'متابعة العمليات اليومية والاختناقات.',
                        'tips' => array (
),
                    ],
                ],
            ],
            [
                'id' => 'more',
                'title' => 'المزيد',
                'icon' => 'fas fa-ellipsis-h',
                'summary' => 'شهادات، صلاحيات/أدوار، رسائل، وتقارير شاملة — حسب تفعيل الميزة والصلاحية.',
                'audience' => 'حسب الصلاحية',
                'decision' => 'غياب رابط غالباً صلاحية أو إعداد إخفاء (مثل الشهادات) وليس عطلاً.',
                'steps' => [
                    'افتح ما يظهر في سايدبارك فقط.',
                    'للصلاحيات: أدوار ← صلاحيات ← صلاحيات مستخدم.',
                ],
                'screenshot' => 'more.png',
                'items' => [
                    [
                        'title' => 'قائمة الشهادات',
                        'route' => 'admin.certificates.index',
                        'body' => 'قائمة الشهادات الصادرة وحالاتها (إن كانت الشهادات مفعّلة).',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'إصدار شهادة',
                        'route' => 'admin.certificates.create',
                        'body' => 'إصدار شهادة يدوياً لطالب/مسار.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الأدوار',
                        'route' => 'admin.roles.index',
                        'body' => 'تعريف أدوار RBAC وربط الصلاحيات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الصلاحيات',
                        'route' => 'admin.permissions.index',
                        'body' => 'كتالوج الصلاحيات المتاحة في النظام.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'صلاحيات المستخدمين',
                        'route' => 'admin.user-permissions.index',
                        'body' => 'صلاحيات مخصصة على مستوى مستخدم معيّن.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'الرسائل',
                        'route' => 'admin.messages.index',
                        'body' => 'رسائل داخلية إدارية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'لوحة التقارير',
                        'route' => 'admin.reports.index',
                        'body' => 'لوحة دخول للتقارير الشاملة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير المستخدمين',
                        'route' => 'admin.reports.users',
                        'body' => 'تقارير عن المستخدمين ونشاطهم.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير الكورسات',
                        'route' => 'admin.reports.courses',
                        'body' => 'تقارير الكورسات والاشتراكات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التقارير المالية',
                        'route' => 'admin.reports.financial',
                        'body' => 'تقارير مالية تجميعية.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التقارير الأكاديمية',
                        'route' => 'admin.reports.academic',
                        'body' => 'تقارير أكاديمية/جودة.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'تقارير النشاط',
                        'route' => 'admin.reports.activities',
                        'body' => 'تقارير النشاط والسجلات.',
                        'tips' => array (
),
                    ],
                    [
                        'title' => 'التقرير الشامل',
                        'route' => 'admin.reports.comprehensive',
                        'body' => 'تقرير شامل يجمع عدة محاور دفعة واحدة.',
                        'tips' => array (
),
                    ],
                ],
            ],
        ];
    }

    /**
     * مسارات السايدبار الحالي يجب أن تكون مغطاة بالكامل.
     * @return list<string>
     */
    public static function expectedSidebarRoutes(): array
    {
        $routes = [];
        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (! empty($item['route'])) {
                    $routes[] = $item['route'];
                }
            }
        }

        return array_values(array_unique($routes));
    }

    /**
     * @return list<array{route: string, file: string}>
     */
    public static function screenshotTargets(): array
    {
        return [
            ['route' => 'admin.academy-insights.index', 'file' => 'insights.png'],
            ['route' => 'admin.dashboard', 'file' => 'home.png'],
            ['route' => 'admin.free-trial-bookings.index', 'file' => 'ops.png'],
            ['route' => 'admin.tutor-applications.hub', 'file' => 'hiring.png'],
            ['route' => 'admin.site-services.index', 'file' => 'site.png'],
            ['route' => 'admin.students-accounts.index', 'file' => 'students.png'],
            ['route' => 'admin.orders.index', 'file' => 'sales.png'],
            ['route' => 'admin.invoices.index', 'file' => 'finance.png'],
            ['route' => 'admin.users.index', 'file' => 'system.png'],
            ['route' => 'admin.agreements.index', 'file' => 'agreements.png'],
            ['route' => 'admin.coupons.index', 'file' => 'marketing.png'],
            ['route' => 'admin.service-packages.index', 'file' => 'paid.png'],
            ['route' => 'admin.advanced-courses.index', 'file' => 'product.png'],
            ['route' => 'admin.employees.index', 'file' => 'team.png'],
            ['route' => 'admin.quality-control.index', 'file' => 'quality.png'],
            ['route' => 'admin.roles.index', 'file' => 'more.png'],
        ];
    }
}
