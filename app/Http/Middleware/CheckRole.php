<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware للتحقق من الأدوار
 * يحمي الصفحات بناءً على دور المستخدم
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  الأدوار المسموحة (مفصولة بـ |)
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            // تسجيل محاولة الوصول بدون تسجيل دخول
            Log::warning('محاولة الوصول غير مصرح بها', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return redirect('/login')
                ->with('error', 'يجب تسجيل الدخول أولاً')
                ->with('intended', $request->fullUrl());
        }

        $user = Auth::user();

        // التحقق من حالة الحساب
        if (isset($user->status) && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/login')
                ->with('error', 'حسابك غير نشط. يرجى الاتصال بالإدارة');
        }

        // مدير المنصة يتجاوز قيد الدور — دخول الحصة كان يُرفض لأن الغرفة مسجّلة تحت role:student.
        $rawRole = strtolower(trim((string) $user->role));
        if ($user->isAdmin() || in_array($rawRole, ['admin', 'super_admin'], true)) {
            return $next($request);
        }

        // التحقق من الدور
        $allowedRoles = explode('|', $role);
        $userRole = strtolower(trim($user->role));

        // دعم الأدوار القديمة والجديدة
        $roleMapping = [
            'admin' => 'super_admin',
            // «معلم» في الواجهة = طالب — لا يُعاد توجيهه كمدرب
            'teacher' => 'student',
            // ولي الأمر يستخدم بوابة الطالب ويربط الأبناء من الملف
            'parent' => 'student',
        ];

        if (isset($roleMapping[$userRole])) {
            $userRole = $roleMapping[$userRole];
        }

        // تحويل الأدوار المسموحة إلى lowercase
        $allowedRoles = array_map('strtolower', array_map('trim', $allowedRoles));

        if (!in_array($userRole, $allowedRoles)) {
            // تسجيل محاولة الوصول غير مصرح بها
            Log::warning('محاولة الوصول غير مصرح بها - عدم تطابق الدور', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'required_roles' => $allowedRoles,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            // توجيه المستخدم إلى الـ dashboard المناسب بناءً على دوره
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'غير مسموح لك بالوصول لهذه الصفحة');
            } elseif ($user->isInstructor()) {
                return redirect()->to($user->instructorHomeUrl())
                    ->with('error', 'غير مسموح لك بالوصول لهذه الصفحة');
            } else {
                return redirect()->route('dashboard')
                    ->with('error', 'غير مسموح لك بالوصول لهذه الصفحة');
            }
        }

        return $next($request);
    }
}

