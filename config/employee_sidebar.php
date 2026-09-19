<?php

/**
 * خريطة السايدبار للموظفين.
 *
 * كل عنصر يحتوي على:
 *   permission => اسم صلاحية RBAC (من جدول permissions) مطلوبة لإظهار هذا العنصر.
 *                 إذا لم يُحدَّد، يُستخدَم المفتاح نفسه (key).
 *
 * الصلاحيات المتاحة في النظام:
 *   manage.orders      → قسم المبيعات
 *   manage.invoices    → قسم المحاسبة
 *   manage.users       → قسم الموارد البشرية
 *   manage.tasks       → قسم المهام والإجازات
 *   view.statistics    → قسم التقارير + الإشراف
 *   view.calendar      → قسم التقويم
 *   manage.messages    → إدارة الرسائل
 *   view.dashboard     → لوحة التحكم الرئيسية
 *   manage.coupons     → إدارة الكوبونات
 *   manage.referrals   → إدارة الإحالات
 *   manage.courses     → إدارة الكورسات
 */
return [
    'items' => [
        'dashboard' => [
            // متاح دائماً لكل موظف (alwaysAllowed في employeeCan)
            'label' => 'لوحة التحكم',
            'icon' => 'fas fa-home',
            'route' => 'employee.dashboard',
            'route_patterns' => ['employee.dashboard'],
            'active_class' => 'bg-blue-600 shadow-lg',
        ],
        /*
         * موظف له دور RBAC: أقسام لوحة الموظف لا تغطي كل صلاحيات admin (شهادات، وسائط، أدوار، …).
         * يُعرض هذا الرابط عند امتلاك view.dashboard على الدور (يُدمج تلقائياً) أو أي مسار معادل.
         * السايدبار الكامل للإدارة يقرأ hasPermission() لكل قسم.
         */
        'admin_panel' => [
            'permission' => 'view.dashboard',
            'label' => 'لوحة تحكم الإدارة',
            'icon' => 'fas fa-chart-line',
            'route' => 'admin.dashboard',
            'route_patterns' => ['admin.dashboard', 'admin.profile*'],
            'active_class' => 'bg-indigo-600 shadow-lg',
        ],
        'desk_accountant' => [
            'permission' => 'manage.invoices',
            'label' => 'لوحة المحاسب',
            'icon' => 'fas fa-calculator',
            'route' => 'employee.accountant-desk.index',
            'route_patterns' => ['employee.accountant-desk.*'],
            'active_class' => 'bg-amber-600 shadow-lg',
        ],
        'sales_desk' => [
            'permission' => 'manage.orders',
            'label' => 'لوحة المبيعات',
            'icon' => 'fas fa-chart-line',
            'route' => 'employee.sales.desk',
            'route_patterns' => ['employee.sales.desk', 'employee.sales.leads.*'],
            'active_class' => 'bg-emerald-600 shadow-lg',
        ],
        'sales_orders' => [
            'permission' => 'manage.orders',
            'label' => 'طلبات المبيعات',
            'icon' => 'fas fa-shopping-bag',
            'route' => 'employee.sales.orders.index',
            'route_patterns' => ['employee.sales.orders.*'],
            'active_class' => 'bg-emerald-700 shadow-lg',
        ],
        'crm_desk' => [
            'permission' => 'crm_desk',
            'label' => 'لوحة CRM',
            'icon' => 'fas fa-funnel-dollar',
            'route' => 'employee.crm.dashboard',
            'route_patterns' => ['employee.crm.dashboard'],
            'active_class' => 'bg-indigo-600 shadow-lg',
        ],
        'crm_leads' => [
            'permission' => 'crm_desk',
            'label' => 'العملاء المحتملون',
            'icon' => 'fas fa-user-plus',
            'route' => 'employee.crm.leads.index',
            'route_patterns' => ['employee.crm.leads.index', 'employee.crm.leads.show', 'employee.crm.leads.update', 'employee.crm.leads.transition', 'employee.crm.leads.note', 'employee.crm.leads.confirm-payment'],
            'active_class' => 'bg-indigo-700 shadow-lg',
        ],
        'crm_leads_create' => [
            'permission' => 'crm_desk',
            'label' => 'إضافة عميل جديد',
            'icon' => 'fas fa-plus-circle',
            'route' => 'employee.crm.leads.create',
            'route_patterns' => ['employee.crm.leads.create', 'employee.crm.leads.store'],
            'active_class' => 'bg-teal-600 shadow-lg',
        ],
        'crm_marketing_desk' => [
            'permission' => 'crm_desk',
            'label' => 'مسوق بالعمولة',
            'icon' => 'fas fa-handshake',
            'route' => 'employee.crm.marketing.desk',
            'route_patterns' => ['employee.crm.marketing.*'],
            'active_class' => 'bg-teal-700 shadow-lg',
        ],
        'crm_marketing_inbox' => [
            'permission' => 'crm_desk',
            'label' => 'بيانات المسوقين',
            'icon' => 'fas fa-inbox',
            'route' => 'employee.crm.marketing-inbox.index',
            'route_patterns' => ['employee.crm.marketing-inbox.*'],
            'active_class' => 'bg-emerald-700 shadow-lg',
        ],
        'crm_commissions' => [
            'permission' => 'crm_desk',
            'label' => 'عمولاتي',
            'icon' => 'fas fa-coins',
            'route' => 'employee.crm.commissions',
            'route_patterns' => ['employee.crm.commissions', 'employee.crm.commissions.approve'],
            'active_class' => 'bg-violet-600 shadow-lg',
        ],
        'crm_orders' => [
            'permission' => 'crm_desk',
            'label' => 'طلبات بانتظار الدفع',
            'icon' => 'fas fa-shopping-bag',
            'route' => 'employee.crm.orders',
            'route_patterns' => ['employee.crm.orders', 'employee.crm.orders.approve'],
            'active_class' => 'bg-amber-600 shadow-lg',
        ],
        'crm_team' => [
            'permission' => 'crm_desk',
            'label' => 'فريقي وأداء الأعضاء',
            'icon' => 'fas fa-users-cog',
            'route' => 'employee.crm.team.index',
            'route_patterns' => ['employee.crm.team.*'],
            'active_class' => 'bg-sky-600 shadow-lg',
        ],
        'crm_reports' => [
            'permission' => 'crm_desk',
            'label' => 'تقارير CRM',
            'icon' => 'fas fa-file-upload',
            'route' => 'employee.crm.reports.index',
            'route_patterns' => ['employee.crm.reports.*'],
            'active_class' => 'bg-rose-600 shadow-lg',
        ],
        'crm_sales_financial' => [
            'permission' => 'crm_desk',
            'label' => 'التقارير المالية للمبيعات',
            'icon' => 'fas fa-chart-line',
            'route' => 'employee.crm.sales-financial',
            'route_patterns' => ['employee.crm.sales-financial'],
            'active_class' => 'bg-emerald-600 shadow-lg',
        ],
        'crm_messages' => [
            'permission' => 'crm_desk',
            'label' => 'تواصل CRM',
            'icon' => 'fas fa-comments',
            'route' => 'employee.crm.messages.index',
            'route_patterns' => ['employee.crm.messages.*'],
            'active_class' => 'bg-cyan-600 shadow-lg',
        ],
        'hr_desk' => [
            'permission' => 'manage.users',
            'label' => 'لوحة الموارد البشرية',
            'icon' => 'fas fa-users',
            'route' => 'employee.hr-desk.index',
            'route_patterns' => ['employee.hr-desk.*'],
            'active_class' => 'bg-rose-600 shadow-lg',
        ],
        'hr_leave_requests' => [
            'permission' => 'manage.users',
            'label' => 'مراجعة الإجازات',
            'icon' => 'fas fa-calendar-check',
            'route' => 'employee.hr.leaves.index',
            'route_patterns' => ['employee.hr.leaves.*'],
            'active_class' => 'bg-rose-700 shadow-lg',
        ],
        'hr_directory' => [
            'permission' => 'manage.users',
            'label' => 'دليل الموظفين',
            'icon' => 'fas fa-address-book',
            'route' => 'employee.hr.employees.index',
            'route_patterns' => ['employee.hr.employees.*'],
            'active_class' => 'bg-indigo-700 shadow-lg',
        ],
        'hr_recruitment' => [
            'permission' => 'manage.users',
            'label' => 'التوظيف والمقابلات',
            'icon' => 'fas fa-user-tie',
            'route' => 'employee.hr.recruitment.index',
            'route_patterns' => ['employee.hr.recruitment.*'],
            'active_class' => 'bg-violet-700 shadow-lg',
        ],
        'supervision_desk' => [
            'permission' => 'view.statistics',
            'label' => 'لوحة الإشراف',
            'icon' => 'fas fa-clipboard-check',
            'route' => 'employee.supervision-desk.index',
            'route_patterns' => ['employee.supervision-desk.*'],
            'active_class' => 'bg-indigo-600 shadow-lg',
        ],
        'academic_supervision_desk' => [
            // صلاحية RBAC: إشراف أكاديمي (لوحة الموظف + مسارات admin.academic-supervision)
            'permission' => 'academic_supervision.manage',
            'label' => 'الإشراف الأكاديمي',
            'icon' => 'fas fa-user-graduate',
            'route' => 'employee.academic-supervision.index',
            'route_patterns' => ['employee.academic-supervision.*'],
            'active_class' => 'bg-teal-600 shadow-lg',
        ],
        'public_catalog' => [
            'permission' => 'manage.courses',
            'label' => 'تصفح الكورسات',
            'icon' => 'fas fa-graduation-cap',
            'route' => 'public.courses',
            'route_patterns' => ['public.courses', 'public.course.*'],
            'active_class' => 'bg-teal-600 shadow-lg',
        ],
        'tasks' => [
            'permission' => 'manage.tasks',
            'label' => 'مهامي',
            'icon' => 'fas fa-tasks',
            'route' => 'employee.tasks.index',
            'route_patterns' => ['employee.tasks.*'],
            'active_class' => 'bg-sky-600 shadow-lg',
        ],
        'leaves' => [
            'permission' => 'manage.tasks',
            'label' => 'إجازاتي',
            'icon' => 'fas fa-umbrella-beach',
            'route' => 'employee.leaves.index',
            'route_patterns' => ['employee.leaves.*'],
            'active_class' => 'bg-cyan-600 shadow-lg',
        ],
        'accounting' => [
            'permission' => 'manage.invoices',
            'label' => 'محاسبتي الشخصية',
            'icon' => 'fas fa-wallet',
            'route' => 'employee.accounting.index',
            'route_patterns' => ['employee.accounting.*'],
            'active_class' => 'bg-slate-500 shadow-lg',
        ],
        'agreements' => [
            'permission' => 'manage.invoices',
            'label' => 'اتفاقيات العمل',
            'icon' => 'fas fa-file-contract',
            'route' => 'employee.agreements.index',
            'route_patterns' => ['employee.agreements.*'],
            'active_class' => 'bg-violet-600 shadow-lg',
        ],
        'reports' => [
            'permission' => 'view.statistics',
            'label' => 'تقاريري',
            'icon' => 'fas fa-chart-bar',
            'route' => 'employee.reports',
            'route_patterns' => ['employee.reports'],
            'active_class' => 'bg-purple-600 shadow-lg',
        ],
        'calendar' => [
            'permission' => 'view.calendar',
            'label' => 'التقويم',
            'icon' => 'fas fa-calendar-alt',
            'route' => 'employee.calendar',
            'route_patterns' => ['employee.calendar*'],
            'active_class' => 'bg-orange-600 shadow-lg',
        ],
        'profile' => [
            // متاح دائماً لكل موظف (alwaysAllowed)
            'label' => 'الملف الشخصي',
            'icon' => 'fas fa-user',
            'route' => 'employee.profile',
            'route_patterns' => ['employee.profile*'],
            'active_class' => 'bg-blue-600 shadow-lg',
        ],
        'notifications' => [
            // متاح دائماً لكل موظف (alwaysAllowed)
            'label' => 'الإشعارات',
            'icon' => 'fas fa-bell',
            'route' => 'employee.notifications',
            'route_patterns' => ['employee.notifications*'],
            'active_class' => 'bg-blue-600 shadow-lg',
        ],
        'settings' => [
            // متاح دائماً لكل موظف (alwaysAllowed)
            'label' => 'الإعدادات',
            'icon' => 'fas fa-cog',
            'route' => 'employee.settings',
            'route_patterns' => ['employee.settings*'],
            'active_class' => 'bg-slate-600 shadow-lg',
        ],
    ],

    /*
     * قائمة كل وظيفة محددة: الأقسام وترتيب العناصر.
     * يُعرض فقط ما يملك الموظف صلاحيته (employeeCan).
     */
    'menus_by_job' => [
        'accountant' => [
            ['title' => 'القيادة', 'keys' => ['dashboard']],
            ['title' => 'المحاسبة والمالية', 'keys' => ['desk_accountant', 'agreements', 'accounting']],
            ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التخطيط والتقارير', 'keys' => ['calendar', 'reports']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'sales' => [
            ['title' => 'CRM حصتك', 'keys' => ['crm_desk', 'crm_leads', 'crm_marketing_inbox', 'crm_commissions', 'crm_reports', 'crm_messages']],
            ['title' => 'القيادة والمبيعات', 'keys' => ['dashboard', 'sales_desk', 'sales_orders']],
            ['title' => 'الكتالوج', 'keys' => ['public_catalog']],
            ['title' => 'المهام والمتابعة', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التقارير', 'keys' => ['reports', 'calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'hr' => [
            ['title' => 'القيادة والموارد البشرية', 'keys' => ['dashboard', 'hr_desk', 'hr_leave_requests', 'hr_directory', 'hr_recruitment']],
            ['title' => 'مهامي وإجازاتي', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التقارير', 'keys' => ['reports', 'calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'general_supervision' => [
            ['title' => 'القيادة والإشراف', 'keys' => ['dashboard', 'supervision_desk']],
            ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التقارير', 'keys' => ['reports', 'calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'supervisor' => [
            ['title' => 'القيادة والإشراف', 'keys' => ['dashboard', 'supervision_desk']],
            ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التقارير', 'keys' => ['reports', 'calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'academic_supervisor' => [
            ['title' => 'الإشراف الأكاديمي', 'keys' => ['dashboard', 'academic_supervision_desk']],
            ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التخطيط', 'keys' => ['calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'crm_marketing' => [
            ['title' => 'CRM حصتك', 'keys' => ['crm_desk', 'crm_marketing_desk', 'crm_leads', 'crm_leads_create', 'crm_commissions', 'crm_reports', 'crm_messages']],
            ['title' => 'المهام والمتابعة', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التخطيط', 'keys' => ['calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'crm_team_leader' => [
            ['title' => 'CRM حصتك', 'keys' => ['crm_desk', 'crm_team', 'crm_leads', 'crm_marketing_inbox', 'crm_commissions', 'crm_reports', 'crm_messages']],
            ['title' => 'المهام والمتابعة', 'keys' => ['tasks', 'leaves', 'reports']],
            ['title' => 'التخطيط', 'keys' => ['calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        'crm_finance' => [
            ['title' => 'CRM حصتك', 'keys' => ['crm_desk', 'crm_sales_financial', 'crm_leads', 'crm_team', 'crm_orders', 'crm_commissions', 'crm_messages']],
            ['title' => 'المهام والمتابعة', 'keys' => ['tasks', 'leaves']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
        /*
         * الموظف المخصص (custom): يظهر كل الأقسام مُرشَّحة بصلاحياته RBAC.
         */
        'custom' => [
            ['title' => 'CRM حصتك', 'keys' => ['crm_desk', 'crm_leads', 'crm_commissions']],
            ['title' => 'القيادة', 'keys' => ['dashboard', 'admin_panel']],
            ['title' => 'المبيعات', 'keys' => ['sales_desk', 'sales_orders']],
            ['title' => 'المحاسبة والمالية', 'keys' => ['desk_accountant', 'agreements', 'accounting']],
            ['title' => 'الموارد البشرية', 'keys' => ['hr_desk', 'hr_leave_requests', 'hr_directory', 'hr_recruitment']],
            ['title' => 'الإشراف', 'keys' => ['supervision_desk', 'academic_supervision_desk']],
            ['title' => 'الكورسات', 'keys' => ['public_catalog']],
            ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
            ['title' => 'التقارير والتقويم', 'keys' => ['reports', 'calendar']],
            ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
        ],
    ],

    /** موظف بلا وظيفة محددة أو كود غير معروف: كل الأقسام مرشَّحة بالصلاحيات */
    'fallback_sections' => [
        ['title' => 'القيادة', 'keys' => ['dashboard']],
        ['title' => 'المبيعات', 'keys' => ['sales_desk', 'sales_orders']],
        ['title' => 'المحاسبة والمالية', 'keys' => ['desk_accountant', 'agreements', 'accounting']],
        ['title' => 'الموارد البشرية', 'keys' => ['hr_desk', 'hr_leave_requests', 'hr_directory', 'hr_recruitment']],
        ['title' => 'الإشراف', 'keys' => ['supervision_desk']],
        ['title' => 'الكورسات', 'keys' => ['public_catalog']],
        ['title' => 'المهام والإجازات', 'keys' => ['tasks', 'leaves']],
        ['title' => 'التقارير والتقويم', 'keys' => ['reports', 'calendar']],
        ['title' => 'حسابي', 'keys' => ['profile', 'notifications', 'settings']],
    ],
];
