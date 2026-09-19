<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // الوصول إلى لوحة الأدمن
            ['name' => 'admin.access', 'display_name' => 'الدخول إلى لوحة الأدمن', 'description' => 'إمكانية فتح لوحة تحكم الأدمن', 'group' => 'إدارة النظام'],

            // إدارة النظام
            ['name' => 'view.dashboard', 'display_name' => 'عرض لوحة التحكم', 'description' => 'إمكانية الوصول إلى لوحة التحكم', 'group' => 'إدارة النظام'],
            ['name' => 'manage.users', 'display_name' => 'إدارة المستخدمين', 'description' => 'إدارة المستخدمين (عرض، إضافة، تعديل، حذف)', 'group' => 'إدارة النظام'],
            ['name' => 'manage.orders', 'display_name' => 'إدارة الطلبات', 'description' => 'إدارة طلبات التسجيل في الكورسات', 'group' => 'إدارة النظام'],
            ['name' => 'manage.notifications', 'display_name' => 'إدارة الإشعارات', 'description' => 'إرسال وإدارة الإشعارات', 'group' => 'إدارة النظام'],
            ['name' => 'view.activity-log', 'display_name' => 'عرض سجل النشاطات', 'description' => 'عرض سجل نشاطات المستخدمين', 'group' => 'إدارة النظام'],
            ['name' => 'view.statistics', 'display_name' => 'عرض الإحصائيات', 'description' => 'عرض إحصائيات المنصة', 'group' => 'إدارة النظام'],
            ['name' => 'manage.roles', 'display_name' => 'إدارة الأدوار', 'description' => 'إدارة الأدوار والصلاحيات', 'group' => 'إدارة النظام'],
            ['name' => 'manage.permissions', 'display_name' => 'إدارة الصلاحيات', 'description' => 'إدارة الصلاحيات', 'group' => 'إدارة النظام'],
            ['name' => 'manage.user-permissions', 'display_name' => 'إدارة صلاحيات المستخدمين', 'description' => 'إدارة صلاحيات المستخدمين مباشرة', 'group' => 'إدارة النظام'],

            // إدارة المحاسبة
            ['name' => 'manage.invoices', 'display_name' => 'إدارة الفواتير', 'description' => 'إدارة الفواتير', 'group' => 'إدارة المحاسبة'],
            ['name' => 'manage.payments', 'display_name' => 'إدارة المدفوعات', 'description' => 'إدارة المدفوعات', 'group' => 'إدارة المحاسبة'],
            ['name' => 'manage.transactions', 'display_name' => 'إدارة المعاملات المالية', 'description' => 'إدارة المعاملات المالية', 'group' => 'إدارة المحاسبة'],
            ['name' => 'manage.wallets', 'display_name' => 'إدارة المحافظ', 'description' => 'إدارة محافظ المستخدمين', 'group' => 'إدارة المحاسبة'],
            // ['name' => 'manage.subscriptions', 'display_name' => 'إدارة الاشتراكات', 'description' => 'إدارة الاشتراكات', 'group' => 'إدارة المحاسبة'], // removed SaaS
            ['name' => 'manage.installments', 'display_name' => 'إدارة خطط التقسيط', 'description' => 'إدارة خطط التقسيط والاشتراكات الذكية', 'group' => 'إدارة المحاسبة'],

            // إدارة التسويق
            ['name' => 'manage.coupons', 'display_name' => 'إدارة الكوبونات', 'description' => 'إدارة الكوبونات والخصومات', 'group' => 'إدارة التسويق'],
            ['name' => 'manage.referrals', 'display_name' => 'إدارة برنامج الإحالات', 'description' => 'إدارة برنامج الإحالات', 'group' => 'إدارة التسويق'],
            ['name' => 'manage.loyalty', 'display_name' => 'إدارة برامج الولاء', 'description' => 'إدارة برامج الولاء', 'group' => 'إدارة التسويق'],

            // الشهادات والإنجازات
            ['name' => 'manage.certificates', 'display_name' => 'إدارة الشهادات', 'description' => 'إدارة الشهادات', 'group' => 'الشهادات والإنجازات'],
            ['name' => 'manage.achievements', 'display_name' => 'إدارة الإنجازات', 'description' => 'إدارة الإنجازات', 'group' => 'الشهادات والإنجازات'],
            ['name' => 'manage.badges', 'display_name' => 'إدارة الشارات', 'description' => 'إدارة الشارات', 'group' => 'الشهادات والإنجازات'],
            ['name' => 'manage.reviews', 'display_name' => 'إدارة التقييمات', 'description' => 'إدارة التقييمات والمراجعات', 'group' => 'الشهادات والإنجازات'],

            // إدارة المحتوى
            ['name' => 'manage.academic-years', 'display_name' => 'إدارة السنوات الدراسية', 'description' => 'إدارة السنوات الدراسية', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.academic-subjects', 'display_name' => 'إدارة المواد الدراسية', 'description' => 'إدارة المواد الدراسية', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.courses', 'display_name' => 'إدارة الكورسات', 'description' => 'إدارة الكورسات والدروس', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.enrollments', 'display_name' => 'إدارة تسجيل الطلاب', 'description' => 'إدارة تسجيل الطلاب في الكورسات', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.lectures', 'display_name' => 'إدارة المحاضرات', 'description' => 'إدارة المحاضرات', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.assignments', 'display_name' => 'إدارة الواجبات', 'description' => 'إدارة الواجبات والمشاريع', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.exams', 'display_name' => 'إدارة الامتحانات', 'description' => 'إدارة الامتحانات', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.question-bank', 'display_name' => 'إدارة بنك الأسئلة', 'description' => 'إدارة بنك الأسئلة', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.attendance', 'display_name' => 'إدارة الحضور (لوحة الأدمن)', 'description' => 'عرض وإدارة الحضور والربط مع Teams من لوحة التحكم', 'group' => 'إدارة المحتوى'],
            ['name' => 'manage.tutoring-groups', 'display_name' => 'إدارة المجموعات الفردية والجماعية', 'description' => 'إنشاء مجموعات الحجز (فردي/جماعي) وجداول عمل المدربين والحجوزات — منفصل عن الكورسات', 'group' => 'إدارة المحتوى'],

            // إدارة الصفحات الخارجية
            ['name' => 'manage.about-page', 'display_name' => 'إدارة صفحة «من نحن»', 'description' => 'تعديل محتوى صفحة من نحن (الإدارة العليا) من لوحة الأدمن', 'group' => 'إدارة الصفحات الخارجية'],
            ['name' => 'manage.faq', 'display_name' => 'إدارة الأسئلة الشائعة', 'description' => 'إدارة الأسئلة الشائعة', 'group' => 'إدارة الصفحات الخارجية'],
            ['name' => 'manage.contact-messages', 'display_name' => 'إدارة رسائل التواصل', 'description' => 'إدارة رسائل التواصل', 'group' => 'إدارة الصفحات الخارجية'],
                    ['name' => 'manage.free-trial-bookings', 'display_name' => 'إدارة الحجوزات المجانية', 'description' => 'طلبات وتنسيق الحصص المجانية (جدول المعلم أو توصيف يدوي) بدون نوافذ أسبوع عامة', 'group' => 'إدارة الصفحات الخارجية'],
            ['name' => 'manage.site-services', 'display_name' => 'إدارة خدمات الموقع', 'description' => 'إدارة صفحة الخدمات ومحتوى كل خدمة في الواجهة العامة', 'group' => 'إدارة الصفحات الخارجية'],
            ['name' => 'manage.site-testimonials', 'display_name' => 'إدارة آراء الموقع (الرئيسية)', 'description' => 'إدارة شهادات المعلمين الظاهرة في الصفحة الرئيسية وصفحة الآراء', 'group' => 'إدارة الصفحات الخارجية'],
            ['name' => 'manage.system-settings', 'display_name' => 'إعدادات النظام (الفوتر والتواصل)', 'description' => 'تعديل بيانات الفوتر والهاتف وروابط السوشيال في الواجهة العامة', 'group' => 'إدارة الصفحات الخارجية'],

            // المهام
            ['name' => 'manage.tasks', 'display_name' => 'إدارة المهام', 'description' => 'إدارة المهام', 'group' => 'المهام'],
            ['name' => 'view.tasks', 'display_name' => 'عرض المهام', 'description' => 'عرض المهام المخصصة', 'group' => 'المهام'],

            // المحافظ الذكية
            ['name' => 'view.wallets', 'display_name' => 'عرض المحافظ', 'description' => 'عرض المحافظ الذكية', 'group' => 'المحافظ الذكية'],

            // الرسائل والتقارير
            ['name' => 'manage.messages', 'display_name' => 'إدارة الرسائل', 'description' => 'إدارة الرسائل والتقارير', 'group' => 'الرسائل والتقارير'],

            // التقويم
            ['name' => 'view.calendar', 'display_name' => 'عرض التقويم', 'description' => 'عرض التقويم', 'group' => 'التقويم'],

            // جلسات البث المباشر
            ['name' => 'manage.live-sessions',       'display_name' => 'إدارة جلسات البث المباشر',       'description' => 'إدارة الجلسات المباشرة والتسجيلات',           'group' => 'جلسات البث المباشر'],
            ['name' => 'manage.live-servers',        'display_name' => 'إدارة سيرفرات البث (VPS)',        'description' => 'إدارة سيرفرات البث المباشر',                 'group' => 'جلسات البث المباشر'],

            // الاتفاقيات والسحب
            ['name' => 'manage.agreements',          'display_name' => 'إدارة اتفاقيات المدربين',         'description' => 'إدارة اتفاقيات المدربين',                     'group' => 'الاتفاقيات والسحب'],
            ['name' => 'manage.withdrawals',         'display_name' => 'إدارة طلبات السحب',               'description' => 'إدارة طلبات سحب الأرباح',                    'group' => 'الاتفاقيات والسحب'],
            ['name' => 'manage.employee-agreements', 'display_name' => 'إدارة اتفاقيات الموظفين',         'description' => 'إدارة اتفاقيات الموظفين ورواتبهم',           'group' => 'الاتفاقيات والسحب'],

            // المالية التفصيلية
            ['name' => 'manage.salaries',            'display_name' => 'إدارة رواتب المدربين',            'description' => 'إدارة رواتب وتسويات المدربين',               'group' => 'المالية التفصيلية'],
            ['name' => 'manage.expenses',            'display_name' => 'إدارة المصروفات',                 'description' => 'إدارة المصروفات والنفقات التشغيلية',         'group' => 'المالية التفصيلية'],
            ['name' => 'manage.instructor-accounts', 'display_name' => 'إدارة حسابات المدربين',           'description' => 'عرض وإدارة الحسابات المالية للمدربين',       'group' => 'المالية التفصيلية'],

            // العناصر المدفوعة التفصيلية
            ['name' => 'manage.video-providers',     'display_name' => 'إدارة مصادر الفيديو',             'description' => 'إدارة مصادر وموفري الفيديو للمنصة',         'group' => 'إدارة المحتوى'],
            ['name' => 'manage.packages',            'display_name' => 'إدارة الباقات والأسعار',          'description' => 'إدارة باقات الاشتراك والتسعير',              'group' => 'العناصر المدفوعة'],
            // ['name' => 'manage.teacher-features',    'display_name' => 'إدارة مزايا اشتراك المدربين',     'description' => 'إدارة مزايا ومستويات اشتراك المدربين',       'group' => 'العناصر المدفوعة'], // removed SaaS
            ['name' => 'manage.curriculum-library',  'display_name' => 'إدارة المناهج التفاعلية',             'description' => 'إدارة مكتبة المناهج التفاعلية (Manahij X) وتحويل العروض',            'group' => 'إدارة المحتوى'],

            // التسويق التفصيلي
            ['name' => 'manage.popup-ads',           'display_name' => 'إدارة الإعلانات المنبثقة',        'description' => 'إدارة البوبأب والإعلانات الترويجية',         'group' => 'إدارة التسويق'],
            ['name' => 'manage.personal-branding',   'display_name' => 'إدارة العلامة الشخصية',           'description' => 'إدارة تخصيص العلامة التجارية للمدربين',     'group' => 'إدارة التسويق'],

            // رقابة الجودة والمتابعة
            ['name' => 'manage.quality-control',     'display_name' => 'الرقابة والجودة',                 'description' => 'متابعة جودة العمليات ورقابة الطلاب والمدربين','group' => 'الرقابة والجودة'],
            ['name' => 'manage.student-control',     'display_name' => 'إدارة رقابة الطلاب',              'description' => 'متابعة وتحليل أداء الطلاب',                  'group' => 'الرقابة والجودة'],

            // الإدارة التفصيلية للنظام
            ['name' => 'manage.performance',         'display_name' => 'مراقبة أداء المنصة',              'description' => 'عرض إحصائيات وأداء المنصة التقني',           'group' => 'إدارة النظام'],
            ['name' => 'manage.email-broadcasts',    'display_name' => 'إدارة البريد الجماعي (Gmail)',    'description' => 'إرسال وإدارة البريد الجماعي عبر Gmail',       'group' => 'إدارة النظام'],
            ['name' => 'manage.two-factor-logs',     'display_name' => 'سجلات المصادقة الثنائية',         'description' => 'عرض سجلات المصادقة الثنائية',                 'group' => 'إدارة النظام'],

            // الموارد البشرية التفصيلية
            ['name' => 'manage.leaves',              'display_name' => 'إدارة طلبات الإجازة',             'description' => 'إدارة ومراجعة طلبات إجازة الموظفين',         'group' => 'الموارد البشرية'],
            ['name' => 'manage.instructor-requests', 'display_name' => 'إدارة طلبات انضمام المدربين',     'description' => 'مراجعة والبت في طلبات انضمام المدربين',       'group' => 'الموارد البشرية'],
            ['name' => 'academic_supervision.manage', 'display_name' => 'إدارة الإشراف الأكاديمي',         'description' => 'تعيين المشرفين ومتابعة الإشراف الأكاديمي من لوحة الإدارة', 'group' => 'الموارد البشرية'],

            // التقارير
            ['name' => 'view.reports',               'display_name' => 'عرض التقارير الشاملة',            'description' => 'الوصول إلى جميع صفحات التقارير',             'group' => 'التقارير'],
            ['name' => 'view.financial-reports',     'display_name' => 'عرض التقارير المالية',            'description' => 'الوصول إلى التقارير المالية والمحاسبية',      'group' => 'التقارير'],
            ['name' => 'view.academic-reports',      'display_name' => 'عرض التقارير الأكاديمية',         'description' => 'الوصول إلى التقارير الأكاديمية',             'group' => 'التقارير'],

            // المبيعات التفصيلية
            ['name' => 'manage.leads',               'display_name' => 'إدارة العملاء المحتملين (Leads)', 'description' => 'إدارة قاعدة العملاء المحتملين',               'group' => 'إدارة المبيعات'],
            ['name' => 'view.sales-analytics',       'display_name' => 'عرض تحليلات المبيعات',           'description' => 'الوصول إلى لوحة تحليلات المبيعات',           'group' => 'إدارة المبيعات'],

            // التحكم بالطلاب والخدمات
            ['name' => 'manage.support-tickets',     'display_name' => 'إدارة تذاكر الدعم الفني',        'description' => 'إدارة تذاكر الدعم الفني وتصنيفاتها',         'group' => 'التحكم بالطلاب'],
            ['name' => 'manage.consultations',       'display_name' => 'إدارة استشارات المدربين',        'description' => 'إدارة جلسات الاستشارات مع المدربين',         'group' => 'التحكم بالطلاب'],
            // ['name' => 'manage.hiring-academies',    'display_name' => 'إدارة الأكاديميات وفرص العمل',   'description' => 'إدارة الأكاديميات التوظيفية وفرص العمل',     'group' => 'التحكم بالطلاب'], // removed SaaS
            ['name' => 'manage.students-accounts',   'display_name' => 'إدارة حسابات الطلاب',            'description' => 'إدارة حسابات ومعلومات الطلاب',               'group' => 'التحكم بالطلاب'],

            // صلاحيات المدرب
            ['name' => 'instructor.view.courses', 'display_name' => 'عرض كورساتي', 'description' => 'عرض الكورسات الخاصة بالمدرب', 'group' => 'صلاحيات المدرب'],
            ['name' => 'instructor.manage.lectures', 'display_name' => 'إدارة محاضراتي', 'description' => 'إدارة المحاضرات الخاصة بالمدرب', 'group' => 'صلاحيات المدرب'],
            ['name' => 'instructor.manage.assignments', 'display_name' => 'إدارة واجباتي', 'description' => 'إدارة الواجبات الخاصة بالمدرب', 'group' => 'صلاحيات المدرب'],
            ['name' => 'instructor.manage.exams', 'display_name' => 'إدارة اختباراتي', 'description' => 'إدارة الامتحانات الخاصة بالمدرب', 'group' => 'صلاحيات المدرب'],
            ['name' => 'instructor.manage.attendance', 'display_name' => 'إدارة الحضور', 'description' => 'إدارة الحضور والانصراف', 'group' => 'صلاحيات المدرب'],
            ['name' => 'instructor.view.tasks', 'display_name' => 'عرض مهامي', 'description' => 'عرض المهام الخاصة بالمدرب', 'group' => 'صلاحيات المدرب'],

            // صلاحيات الطالب
            ['name' => 'student.view.courses', 'display_name' => 'تصفح الكورسات', 'description' => 'تصفح الكورسات المتاحة', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.my-courses', 'display_name' => 'عرض كورساتي', 'description' => 'عرض الكورسات المسجل فيها', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.orders', 'display_name' => 'عرض طلباتي', 'description' => 'عرض طلبات التسجيل', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.invoices', 'display_name' => 'عرض فواتيري', 'description' => 'عرض الفواتير', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.wallet', 'display_name' => 'عرض محفظتي', 'description' => 'عرض المحفظة', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.certificates', 'display_name' => 'عرض شهاداتي', 'description' => 'عرض الشهادات', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.achievements', 'display_name' => 'عرض إنجازاتي', 'description' => 'عرض الإنجازات', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.exams', 'display_name' => 'عرض الامتحانات', 'description' => 'عرض الامتحانات المتاحة', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.calendar', 'display_name' => 'عرض التقويم', 'description' => 'عرض التقويم الأكاديمي', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.notifications', 'display_name' => 'عرض الإشعارات', 'description' => 'عرض الإشعارات', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.profile', 'display_name' => 'عرض البروفايل', 'description' => 'عرض وتعديل البروفايل', 'group' => 'صلاحيات الطالب'],
            ['name' => 'student.view.settings', 'display_name' => 'عرض الإعدادات', 'description' => 'عرض الإعدادات', 'group' => 'صلاحيات الطالب'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('تم إنشاء ' . count($permissions) . ' صلاحية بنجاح!');
    }
}
