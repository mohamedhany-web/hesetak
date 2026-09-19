<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Support\RbacAdminRouteAccess;
use App\Models\TwoFactorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL) || str_starts_with((string) $redirectUrl, '/')) {
                session(['url.intended' => $redirectUrl]);
            }
        }

        $authBackgroundUrl = \App\Providers\AppServiceProvider::authBackgroundUrl();
        return view('auth.login', compact('authBackgroundUrl'));
    }

    public function showRegister(Request $request)
    {
        // حفظ redirect URL إذا كان موجوداً في session
        if ($request->has('redirect')) {
            session(['register_redirect' => $request->input('redirect')]);
        }

        if ($request->filled('ref')) {
            session([
                'pending_referral_code' => strtoupper(preg_replace('/\s+/', '', (string) $request->query('ref'))),
            ]);
        }

        $pendingReferralCode = session('pending_referral_code');

        $phoneCountries = config('phone_countries.countries', []);
        $defaultCountry = collect($phoneCountries)->firstWhere('code', config('phone_countries.default_country', 'SA'));
        $authBackgroundUrl = \App\Providers\AppServiceProvider::authBackgroundUrl();
        return view('auth.register', compact('phoneCountries', 'defaultCountry', 'authBackgroundUrl', 'pendingReferralCode'));
    }

    public function login(Request $request)
    {
        // حماية من Honeypot (البوتات)
        if ($request->filled('website')) {
            \Log::warning('Bot detected - Honeypot field filled', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->withInput();
        }

        // تنظيف وتطهير المدخلات
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');

        // Validation محسن مع حماية من SQL Injection
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => [
                'required',
                'email', // إزالة rfc,dns لأنها قد تفشل مع بعض البريدات
                'max:255',
                function ($attribute, $value, $fail) {
                    // حماية من SQL Injection - التحقق من عدم وجود أحرف خطيرة
                    if (preg_match('/[<>"\';()&|`$]/', $value)) {
                        $fail('البريد الإلكتروني يحتوي على أحرف غير مسموحة.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني طويل جداً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Rate Limiting - حماية من Brute Force (محاولات فاشلة فقط؛ النجاح يمسح العداد)
            $key = 'login_attempts_' . $request->ip();
            $maxAttempts = 10;
            $decayMinutes = 15;
            
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($key);
                \Log::warning('Too many login attempts', [
                    'ip' => $request->ip(),
                    'seconds_remaining' => $seconds,
                ]);
                
                return back()->withErrors([
                    'email' => "تم تجاوز عدد المحاولات المسموح. يرجى المحاولة بعد {$seconds} ثانية.",
                ])->withInput();
            }

            // البحث عن المستخدم باستخدام البريد الإلكتروني فقط (حماية من SQL Injection باستخدام Eloquent)
            // البحث بدون case sensitivity لتجنب مشاكل الأحرف الكبيرة/الصغيرة
            $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            
            \Log::info('Login attempt', [
                'email_input' => $email,
                'email_lower' => strtolower($email),
                'user_found' => $user ? true : false,
                'user_id' => $user ? $user->id : null,
            ]);
            
            // التحقق من وجود المستخدم
            if (!$user) {
                // زيادة عداد المحاولات الفاشلة
                \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);
                
                // تسجيل محاولة دخول فاشلة
                try {
                    $securityService = app(\App\Services\SecurityService::class);
                    $securityService->logSuspiciousActivity('Failed Login - User Not Found', $request, "Email: {$email}");
                } catch (\Exception $e) {
                    // تجاهل خطأ SecurityService إذا لم يكن موجوداً
                }
                
                \Log::warning('محاولة دخول فاشلة - مستخدم غير موجود', [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent(), 0, 255),
                ]);
                
                // رسالة عامة لتجنب User Enumeration
                return back()->withErrors([
                    'email' => 'بيانات الدخول غير صحيحة.',
                ])->withInput();
            }
            
            // التحقق من أن المستخدم نشط
            if (!$user->is_active) {
                \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);
                
                return back()->withErrors([
                    'email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.',
                ])->withInput();
            }
            
            // التحقق من كلمة المرور (حماية من Timing Attacks)
            if (!Hash::check($password, $user->password)) {
                // زيادة عداد المحاولات الفاشلة
                \Illuminate\Support\Facades\RateLimiter::hit($key, $decayMinutes * 60);
                
                // تسجيل محاولة دخول فاشلة
                try {
                    $securityService = app(\App\Services\SecurityService::class);
                    $securityService->logSuspiciousActivity('Failed Login - Wrong Password', $request, "User ID: {$user->id}");
                } catch (\Exception $e) {
                    // تجاهل خطأ SecurityService إذا لم يكن موجوداً
                }
                
                \Log::warning('محاولة دخول فاشلة - كلمة مرور خاطئة', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent(), 0, 255),
                ]);
                
                // رسالة عامة لتجنب User Enumeration
                return back()->withErrors([
                    'email' => 'بيانات الدخول غير صحيحة.',
                ])->withInput();
            }
            
            // مسح عداد المحاولات عند النجاح
            \Illuminate\Support\Facades\RateLimiter::clear($key);

            // إذا كان المستخدم مطلوب له 2FA (أدمن/مدرب) → إرسال رمز عبر البريد فقط ثم طلب التحدي
            if ($user->requiresTwoFactor()) {
                $request->session()->put('login.id', $user->id);
                $request->session()->put('login.remember', $request->boolean('remember'));
                $request->session()->save();
                $code = (string) random_int(100000, 999999);
                Cache::put('2fa_code_' . $user->id, $code, now()->addMinutes(10));
                try {
                    Mail::to($user->email)->send(new TwoFactorCodeMail($code));
                    \Log::info('تم إرسال رمز 2FA إلى البريد', ['user_id' => $user->id, 'email' => $user->email]);
                    TwoFactorLog::create([
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'event' => TwoFactorLog::EVENT_CHALLENGE_SENT,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                    Cache::forget('2fa_code_' . $user->id);
                    \Log::error('فشل إرسال رمز 2FA', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                    return back()->withErrors(['email' => 'تعذر إرسال رمز التحقق إلى بريدك. تحقق من إعدادات البريد أو حاول لاحقاً.'])->withInput();
                }
                return redirect()->route('two-factor.challenge');
            }
            
            // تسجيل الدخول
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            
            // حفظ معرف الجلسة الجديد في الكاش
            $sessionId = $request->session()->getId();
            $cacheKey = "user_session_{$user->id}";
            \Cache::put($cacheKey, $sessionId, now()->addDays(7));
            
            // تحديث آخر دخول (حماية من SQL Injection باستخدام Eloquent)
            $user->update(['last_login_at' => now()]);
            
            \Log::info('دخول ناجح للمستخدم', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'ip' => $request->ip(),
            ]);
            
            // إرجاع المستخدم للصفحة التي حاول الوصول إليها أو للـ dashboard
            if ($user->isEmployee()) {
                if ($user->roles()->exists()) {
                    $adminRoute = RbacAdminRouteAccess::firstPostLoginAdminRouteName($user);
                    if ($adminRoute !== null) {
                        return redirect()->intended(route($adminRoute));
                    }

                    return redirect()->intended(route('employee.dashboard'));
                }

                return redirect()->intended(route('employee.dashboard'));
            }
            
            if ($user->role === 'super_admin' || $user->role === 'admin') {
                return redirect()->intended(route('admin.dashboard'));
            }
            
            if ($user->isInstructor()) {
                if (! $user->canAccessInstructorPanel()) {
                    return redirect()->route('public.tutor.apply.profile');
                }

                return redirect()->intended(route('dashboard'));
            }
            
            return redirect()->intended(route('dashboard'));
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('خطأ في قاعدة البيانات أثناء تسجيل الدخول', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return back()->withErrors([
                'email' => 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.',
            ])->withInput();
        } catch (\Exception $e) {
            \Log::error('خطأ في تسجيل الدخول', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);
            
            return back()->withErrors([
                'email' => 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة لاحقاً.',
            ])->withInput();
        }
    }

    public function register(Request $request)
    {
        $countries = config('phone_countries.countries', []);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:12',
            'country_iso' => 'nullable|string|size:2',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'timezone' => ['nullable', 'string', 'max:64'],
            'timezone_auto' => ['nullable', 'string', 'max:64'],
        ], [
            'name.required' => 'الاسم مطلوب',
            'country_code.required' => 'كود الدولة مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
        ]);

        $phoneCountries = $countries;
        $defaultCountry = collect($countries)->firstWhere('code', config('phone_countries.default_country', 'SA'));

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with(compact('phoneCountries', 'defaultCountry'));
        }

        // التحقق من صحة رقم الهاتف حسب الدولة (ISO أولاً ثم كود الاتصال)
        $country = null;
        if ($request->filled('country_iso')) {
            $country = collect($countries)->firstWhere('code', strtoupper((string) $request->country_iso));
            if ($country && ($country['dial_code'] ?? '') !== $request->country_code) {
                $country = null;
            }
        }
        if (! $country) {
            $country = collect($countries)->firstWhere('dial_code', $request->country_code);
        }
        $phoneRegex = $country['validation']['regex'] ?? '/^\d{6,15}$/';
        if (! $country) {
            return back()->withErrors(['phone' => 'كود الدولة غير مدعوم.'])->withInput()->with(compact('phoneCountries', 'defaultCountry'));
        }
        $nationalNumber = preg_replace('/\D/', '', $request->phone);
        $nationalNumber = ltrim($nationalNumber, '0');
        if (! preg_match($phoneRegex, $nationalNumber)) {
            $example = $country['example'] ?? $country['placeholder'] ?? '';
            $message = $example !== ''
                ? ('رقم الهاتف غير صحيح لهذه الدولة. مثال: ' . $example)
                : 'رقم الهاتف غير صحيح لهذه الدولة.';

            return back()->withErrors(['phone' => $message])->withInput()->with(compact('phoneCountries', 'defaultCountry'));
        }
        $dial = $country['dial_code'] ?? '';
        $fullPhone = ($dial === '' || $dial === 'OTHER') ? ('OTHER_' . $nationalNumber) : ($dial . $nationalNumber);
        if (User::where('phone', $fullPhone)->exists()) {
            return back()->withErrors(['phone' => 'رقم الهاتف مسجل مسبقاً'])->withInput()->with(compact('phoneCountries', 'defaultCountry'));
        }

        // التسجيل متاح فقط للطلاب
        $timezone = \App\Support\AppTimezone::normalize($request->input('timezone'))
            ?: \App\Support\AppTimezone::normalize($request->input('timezone_auto'))
            ?: \App\Support\AppTimezone::normalize(session('pending_timezone'));

        $user = User::create([
            'name' => $request->name,
            'phone' => $fullPhone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student', // فقط طالب
            'is_active' => true,
            'timezone' => $timezone,
        ]);
        session()->forget('pending_timezone');

        $referralCode = $request->input('referral_code');
        if ($referralCode === null || $referralCode === '') {
            $referralCode = session('pending_referral_code');
        }
        if ($referralCode === null || $referralCode === '') {
            $referralCode = $request->query('ref');
        }
        $referralCode = $referralCode ? strtoupper(preg_replace('/\s+/', '', (string) $referralCode)) : null;
        session()->forget('pending_referral_code');

        // معالجة كود الإحالة في Queue لتقليل الضغط على السيرفر
        \App\Jobs\ProcessStudentRegistration::dispatch(
            $user->id,
            $referralCode
        )->onQueue('registrations');

        Auth::login($user);

        // التحقق من وجود redirect URL في session (من صفحة التسجيل)
        $redirectUrl = session('register_redirect');
        if ($redirectUrl) {
            session()->forget('register_redirect');
            // التحقق من أن URL صحيح
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL) || str_starts_with($redirectUrl, '/')) {
                return redirect($redirectUrl);
            }
        }

        // التحقق من وجود redirect parameter في request
        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL) || str_starts_with($redirectUrl, '/')) {
                return redirect($redirectUrl);
            }
        }

        // بعد إنشاء الحساب نوجّه مباشرة للداشبورد (بدون استخدام intended لتجنب التوجيه لرابط API أو صفحة قديمة)
        session()->forget('url.intended');
        if ($user->isEmployee()) {
            return redirect()->route('employee.dashboard');
        }
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // مسح معرف الجلسة من الكاش لتجنب مشاكل تسجيل الدخول اللاحق
        if ($user) {
            $cacheKey = "user_session_{$user->id}";
            \Cache::forget($cacheKey);
            \Log::info('تسجيل خروج للمستخدم: ' . $user->id . ' - تم مسح الجلسة من الكاش');
        }
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
