<?php

namespace App\Support;

class EmployeePermissionCatalog
{
    /**
     * @return list<string>
     */
    public static function salesDepartmentJobCodes(): array
    {
        return config('crm.sales_department_job_codes', [
            'sales', 'crm_marketing', 'crm_team_leader', 'crm_finance',
        ]);
    }

    public static function isSalesDepartmentJob(?string $jobCode): bool
    {
        return $jobCode && in_array($jobCode, self::salesDepartmentJobCodes(), true);
    }

    /**
     * @return array<string, string> key => label
     */
    public static function salesPermissionGroups(): array
    {
        $sidebarItems = config('employee_sidebar.items', []);
        $crmPerms = config('crm.permissions', []);

        $salesSidebarKeys = [
            'sales_desk', 'sales_orders', 'public_catalog',
            'crm_desk', 'crm_leads', 'crm_leads_create', 'crm_commissions',
            'crm_orders', 'crm_team', 'crm_reports', 'crm_sales_financial', 'crm_messages',
            'crm_marketing_desk', 'crm_marketing_inbox',
            'tasks', 'leaves', 'calendar',
        ];

        $groups = [
            'المبيعات' => [],
            'CRM حصتك' => [],
            'صلاحيات CRM الدقيقة' => $crmPerms,
            'عام' => [],
        ];

        foreach ($salesSidebarKeys as $key) {
            if (! isset($sidebarItems[$key])) {
                continue;
            }
            $label = $sidebarItems[$key]['label'] ?? $key;
            if (str_starts_with($key, 'crm_')) {
                $groups['CRM حصتك'][$key] = $label;
            } elseif (in_array($key, ['sales_desk', 'sales_orders', 'public_catalog'], true)) {
                $groups['المبيعات'][$key] = $label;
            } else {
                $groups['عام'][$key] = $label;
            }
        }

        // إزالة المفاتيح المكررة بين السايدبار و CRM الدقيقة
        foreach (array_keys($groups['CRM حصتك']) as $sidebarKey) {
            unset($groups['صلاحيات CRM الدقيقة'][$sidebarKey]);
        }

        return array_filter($groups, fn ($items) => ! empty($items));
    }

