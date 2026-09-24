<?php

namespace App\Support;

/**
 * المرجع التشغيلي للوحة إدارة حصتك — يطابق أقسام السايدبار.
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
                    'حدّد العناصر الحمراء/العاجلة (تقارير متأخرة، حجوزات، دعم).',
                    'انتقل مباشرة للقسم المسؤول من السايدبار.',
                ],
                'screenshot' => 'insights.png',
                'items' => [
                    [
                        'title' => 'تحليلات وتوجهات',
                        'route' => 'admin.academy-insights.index',
                        'body' => 'ملخص حي لاتجاهات الحجوزات، الجودة، والمالية. استخدمه كرادار تشغيل وليس كتقرير أرشيفي.',
                        'tips' => ['حدّث الصفحة عند بداية الوردية', 'لا تعتمد على الرقم وحده — افتح الصف المتأثر'],
                    ],
                ],
            ],
            [
                'id' => 'home',
                'title' => 'الرئيسية',
                'icon' => 'fas fa-chart-line',
                'summary' => 'نقطة الدخول الشخصية: لوحة التحكم، وارد الإشعارات، والملف.',
                'audience' => 'كل أدوار الإدارة',
                'decision' => 'استخدم الإشعارات كقائمة مهام قصيرة قبل التعمق.',
                'steps' => [
                    'راجع لوحة التحكم للأرقام الكلية.',
                    'افتح وارد الإشعارات وعلّم المقروء.',
                    'حدّث ملفك إن تغيّر الهاتف أو التوقيت.',
                ],
                'screenshot' => 'home.png',
                'items' => [
                    [
                        'title' => 'لوحة التحكم',
                        'route' => 'admin.dashboard',
                        'body' => 'نظرة عامة على الطلاب، المعلمين، الطلبات، والحصص. ليست مكان التنفيذ التفصيلي.',
                        'tips' => ['إن ظهر رقم غريب انتقل لقسم المصدر (طلبات/مالية/تشغيل)'],
                    ],
                    [
                        'title' => 'وارد الإشعارات',
                        'route' => 'admin.notifications.inbox',
                        'body' => 'صندوق تنبيهات الموظف: طلبات، دعم، تقارير، موافقات.',
                        'tips' => ['لا تترك غير مقروء أكثر من يوم عمل'],
                    ],
                    [
                        'title' => 'الملف الشخصي',
                        'route' => 'admin.profile',
                        'body' => 'بيانات الحساب الإداري وكلمة المرور.',
                        'tips' => ['فعّل التحقق الثنائي إن طُلب من السياسة'],
                    ],
                ],
            ],
            [
                'id' => 'ops',
                'title' => 'التشغيل',
                'icon' => 'fas fa-bolt',
                'summary' => 'الطابور اليومي للتواصل والحصة التجريبية — أسرع نقاط تماس مع الزائر.',
                'audience' => 'علاقات عملاء · تشغيل',
                'decision' => 'كل رسالة تواصل أو حصة مجانية = فرصة تحويل أو ثقة.',
                'steps' => [
                    'راجع الحصص المجانية القادمة اليوم.',
                    'أرد على رسائل التواصل غير المقروءة.',
                    'سجّل نتيجة المتابعة في CRM إن لزم.',
                ],
                'screenshot' => 'ops.png',
                'items' => [
                    [
                        'title' => 'الحصة المجانية',
                        'route' => 'admin.free-trial-bookings.index',
                        'body' => 'إدارة حجوزات التجربة: تأكيد، إلغاء، متابعة الحضور. هذه البوابة الأولى لكثير من أولياء الأمور.',
                        'tips' => ['تأكيد الموعد قبل 24 ساعة', 'بعد الحضور ادفع الرصاص نحو باقة أو مطابقة معلم'],
                    ],
                    [
                        'title' => 'رسائل التواصل',
                        'route' => 'admin.contact-messages.index',
                        'body' => 'وارد نموذج «تواصل معنا» من الموقع العام.',
                        'tips' => ['صنّف الاستفسار (حجز / شكوى / توظيف)', 'حوّل لـ CRM إن كان lead مبيعات'],
                    ],
                ],
            ],
            [
                'id' => 'hiring',
                'title' => 'التوظيف',
                'icon' => 'fas fa-user-tie',
                'summary' => 'مسار اعتماد المعلم: تقديم + تخصص → مقابلة → عقد → تفعيل.',
                'audience' => 'فريق توظيف · مشرف أكاديمي',
                'decision' => 'لا يُفعَّل معلم قبل اكتمال المراجعة والمطابقة (مرحلة+مادة+منهج) والمقابلة/العقد حسب الإعدادات.',
                'steps' => [
                    'راجع الطلبات الجديدة وتحقق من التخصص الثلاثي.',
                    'ادعُ للمقابلة وافتح مواعيد السلوتات.',
                    'بعد النجاح أرسل عرض العقد للتوقيع ثم فعّل الحساب.',
                ],
                'screenshot' => 'hiring.png',
                'items' => [
                    [
                        'title' => 'منشئ نموذج التقديم',
                        'route' => 'admin.hiring-form.edit',
                        'body' => 'يبني حقول صفحة تقديم المعلم على الموقع.',
                        'tips' => ['لا تكثر الحقول الاختيارية بلا فائدة تشغيلية'],
                    ],
                    [
                        'title' => 'مواعيد المقابلات',
                        'route' => 'admin.tutor-interview-slots.index',
                        'body' => 'أوقات يختار منها المرشح؛ تأكيد إيميل/واتساب تلقائي.',
                        'tips' => ['اترك سعة كافية؛ راقب المتغيبين'],
                    ],
                    [
                        'title' => 'إعدادات البروسيس',
                        'route' => 'admin.hiring.settings.edit',
                        'body' => 'تفعيل/تعطيل المقابلة والعقد ودقائق حجب التغيب والقوالب.',
                        'tips' => ['عدّل القوالب دون كسر placeholders'],
                    ],
                    [
                        'title' => 'لوحة التوظيف',
                        'route' => 'admin.tutor-applications.hub',
                        'body' => 'مركز قرار لطلبات التوظيف والحالات.',
                        'tips' => ['افصل «قيد المراجعة» عن «بانتظار التفعيل»'],
                    ],
                    [
                        'title' => 'مراجعة الطلبات',
                        'route' => 'admin.tutor-applications.index',
                        'body' => 'قائمة الطلبات التفصيلية مع الفلاتر حسب الحالة.',
                        'tips' => ['وثّق سبب الرفض باختصار'],
                    ],
                    [
                        'title' => 'المعلمون المفعّلون',
                        'route' => 'admin.tutor-applications.activated',
                        'body' => 'من اكتمل تفعيلهم وصاروا جاهزين للتدريس.',
                        'tips' => ['بعد التفعيل راجع ملف المطابقة والتوافر'],
                    ],
                ],
            ],
            [
                'id' => 'site',
                'title' => 'الموقع',
                'icon' => 'fas fa-globe',
                'summary' => 'محتوى الواجهة العامة: خدمات، آراء، من نحن، FAQ، وإعدادات النظام الظاهرة للزائر.',
                'audience' => 'تسويق · إدارة محتوى',
                'decision' => 'أي نص عام يجب أن يطابق براند حصتك (أمريكا · مصر · السعودية فقط في الحضور المعلن).',
                'steps' => [
                    'حدّث الشهادات/الآراء عند توفر محتوى جديد.',
                    'راجع FAQ بعد أي تغيير في السياسات.',
                    'لا تغيّر إعدادات النظام إلا بقرار واضح.',
                ],
                'screenshot' => 'site.png',
                'items' => [
                    ['title' => 'خدمات الموقع', 'route' => 'admin.site-services.index', 'body' => 'بطاقات الخدمات على الصفحات العامة.', 'tips' => []],
                    ['title' => 'آراء الرئيسية', 'route' => 'admin.site-testimonials.index', 'body' => 'شهادات أولياء الأمور/الطلاب في الهوم.', 'tips' => ['فضّل شهادات حقيقية مع سياق منهج/مادة']],
                    ['title' => 'من نحن', 'route' => 'admin.about.index', 'body' => 'محتوى صفحة التعريف بالمنصة.', 'tips' => []],
                    ['title' => 'الأسئلة الشائعة', 'route' => 'admin.faq.index', 'body' => 'FAQ للزائر والطالب/المعلم.', 'tips' => []],
                    ['title' => 'إعدادات النظام', 'route' => 'admin.system-settings.edit', 'body' => 'إعدادات عامة للمنصة (عملة، سياسات ظاهرة، …).', 'tips' => ['اختبر الأثر على الدفع والواجهة بعد أي حفظ']],
                ],
            ],
            [
                'id' => 'students',
                'title' => 'الطلاب',
                'icon' => 'fas fa-user-graduate',
                'summary' => 'دورة حياة الطالب: حساب، ولي أمر، مطابقة/تسكين، رصيد حصص، حصص 1:1، تقارير، دعم.',
                'audience' => 'تشغيل · علاقات عملاء · مشرف أكاديمي',
                'decision' => 'الحصة لا تُغلق مالياً قبل تقرير المعلم الإلزامي.',
                'steps' => [
                    'أنشئ/فعّل حساب الطالب أو اربط ولي الأمر.',
                    'امنح رصيد باقة أو سجّل تسكيناً.',
                    'تابع الحصص والتقارير وطابور التقارير المتأخرة.',
                    'عالج تذاكر الدعم والاستشارات.',
                ],
                'screenshot' => 'students.png',
                'items' => [
                    ['title' => 'حسابات الطلاب', 'route' => 'admin.students-accounts.index', 'body' => 'إنشاء وتفعيل وتعطيل حسابات الطلاب.', 'tips' => ['تحقق من رقم الهاتف والتوقيت']],
                    ['title' => 'أولياء الأمور', 'route' => 'admin.parents.index', 'body' => 'ربط ولي الأمر بأبنائه ومتابعة الحسابات العائلية.', 'tips' => []],
                    ['title' => 'التسكين / المطابقة', 'route' => 'admin.placement.index', 'body' => 'تسكين طالب مع معلم حسب المرحلة والمادة ونوع المنهج.', 'tips' => ['المطابقة ثلاثية إلزامية']],
                    ['title' => 'أرصدة الحصص', 'route' => 'admin.student-entitlements.index', 'body' => 'رصيد باقات الحصص الفردية للطالب.', 'tips' => ['تبديل المعلم بلا خصم رصيد']],
                    ['title' => 'الحصص الخاصة 1:1', 'route' => 'admin.one-to-one-sessions.index', 'body' => 'جدول الجلسات الفردية وحالاتها.', 'tips' => []],
                    ['title' => 'طابور التقارير', 'route' => 'admin.one-to-one-report-queue.index', 'body' => 'تقارير معلمين ناقصة تمنع إقفال الأجر.', 'tips' => ['تابع المتأخر فوراً']],
                    ['title' => 'تنظيف الحصص', 'route' => 'admin.student-lesson-cleanup.index', 'body' => 'أدوات صيانة لحالات حصص معلّقة أو غير مكتملة.', 'tips' => []],
                    ['title' => 'تسجيلات الكورسات', 'route' => 'admin.online-enrollments.index', 'body' => 'اشتراكات الطلاب في الكورسات المسجّلة.', 'tips' => []],
                    ['title' => 'الحضور', 'route' => 'admin.attendance.index', 'body' => 'سجلات حضور المحاضرات (إن فُعّلت).', 'tips' => ['للكورسات المسجّلة الاعتماد على تقدّم المشاهدة']],
                    ['title' => 'دعم فني', 'route' => 'admin.support-tickets.index', 'body' => 'تذاكر الطلاب حتى الإغلاق.', 'tips' => []],
                    ['title' => 'تصنيفات الاستفسار', 'route' => 'admin.support-inquiry-categories.index', 'body' => 'تصنيفات نموذج الدعم.', 'tips' => []],
                    ['title' => 'الاستشارات', 'route' => 'admin.consultations.index', 'body' => 'طلبات استشارة مرتبطة بالمعلمين/التشغيل.', 'tips' => []],
                ],
            ],
            [
                'id' => 'sales',
                'title' => 'المبيعات',
                'icon' => 'fas fa-chart-pie',
                'summary' => 'CRM وخط الأنابيب والطلبات — من lead إلى طلب مدفوع.',
                'audience' => 'مبيعات · CRM',
                'decision' => 'تأكيد الدفع النهائي من webhook البوابة فقط، لا من إعادة التوجيه.',
                'steps' => [
                    'راجع لوحة CRM والـ pipeline.',
                    'تابع الـ leads والمبيعات.',
                    'افتح الطلبات للمراجعة/الاعتماد.',
                ],
                'screenshot' => 'sales.png',
                'items' => [
                    ['title' => 'لوحة CRM', 'route' => 'admin.crm.dashboard', 'body' => 'مؤشرات المبيعات والفرص.', 'tips' => []],
                    ['title' => 'خط الأنابيب', 'route' => 'admin.crm.pipeline', 'body' => 'مراحل الفرصة من تواصل حتى إغلاق.', 'tips' => []],
                    ['title' => 'العملاء المحتملون (CRM)', 'route' => 'admin.crm.leads.index', 'body' => 'إدارة leads داخل CRM.', 'tips' => []],
                    ['title' => 'عمولات CRM', 'route' => 'admin.crm.commissions.index', 'body' => 'عمولات فرق المبيعات.', 'tips' => []],
                    ['title' => 'تدقيق CRM', 'route' => 'admin.crm.audit.index', 'body' => 'سجل تغييرات CRM.', 'tips' => []],
                    ['title' => 'مجموعات CRM', 'route' => 'admin.crm.groups.index', 'body' => 'تنظيم فرق المبيعات.', 'tips' => []],
                    ['title' => 'ليدز المبيعات', 'route' => 'admin.sales.leads.index', 'body' => 'قائمة leads مسار المبيعات.', 'tips' => []],
                    ['title' => 'المبيعات', 'route' => 'admin.sales.index', 'body' => 'ملخص عمليات البيع.', 'tips' => []],
                    ['title' => 'الطلبات', 'route' => 'admin.orders.index', 'body' => 'كل طلبات الشراء (باقات/كورسات) وحالات الاعتماد والدفع.', 'tips' => ['لا تعتمد طلباً يدوياً قبل تأكيد البوابة']],
                ],
            ],
            [
                'id' => 'finance',
                'title' => 'المالية',
                'icon' => 'fas fa-coins',
                'summary' => 'فواتير، مدفوعات، محافظ، رواتب معلمين، مصروفات، تقسيط، وتقارير.',
                'audience' => 'محاسب مالي · مدير عام',
                'decision' => 'كل حركة مالية يجب أن تبقى في السجل الدائم — لا تعديل مباشر بلا أثر.',
                'steps' => [
                    'راجع المدفوعات والطلبات غير المكتملة.',
                    'تابع محافظ المنصة ومحافظ المستخدمين.',
                    'جهز رواتب المعلمين بعد اكتمال التقارير.',
                ],
                'screenshot' => 'finance.png',
                'items' => [
                    ['title' => 'الفواتير', 'route' => 'admin.invoices.index', 'body' => 'إصدار ومتابعة فواتير العملاء.', 'tips' => []],
                    ['title' => 'المدفوعات', 'route' => 'admin.payments.index', 'body' => 'حركات الدفع المرتبطة بالبوابات.', 'tips' => []],
                    ['title' => 'المعاملات', 'route' => 'admin.transactions.index', 'body' => 'دفتر حركات مالي شامل.', 'tips' => []],
                    ['title' => 'المحافظ', 'route' => 'admin.wallets.index', 'body' => 'محافظ المستخدمين (طلاب/معلمون).', 'tips' => []],
                    ['title' => 'محافظ المنصة', 'route' => 'admin.platform-wallets.index', 'body' => 'تجميع أرصدة المنصة مقابل الطلاب والمعلمين.', 'tips' => []],
                    ['title' => 'رواتب المدربين', 'route' => 'admin.salaries.index', 'body' => 'احتساب وصرف مستحقات المعلمين.', 'tips' => ['لا تصرف قبل اكتمال التقرير الإلزامي']],
                    ['title' => 'حسابات المدربين', 'route' => 'admin.accounting.instructor-accounts.index', 'body' => 'حسابات محاسبية مرتبطة بكل معلم.', 'tips' => []],
                    ['title' => 'المصروفات', 'route' => 'admin.expenses.index', 'body' => 'تسجيل مصروفات التشغيل.', 'tips' => []],
                    ['title' => 'خطط التقسيط', 'route' => 'admin.installments.plans.index', 'body' => 'قوالب خطط التقسيط.', 'tips' => []],
                    ['title' => 'اتفاقيات التقسيط', 'route' => 'admin.installments.agreements.index', 'body' => 'اتفاقيات تقسيط فعّالة مع العملاء.', 'tips' => []],
                    ['title' => 'تقارير المحاسبة', 'route' => 'admin.accounting.reports', 'body' => 'تقارير دورية للإيراد والمصروف.', 'tips' => []],
                ],
            ],
            [
                'id' => 'system',
                'title' => 'النظام',
                'icon' => 'fas fa-cogs',
                'summary' => 'المستخدمون، بوابات الدفع، الإشعارات، السجلات، والإحصاءات.',
                'audience' => 'مدير عام · تقنية',
                'decision' => 'صلاحية تُتحقق عند التنفيذ — لا تعتمد على إخفاء الزر فقط.',
                'steps' => [
                    'راجع المستخدمين والأدوار عند تعيين موظف جديد.',
                    'اختبر بوابة الدفع في وضع آمن قبل الإنتاج.',
                    'راقب سجل النشاط والتحقق الثنائي.',
                ],
                'screenshot' => 'system.png',
                'items' => [
                    ['title' => 'المستخدمون', 'route' => 'admin.users.index', 'body' => 'حسابات النظام والأدوار.', 'tips' => []],
                    ['title' => 'بوابات الدفع', 'route' => 'admin.payment-gateways.index', 'body' => 'إعداد فواتيرك/PayPal/غيرها.', 'tips' => ['لا تخلط مفاتيح الاختبار بالإنتاج']],
                    ['title' => 'إشعارات النظام', 'route' => 'admin.notifications.index', 'body' => 'قوالب/إرسال إشعارات المنصة.', 'tips' => []],
                    ['title' => 'إشعارات الموظفين', 'route' => 'admin.employee-notifications.index', 'body' => 'تنبيهات داخلية للفريق.', 'tips' => []],
                    ['title' => 'سجل النشاط', 'route' => 'admin.activity-log', 'body' => 'من فعل ماذا ومتى.', 'tips' => []],
                    ['title' => 'سجلات التحقق الثنائي', 'route' => 'admin.two-factor-logs.index', 'body' => 'محاولات 2FA.', 'tips' => []],
                    ['title' => 'الإحصاءات', 'route' => 'admin.statistics.index', 'body' => 'إحصاءات منصة عامة.', 'tips' => []],
                    ['title' => 'الأداء', 'route' => 'admin.performance.index', 'body' => 'مؤشرات أداء تشغيلية.', 'tips' => []],
                ],
            ],
            [
                'id' => 'agreements',
                'title' => 'الاتفاقيات',
                'icon' => 'fas fa-file-contract',
                'summary' => 'اتفاقيات معلمين وموظفين وطلبات السحب.',
                'audience' => 'مالي · موارد بشرية تشغيلية · مدير عام',
                'decision' => 'لا تُصرف مستحقات قبل اكتمال الاتفاق المطلوب.',
                'steps' => [
                    'راجع اتفاقيات المعلمين الجديدة.',
                    'تابع طلبات السحب والموافقات.',
                ],
                'screenshot' => 'agreements.png',
                'items' => [
                    ['title' => 'اتفاقيات المعلمين', 'route' => 'admin.agreements.index', 'body' => 'عقود/اتفاقيات المعلمين مع المنصة.', 'tips' => []],
                    ['title' => 'اتفاقيات الموظفين', 'route' => 'admin.employee-agreements.index', 'body' => 'اتفاقيات فريق الإدارة.', 'tips' => []],
                    ['title' => 'طلبات السحب', 'route' => 'admin.withdrawals.index', 'body' => 'طلبات سحب المستحقات من محافظ المعلمين.', 'tips' => ['اربط الموافقة بحالة التقارير']],
                ],
            ],
            [
                'id' => 'marketing',
                'title' => 'التسويق',
                'icon' => 'fas fa-bullhorn',
                'summary' => 'إعلانات منبثقة، كوبونات، إحالات وعمولات تسويق.',
                'audience' => 'تسويق · مبيعات',
                'decision' => 'كل عرض يجب أن يكون قابلاً للقياس عبر كود/إحالة.',
                'steps' => [
                    'أنشئ كوبوناً بحملة واضحة.',
                    'راقب الإحالات والعمولات.',
                ],
                'screenshot' => 'marketing.png',
                'items' => [
                    ['title' => 'إعلانات منبثقة', 'route' => 'admin.popup-ads.index', 'body' => 'Popups على الموقع.', 'tips' => ['لا تكثرها على الجوال']],
                    ['title' => 'كوبونات وخصومات', 'route' => 'admin.coupons.index', 'body' => 'أكواد الخصم.', 'tips' => []],
                    ['title' => 'عمولات الكوبونات', 'route' => 'admin.coupon-commissions.index', 'body' => 'عمولات المسوقين على الكوبونات.', 'tips' => []],
                    ['title' => 'برامج الإحالة', 'route' => 'admin.referral-programs.index', 'body' => 'إعداد برامج دعوة الأصدقاء.', 'tips' => []],
                    ['title' => 'الإحالات', 'route' => 'admin.referrals.index', 'body' => 'سجل الإحالات الفعلية.', 'tips' => []],
                ],
            ],
            [
                'id' => 'paid',
                'title' => 'مدفوع — الباقات والأسعار',
                'icon' => 'fas fa-credit-card',
                'summary' => 'باقات الحصص الفردية وأسعارها ومنحها يدوياً.',
                'audience' => 'مبيعات · مالي · تشغيل',
                'decision' => 'الباقة = رصيد ساعات للحجز 1:1 — ليست اشتراك كورس مسجّل.',
                'steps' => [
                    'عرّف باقة الحصص وعدد الوحدات.',
                    'اضبط قواعد التسعير إن وُجدت.',
                    'امنح باقة يدوياً عند الاستثناء المعتمد.',
                ],
                'screenshot' => 'paid.png',
                'items' => [
                    ['title' => 'باقات الأسعار', 'route' => 'admin.packages.index', 'body' => 'باقات تسعير عامة/تسويقية.', 'tips' => []],
                    ['title' => 'باقات الحصص', 'route' => 'admin.service-packages.index', 'body' => 'باقات رصيد الحصص الفردية.', 'tips' => []],
                    ['title' => 'منح باقة يدوياً', 'route' => 'admin.service-packages.grant', 'body' => 'إضافة رصيد لطالب بدون checkout.', 'tips' => ['سجّل السبب في الملاحظة']],
                    ['title' => 'تسعير خصص باقتك', 'route' => 'admin.service-package-pricing-rules.index', 'body' => 'قواعد تسعير/خصم على الباقات.', 'tips' => []],
                ],
            ],
            [
                'id' => 'product',
                'title' => 'المنتج التعليمي',
                'icon' => 'fas fa-graduation-cap',
                'summary' => 'مطابقة المناهج، المكتبات، تشغيل المعلمين، الكورسات المسجّلة، والبث.',
                'audience' => 'مشرف أكاديمي · محتوى · تشغيل معلمين',
                'decision' => 'مسار 1:1 منفصل عن مسار الكورسات المسجّلة — لا تخلط التسعير أو الحضور.',
                'steps' => [
                    'حدّث كتالوج المطابقة (مرحلة · مادة · نوع منهج).',
                    'راجع ملفات المعلمين للمطابقة.',
                    'أدِر محتوى الكورسات المسجّلة والمحاضرات.',
                    'راقب جلسات البث إن فُعّلت.',
                ],
                'screenshot' => 'product.png',
                'items' => [
                    ['title' => 'المراحل الدراسية', 'route' => 'admin.academic-years.index', 'body' => 'مراحل المطابقة (ابتدائي/متوسط/…).', 'tips' => []],
                    ['title' => 'المواد / مجموعات المهارات', 'route' => 'admin.academic-subjects.index', 'body' => 'المواد المرتبطة بالمطابقة.', 'tips' => []],
                    ['title' => 'أنواع المناهج', 'route' => 'admin.curriculum-types.index', 'body' => 'نوع المنهج (سعودي/…). صياغة «منهج …» وليس فروعاً جغرافية.', 'tips' => []],
                    ['title' => 'ملفات المعلمين (المطابقة)', 'route' => 'admin.personal-branding.index', 'body' => 'بروفايل المعلم المستخدم في دليل الحجز.', 'tips' => ['اكتمال الملف شرط ظهور جيد في الدليل']],
                    ['title' => 'مكتبة الملفات', 'route' => 'admin.libraries.index', 'body' => 'مركز ماتريال وفيديو وهيكل.', 'tips' => []],
                    ['title' => 'تشغيل المعلمين', 'route' => 'admin.teachers.index', 'body' => 'مركز تحكم المعلمين وتبديل المعلم بلا خصم رصيد.', 'tips' => []],
                    ['title' => 'الكورسات المسجّلة', 'route' => 'admin.advanced-courses.index', 'body' => 'إدارة كورسات VOD المستقلة.', 'tips' => ['لا تخلطها مع حضور الحصص الحية']],
                    ['title' => 'البث المباشر', 'route' => 'admin.live-sessions.index', 'body' => 'جلسات البث والتسجيلات والسيرفرات.', 'tips' => []],
                ],
            ],
            [
                'id' => 'team',
                'title' => 'الفريق',
                'icon' => 'fas fa-users-cog',
                'summary' => 'موظفو الإدارة والصلاحيات والمهام الداخلية.',
                'audience' => 'مدير عام',
                'decision' => 'امنح أقل صلاحية كافية للمهمة (least privilege).',
                'steps' => [
                    'أنشئ دور الموظف.',
                    'اربط الصلاحيات من RBAC.',
                    'راجع المهام الداخلية عند الحاجة.',
                ],
                'screenshot' => 'team.png',
                'items' => [
                    ['title' => 'الفريق والصلاحيات', 'route' => null, 'body' => 'إدارة الموظفين والأدوار من مجموعة «الفريق» في السايدبار (مستخدمون موظفون / أدوار / مهام).', 'tips' => ['لا تعطِ admin.access بلا حاجة']],
                ],
            ],
            [
                'id' => 'more',
                'title' => 'المزيد',
                'icon' => 'fas fa-ellipsis-h',
                'summary' => 'أدوات إضافية وإعدادات متفرقة تظهر حسب الصلاحية.',
                'audience' => 'حسب الصلاحية',
                'decision' => 'افتح القسم فقط إن ظهر في سايدبارك — غياب الرابط غالباً صلاحية وليس عطلاً.',
                'steps' => [
                    'إن احتجت أداة غير ظاهرة اطلب الصلاحية من المدير.',
                ],
                'screenshot' => 'more.png',
                'items' => [
                    ['title' => 'أدوات إضافية', 'route' => null, 'body' => 'مجموعات فرعية تحت «المزيد» تختلف حسب تفعيل الميزات في المنصة.', 'tips' => []],
                ],
            ],
        ];
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
            ['route' => 'admin.roles.index', 'file' => 'more.png'],
        ];
    }
}
