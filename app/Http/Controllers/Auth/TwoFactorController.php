<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorLog;
use App\Models\User;
use App\Support\RbacAdminRouteAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * عرض صفحة إدخال رمز المصادقة الثنائية (بعد كلمة المرور عند الدخول)
     */
    public function showChallenge(Request $request)
    {
        if (!$request->session()->has('login.id')) {
            return redirect()->route('login')
                ->with('warning', 'انتهت خطوة التحقق أو الجلسة. أدخل البريد وكلمة المرور من جديد.');
        }
        $userId = $request->session()->get('login.id');
        $user = User::find($userId);
        if (!$user || !$user->requiresTwoFactor()) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login')
                ->with('warning', 'لا يمكن متابعة التحقق الثنائي لهذا الحساب. سجّل الدخول من جديد.');
        }
        // 2FA عبر البريد عند تفعيل الإلزام من إعدادات النظام (حالياً للأدمن فقط)
        $useEmail = true;
        return view('auth.two-factor.challenge', compact('useEmail'));
    }

    /**
     * التحقق من رمز المصادقة الثنائية وإكمال تسجيل الدخول
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => 'required|string|min:6|max:10',
        ], [
            'code.required' => 'رمز التحقق مطلوب',
            'code.min' => 'رمز التحقق يتكون من 6 أرقام',
        ]);

        if (!$request->session()->has('login.id')) {
            \Log::warning('2FA verify: session missing login.id', ['session_id' => $request->session()->getId()]);
            return redirect()->route('login')->withErrors(['code' => 'انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.']);
        }

        $user = User::find($request->session()->get('login.id'));
        if (!$user || !$user->requiresTwoFactor()) {
            $request->session()->forget(['login.id', 'login.remember']);
            return redirect()->route('login');
        }

        // توحيد الرمز: أرقام إنجليزية فقط (دعم الأرقام العربية إن وُجدت)
        $codeInput = $this->normalize2FACode($request->code);
        if (strlen($codeInput) !== 6) {
            return back()->withErrors(['code' => 'رمز التحقق يتكون من 6 أرقام.']);
        }

        $cachedCode = Cache::get('2fa_code_' . $user->id);
        $valid = $cachedCode !== null && (string) $cachedCode === $codeInput;
        if ($valid) {
            Cache::forget('2fa_code_' . $user->id);
        }

        if (!$valid) {
            TwoFactorLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => TwoFactorLog::EVENT_FAILED,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        TwoFactorLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'event' => TwoFactorLog::EVENT_VERIFIED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $remember = $request->session()->get('login.remember', false);
        $request->session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate();

        Auth::login($user, $remember);
        $user->update(['last_login_at' => now()]);

        // تحديث الكاش بمعرف الجلسة الحالية حتى لا يعتبر PreventConcurrentSessions الجلسة «متزامنة» ويُخرج المستخدم
        $sessionId = $request->session()->getId();
        $cacheKey = "user_session_{$user->id}";
        Cache::put($cacheKey, $sessionId, now()->addDays(7));

        if ($user->isEmployee()) {
            return redirect()->intended(route($this->getDashboardRoute($user)));
        }
        if ($user->role === 'super_admin' || $user->role === 'admin') {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->isInstructor()) {
            return redirect()->intended($user->instructorHomeUrl());
        }
        return redirect()->intended(route('dashboard'));
    }

    /**
     * عرض صفحة إعداد المصادقة الثنائية (TOTP) — لمن يُشمَّل بـ requiresTwoFactor
     */
    public function showSetup(Request $request)
    {
        $user = $request->user();
        if (!$user->requiresTwoFactor()) {
            abort(403, 'المصادقة الثنائية متاحة للإدمن فقط.');
        }
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route($this->getDashboardRoute($user))
                ->with('info', 'المصادقة الثنائية مفعّلة مسبقاً.');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey(32);
        $request->session()->put('two_factor.setup_secret', $secret);

        $appName = config('app.name');
        $accountLabel = $user->email ?? ('user-'.$user->id.'@'.Str::slug($appName).'.local');
        $qrCodeUrl = $google2fa->getQRCodeUrl($appName, $accountLabel, $secret);

        return view('auth.two-factor.setup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * تأكيد تفعيل المصادقة الثنائية بعد إدخال الرمز
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'رمز التحقق مطلوب',
            'code.size' => 'رمز التحقق يتكون من 6 أرقام',
        ]);

        $user = $request->user();
        if (!$user->requiresTwoFactor()) {
            abort(403);
        }
        $secret = $request->session()->get('two_factor.setup_secret');
        if (!$secret) {
            return redirect()->route('two-factor.setup')->withErrors(['code' => 'انتهت الجلسة. يرجى البدء من جديد.']);
        }

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $request->code, 2)) {
            return back()->withErrors(['code' => 'رمز التحقق غير صحيح.']);
        }

        $recoveryCodes = collect(range(1, 8))->map(fn() => Str::random(10))->values()->all();
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => array_map(fn($c) => $c, $recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.setup_secret');

        $dashboardRoute = $this->getDashboardRoute($user);
        return redirect()->route($dashboardRoute)
            ->with('success', 'تم تفعيل المصادقة الثنائية بنجاح.')
            ->with('recovery_codes', $recoveryCodes);
    }

    /**
     * تعطيل المصادقة الثنائية (يتطلب كلمة المرور)
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ], ['password.required' => 'كلمة المرور مطلوبة للتأكيد.']);

        $user = $request->user();
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $dashboardRoute = $this->getDashboardRoute($user);
        return redirect()->route($dashboardRoute)->with('success', 'تم تعطيل المصادقة الثنائية.');
    }

    protected function getDashboardRoute(User $user): string
    {
        if ($user->isEmployee() || $user->role === 'employee') {
            if ($user->roles()->exists() && $user->userHasAdminAccessCapability()) {
                $adminRoute = RbacAdminRouteAccess::firstPostLoginAdminRouteName($user);
                if ($adminRoute !== null) {
                    return $adminRoute;
                }
            }

            return 'employee.dashboard';
        }
        if ($user->role === 'super_admin' || $user->role === 'admin') {
            return 'admin.dashboard';
        }
        if ($user->isInstructor()) {
            return $user->canAccessInstructorPanel() ? 'dashboard' : 'public.tutor.apply.profile';
        }
        return 'dashboard';
    }

    /** توحيد رمز 2FA: استخراج 6 أرقام فقط مع دعم الأرقام العربية ٠-٩ */
    private function normalize2FACode(string $input): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $s = str_replace($arabic, $english, trim($input));
        $digits = preg_replace('/\D/', '', $s);

        return strlen($digits) >= 6 ? substr($digits, 0, 6) : $digits;
    }
}
