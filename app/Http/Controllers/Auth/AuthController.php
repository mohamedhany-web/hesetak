<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Support\RbacAdminRouteAccess;
use App\Support\StudentExclusiveSession;
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
        $academicYears = \App\Support\StudentLearningProfile::publicYears();
        $gradesByYear = \App\Support\StudentLearningProfile::gradesByYearMap($academicYears);
        $subjectsByYear = $this->subjectsByYearMap($academicYears);
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

        $response = response()->view('auth.register', compact(
            'phoneCountries',
            'defaultCountry',
            'authBackgroundUrl',
            'pendingReferralCode',
            'academicYears',
            'gradesByYear',
            'subjectsByYear',
            'curriculumTypes'
        ));

        $referralService = app(\App\Services\ReferralService::class);
        $deviceFingerprint = $referralService->resolveDeviceFingerprint(
            $request->cookie(\App\Services\ReferralService::DEVICE_COOKIE)
        );

        return $response->withCookie($referralService->queueDeviceFingerprintCookie($deviceFingerprint));
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

            // طلاب فقط: جلسة واحدة — إبطال جلسات الأجهزة الأخرى
            StudentExclusiveSession::claim($user, $request->session()->getId());
            
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
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'timezone' => ['nullable', 'string', 'max:64'],
            'timezone_auto' => ['nullable', 'string', 'max:64'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'parent_country_code' => 'required|string|max:12',
            'parent_country_iso' => 'nullable|string|size:2',
            'parent_phone' => 'required|string|max:20',
            'academic_grade_id' => ['nullable', 'integer'],
            'preferred_curriculum_type' => ['required', 'string', 'max:40'],
            'preferred_subject_ids' => ['nullable', 'array'],
            'preferred_subject_ids.*' => ['integer', 'exists:academic_subjects,id'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ], [
            'name.required' => 'الاسم مطلوب',
            'country_code.required' => 'كود الدولة مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'هذا البريد مرتبط بحساب موجود. استخدمه لتسجيل الدخول.',
            'parent_phone.required' => 'رقم ولي الأمر مطلوب',
            'parent_country_code.required' => 'كود دولة ولي الأمر مطلوب',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'academic_year_id.required' => 'المرحلة الدراسية مطلوبة',
            'academic_year_id.exists' => 'المرحلة الدراسية غير صالحة',
            'academic_grade_id.required' => 'الصف الدراسي مطلوب',
            'preferred_curriculum_type.required' => 'نوع المنهج مطلوب',
        ]);

        $phoneCountries = $countries;
        $defaultCountry = collect($countries)->firstWhere('code', config('phone_countries.default_country', 'SA'));
        $academicYears = \App\Support\StudentLearningProfile::publicYears();
        $gradesByYear = \App\Support\StudentLearningProfile::gradesByYearMap($academicYears);
        $subjectsByYear = $this->subjectsByYearMap($academicYears);
        $curriculumTypes = \App\Support\HesetakMatchCatalog::curriculumTypes();

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with(compact(
                'phoneCountries',
                'defaultCountry',
                'academicYears',
                'gradesByYear',
                'subjectsByYear',
                'curriculumTypes'
            ));
        }

        $curriculumKey = \App\Support\HesetakMatchCatalog::allowedCurriculumTypeKeys();
        $preferredCurriculum = strtolower(trim((string) $request->input('preferred_curriculum_type')));
        if (! in_array($preferredCurriculum, $curriculumKey, true)) {
            return back()->withErrors([
                'preferred_curriculum_type' => 'نوع المنهج غير صالح.',
            ])->withInput()->with(compact('phoneCountries', 'defaultCountry', 'academicYears', 'gradesByYear', 'subjectsByYear', 'curriculumTypes'));
        }

        $yearId = (int) $request->input('academic_year_id');
        if (! $academicYears->contains('id', $yearId)) {
            return back()->withErrors([
                'academic_year_id' => 'المرحلة الدراسية غير متاحة للتسجيل.',
            ])->withInput()->with(compact('phoneCountries', 'defaultCountry', 'academicYears', 'gradesByYear', 'subjectsByYear', 'curriculumTypes'));
        }

        $gradeOptions = $gradesByYear[(string) $yearId] ?? [];
        $gradeId = $request->filled('academic_grade_id') ? (int) $request->input('academic_grade_id') : null;
        if ($gradeOptions !== []) {
            $gradeIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $gradeOptions);
            if (! $gradeId || ! in_array($gradeId, $gradeIds, true)) {
                return back()->withErrors([
                    'academic_grade_id' => 'اختر الصف الدراسي لهذه المرحلة.',
                ])->withInput()->with(compact('phoneCountries', 'defaultCountry', 'academicYears', 'gradesByYear', 'subjectsByYear', 'curriculumTypes'));
            }
        } else {
            $gradeId = null;
        }

        $formContext = compact(
            'phoneCountries',
            'defaultCountry',
            'academicYears',
            'gradesByYear',
            'subjectsByYear',
            'curriculumTypes'
        );

        $studentPhone = $this->resolveRegistrationPhone($request, $countries, 'phone', 'country_code', 'country_iso', 'رقم الهاتف');
        if (isset($studentPhone['error'])) {
            return back()->withErrors(['phone' => $studentPhone['error']])->withInput()->with($formContext);
        }
        $fullPhone = $studentPhone['full'];
        if (User::where('phone', $fullPhone)->exists()) {
            return back()->withErrors(['phone' => 'رقم الهاتف مسجل مسبقاً'])->withInput()->with($formContext);
        }

        $parentPhone = $this->resolveRegistrationPhone($request, $countries, 'parent_phone', 'parent_country_code', 'parent_country_iso', 'رقم ولي الأمر');
        if (isset($parentPhone['error'])) {
            return back()->withErrors(['parent_phone' => $parentPhone['error']])->withInput()->with($formContext);
        }

        // التسجيل متاح فقط للطلاب
        $timezone = \App\Support\AppTimezone::normalize($request->input('timezone'))
            ?: \App\Support\AppTimezone::normalize($request->input('timezone_auto'))
            ?: \App\Support\AppTimezone::normalize(session('pending_timezone'));

        $userPayload = [
            'name' => $request->name,
            'phone' => $fullPhone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'is_active' => true,
            'timezone' => $timezone,
            'academic_year_id' => $yearId,
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'parent_phone')) {
            $userPayload['parent_phone'] = $parentPhone['full'];
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'birth_date') && $request->filled('birth_date')) {
            $userPayload['birth_date'] = $request->date('birth_date')->format('Y-m-d');
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_grade_id')) {
            $userPayload['academic_grade_id'] = $gradeId;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'preferred_curriculum_type')) {
            $userPayload['preferred_curriculum_type'] = $preferredCurriculum;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'preferred_subject_ids')) {
            $subjectIds = collect($request->input('preferred_subject_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            $allowedForYear = collect($subjectsByYear[(string) $yearId] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();
            $userPayload['preferred_subject_ids'] = array_values(array_intersect($subjectIds, $allowedForYear));
        }

        $user = User::create($userPayload);
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

        $referralService = app(\App\Services\ReferralService::class);
        $deviceFingerprint = $referralService->resolveDeviceFingerprint(
            $request->cookie(\App\Services\ReferralService::DEVICE_COOKIE)
        );

        // معالجة كود الإحالة في Queue لتقليل الضغط على السيرفر
        \App\Jobs\ProcessStudentRegistration::dispatch(
            $user->id,
            $referralCode,
            $deviceFingerprint
        )->onQueue('registrations');

        Auth::login($user);
        $request->session()->regenerate();
        StudentExclusiveSession::claim($user, $request->session()->getId());

        $cookie = $referralService->queueDeviceFingerprintCookie($deviceFingerprint);

        // التحقق من وجود redirect URL في session (من صفحة التسجيل)
        $redirectUrl = session('register_redirect');
        if ($redirectUrl) {
            session()->forget('register_redirect');
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL) || str_starts_with((string) $redirectUrl, '/')) {
                session()->forget('url.intended');

                return redirect($redirectUrl)->withCookie($cookie);
            }
        }

        if ($request->has('redirect')) {
            $redirectUrl = $request->input('redirect');
            if (filter_var($redirectUrl, FILTER_VALIDATE_URL) || str_starts_with((string) $redirectUrl, '/')) {
                session()->forget('url.intended');

                return redirect($redirectUrl)->withCookie($cookie);
            }
        }

        $intended = session('url.intended');
        if (is_string($intended) && $intended !== '' && preg_match('#/(service-packages/.+/checkout|gift-package|pricing)(/|\?|$)#', $intended)) {
            session()->forget('url.intended');

            return redirect($intended)->withCookie($cookie);
        }

        session()->forget('url.intended');
        if ($user->isEmployee()) {
            return redirect()->route('employee.dashboard')->withCookie($cookie);
        }
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->withCookie($cookie);
        }

        return redirect()->route('dashboard')->withCookie($cookie);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\AcademicYear>  $years
     * @return array<string, list<array{id:int,name:string}>>
     */
    protected function subjectsByYearMap($years): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_subjects')) {
            return [];
        }

        $map = [];
        foreach ($years as $year) {
            // نفس منطق الكتالوج: مواد المرحلة + مواد عامة (academic_year_id فارغ)
            $map[(string) $year->id] = \App\Models\AcademicSubject::query()
                ->active()
                ->where(function ($q) use ($year) {
                    $q->whereNull('academic_year_id')
                        ->orWhere('academic_year_id', $year->id);
                })
                ->ordered()
                ->get(['id', 'name'])
                ->map(fn ($s) => ['id' => (int) $s->id, 'name' => (string) $s->name])
                ->values()
                ->all();
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $countries
     * @return array{full: string}|array{error: string}
     */
    protected function resolveRegistrationPhone(
        Request $request,
        array $countries,
        string $numberField,
        string $dialField,
        string $isoField,
        string $label
    ): array {
        $country = null;
        $dialCode = (string) $request->input($dialField, '');
        if ($request->filled($isoField)) {
            $country = collect($countries)->firstWhere('code', strtoupper((string) $request->input($isoField)));
            if ($country && ($country['dial_code'] ?? '') !== $dialCode) {
                $country = null;
            }
        }
        if (! $country) {
            $country = collect($countries)->firstWhere('dial_code', $dialCode);
        }
        if (! $country) {
            return ['error' => 'كود الدولة غير مدعوم.'];
        }

        $nationalNumber = ltrim(preg_replace('/\D/', '', (string) $request->input($numberField, '')) ?? '', '0');
        $phoneRegex = $country['validation']['regex'] ?? '/^\d{6,15}$/';
        if (! preg_match($phoneRegex, $nationalNumber)) {
            $example = $country['example'] ?? $country['placeholder'] ?? '';

            return ['error' => $example !== ''
                ? ($label.' غير صحيح لهذه الدولة. مثال: '.$example)
                : ($label.' غير صحيح لهذه الدولة.')];
        }

        $dial = $country['dial_code'] ?? '';
        $full = ($dial === '' || $dial === 'OTHER') ? ('OTHER_'.$nationalNumber) : ($dial.$nationalNumber);

        return ['full' => $full];
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user && StudentExclusiveSession::appliesTo($user)) {
            StudentExclusiveSession::forget($user);
            \Log::info('تسجيل خروج للطالب: '.$user->id.' - تم مسح قفل الجلسة الحصرية');
        }
        
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