    /**
     * @return list<string>
     */
    public static function allowedPermissionKeys(): array
    {
        $keys = [];
        foreach (self::salesPermissionGroups() as $items) {
            $keys = array_merge($keys, array_keys($items));
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  list<string>  $selected
     * @return list<string>
     */
    public static function sanitize(array $selected): array
    {
        $allowed = self::allowedPermissionKeys();

        return array_values(array_intersect($selected, $allowed));
    }

    /**
     * تحويل مفاتيح صلاحيات الموظف/الوظيفة إلى أسماء صلاحيات RBAC
     * المستخدمة في سايدبار الأدمن والـ middleware (مصدر واحد للحقيقة).
     *
     * @param  list<string>  $employeeKeys
     * @return list<string>
     */
    public static function rbacPermissionsImpliedBy(array $employeeKeys): array
    {
        $implied = [];

        foreach ($employeeKeys as $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            foreach (self::mapEmployeeKeyToRbac($key) as $rbac) {
                $implied[] = $rbac;
            }
        }

        return array_values(array_unique($implied));
    }

    /**
     * @return list<string>
     */
    public static function mapEmployeeKeyToRbac(string $key): array
    {
        return match ($key) {
            'sales_desk', 'sales_orders', 'crm_orders', 'crm_view_all_orders' => ['manage.orders'],
            'public_catalog' => ['manage.orders'],
            'crm_desk', 'crm_leads', 'crm_leads_create', 'crm_create_leads', 'crm_edit_leads',
            'crm_assign_leads', 'crm_transition_leads', 'crm_add_notes', 'crm_submit_to_sales',
            'crm_view_all_leads', 'crm_commissions', 'crm_approve_commissions', 'crm_approve_payments',
            'crm_team', 'crm_manage_team', 'crm_view_team_performance', 'crm_reports',
            'crm_submit_reports', 'crm_view_submitted_reports',
            'crm_marketing_desk', 'crm_marketing_inbox' => ['manage.leads'],
            'crm_sales_financial', 'crm_view_sales_financial_reports' => ['manage.leads', 'view.sales-analytics'],
            'crm_messages', 'crm_view_messages', 'crm_send_messages' => ['manage.leads', 'manage.messages'],
            'desk_accountant', 'accounting', 'agreements' => ['manage.invoices'],
            'hr_desk', 'hr_leave_requests', 'hr_directory', 'hr_recruitment' => ['manage.users'],
            'supervision_desk' => ['view.statistics', 'view.reports'],
            'academic_supervision_desk' => ['academic_supervision.manage'],
            'admin_panel' => ['view.dashboard', 'admin.access'],
            'tasks' => ['manage.tasks', 'view.tasks'],
            'leaves' => ['manage.leaves'],
            'calendar' => ['view.calendar', 'manage.leaves'],
            'reports' => ['view.reports', 'view.statistics'],
            'dashboard', 'profile', 'notifications', 'settings' => [],
            default => str_contains($key, '.') ? [$key] : [],
        };
    }

    /**
     * هل مجموعة الصلاحيات المحفوظة (وظيفة/تخصيص) تكفي لمفتاح عنصر السايدبار؟
     *
     * @param  list<string>  $held
     */
    public static function employeeKeyIsGranted(string $key, array $held): bool
    {
        if (in_array($key, $held, true)) {
            return true;
        }

        // توافق aliases
        if ($key === 'sales_orders' && (in_array('sales_desk', $held, true) || in_array('manage.orders', $held, true))) {
            return true;
        }
        if ($key === 'sales_desk' && (in_array('sales_orders', $held, true) || in_array('manage.orders', $held, true))) {
            return true;
        }
        if ($key === 'manage.orders' && (in_array('sales_desk', $held, true) || in_array('sales_orders', $held, true))) {
            return true;
        }
        if ($key === 'calendar' && in_array('view.calendar', $held, true)) {
            return true;
        }
        if ($key === 'view.calendar' && in_array('calendar', $held, true)) {
            return true;
        }
        if ($key === 'tasks' && (in_array('manage.tasks', $held, true) || in_array('view.tasks', $held, true))) {
            return true;
        }
        if ($key === 'leaves' && in_array('manage.leaves', $held, true)) {
            return true;
        }
        if ($key === 'reports' && (in_array('view.statistics', $held, true) || in_array('view.reports', $held, true))) {
            return true;
        }

        // CRM: مفاتيح السايدبار تُمنَح عبر crm_desk + قيد دقيق للحسّاس
        $hasDesk = in_array('crm_desk', $held, true);

        return match ($key) {
            'crm_leads', 'crm_commissions', 'crm_reports' => $hasDesk,
            'crm_messages' => $hasDesk
                || in_array('crm_view_messages', $held, true)
                || in_array('crm_send_messages', $held, true),
            'crm_leads_create' => in_array('crm_create_leads', $held, true),
            'crm_marketing_desk' => in_array('crm_create_leads', $held, true)
                || in_array('crm_submit_to_sales', $held, true)
                || in_array('crm_marketing_desk', $held, true),
            'crm_marketing_inbox' => in_array('crm_marketing_inbox', $held, true)
                || in_array('crm_transition_leads', $held, true)
                || in_array('crm_assign_leads', $held, true),
            'crm_sales_financial' => in_array('crm_view_sales_financial_reports', $held, true),
            'crm_team' => in_array('crm_view_team_performance', $held, true)
                || in_array('crm_manage_team', $held, true),
            'crm_orders' => in_array('crm_view_all_orders', $held, true)
                || in_array('crm_approve_payments', $held, true),
            'hr_leave_requests', 'hr_directory', 'hr_recruitment' => in_array('hr_desk', $held, true),
            default => false,
        };
    }

    /**
     * صلاحيات مالية حسّاسة — لا تُمنَح عبر مبيعات/CRM ضمنية.
     *
     * @return list<string>
     */
    public static function financialPermissionNames(): array
    {
        return [
            'manage.payments',
            'manage.invoices',
            'manage.transactions',
            'manage.wallets',
            'view.wallets',
            'view.financial-reports',
            'manage.expenses',
            'manage.salaries',
            'manage.instructor-accounts',
            'manage.withdrawals',
            'manage.installments',
        ];
    }
}
