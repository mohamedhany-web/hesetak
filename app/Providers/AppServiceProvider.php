<?php

namespace App\Providers;

use App\Services\AdminPanelBranding;
use App\Services\PublicFooterSettings;
use App\Support\AppTimezone;
use App\Support\ErrorPageContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** مسار صورة خلفية صفحات تسجيل الدخول/إنشاء الحساب في التخزين (نفس أسلوب مسارات التعلم) */
    public const AUTH_BACKGROUND_STORAGE_PATH = 'auth-pages/brainstorm-meeting.jpg';

    /** نسخة خفيفة للخلفية داخل public (تحميل سريع من نفس النطاق) */
    public const AUTH_BACKGROUND_PUBLIC_RELATIVE = 'images/auth-hero.jpg';

    /** مسار لوجو المنصة في التخزين (يُعرض من /storage/ مثل الكورسات والصور) */
    public const SITE_LOGO_STORAGE_PATH = 'site/logo.png';

    /**
     * رابط خلفية صفحات المصادقة: يفضّل ملفاً محلياً خفيفاً ثم بروكسي ثابت.
     */
    public static function authBackgroundUrl(): string
    {
        foreach ([self::AUTH_BACKGROUND_PUBLIC_RELATIVE, 'images/brainstorm-meeting.jpg'] as $relative) {
            $full = public_path($relative);
            if (is_file($full) && is_readable($full)) {
                $url = asset($relative);

                return $url.(str_contains($url, '?') ? '&' : '?').'v='.filemtime($full);
            }
        }

        $storagePath = self::AUTH_BACKGROUND_STORAGE_PATH;
        if (Storage::disk('public')->exists($storagePath) || Storage::disk('public')->exists('auth-pages/auth-hero.jpg')) {
            $path = Storage::disk('public')->exists('auth-pages/auth-hero.jpg')
                ? 'auth-pages/auth-hero.jpg'
                : $storagePath;
            $stable = storage_public_url_stable($path);
            if (is_string($stable) && $stable !== '') {
                return $stable;
            }
        }

        return asset(self::AUTH_BACKGROUND_PUBLIC_RELATIVE);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         | مهم: وسيط throttle الافتراضي (مثل throttle:90,1) يستخدم نفس مفتاح العداد لكل المسارات
         | للمستخدم المسجّل (معرّف المستخدم فقط). طلبات poll الإشعارات كل 5 ثوانٍ تملأ ذلك العداد
         | فتُرفض مسارات أخرى ذات حد أقل (مثل throttle:10,1) وتظهر 429 بلا سبب واضح.
         | نفصل poll الشريط عن باقي الحدود بمحددات مسمّاة.
         */
        RateLimiter::for('admin-nav-poll', function (Request $request) {
            $id = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)->by('admin-nav-poll:'.$id);
        });

        RateLimiter::for('employee-nav-poll', function (Request $request) {
            $id = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(120)->by('employee-nav-poll:'.$id);
        });

        RateLimiter::for('admin-employee-notification-store', function (Request $request) {
            $id = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute(30)->by('admin-employee-notification-store:'.$id);
        });

        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinutes(15, 20)->by('auth-login:ip:'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by('auth-register:ip:'.$request->ip());
        });

        RateLimiter::for('auth-password-reset-request', function (Request $request) {
            $ip = (string) $request->ip();
            $email = strtolower(trim((string) $request->input('email', '')));

            $limits = [
                Limit::perMinute(6)->by('auth-password-reset-request:ip:'.$ip),
            ];

            if ($email !== '') {
                $limits[] = Limit::perHour(6)->by('auth-password-reset-request:email:'.$email);
            }

            return $limits;
        });

        RateLimiter::for('auth-password-reset-submit', function (Request $request) {
            return Limit::perMinute(10)->by('auth-password-reset-submit:ip:'.$request->ip());
        });

        // تحميل دوال المساعدة (تُحمّل من هنا لضمان توفرها حتى قبل composer dump-autoload)
        $filesystemHelper = app_path('Helpers/FilesystemHelper.php');
        if (file_exists($filesystemHelper)) {
            require_once $filesystemHelper;
        }
        $studentUiHelper = app_path('Helpers/StudentUiHelper.php');
        if (file_exists($studentUiHelper)) {
            require_once $studentUiHelper;
        }

        // ضمان وجود صورة الخلفية في التخزين (نفس مسار صور المسارات) لتعمل على السيرفر عبر /storage/
        $authStoragePath = self::AUTH_BACKGROUND_STORAGE_PATH;
        $disk = Storage::disk('public');
        if (! $disk->exists($authStoragePath) && ! $disk->exists('auth-pages/auth-hero.jpg')) {
            $sources = [self::AUTH_BACKGROUND_PUBLIC_RELATIVE, 'images/brainstorm-meeting.jpg', 'images/brainstorm-meeting.png'];
            foreach ($sources as $source) {
                $publicPath = public_path($source);
                if (File::isFile($publicPath)) {
                    $dir = dirname($authStoragePath);
                    if (! $disk->exists($dir)) {
                        $disk->makeDirectory($dir);
                    }
                    $disk->put($authStoragePath, File::get($publicPath));
                    break;
                }
            }
        }

        // صورة خلفية صفحات تسجيل الدخول وإنشاء الحساب: محلي خفيف أولاً (نفس نطاق الطلب)
        View::composer(['auth.login', 'auth.register', 'auth.forgot-password', 'auth.two-factor.challenge', 'layouts.auth-atheer'], function ($view) {
            $view->with('authBackgroundUrl', self::authBackgroundUrl());
            $logo = AdminPanelBranding::logoPublicUrl();
            // تجنّب روابط R2 الموقّعة الثقيلة على صفحات المصادقة إن وُجد بروكسي ثابت
            if (is_string($logo) && str_contains($logo, 'cloudflarestorage.com')) {
                $path = \App\Models\Setting::getValue(AdminPanelBranding::SETTING_KEY)
                    ?: self::SITE_LOGO_STORAGE_PATH;
                if (is_string($path) && $path !== '') {
                    $stable = storage_public_url_stable($path);
                    if (is_string($stable) && $stable !== '') {
                        $logo = $stable;
                    }
                }
            }
            $view->with('adminPanelLogoUrl', $logo);
        });

        // لوجو المنصة: نسخ إلى التخزين إن لم يكن موجوداً (نفس أسلوب صورة تسجيل الدخول)
        $logoPath = self::SITE_LOGO_STORAGE_PATH;
        if (! $disk->exists($logoPath)) {
            $logoSources = [
                public_path('img/brand/hesetak-logo.png'),
                public_path('img/brand/hesetak-mark.png'),
            ];
            foreach ($logoSources as $logoSource) {
                if (! File::isFile($logoSource)) {
                    continue;
                }
                $dir = dirname($logoPath);
                if (! $disk->exists($dir)) {
                    $disk->makeDirectory($dir);
                }
                $disk->put($logoPath, File::get($logoSource));
                break;
            }
        }
        // حساب رابط اللوجو عند عرض الصفحة (مثل authBackgroundUrl) لضمان ظهور الصورة مع الطلب الحالي
        View::composer(['layouts.instructor-sidebar', 'layouts.student-sidebar', 'layouts.app', 'layouts.admin'], function ($view) use ($disk, $logoPath) {
            $url = AdminPanelBranding::logoPublicUrl() ?: AdminPanelBranding::inlineFallbackDataUri();
            $view->with('platformLogoUrl', $url);
        });

        // إجبار روابط الموقع على HTTPS في الإنتاج (حل مشكلة عدم ظهور الصور عند Mixed Content)
        if ($this->app->environment('production') && config('app.url')) {
            URL::forceScheme('https');
            $publicUrl = config('filesystems.disks.public.url');
            if ($publicUrl && str_starts_with($publicUrl, 'http://')) {
                config(['filesystems.disks.public.url' => 'https://'.substr($publicUrl, 7)]);
            }
        }

        /*
         | مهم (محلي/تطوير): إذا كان APP_URL يختلف عن Host الحقيقي في المتصفح (مثلاً localhost مقابل 127.0.0.1)
         | فقد لا يُعاد إرسال كوكي الجلسة بشكل صحيح في بعض الحالات ويظهر 419 عند إرسال النماذج.
         | نُجبر Laravel على استخدام نفس أصل الطلب الحالي في بيئة التطوير فقط.
         */
        View::composer('*', function ($view): void {
            if (app()->runningInConsole()) {
                return;
            }
            $view->with('storageBaseUrl', storage_base_url());
        });

        // Observers للنماذج - مع تحسينات الأداء
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\StudentCourseEnrollment::observe(\App\Observers\EnrollmentObserver::class);
        \App\Models\Exam::observe(\App\Observers\ExamObserver::class);
        \App\Models\AdvancedCourse::observe(\App\Observers\AdvancedCourseObserver::class);
        \App\Models\ExamAttempt::observe(\App\Observers\ExamAttemptObserver::class);

        // Observers للتقويم والإشعارات
        \App\Models\Lecture::observe(\App\Observers\LectureObserver::class);
        \App\Models\Assignment::observe(\App\Observers\AssignmentObserver::class);
        \App\Models\LectureAssignment::observe(\App\Observers\LectureAssignmentObserver::class);
        \App\Models\StudentInstructorAssignment::observe(\App\Observers\StudentInstructorAssignmentObserver::class);

        // تفعيل Event Listeners لتسجيل النشاطات
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\LogLoginActivity::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            \App\Listeners\LogLogoutActivity::class
        );

        // Security Event Listeners
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            [\App\Listeners\SecurityEventListener::class, 'handleFailedLogin']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Login::class,
            [\App\Listeners\SecurityEventListener::class, 'handleSuccessfulLogin']
        );

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            [\App\Listeners\SecurityEventListener::class, 'handleLogout']
        );

        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ? true : null;
            }
        });

        View::composer(
            [
                'welcome',
                'public.services.index',
                'public.services.show',
                'public.pricing',
            ],
            function ($view) {
                $view->with('publicFooter', PublicFooterSettings::payload());
            }
        );

        View::composer(['layouts.admin', 'layouts.employee'], function ($view) {
            $view->with('adminPanelLogoUrl', AdminPanelBranding::logoPublicUrl());
        });

        // تحميل أدوار الموظف وصلاحياتها مرة واحدة لعرض السايدبار (موظف + أدمن) بشكل موثوق بعد تعديل الدور
        View::composer(['layouts.admin', 'layouts.employee'], function () {
            if (Auth::check()) {
                Auth::user()->loadMissing(['roles.permissions']);
            }
        });

        View::composer('errors.*', function ($view) {
            $view->with([
                'errorHomeUrl' => ErrorPageContext::homeUrl(),
                'errorHomeLabel' => ErrorPageContext::homeLabel(),
            ]);
        });

        View::composer(['layouts.student-dashboard', 'layouts.student-timeline', 'layouts.auth-atheer', 'auth.register', 'auth.login', 'layouts.app', 'layouts.admin'], function ($view) {
            $view->with([
                'viewerTimezone' => AppTimezone::forUser(Auth::user()),
                'academyTimezone' => AppTimezone::academy(),
                'timezoneSyncUrl' => \Illuminate\Support\Facades\Route::has('account.timezone.sync')
                    ? route('account.timezone.sync')
                    : null,
            ]);
        });

        Blade::directive('appdatetime', function ($expression) {
            return "<?php echo \\App\\Support\\AppTimezone::labelHtml($expression, \\App\\Support\\AppTimezone::forUser(auth()->user()), app()->getLocale()); ?>";
        });
    }
}
