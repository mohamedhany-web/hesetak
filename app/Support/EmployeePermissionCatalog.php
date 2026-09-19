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
}
