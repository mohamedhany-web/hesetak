<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TwoFactorCodeMail;
use App\Models\Setting;
use App\Models\TwoFactorLog;
use App\Services\AdminPanelBranding;
use App\Services\KashierSettings;
use App\Services\PayPalSettings;
use App\Services\PaymentGatewaySettings;
use App\Services\PlatformSecuritySettings;
use App\Services\PlatformMediaSettings;
use App\Services\PublicFooterSettings;
use App\Services\SiteDataWipeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class SystemSettingsController extends Controller
{
    private const SESSION_2FA_ENABLE = 'system_settings_2fa_enable_started';

    /** نسخة احتياطية عند فقدان الجلسة بين طلب الرمز وصفحة التأكيد */
    private function twoFactorEnablePendingCacheKey(int $userId): string
    {
        return 'system_2fa_enable_pending_' . $userId;
    }

    private function hasTwoFactorEnableFlowPending(Request $request): bool
    {
        $uid = (int) $request->user()->id;

        return (bool) $request->session()->get(self::SESSION_2FA_ENABLE)
            || (bool) Cache::get($this->twoFactorEnablePendingCacheKey($uid));
    }

    private function forgetTwoFactorEnableFlow(Request $request): void
    {
        $uid = (int) $request->user()->id;
        $request->session()->forget(self::SESSION_2FA_ENABLE);
        Cache::forget($this->twoFactorEnablePendingCacheKey($uid));
    }

    public function __construct()
    {
        $this->middleware('can:manage.system-settings');
    }

    public function edit(): View
    {
        $defaults = PublicFooterSettings::defaults();
        $values = [];
        foreach (array_keys($defaults) as $key) {
            $stored = Setting::getValue($key);
            $values[$key] = $stored ?? '';
        }

        $adminPanelLogoUrl = AdminPanelBranding::logoPublicUrl();
        $adminTwoFactorRequired = PlatformSecuritySettings::isAdminTwoFactorRequired();
        $currentUser = auth()->user();
        $admin2faAppliesToCurrentUserRole = $currentUser && in_array((string) $currentUser->role, ['super_admin', 'admin'], true);

        $r2PublicUrl = Setting::getValue(PlatformMediaSettings::SETTING_R2_PUBLIC_URL) ?? '';
        $fawaterakGatewayEnabled = PaymentGatewaySettings::isFawaterakEnabled();
        $paypalGatewayEnabled = PayPalSettings::isEnabled();
        $kashierGatewayEnabled = KashierSettings::isEnabled();
        $onlineGatewaysEnabledCount = (int) $fawaterakGatewayEnabled + (int) $paypalGatewayEnabled + (int) $kashierGatewayEnabled;

        return view('admin.system-settings.edit', compact(
            'defaults',
            'values',
            'adminPanelLogoUrl',
            'adminTwoFactorRequired',
            'admin2faAppliesToCurrentUserRole',
            'r2PublicUrl',
            'fawaterakGatewayEnabled',
            'paypalGatewayEnabled',
            'kashierGatewayEnabled',
            'onlineGatewaysEnabledCount'
        ));
    }

    /**
     * بدء تفعيل إلزام 2FA: إرسال رمز للبريد والانتقال لصفحة التأكيد.
     */
    public function requestTwoFactorEnable(Request $request): RedirectResponse
    {
        if (PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            return redirect()->route('admin.system-settings.edit')
                ->with('info', 'إلزام المصادقة الثنائية مفعّل مسبقاً.');
        }

        $user = $request->user();
        if (! $user || empty($user->email)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'لا يوجد بريد إلكتروني مرتبط بحسابك. أضف بريداً صالحاً في الملف الشخصي ثم أعد المحاولة.']);
        }

        $rateKey = 'system-2fa-enable:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'عدد كبير من الطلبات. حاول بعد دقيقة.']);
        }
        RateLimiter::hit($rateKey, 60);

        $code = (string) random_int(100000, 999999);
        Cache::put('system_2fa_enable_code_' . $user->id, $code, now()->addMinutes(15));
        $request->session()->put(self::SESSION_2FA_ENABLE, true);

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($code, true));
            TwoFactorLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => TwoFactorLog::EVENT_CHALLENGE_SENT,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable $e) {
            report($e);
            Cache::forget('system_2fa_enable_code_' . $user->id);
            $this->forgetTwoFactorEnableFlow($request);

            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'تعذّر إرسال البريد. تحقق من إعدادات البريد في السيرفر ثم أعد المحاولة.']);
        }

        Cache::put($this->twoFactorEnablePendingCacheKey($user->id), true, now()->addMinutes(20));
        $request->session()->save();

        return redirect()->route('admin.system-settings.two-factor.confirm')
            ->with('success', 'تم إرسال رمز مكوّن من 6 أرقام إلى بريدك: ' . $user->email);
    }

    public function showTwoFactorConfirm(Request $request): View|RedirectResponse
    {
        if (PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            return redirect()->route('admin.system-settings.edit');
        }
        if (! $this->hasTwoFactorEnableFlowPending($request)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'ابدأ من صفحة الإعدادات باختيار تفعيل المصادقة الثنائية.']);
        }

        if (! $request->session()->get(self::SESSION_2FA_ENABLE)) {
            $request->session()->put(self::SESSION_2FA_ENABLE, true);
            $request->session()->save();
        }

        return view('admin.system-settings.two-factor-confirm', [
            'userEmail' => $request->user()->email,
        ]);
    }

    public function confirmTwoFactorEnable(Request $request): RedirectResponse
    {
        if (PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            return redirect()->route('admin.system-settings.edit');
        }
        if (! $this->hasTwoFactorEnableFlowPending($request)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'انتهت الجلسة أو انتهت صلاحية طلب التفعيل. أعد طلب الرمز من صفحة الإعدادات.']);
        }

        if (! $request->session()->get(self::SESSION_2FA_ENABLE)) {
            $request->session()->put(self::SESSION_2FA_ENABLE, true);
            $request->session()->save();
        }

        $request->validate([
            'code' => ['required', 'string', 'min:6', 'max:12'],
        ], [
            'code.required' => 'رمز التحقق مطلوب.',
        ]);

        $user = $request->user();
        $codeInput = $this->normalize2FACode($request->input('code', ''));
        if (strlen($codeInput) !== 6) {
            return back()->withErrors(['code' => 'الرمز يجب أن يكون 6 أرقام.'])->withInput();
        }

        $cached = Cache::get('system_2fa_enable_code_' . $user->id);
        if ($cached === null || (string) $cached !== $codeInput) {
            TwoFactorLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => TwoFactorLog::EVENT_FAILED,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return back()->withErrors(['code' => 'رمز التحقق غير صحيح أو منتهٍ.'])->withInput();
        }

        Cache::forget('system_2fa_enable_code_' . $user->id);
        $this->forgetTwoFactorEnableFlow($request);

        PlatformSecuritySettings::setAdminTwoFactorRequired(true);

        if (! PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            report(new \RuntimeException('admin_2fa_required: setAdminTwoFactorRequired(true) did not persist (check settings table / DB).'));

            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['two_factor' => 'تعذّر حفظ تفعيل الإلزام في قاعدة البيانات. تحقق من اتصال MySQL وجدول settings ثم أعد المحاولة.']);
        }

        TwoFactorLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'event' => TwoFactorLog::EVENT_VERIFIED,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('admin.system-settings.edit')
            ->with('success', 'تم تفعيل إلزام المصادقة الثنائية لحسابات الأدمن فقط. سيُطلب رمز من البريد بعد كلمة المرور عند تسجيل الدخول.');
    }

    public function resendTwoFactorEnableCode(Request $request): RedirectResponse
    {
        return $this->requestTwoFactorEnable($request);
    }

    /**
     * تعطيل إلزام 2FA على مستوى المنصة (يتطلب كلمة مرور الحساب).
     */
    public function disablePlatformTwoFactor(Request $request): RedirectResponse
    {
        if (! PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            return redirect()->route('admin.system-settings.edit')
                ->with('info', 'إلزام المصادقة الثنائية غير مفعّل حالياً.');
        }

        $request->validate([
            'password' => ['required', 'string'],
        ], ['password.required' => 'أدخل كلمة المرور للتأكيد.']);

        $user = $request->user();
        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'كلمة المرور غير صحيحة.'])->withInput();
        }

        PlatformSecuritySettings::setAdminTwoFactorRequired(false);

        return redirect()->route('admin.system-settings.edit')
            ->with('success', 'تم تعطيل إلزام المصادقة الثنائية على مستوى المنصة.');
    }

    private function normalize2FACode(string $input): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $s = str_replace($arabic, $english, trim($input));
        $digits = preg_replace('/\D/', '', $s);

        return strlen($digits) >= 6 ? substr($digits, 0, 6) : $digits;
    }

    public function update(Request $request): RedirectResponse
    {
        $footerRules = [
            'footer_brand_tagline' => ['nullable', 'string', 'max:160'],
            'footer_blurb' => ['nullable', 'string', 'max:600'],
            'footer_email' => ['nullable', 'email', 'max:190'],
            'footer_phone' => ['nullable', 'string', 'max:80'],
            'footer_whatsapp_url' => ['nullable', 'string', 'max:500'],
            'footer_bottom_tagline' => ['nullable', 'string', 'max:200'],
            'social_facebook_url' => ['nullable', 'string', 'max:500'],
            'social_x_url' => ['nullable', 'string', 'max:500'],
            'social_instagram_url' => ['nullable', 'string', 'max:500'],
            'social_youtube_url' => ['nullable', 'string', 'max:500'],
            'social_linkedin_url' => ['nullable', 'string', 'max:500'],
            'social_tiktok_url' => ['nullable', 'string', 'max:500'],
            'social_telegram_url' => ['nullable', 'string', 'max:500'],
            'social_snapchat_url' => ['nullable', 'string', 'max:500'],
            'r2_public_url' => ['nullable', 'string', 'max:500'],
        ];

        $footerData = [];
        foreach (PublicFooterSettings::editableKeys() as $key) {
            $v = $request->input($key, '');
            $footerData[$key] = ($v === null || $v === '') ? null : (is_string($v) ? trim($v) : $v);
        }

        $validated = Validator::make(
            array_merge($footerData, [
                'admin_panel_logo' => $request->file('admin_panel_logo'),
                'remove_admin_panel_logo' => $request->boolean('remove_admin_panel_logo'),
                'r2_public_url' => $request->input('r2_public_url'),
            ]),
            array_merge($footerRules, [
                'admin_panel_logo' => ['nullable', 'image', 'max:'.config('upload_limits.max_upload_kb'), 'mimes:jpg,jpeg,png,webp,gif'],
                'remove_admin_panel_logo' => ['nullable', 'boolean'],
            ])
        )->validate();

        $r2Raw = isset($validated['r2_public_url']) ? trim((string) $validated['r2_public_url']) : '';
        if ($r2Raw !== '' && str_contains($r2Raw, 'cloudflarestorage.com')) {
            return back()->withErrors([
                'r2_public_url' => 'استخدم رابط R2 العام (pub-xxx.r2.dev) وليس cloudflarestorage.com.',
            ])->withInput();
        }
        if ($r2Raw !== '' && ! filter_var($r2Raw, FILTER_VALIDATE_URL)) {
            return back()->withErrors(['r2_public_url' => 'رابط R2 العام غير صالح.'])->withInput();
        }

        foreach (PublicFooterSettings::editableKeys() as $key) {
            $raw = isset($validated[$key]) && $validated[$key] !== null ? trim((string) $validated[$key]) : '';
            if ($raw !== '' && str_starts_with($key, 'social_') && str_ends_with($key, '_url')) {
                if (! filter_var($raw, FILTER_VALIDATE_URL)) {
                    return back()->withErrors([$key => 'رابط غير صالح.'])->withInput();
                }
            }
            if ($raw !== '' && $key === 'footer_whatsapp_url' && ! filter_var($raw, FILTER_VALIDATE_URL)) {
                return back()->withErrors(['footer_whatsapp_url' => 'رابط واتساب غير صالح.'])->withInput();
            }
        }

        try {
            if ($request->boolean('remove_admin_panel_logo')) {
                AdminPanelBranding::removeLogo();
            }
            if ($request->hasFile('admin_panel_logo')) {
                AdminPanelBranding::storeLogo($request->file('admin_panel_logo'));
            }
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'admin_panel_logo' => 'تعذّر رفع أو حذف الشعار. إن كنت تستخدم Cloudflare R2 فتأكد من AWS_* و AWS_URL ثم نفّذ php artisan config:clear.',
            ])->withInput();
        }

        foreach (PublicFooterSettings::editableKeys() as $key) {
            $raw = isset($validated[$key]) && $validated[$key] !== null ? trim((string) $validated[$key]) : '';
            Setting::setValue($key, $raw !== '' ? $raw : null);
        }

        Setting::setValue(
            PlatformMediaSettings::SETTING_R2_PUBLIC_URL,
            $r2Raw !== '' ? rtrim($r2Raw, '/') : null
        );
        PlatformMediaSettings::forgetCache();
        AdminPanelBranding::forgetLogoUrlCache();

        PublicFooterSettings::forgetCache();

        return redirect()->route('admin.system-settings.edit')->with('success', 'تم حفظ إعدادات النظام بنجاح.');
    }

    /**
     * مسح كل بيانات الموقع مع الإبقاء على حساب الأدمن الحالي فقط، ثم بذر الصلاحيات.
     */
    public function wipeSiteData(Request $request, SiteDataWipeService $wipe): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! in_array((string) $user->role, ['super_admin', 'admin'], true)) {
            abort(403);
        }

        $rateKey = 'site-wipe:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 2)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['wipe' => 'محاولات كثيرة. انتظر دقيقة ثم أعد المحاولة.']);
        }
        RateLimiter::hit($rateKey, 120);

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'confirm_phrase' => ['required', 'string'],
        ], [
            'password.required' => 'أدخل كلمة مرور حسابك للتأكيد.',
            'confirm_phrase.required' => 'اكتب عبارة التأكيد.',
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['wipe' => 'كلمة المرور غير صحيحة.']);
        }

        $expected = 'مسح كل البيانات';
        if (trim((string) $validated['confirm_phrase']) !== $expected) {
            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['wipe' => 'عبارة التأكيد غير مطابقة. اكتب بالضبط: '.$expected]);
        }

        try {
            $result = $wipe->wipeExceptAdmins([(int) $user->id], true);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.system-settings.edit')
                ->withErrors(['wipe' => 'فشل المسح: '.$e->getMessage()]);
        }

        return redirect()->route('admin.system-settings.edit')->with(
            'success',
            sprintf(
                'تم مسح بيانات الموقع. أُبقي على حسابك (%s). جداول ممسوحة: %d · مستخدمون محذوفون: %d · أُعيد بذر الصلاحيات.',
                $user->email ?: $user->name,
                $result['tables_cleared'],
                $result['users_deleted']
            )
        );
    }
}
