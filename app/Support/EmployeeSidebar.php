<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Route;

final class EmployeeSidebar
{
    /**
     * مفتاح فريد لرابط القائمة (تفادي تكرار نفس المسار بين القائمة المخصصة وصلاحيات الدور).
     */
    public static function sidebarLinkKey(array $link): string
    {
        $route = $link['route'] ?? '';
        $params = $link['route_params'] ?? [];
        if (is_array($params)) {
            ksort($params);
        }

        return $route.'|'.json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * أقسام إضافية: رابط لكل صلاحية على الدور تؤدي إلى صفحة الإدارة/الموظف المقابلة.
     *
     * @param  array<string, true>  $seenLinkKeys
     * @return list<array{title: ?string, links: list<array<string, mixed>>}>
     */
    public static function rbacPermissionSidebarSections(User $user, array &$seenLinkKeys): array
    {
        if (! $user->is_employee || ! $user->roles()->exists()) {
            return [];
        }

        $byPermission = config('rbac_permission_sidebar.by_permission', []);
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('display_name')
            ->get();

        $grouped = [];

        foreach ($permissions as $perm) {
            $name = $perm->name;
            if (str_starts_with($name, 'instructor.') || str_starts_with($name, 'student.')) {
                continue;
            }
            if (! $user->hasPermission($name)) {
                continue;
            }
            $meta = $byPermission[$name] ?? null;
            if (! is_array($meta) || empty($meta['route'])) {
                continue;
            }
            $routeName = $meta['route'];
            if (! Route::has($routeName)) {
                continue;
            }

            $link = [
                'key' => 'rbacperm_'.$name,
                'route' => $routeName,
                'label' => $perm->display_name,
                'icon' => $meta['icon'] ?? 'fas fa-folder',
                'route_patterns' => $meta['route_patterns'] ?? [],
            ];
            if (! empty($meta['route_params']) && is_array($meta['route_params'])) {
                $link['route_params'] = $meta['route_params'];
            }

            $dedupe = $meta['dedupe_key'] ?? self::sidebarLinkKey($link);
            if (isset($seenLinkKeys[$dedupe])) {
                continue;
            }

            $urlKey = self::sidebarLinkKey($link);
            if (isset($seenLinkKeys[$urlKey])) {
                continue;
            }

            $seenLinkKeys[$dedupe] = true;
            $seenLinkKeys[$urlKey] = true;

            $groupTitle = $perm->group ?: 'صلاحيات الدور';
            $grouped[$groupTitle][] = $link;
        }

        $sections = [];
        foreach ($grouped as $title => $links) {
            if ($links !== []) {
                $sections[] = ['title' => $title, 'links' => $links];
            }
        }

        return $sections;
    }

    /**
     * @return list<array{title: ?string, links: list<array<string, mixed>>}>
     */
    public static function sectionsFor(User $user): array
    {
        $code = $user->employeeJob?->code;
        $jobMenus = is_string($code) ? config('employee_sidebar.menus_by_job.'.$code) : null;

        // أولوية قائمة الوظيفة إن وُجدت (سيلز/محاسب/HR…) حتى مع دور RBAC
        if (is_array($jobMenus) && $jobMenus !== []) {
            $sections = $jobMenus;
        } elseif ($user->roles()->exists()) {
            $sections = config('employee_sidebar.menus_by_job.custom', []);
        } else {
            $sections = config('employee_sidebar.fallback_sections', []);
        }
        $items = config('employee_sidebar.items', []);

        $out = [];
        $seenLinkKeys = [];

        foreach ($sections as $section) {
            $links = [];
            foreach ($section['keys'] ?? [] as $key) {
                $meta = $items[$key] ?? null;
                // مصدر الحقيقة = مفتاح عنصر القائمة (نفس employee.can:* وصلاحيات الوظيفة)
                if (! $user->employeeCan($key)) {
                    continue;
                }
                if ($meta === null || empty($meta['route'])) {
                    continue;
                }
                if (! Route::has($meta['route'])) {
                    continue;
                }
                $link = array_merge($meta, ['key' => $key]);
                $seenLinkKeys[self::sidebarLinkKey($link)] = true;
                $links[] = $link;
            }
            if ($links !== []) {
                $out[] = [
                    'title' => $section['title'] ?? null,
                    'links' => $links,
                ];
            }
        }

        // روابط إضافية من صلاحيات الدور فقط لغير أقسام المبيعات (لتجنب تضخيم القائمة)
        if ($user->is_employee && $user->roles()->exists() && ! $user->isSalesDepartmentEmployee()) {
            $rbacExtra = self::rbacPermissionSidebarSections($user, $seenLinkKeys);
            if ($rbacExtra !== []) {
                $out = array_merge($out, $rbacExtra);
            }
        }

        return $out;
    }

    public static function linkIsActive(array $link): bool
    {
        foreach ($link['route_patterns'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }
}
