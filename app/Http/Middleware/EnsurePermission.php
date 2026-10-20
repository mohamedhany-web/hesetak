<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware للتحقق من الصلاحيات المحددة
 * يستخدم للتحقق من أن المستخدم لديه صلاحية معينة للوصول إلى المورد
 */
class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  اسم الصلاحية المطلوبة
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return redirect('/login')->with('error', 'يجب تسجيل الدخول أولاً');
        }

        $user = Auth::user();

        // Super Admin بدون أدوار RBAC مخصصة → يتجاوز كل الفحوصات
        if ($user->isAdmin() && !$user->roles()->exists()) {
            return $next($request);
        }

        // admin.access: يتطلب الصلاحية فعلياً (لا يكفي وجود أي دور RBAC للموظف)
        if ($permission === 'admin.access') {
            if (! $user->userHasAdminAccessCapability()) {
                abort(403, 'ليس لديك الصلاحية للوصول إلى هذه الصفحة');
            }

            return $next($request);
        }

        // التحقق من الصلاحية
        if (!$user->hasPermission($permission)) {
            abort(403, 'ليس لديك الصلاحية للوصول إلى هذه الصفحة');
        }

        return $next($request);
    }
}
