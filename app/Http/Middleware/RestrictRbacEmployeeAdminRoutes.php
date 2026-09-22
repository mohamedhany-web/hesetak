<?php

namespace App\Http\Middleware;

use App\Support\RbacAdminRouteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * موظف له دور RBAC: لا يصل إلا لمسارات admin المصرّح بها في rbac_admin_route_access
 * وبصلاحية مذكورة صراحة على دوره (بدون توسيع أسماء قديمة).
 */
class RestrictRbacEmployeeAdminRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_employee || ! $user->roles()->exists()) {
            return $next($request);
        }

        // المدير العام / سوبر أدمن يتجاوز قيود موظف RBAC بالكامل
        if (in_array((string) $user->role, ['super_admin', 'admin'], true)
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (! $routeName || ! str_starts_with($routeName, 'admin.')) {
            return $next($request);
        }

        if (RbacAdminRouteAccess::userMayAccessAdminRoute($user, $routeName)) {
            return $next($request);
        }

        abort(403, 'ليس لديك صلاحية الوصول إلى هذا القسم.');
    }
}
