<?php

namespace Database\Seeders;

use App\Models\EmployeeJob;
use Illuminate\Database\Seeder;

class EmployeeJobSeeder extends Seeder
{
    /**
     * الوظائف الثابتة للموظفين مع صلاحيات السايدبار لكل وظيفة
     */
    public function run(): void
    {
        $crmMarketing = [
            'crm_desk', 'crm_create_leads', 'crm_submit_to_sales', 'crm_edit_leads', 'crm_add_notes',
            'crm_submit_reports', 'crm_view_messages', 'crm_send_messages',
        ];
        $crmSales = [
            'crm_desk', 'crm_edit_leads', 'crm_transition_leads', 'crm_add_notes',
            'crm_submit_reports', 'crm_view_messages', 'crm_send_messages',
        ];
        $crmTeamLeader = [
            'crm_desk', 'crm_assign_leads', 'crm_manage_team', 'crm_view_team_performance',
            'crm_transition_leads', 'crm_add_notes', 'crm_submit_reports', 'crm_view_messages', 'crm_send_messages',
        ];
        $crmFinance = [
            'crm_desk', 'crm_view_all_leads', 'crm_view_all_orders', 'crm_approve_payments', 'crm_approve_commissions',
            'crm_view_sales_financial_reports', 'crm_view_submitted_reports', 'crm_view_team_performance',
            'crm_view_messages', 'crm_send_messages', 'crm_add_notes',
        ];

        $jobs = [
            [
                'name' => 'محاسب',
                'code' => 'accountant',
                'description' => 'مسؤول عن الحسابات والرواتب والاتفاقيات',
                'permissions' => [
                    'dashboard', 'tasks', 'leaves', 'accounting', 'agreements', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                    'desk_accountant',
                ],
            ],
            [
                'name' => 'اشراف عام',
                'code' => 'general_supervision',
                'description' => 'متابعة عامة وتقارير وإحصائيات وجودة التنفيذ',
                'permissions' => [
                    'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                    'supervision_desk',
                ],
            ],
            [
                'name' => 'HR',
                'code' => 'hr',
                'description' => 'الموارد البشرية ومتابعة الموظفين والإجازات',
                'permissions' => [
                    'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                    'hr_desk', 'hr_leave_requests', 'hr_directory', 'hr_recruitment',
                ],
            ],
            [
                'name' => 'مشرفه',
                'code' => 'supervisor',
                'description' => 'مشرف/ة ومتابعة المهام والتقارير',
                'permissions' => [
                    'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                    'supervision_desk',
                ],
            ],
            [
                'name' => 'مشرف أكاديمي',
                'code' => 'academic_supervisor',
                'description' => 'متابعة الطلاب المعيّنين: الكورسات، الاشتراك، الميتينج، والنشاط',
                'permissions' => [
                    'dashboard', 'tasks', 'leaves', 'calendar',
                    'profile', 'notifications', 'settings',
                    'academic_supervision_desk',
                ],
            ],
            [
                'name' => 'سيلز',
                'code' => 'sales',
                'description' => 'المبيعات ومتابعة الطلبات والعملاء',
                'permissions' => array_merge([
                    'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                    'sales_desk',
                    'public_catalog',
                ], $crmSales),
            ],
            [
                'name' => 'تسويق CRM',
                'code' => 'crm_marketing',
                'description' => 'إضافة ومتابعة Leads التسويقية',
                'permissions' => array_merge([
                    'dashboard', 'tasks', 'leaves', 'calendar',
                    'profile', 'notifications', 'settings',
                ], $crmMarketing),
            ],
            [
                'name' => 'قائد فريق CRM',
                'code' => 'crm_team_leader',
                'description' => 'متابعة أداء فريق التسويق والمبيعات',
                'permissions' => array_merge([
                    'dashboard', 'tasks', 'leaves', 'reports', 'calendar',
                    'profile', 'notifications', 'settings',
                ], $crmTeamLeader),
            ],
            [
                'name' => 'مالية CRM',
                'code' => 'crm_finance',
                'description' => 'اعتماد المدفوعات والعمولات',
                'permissions' => array_merge([
                    'dashboard', 'tasks', 'leaves', 'calendar',
                    'profile', 'notifications', 'settings',
                ], $crmFinance),
            ],
            [
                'name' => 'مخصص',
                'code' => 'custom',
                'description' => 'وظيفة مخصصة يتم ضبط صلاحياتها من هنا',
                'permissions' => [],
            ],
        ];

        foreach ($jobs as $job) {
            EmployeeJob::updateOrCreate(
                ['code' => $job['code']],
                [
                    'name' => $job['name'],
                    'description' => $job['description'] ?? null,
                    'responsibilities' => null,
                    'permissions' => $job['permissions'],
                    'min_salary' => null,
                    'max_salary' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
