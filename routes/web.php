<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storage files (صور وملفات) - يجب أن يكون أول Route لضمان عدم اعتراضه
| يعمل عند عدم وجود symlink public/storage على الاستضافة
|--------------------------------------------------------------------------
*/
Route::get('/media/{path}', [\App\Http\Controllers\StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.media')
    ->middleware('web');

Route::get('/storage/{path}', [\App\Http\Controllers\StorageFileController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.file')
    ->middleware('web');

/*
|--------------------------------------------------------------------------
| Instructor panel CSS — عبر Laravel عند فشل خدمة public/css على الاستضافة
|--------------------------------------------------------------------------
*/
Route::get('/__glottical/instructor-panel.css', function () {
    $path = public_path('css/instructor-panel.css');
    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Content-Type' => 'text/css; charset=UTF-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('assets.instructor-panel.css');

Route::post('/webhooks/fawaterak', [\App\Http\Controllers\Webhooks\FawaterakWebhookController::class, 'handle'])
    ->name('webhooks.fawaterak')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::post('/webhooks/paypal', [\App\Http\Controllers\Webhooks\PayPalWebhookController::class, 'handle'])
    ->name('webhooks.paypal')
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| حصتك Whiteboard (أصول اللوحة) — تمرير عبر Laravel
| يعمل عندما لا يُخدم public/vendor مباشرة (جذر الموقع ليس public أو قواعد .htaccess)
|--------------------------------------------------------------------------
*/
Route::get('/mx-vendor/excalidraw/{path}', function (string $path) {
    $path = rawurldecode($path);
    $path = str_replace('..', '', $path);
    $path = ltrim(str_replace('\\', '/', $path), '/');

    $basePath = public_path('vendor/excalidraw');
    $filePath = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

    if (! @file_exists($filePath) || ! @is_file($filePath)) {
        abort(404, 'Whiteboard asset not found');
    }

    $realPath = @realpath($filePath) ?: $filePath;
    $allowedPath = @realpath($basePath) ?: $basePath;
    $normalizedRealPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath);
    $normalizedAllowedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedPath);

    if ($allowedPath === '' || strpos($normalizedRealPath, $normalizedAllowedPath) !== 0) {
        abort(404, 'Access denied');
    }

    if (! @is_readable($realPath)) {
        abort(403, 'File not readable');
    }

    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    // أولوية للامتداد: على Windows/بعض الاستضافات يعيد mime_content_type لـ .js قيمة text/plain
    // ومع X-Content-Type-Options: nosniff لا يُنفَّذ السكربت ويبقى مكتبة اللوحة غير معرّفة.
    $mimeType = match ($extension) {
        'js', 'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
        'woff2' => 'font/woff2',
        'woff' => 'font/woff',
        default => null,
    };
    if ($mimeType === null) {
        $mimeType = @mime_content_type($realPath) ?: 'application/octet-stream';
    }

    return response()->file($realPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('mx.vendor.excalidraw')->middleware('web');

// Sitemap Route — حصتك SEO
Route::get('/sitemap.xml', function () {
    $xmlEscape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $urls = [];

    // الصفحة الرئيسية
    $urls[] = ['loc' => url('/'), 'lastmod' => now()->toDateString(), 'changefreq' => 'daily', 'priority' => '1.0'];

    // الصفحات العامة الثابتة — خريطة حصتك الحالية
    $staticPages = [
        ['url' => '/instructors',    'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => '/curricula',      'priority' => '0.9', 'changefreq' => 'weekly'],
        ['url' => '/courses',        'priority' => '0.9', 'changefreq' => 'daily'],
        ['url' => '/pricing',        'priority' => '0.85', 'changefreq' => 'weekly'],
        ['url' => '/about',          'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => '/how-it-works',   'priority' => '0.75', 'changefreq' => 'monthly'],
        ['url' => '/for-students',   'priority' => '0.7', 'changefreq' => 'monthly'],
        ['url' => '/for-teachers',   'priority' => '0.7', 'changefreq' => 'monthly'],
        ['url' => '/faq',            'priority' => '0.7', 'changefreq' => 'monthly'],
        ['url' => '/contact',        'priority' => '0.7', 'changefreq' => 'monthly'],
        ['url' => '/tutor/apply',    'priority' => '0.65', 'changefreq' => 'monthly'],
        ['url' => '/certificates/verify', 'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => '/help',           'priority' => '0.5', 'changefreq' => 'monthly'],
        ['url' => '/terms',          'priority' => '0.4', 'changefreq' => 'yearly'],
        ['url' => '/privacy',        'priority' => '0.4', 'changefreq' => 'yearly'],
        ['url' => '/refund',         'priority' => '0.4', 'changefreq' => 'yearly'],
    ];

    foreach ($staticPages as $page) {
        $urls[] = [
            'loc' => url($page['url']),
            'lastmod' => now()->toDateString(),
            'changefreq' => $page['changefreq'],
            'priority' => $page['priority'],
        ];
    }

    // الكورسات النشطة مع صورة (Image Sitemap)
    try {
        $courses = \App\Models\AdvancedCourse::where('is_active', true)
            ->select('id', 'title', 'thumbnail', 'description', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($courses as $course) {
            $entry = [
                'loc' => url('/course/'.$course->id),
                'lastmod' => optional($course->updated_at)->format('Y-m-d') ?: now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
            if ($course->thumbnail) {
                $entry['image_loc'] = storage_asset(str_replace('\\', '/', $course->thumbnail));
                $entry['image_title'] = $course->title ?? '';
                $entry['image_caption'] = \Illuminate\Support\Str::limit(strip_tags($course->description ?? ''), 100);
            }
            $urls[] = $entry;
        }
    } catch (\Exception $e) {
    }

    // المدربون النشطون مع صورة
    try {
        $instructors = \App\Models\User::whereIn('role', ['instructor', 'teacher'])
            ->where('is_active', true)
            ->select('id', 'uuid', 'name', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get();

        foreach ($instructors as $instructor) {
            $urls[] = [
                'loc' => route('public.instructors.show', $instructor),
                'lastmod' => optional($instructor->updated_at)->format('Y-m-d') ?: now()->toDateString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }
    } catch (\Exception $e) {
    }

    // معرض الوسائط القديم غير مُدرَج في الـ sitemap

    // بناء XML مع دعم Image Sitemap
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'.PHP_EOL;
    $sitemap .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.PHP_EOL;

    foreach ($urls as $entry) {
        $sitemap .= '  <url>'.PHP_EOL;
        $sitemap .= '    <loc>'.$xmlEscape($entry['loc']).'</loc>'.PHP_EOL;
        $sitemap .= '    <lastmod>'.$xmlEscape($entry['lastmod']).'</lastmod>'.PHP_EOL;
        $sitemap .= '    <changefreq>'.$xmlEscape($entry['changefreq']).'</changefreq>'.PHP_EOL;
        $sitemap .= '    <priority>'.$xmlEscape($entry['priority']).'</priority>'.PHP_EOL;
        if (! empty($entry['image_loc'])) {
            $sitemap .= '    <image:image>'.PHP_EOL;
            $sitemap .= '      <image:loc>'.$xmlEscape($entry['image_loc']).'</image:loc>'.PHP_EOL;
            if (! empty($entry['image_title'])) {
                $sitemap .= '      <image:title>'.$xmlEscape($entry['image_title']).'</image:title>'.PHP_EOL;
            }
            if (! empty($entry['image_caption'])) {
                $sitemap .= '      <image:caption>'.$xmlEscape($entry['image_caption']).'</image:caption>'.PHP_EOL;
            }
            $sitemap .= '    </image:image>'.PHP_EOL;
        }
        $sitemap .= '  </url>'.PHP_EOL;
    }
    $sitemap .= '</urlset>';

    return response($sitemap, 200)
        ->header('Content-Type', 'application/xml')
        ->header('Cache-Control', 'public, max-age=3600');
})->name('sitemap');

// الصفحة الرئيسية (Home) - الترجمة عبر SetLocale في مجموعة web
Route::get('/', [\App\Http\Controllers\Public\LandingController::class, 'index'])->name('home');

// خدمة أصول أثير عبر Laravel عندما تفشل الملفات الثابتة من /public على الاستضافة
$serveAtheerAsset = static function (array $candidates, string $contentType) {
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return response((string) file_get_contents($path), 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
};

Route::get('/assets/atheer.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [resource_path('css/atheer.css'), public_path('css/atheer.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.atheer.css');

Route::get('/css/atheer.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [resource_path('css/atheer.css'), public_path('css/atheer.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.atheer.css.public');

Route::get('/css/admin-atheer.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [resource_path('css/admin-atheer.css'), public_path('css/admin-atheer.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.admin-atheer.css');

Route::get('/js/atheer-tailwind-config.js', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [resource_path('js/atheer-tailwind-config.js'), public_path('js/atheer-tailwind-config.js')],
        'application/javascript; charset=UTF-8'
    );
})->name('assets.atheer.tailwind');

// أصول اللاندنج عبر Laravel — على الاستضافة الحالية /css/landing/* و /js/landing/* و /img/* ترجع 404 كملفات ثابتة
Route::get('/css/landing/{sheet}.css', function (string $sheet) use ($serveAtheerAsset) {
    $sheet = basename($sheet);
    if (! preg_match('/^[A-Za-z0-9\-]+$/', $sheet)) {
        abort(404);
    }

    // public أولاً (النسخة المحدّثة)، ثم resources كنسخة احتياطية
    return $serveAtheerAsset(
        [public_path("css/landing/{$sheet}.css"), resource_path("css/landing/{$sheet}.css")],
        'text/css; charset=UTF-8'
    );
})->where('sheet', '[A-Za-z0-9\-]+')->name('assets.landing.css');

// Student timeline — public أولاً إن وُجد، وإلا resources (يُفضّل مزامنة public عند النشر)
Route::get('/css/student-timeline.css', function () use ($serveAtheerAsset) {
    $public = public_path('css/student-timeline.css');
    $resource = resource_path('css/student-timeline.css');
    $candidates = [];
    if (is_file($public) && is_file($resource)) {
        // اختر الأحدث تعديلاً حتى لا تُخدم نسخة public قديمة بدون أنماط المكتبة
        $candidates = filemtime($public) >= filemtime($resource)
            ? [$public, $resource]
            : [$resource, $public];
    } else {
        $candidates = [$public, $resource];
    }

    return $serveAtheerAsset($candidates, 'text/css; charset=UTF-8');
})->name('assets.student-timeline.css');

// Instructor panel CSS — نفس مشكلة Hostinger: /css/* الثابت خارج document root
Route::get('/css/instructor-dashboard.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [public_path('css/instructor-dashboard.css'), resource_path('css/instructor-dashboard.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.instructor-dashboard.css');

Route::get('/css/instructor-panel.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [public_path('css/instructor-panel.css'), resource_path('css/instructor-panel.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.instructor-panel.css.public');

Route::get('/css/hesetak-live-meeting.css', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [public_path('css/hesetak-live-meeting.css')],
        'text/css; charset=UTF-8'
    );
})->name('assets.hesetak-live-meeting.css');

Route::get('/js/platform-protection.js', function () use ($serveAtheerAsset) {
    return $serveAtheerAsset(
        [public_path('js/platform-protection.js'), resource_path('js/platform-protection.js')],
        'application/javascript; charset=UTF-8'
    );
})->name('assets.platform-protection.js');

Route::get('/img/student-timeline/{file}', function (string $file) {
    $file = basename($file);
    if (! preg_match('/^[A-Za-z0-9._\-]+$/', $file) || ! preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $file)) {
        abort(404);
    }

    $path = public_path("img/student-timeline/{$file}");
    if (! is_file($path)) {
        abort(404);
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    return response((string) file_get_contents($path), 200, [
        'Content-Type' => $types[$ext] ?? 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('file', '[A-Za-z0-9._\-]+')->name('assets.student-timeline.img');

Route::get('/img/student-timeline/nav/{file}', function (string $file) {
    $file = basename($file);
    if (! preg_match('/^[A-Za-z0-9._\-]+$/', $file) || ! preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $file)) {
        abort(404);
    }

    $path = public_path("img/student-timeline/nav/{$file}");
    if (! is_file($path)) {
        abort(404);
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    return response((string) file_get_contents($path), 200, [
        'Content-Type' => $types[$ext] ?? 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('file', '[A-Za-z0-9._\-]+')->name('assets.student-timeline.nav');

Route::get('/js/landing/{file}.js', function (string $file) use ($serveAtheerAsset) {
    $file = basename($file);
    if (! preg_match('/^[A-Za-z0-9\-]+$/', $file)) {
        abort(404);
    }

    return $serveAtheerAsset(
        [public_path("js/landing/{$file}.js"), resource_path("js/landing/{$file}.js")],
        'application/javascript; charset=UTF-8'
    );
})->where('file', '[A-Za-z0-9\-]+')->name('assets.landing.js');

Route::get('/img/{folder}/{file}', [\App\Http\Controllers\PublicImgController::class, 'show'])
    ->where(['folder' => 'brand|mycourses|lasles|glottical|sanua|student-timeline', 'file' => '[A-Za-z0-9._\-]+'])
    ->name('assets.landing.img');

/*
| مسار صور مضمون عبر Laravel فقط — على Hostinger /img/* غالباً يُعالج كملف ثابت من
| document root خاطئ فيرجع 404 قبل الوصول لـ index.php (عكس /css/landing/*).
*/
Route::get('/__hesetak/img/{folder}/{file}', [\App\Http\Controllers\PublicImgController::class, 'show'])
    ->where(['folder' => 'brand|mycourses|lasles|glottical|sanua|student-timeline', 'file' => '[A-Za-z0-9._\-]+'])
    ->name('assets.hesetak.img');

// صور لوحة المدرب وغيرها تحت /images/* (نفس مشكلة الاستضافة مع الملفات الثابتة)
Route::get('/images/{path}', function (string $path) {
    $path = str_replace('\\', '/', $path);
    if (str_contains($path, '..') || ! preg_match('/^[A-Za-z0-9._\-\/]+$/', $path)) {
        abort(404);
    }
    if (! preg_match('/\.(png|jpe?g|webp|gif|svg)$/i', $path)) {
        abort(404);
    }

    $full = public_path('images/'.$path);
    $realPublic = realpath(public_path('images'));
    $realFile = realpath($full);
    if ($realPublic === false || $realFile === false || ! str_starts_with($realFile, $realPublic) || ! is_file($realFile)) {
        abort(404);
    }

    $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
    $types = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    return response((string) file_get_contents($realFile), 200, [
        'Content-Type' => $types[$ext] ?? 'application/octet-stream',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*')->name('assets.public.images');

Route::get('/free-trial/slots', [\App\Http\Controllers\Public\FreeTrialBookingController::class, 'slots'])->name('public.free-trial.slots');
Route::post('/free-trial/book', [\App\Http\Controllers\Public\FreeTrialBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.free-trial.book');

// الصفحات العامة
Route::get('/path', [\App\Http\Controllers\Public\PageController::class, 'path'])->name('public.path');
Route::get('/for-students', [\App\Http\Controllers\Public\PageController::class, 'forStudents'])->name('public.for-students');
Route::get('/for-teachers', [\App\Http\Controllers\Public\PageController::class, 'forTeachers'])->name('public.for-teachers');
Route::get('/how-it-works', [\App\Http\Controllers\Public\PageController::class, 'how'])->name('public.how');
Route::redirect('/how', '/how-it-works', 301);
Route::get('/curricula', [\App\Http\Controllers\Public\CurriculaController::class, 'index'])->name('public.curricula');
Route::get('/curricula/{year}', [\App\Http\Controllers\Public\CurriculaController::class, 'show'])->name('public.curricula.show');

// حصتك expandable public IA (config/tadris_nav.php)
foreach (\App\Support\TadrisPublicNav::routable() as $key => $node) {
    Route::get($node['uri'], [\App\Http\Controllers\Public\SitePageController::class, 'show'])
        ->defaults('page', $key)
        ->name($node['route']);
}
Route::get('/about', [\App\Http\Controllers\Public\SitePageController::class, 'show'])
    ->defaults('page', 'about')
    ->name('public.about');
Route::get('/faq', [\App\Http\Controllers\Public\PageController::class, 'faq'])->name('public.faq');
Route::get('/terms', [\App\Http\Controllers\Public\PageController::class, 'terms'])->name('public.terms');
Route::get('/privacy', [\App\Http\Controllers\Public\PageController::class, 'privacy'])->name('public.privacy');
Route::get('/pricing', [\App\Http\Controllers\Public\PageController::class, 'pricing'])->name('public.pricing');
Route::get('/api/public/package-catalog', [\App\Http\Controllers\Public\PackageCatalogApiController::class, '__invoke'])
    ->middleware('throttle:60,1')
    ->name('public.package-catalog');
Route::get('/gift-package', [\App\Http\Controllers\Public\GiftPackageController::class, 'show'])->name('public.gift-package.show');
Route::post('/gift-package', [\App\Http\Controllers\Public\GiftPackageController::class, 'store'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.gift-package.store');
Route::get('/gift-package/orders/{order}/pay', [\App\Http\Controllers\Public\GiftPackageController::class, 'pay'])
    ->middleware('auth')
    ->name('public.gift-package.pay');
Route::post('/gift-package/orders/{order}/fawaterak/prepare', [\App\Http\Controllers\Public\GiftPackageController::class, 'fawaterakPrepare'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.gift-package.fawaterak.prepare');
Route::get('/gift/{token}', [\App\Http\Controllers\Public\GiftPackageController::class, 'claim'])
    ->where('token', '[A-Za-z0-9]{32,64}')
    ->name('public.gift-package.claim');
Route::get('/team', [\App\Http\Controllers\Public\PageController::class, 'team'])->name('public.team');
Route::get('/certificates', [\App\Http\Controllers\Public\PageController::class, 'certificates'])->name('public.certificates');
Route::get('/certificates/verify', [\App\Http\Controllers\Public\CertificateVerificationController::class, 'verify'])->name('public.certificates.verify');
Route::get('/certificates/verify/{code}', [\App\Http\Controllers\Public\CertificateVerificationController::class, 'verify'])->name('public.certificates.verify.code');
Route::get('/help', [\App\Http\Controllers\Public\PageController::class, 'help'])->name('public.help');
Route::get('/refund', [\App\Http\Controllers\Public\PageController::class, 'refund'])->name('public.refund');
Route::get('/testimonials', [\App\Http\Controllers\Public\PageController::class, 'testimonials'])->name('public.testimonials');
Route::get('/events', [\App\Http\Controllers\Public\PageController::class, 'events'])->name('public.events');
Route::get('/partners', [\App\Http\Controllers\Public\PageController::class, 'partners'])->name('public.partners');

// صفحة الخدمات (محتوى من لوحة الإدارة)
Route::get('/services', [\App\Http\Controllers\Public\SiteServiceController::class, 'index'])->name('public.services.index');
Route::get('/services/{siteService}', [\App\Http\Controllers\Public\SiteServiceController::class, 'show'])->name('public.services.show');

// تم إيقاف مجتمع البيانات والذكاء الاصطناعي (مسابقات، داتاسيت، مجتمع) بالكامل، لذا أزيلت جميع مساراته.

// فصول حصتك — دخول الضيوف برابط/كود (مقفول للحصص الخاصة)
Route::get('/classroom/join/{code}', [\App\Http\Controllers\ClassroomJoinController::class, 'show'])->name('classroom.join')->where('code', '[A-Za-z0-9]+');
Route::post('/classroom/join/{code}/enter', [\App\Http\Controllers\ClassroomJoinController::class, 'enter'])->name('classroom.join.enter')->where('code', '[A-Za-z0-9]+');
Route::post('/classroom/join/{code}/heartbeat', [\App\Http\Controllers\ClassroomJoinController::class, 'heartbeat'])->name('classroom.join.heartbeat')->where('code', '[A-Za-z0-9]+');
Route::post('/classroom/join/{code}/leave', [\App\Http\Controllers\ClassroomJoinController::class, 'leave'])->name('classroom.join.leave')->where('code', '[A-Za-z0-9]+');
Route::post('/classroom/join/{code}/share-annotation', [\App\Http\Controllers\ClassroomJoinController::class, 'pushShareAnnotation'])
    ->middleware('throttle:90,1')
    ->name('classroom.join.share-annotation')
    ->where('code', '[A-Za-z0-9]+');
Route::get('/classroom/join/{code}/curriculum/state', [\App\Http\Controllers\ClassroomJoinController::class, 'curriculumState'])
    ->middleware('throttle:120,1')
    ->name('classroom.join.curriculum.state')
    ->where('code', '[A-Za-z0-9]+');
Route::get('/classroom/join/{code}/curriculum/{sessionId}/slide/{slide}', [\App\Http\Controllers\ClassroomJoinController::class, 'curriculumSlide'])
    ->middleware('throttle:180,1')
    ->whereNumber('slide')
    ->name('classroom.join.curriculum.slide')
    ->where('code', '[A-Za-z0-9]+');
Route::get('/classroom/join/{code}/curriculum/{sessionId}/thumb/{slide}', [\App\Http\Controllers\ClassroomJoinController::class, 'curriculumThumb'])
    ->middleware('throttle:180,1')
    ->whereNumber('slide')
    ->name('classroom.join.curriculum.thumb')
    ->where('code', '[A-Za-z0-9]+');

// دخول آمن من المنصة (طالب/معلم مصرّح فقط)
Route::middleware(['auth'])->get('/classroom/enter/{meeting}', [\App\Http\Controllers\Student\ClassroomController::class, 'secureEnter'])
    ->name('classroom.secure-enter');

// التواصل
Route::get('/contact', [\App\Http\Controllers\Public\SitePageController::class, 'contact'])->name('public.contact');
Route::post('/contact', [\App\Http\Controllers\Public\SitePageController::class, 'contactStore'])
    ->middleware('throttle:10,1')
    ->name('public.contact.store');

// متابعة ولي الأمر — تقارير الطالب برقم الدخول
Route::get('/parent-progress', [\App\Http\Controllers\Public\ParentProgressController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('public.parent-progress');

// معرض الصور والفيديوهات
Route::get('/media', [\App\Http\Controllers\Public\MediaController::class, 'index'])->name('public.media.index');
Route::get('/media/{media}', [\App\Http\Controllers\Public\MediaController::class, 'show'])
    ->whereNumber('media')
    ->name('public.media.show');

// صفحة التصنيفات العامة (من course_categories في الموقع)
Route::get('/categories', [\App\Http\Controllers\Public\CategoriesController::class, 'index'])->name('public.categories');

// مجموعات التدريس الجماعي أُزيلت — تحويل دائم للأسطح الحالية
Route::permanentRedirect('/groups', '/instructors');
Route::permanentRedirect('/groups/{any}', '/instructors')->where('any', '.*');
Route::permanentRedirect('/school/{slug}', '/curricula');
Route::get('/service-packages', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'index'])->name('public.service-packages.index');
Route::get('/service-packages/custom/quote', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'customQuote'])
    ->middleware('throttle:60,1')
    ->name('public.service-packages.custom.quote');
Route::post('/service-packages/custom/checkout', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'storeCustom'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.service-packages.custom.store');
Route::get('/service-packages/custom/orders/{order}/pay', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'customPay'])
    ->middleware('auth')
    ->name('public.service-packages.custom.pay');
Route::post('/service-packages/custom/orders/{order}/fawaterak/prepare', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPrepareCustom'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.service-packages.custom.fawaterak.prepare');
Route::get('/service-packages/custom/orders/{order}/fawaterak/methods', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPaymentMethodsCustom'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('public.service-packages.custom.fawaterak.methods');
Route::post('/service-packages/custom/orders/{order}/fawaterak/pay', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPayCustom'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.service-packages.custom.fawaterak.pay');
Route::get('/service-packages/{servicePackage}/checkout', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'checkout'])->name('public.service-packages.checkout');
Route::post('/service-packages/{servicePackage}/checkout', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'store'])->middleware('auth')->name('public.service-packages.store');
Route::post('/service-packages/{servicePackage}/checkout/fawaterak/prepare', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPrepare'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.service-packages.fawaterak.prepare');
Route::get('/service-packages/{servicePackage}/checkout/fawaterak/methods', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPaymentMethods'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('public.service-packages.fawaterak.methods');
Route::post('/service-packages/{servicePackage}/checkout/fawaterak/pay', [\App\Http\Controllers\Public\ServicePackageCheckoutController::class, 'fawaterakPay'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.service-packages.fawaterak.pay');
Route::post('/service-packages/{servicePackage}/checkout/paypal', [\App\Http\Controllers\Public\PayPalCheckoutController::class, 'startPackage'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.service-packages.paypal');
Route::post('/service-packages/custom/orders/{order}/paypal', [\App\Http\Controllers\Public\PayPalCheckoutController::class, 'startExistingOrder'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.service-packages.custom.paypal');
Route::redirect('/teachers', '/instructors', 301);
Route::get('/teachers/{instructor}', function (\App\Models\User $instructor) {
    return redirect()->route('public.instructors.show', $instructor, 301);
})->name('public.teachers.show');

// صفحة الكورسات العامة (?subject=id & ?delivery=one_to_one|group)
Route::get('/courses', [\App\Http\Controllers\Public\CoursesController::class, 'index'])->name('public.courses');

// مسارات المنتج: كورسات مسجّلة + كتب للقراءة (upsell)
Route::get('/recorded-courses', [\App\Http\Controllers\Public\ProductTrackCatalogController::class, 'recorded'])
    ->name('public.recorded-courses');
Route::get('/books', [\App\Http\Controllers\Public\ProductTrackCatalogController::class, 'books'])
    ->name('public.books');

// صفحة المدربين (الملفات التعريفية المعتمدة)
Route::get('/instructors', [\App\Http\Controllers\Public\InstructorController::class, 'index'])->name('public.instructors.index');
Route::get('/instructors/{instructor}', [\App\Http\Controllers\Public\InstructorController::class, 'show'])->name('public.instructors.show');
Route::get('/tutor/apply', [\App\Http\Controllers\Public\TutorApplyController::class, 'create'])->name('public.tutor.apply');
Route::post('/tutor/apply/register', [\App\Http\Controllers\Public\TutorApplyController::class, 'register'])->name('public.tutor.apply.register');
Route::get('/tutor/apply/profile', [\App\Http\Controllers\Public\TutorApplyController::class, 'profile'])
    ->middleware(['auth', 'role:instructor|teacher'])
    ->name('public.tutor.apply.profile');
Route::post('/tutor/apply/profile', [\App\Http\Controllers\Public\TutorApplyController::class, 'storeProfile'])
    ->middleware(['auth', 'role:instructor|teacher'])
    ->name('public.tutor.apply.profile.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/tutor/interview', [\App\Http\Controllers\Public\TutorInterviewController::class, 'pick'])->name('tutor.interview.pick');
    Route::post('/tutor/interview/book', [\App\Http\Controllers\Public\TutorInterviewController::class, 'book'])->name('tutor.interview.book');
    Route::get('/tutor/interview/{interview}/join', [\App\Http\Controllers\Public\TutorInterviewController::class, 'join'])->name('tutor.interview.join');
});
Route::get('/tutor/contract/{token}', [\App\Http\Controllers\Public\TutorContractController::class, 'show'])->name('tutor.contract.sign');
Route::post('/tutor/contract/{token}', [\App\Http\Controllers\Public\TutorContractController::class, 'sign'])->name('tutor.contract.sign.submit');
Route::get('/tutor/contract/{token}/pdf', [\App\Http\Controllers\Public\TutorContractController::class, 'downloadPdf'])->name('tutor.contract.pdf');


// صفحة تفاصيل الكورس العامة
Route::get('/course/{id}', [\App\Http\Controllers\Public\CourseShowController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('public.course.show');

// سكربت الدفع عبر نطاق الموقع — مسار محايد (قوائم الحجب تعرّف غالباً /fawaterk/)
Route::redirect('/fawaterk/plugin.min.js', '/js/checkout-pay-widget.v1.js', 301);
Route::get('/js/checkout-pay-widget.v1.js', \App\Http\Controllers\Public\FawaterkPluginController::class)
    ->middleware('throttle:240,1')
    ->withoutMiddleware([
        \App\Http\Middleware\CheckActiveStatus::class,
        \App\Http\Middleware\SetLocale::class,
    ])
    ->name('public.fawaterk.plugin');

// صفحة إتمام الطلب (Checkout)
Route::get('/course/{courseId}/checkout', [\App\Http\Controllers\Public\CheckoutController::class, 'show'])
    ->middleware('auth')
    ->name('public.course.checkout');

Route::post('/course/{courseId}/checkout/complete', [\App\Http\Controllers\Public\CheckoutController::class, 'complete'])
    ->middleware('auth')
    ->name('public.course.checkout.complete');

Route::post('/course/{courseId}/checkout/quote', [\App\Http\Controllers\Public\CheckoutController::class, 'quoteCourseCheckout'])
    ->middleware('auth')
    ->name('public.course.checkout.quote');

// التوجيه لبوابة الدفع كاشير (كورس)
Route::post('/course/{courseId}/checkout/kashier', [\App\Http\Controllers\Public\CheckoutController::class, 'redirectToKashier'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.course.checkout.kashier');

Route::post('/course/{courseId}/checkout/fawaterak/prepare', [\App\Http\Controllers\Public\CheckoutController::class, 'fawaterakPrepare'])
    ->middleware('auth')
    ->name('public.course.checkout.fawaterak.prepare');

Route::get('/course/{courseId}/checkout/fawaterak/methods', [\App\Http\Controllers\Public\CheckoutController::class, 'fawaterakPaymentMethods'])
    ->middleware('auth')
    ->name('public.course.checkout.fawaterak.methods');

Route::post('/course/{courseId}/checkout/fawaterak/pay', [\App\Http\Controllers\Public\CheckoutController::class, 'fawaterakPay'])
    ->middleware('auth')
    ->name('public.course.checkout.fawaterak.pay');

Route::post('/course/{courseId}/checkout/paypal', [\App\Http\Controllers\Public\PayPalCheckoutController::class, 'startCourse'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('public.course.checkout.paypal');

// تسجيل مجاني للكورسات المجانية
Route::post('/course/{courseId}/enroll-free', [\App\Http\Controllers\Public\CheckoutController::class, 'enrollFree'])
    ->middleware('auth')
    ->name('public.course.enroll.free');

Route::get('/checkout/kashier/callback', [\App\Http\Controllers\Public\CheckoutController::class, 'kashierCallback'])
    ->middleware('throttle:60,1')
    ->name('public.checkout.kashier.callback');

Route::get('/checkout/fawaterak/{status}', [\App\Http\Controllers\Public\CheckoutController::class, 'fawaterakReturn'])
    ->middleware('auth')
    ->where('status', 'success|fail|pending')
    ->name('public.checkout.fawaterak.return');

Route::get('/checkout/paypal/return', [\App\Http\Controllers\Public\PayPalCheckoutController::class, 'returnFromPaypal'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.checkout.paypal.return');
Route::get('/checkout/paypal/cancel', [\App\Http\Controllers\Public\PayPalCheckoutController::class, 'cancel'])
    ->middleware(['auth', 'throttle:30,1'])
    ->name('public.checkout.paypal.cancel');

// روابط قديمة لمنتج المسارات التعليمية (أُزيل) → الكورسات
Route::redirect('/learning-paths', '/courses', 301);
Route::get('/learning-path/{slug}', function () {
    return redirect()->route('public.courses', [], 301);
})->where('slug', '[a-z0-9-]+');

// صفحة تفاصيل الباقة (للتوافق مع الروابط القديمة)
Route::get('/package/{slug}', function ($slug) {
    $package = \App\Models\Package::where('slug', $slug)
        ->where('is_active', true)
        ->with(['courses' => function ($query) {
            $query->where('is_active', true)
                ->with(['academicSubject', 'academicYear'])
                ->withCount('lessons');
        }])
        ->firstOrFail();

    // باقات ذات صلة
    $relatedPackages = \App\Models\Package::where('is_active', true)
        ->where('id', '!=', $package->id)
        ->withCount('courses')
        ->limit(3)
        ->get();

    return view('package-show', compact('package', 'relatedPackages'));
})->name('public.package.show');

// مسارات المصادقة - محمية بحيث لا يمكن الوصول إليها إذا كان المستخدم مسجل دخول
Route::middleware(['guest', 'guest-only'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware(ThrottleRequests::using('auth-login'));
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware(ThrottleRequests::using('auth-register'));
    // نسيت كلمة المرور: طلب رابط إعادة التعيين + صفحة تعيين كلمة مرور جديدة
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware(ThrottleRequests::using('auth-password-reset-request'))->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->middleware(ThrottleRequests::using('auth-password-reset-submit'))->name('password.update');
    // نفس جلسة «ضيف» مثل الدخول حتى لا تُفقد بيانات خطوة 2FA بعد إعادة التوجيه
    Route::get('/2fa/challenge', [\App\Http\Controllers\Auth\TwoFactorController::class, 'showChallenge'])
        ->middleware('throttle:60,1')
        ->name('two-factor.challenge');
    Route::post('/2fa/verify', [\App\Http\Controllers\Auth\TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:30,1')
        ->name('two-factor.verify');

    // تسجيل الدخول / الربط عبر حساب Google (Gmail)
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])
        ->middleware('throttle:30,1')
        ->name('auth.google.callback');
});

// تسجيل الخروج - يجب أن يكون المستخدم مسجل دخول
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::post('/account/timezone', [\App\Http\Controllers\AccountTimezoneController::class, 'sync'])
    ->middleware('throttle:30,1')
    ->name('account.timezone.sync');

// إعداد المصادقة الثنائية (TOTP) — يظهر فمن يُشمَّل بـ requiresTwoFactor (حالياً أدمن عند تفعيل الإلزام)
Route::middleware(['auth'])->prefix('2fa')->name('two-factor.')->group(function () {
    Route::get('/setup', [\App\Http\Controllers\Auth\TwoFactorController::class, 'showSetup'])->name('setup');
    Route::post('/enable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'enable'])->name('enable');
    Route::post('/disable', [\App\Http\Controllers\Auth\TwoFactorController::class, 'disable'])->name('disable');
});

// =========================
// Public Webhooks / API callbacks (no auth, no CSRF)
// =========================
// ويب هوك تسجيل جلسات البث (Jibri يرفع إلى R2 ثم يستدعي هذا الرابط مع X-Webhook-Token)
Route::post('/api/live-recordings/register', [\App\Http\Controllers\Api\LiveRecordingWebhookController::class, 'register'])
    ->name('api.live-recordings.register');

// Callback من n8n لتحديث تقرير الجلسة (يتطلب X-N8N-Token)
Route::patch('/api/n8n/live-session-reports/{report}', [\App\Http\Controllers\Api\N8nLiveSessionReportController::class, 'update'])
    ->name('api.n8n.live-session-reports.update');
Route::post('/api/n8n/live-session-reports/{report}', [\App\Http\Controllers\Api\N8nLiveSessionReportController::class, 'update'])
    ->name('api.n8n.live-session-reports.update.post');

// Callback من n8n لتقرير اجتماع Classroom (يتطلب X-N8N-Token)
Route::patch('/api/n8n/classroom-meeting-reports/{report}', [\App\Http\Controllers\Api\N8nClassroomMeetingReportController::class, 'update'])
    ->name('api.n8n.classroom-meeting-reports.update');
Route::post('/api/n8n/classroom-meeting-reports/{report}', [\App\Http\Controllers\Api\N8nClassroomMeetingReportController::class, 'update'])
    ->name('api.n8n.classroom-meeting-reports.update.post');

// مسارات لوحة التحكم - محمية بالتأكد من تسجيل الدخول ومنع الجلسات المتزامنة
Route::middleware(['auth', 'prevent-concurrent'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // كتالوج الكورسات — مخفي افتراضياً (config/student_ui.php → show_courses)
    Route::get('/academic-years', function () {
        if (! student_ui('show_courses', false)) {
            return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
        }

        return app(\App\Http\Controllers\Student\AcademicYearController::class)->index();
    })->name('academic-years');
    Route::get('/academic-years/{academicYear}/subjects', function (\App\Models\AcademicYear $academicYear) {
        if (! student_ui('show_courses', false)) {
            return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
        }

        return app(\App\Http\Controllers\Student\AcademicYearController::class)->subjects($academicYear);
    })->name('academic-years.subjects');
    Route::get('/subjects/{academicSubject}/courses', function (\App\Models\AcademicSubject $academicSubject) {
        if (! student_ui('show_courses', false)) {
            return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
        }

        return app(\App\Http\Controllers\Student\SubjectController::class)->courses($academicSubject);
    })->name('subjects.courses');
    Route::get('/courses/{advancedCourse}', function (\App\Models\AdvancedCourse $advancedCourse) {
        if (! student_ui('show_courses', false)) {
            return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
        }

        return app(\App\Http\Controllers\Student\CourseController::class)->show($advancedCourse);
    })->name('courses.show');

    // كورساتي — قائمة الكورسات المستقلة المفعّلة للطالب
    Route::middleware(['role:student'])->group(function () {
        Route::get('/my-courses', function () {
            if (! student_ui('show_courses', false)) {
                return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
            }

            return app(\App\Http\Controllers\Student\MyCourseController::class)->index();
        })->name('my-courses.index');
        Route::get('/my-courses/{course}', function ($course) {
            if (! student_ui('show_courses', false)) {
                return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
            }

            return app(\App\Http\Controllers\Student\MyCourseController::class)->show($course);
        })->middleware(['ownership:course,course'])->name('my-courses.show');

        Route::get('/my-courses/{course}/learn', function ($course, \Illuminate\Http\Request $request) {
            if (! student_ui('show_courses', false)) {
                return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
            }

            return app(\App\Http\Controllers\Student\MyCourseController::class)->learn($course, $request);
        })->middleware(['ownership:course,course'])->name('my-courses.learn');
        Route::get('/my-courses/{course}/lectures/{lecture}', [\App\Http\Controllers\Student\MyCourseController::class, 'getLectureData'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lectures.show');
        Route::get('/my-courses/{course}/lectures/{lecture}/recording-stream', [\App\Http\Controllers\Student\MyCourseController::class, 'streamLectureRecording'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lectures.recording-stream');
        Route::get('/my-courses/{course}/lectures/{lecture}/materials/{material}/download', [\App\Http\Controllers\Student\MyCourseController::class, 'downloadLectureMaterial'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lectures.material.download');
        Route::post('/my-courses/{course}/lectures/{lecture}/video-questions/{videoQuestion}/answer', [\App\Http\Controllers\Student\MyCourseController::class, 'submitLectureVideoQuestionAnswer'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lectures.video-question.answer');
        Route::post('/my-courses/{course}/lectures/{lecture}/progress', [\App\Http\Controllers\Student\MyCourseController::class, 'updateLectureProgress'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lectures.progress');
        Route::get('/my-courses/{course}/lessons/{lesson}/watch', function ($course, $lesson) {
            if (! student_ui('show_courses', false)) {
                return redirect()->route('dashboard')->with('info', 'نظام الكورسات غير متاح حالياً من لوحة الطالب.');
            }

            return app(\App\Http\Controllers\Student\MyCourseController::class)->watchLesson($course, $lesson);
        })->middleware([\App\Http\Middleware\VideoProtectionMiddleware::class, 'ownership:course,course'])
            ->name('my-courses.lesson.watch');
        Route::post('/my-courses/{course}/lessons/{lesson}/progress', [\App\Http\Controllers\Student\MyCourseController::class, 'updateLessonProgress'])
            ->middleware(['ownership:course,course'])
            ->name('my-courses.lesson.progress');

    });

    // الإحالات (طلاب فقط)
    Route::middleware(['role:student'])->group(function () {
        Route::get('/referrals', [\App\Http\Controllers\Student\ReferralController::class, 'index'])->name('referrals.index');
        Route::post('/referrals/copy-link', [\App\Http\Controllers\Student\ReferralController::class, 'copyLink'])->name('referrals.copy-link');
    });

    // API للتحقق من الكوبون
    Route::post('/api/validate-coupon', [\App\Http\Controllers\Student\CouponController::class, 'validateCoupon'])->name('api.validate-coupon');

    // API لمعلومات الفيديو
    Route::post('/api/video/info', [\App\Http\Controllers\Api\VideoInfoController::class, 'getInfo'])->name('api.video.info');

    // API للدروس - محمية بالتأكد من التسجيل
    Route::get('/api/lessons/{lesson}', function (\App\Models\CourseLesson $lesson) {
        $user = auth()->user();

        // التحقق من أن المستخدم طالب
        if (! $user->isStudent()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        // التحقق من أن المستخدم مسجل في الكورس
        if (! $user->isEnrolledIn($lesson->advanced_course_id)) {
            return response()->json(['error' => 'غير مصرح - غير مسجل في الكورس'], 403);
        }

        $progress = $lesson->progress()->where('user_id', $user->id)->first();

        $rawVideo = $lesson->video_url ? trim($lesson->video_url) : null;
        $playbackVideo = $rawVideo
            ? (\App\Helpers\VideoHelper::getEmbedUrl($rawVideo)
                ?? \App\Helpers\VideoHelper::getDirectVideoUrl($rawVideo)
                ?? $rawVideo)
            : null;

        $attachments = $lesson->attachments ? json_decode($lesson->attachments, true) : null;
        if (is_array($attachments)) {
            $attachments = array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                $path = $item['path'] ?? null;
                if (is_string($path) && $path !== '' && ! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
                    $item['path'] = \App\Services\PublicStorageUrl::fromPath($path) ?? $path;
                }

                return $item;
            }, $attachments);
        }

        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'content' => $lesson->content,
            'type' => $lesson->type,
            'video_url' => $playbackVideo,
            'duration_minutes' => $lesson->duration_minutes,
            'attachments' => $attachments,
            'progress' => $progress ? [
                'is_completed' => (bool) $progress->is_completed,
                'progress_percent' => (int) ($progress->progress_percent ?? 0),
                'watch_time' => (int) ($progress->watch_time ?? 0),
            ] : null,
        ]);
    });

    // API للطلاب المسجلين في الكورس - محمية بـ role middleware
    Route::get('/api/courses/{course}/students', function ($course) {
        $instructor = auth()->user();

        // التحقق من أن المستخدم مدرب
        if (! $instructor->isInstructor()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        // التحقق من أن الكورس يخص المدرب
        $advancedCourse = \App\Models\AdvancedCourse::where('id', $course)
            ->where('instructor_id', $instructor->id)
            ->firstOrFail();

        // جلب الطلاب المسجلين في الكورس
        $enrollments = \App\Models\StudentCourseEnrollment::where('advanced_course_id', $course)
            ->where('status', 'active')
            ->with('user')
            ->get();

        $students = $enrollments->map(function ($enrollment) {
            $user = $enrollment->user;

            return [
                'id' => $user->id,
                'name' => $user->name ?? $user->full_name ?? ($user->first_name.' '.$user->last_name),
                'full_name' => $user->full_name ?? ($user->first_name.' '.$user->last_name),
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email,
            ];
        });

        return response()->json([
            'students' => $students,
            'count' => $students->count(),
        ]);
    })->middleware(['auth', 'role:instructor|teacher', 'instructor.activated']);

    // نظام الطلبات - محمي للطلاب فقط
    Route::middleware(['role:student'])->group(function () {
        Route::post('/courses/{advancedCourse}/order', [\App\Http\Controllers\Student\OrderController::class, 'store'])->name('courses.order');
        Route::get('/orders', [\App\Http\Controllers\Student\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Student\OrderController::class, 'show'])
            ->middleware(['ownership:order,order'])
            ->name('orders.show');
    });

    // امتحانات الطلاب - محمية للطلاب فقط
    Route::prefix('exams')->name('student.exams.')->middleware(['role:student'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Student\ExamController::class, 'index'])->name('index');
        Route::get('/{exam}', [\App\Http\Controllers\Student\ExamController::class, 'show'])->name('show');
        Route::post('/{exam}/start', [\App\Http\Controllers\Student\ExamController::class, 'start'])->name('start');
        Route::get('/{exam}/attempts/{attempt}/take', [\App\Http\Controllers\Student\ExamController::class, 'take'])
            ->middleware(\App\Http\Middleware\VideoProtectionMiddleware::class)
            ->name('take');
        Route::post('/{exam}/attempts/{attempt}/save-answer', [\App\Http\Controllers\Student\ExamController::class, 'saveAnswer'])->name('save-answer');
        Route::post('/{exam}/attempts/{attempt}/submit', [\App\Http\Controllers\Student\ExamController::class, 'submit'])->name('submit');
        Route::get('/{exam}/attempts/{attempt}/result', [\App\Http\Controllers\Student\ExamController::class, 'result'])->name('result');
        Route::post('/{exam}/attempts/{attempt}/tab-switch', [\App\Http\Controllers\Student\ExamController::class, 'logTabSwitch'])->name('tab-switch');
    });

    // صفحات الطلاب - محمية للطلاب فقط
    Route::middleware(['role:student'])->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/settings', [\App\Http\Controllers\Student\SettingsController::class, 'index'])->name('settings');
        Route::get('/notifications', [\App\Http\Controllers\Student\NotificationController::class, 'index'])->name('notifications');
        Route::get('/notifications/{notification}/go', [\App\Http\Controllers\Student\NotificationController::class, 'go'])
            ->name('notifications.go');
        Route::get('/notifications/{notification}', [\App\Http\Controllers\Student\NotificationController::class, 'show'])
            ->middleware(['ownership:notification,notification'])
            ->name('notifications.show');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Student\NotificationController::class, 'markAsRead'])
            ->middleware(['ownership:notification,notification'])
            ->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Student\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\Student\NotificationController::class, 'destroy'])
            ->middleware(['ownership:notification,notification'])
            ->name('notifications.destroy');
        Route::post('/notifications/cleanup', [\App\Http\Controllers\Student\NotificationController::class, 'cleanup'])->name('notifications.cleanup');
        Route::get('/api/notifications/unread-count', [\App\Http\Controllers\Student\NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
        Route::get('/api/notifications/recent', [\App\Http\Controllers\Student\NotificationController::class, 'getRecent'])->name('notifications.recent');
        Route::get('/api/notifications/nav', [\App\Http\Controllers\Student\NotificationController::class, 'navPoll'])->name('notifications.nav-poll');
        Route::get('/calendar', [\App\Http\Controllers\Student\CalendarController::class, 'index'])->name('calendar');
        Route::get('/api/calendar/events', [\App\Http\Controllers\Student\CalendarController::class, 'getEvents'])->name('calendar.events');

        Route::get('/consultations', [\App\Http\Controllers\Student\ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/request/{instructor}', [\App\Http\Controllers\Student\ConsultationController::class, 'create'])->name('consultations.create');
        Route::post('/consultations/request/{instructor}', [\App\Http\Controllers\Student\ConsultationController::class, 'store'])->name('consultations.store');
        Route::get('/consultations/{consultation}', [\App\Http\Controllers\Student\ConsultationController::class, 'show'])->name('consultations.show');
        Route::post('/consultations/{consultation}/report-payment', [\App\Http\Controllers\Student\ConsultationController::class, 'reportPayment'])->name('consultations.report-payment');
        Route::get('/my-course-subscriptions', [\App\Http\Controllers\Student\MyCourseSubscriptionController::class, 'index'])->name('student.my-course-subscriptions');
        Route::get('/learn', [\App\Http\Controllers\Student\LearnHubController::class, 'index'])->name('student.learn.index');
        Route::get('/learn/teachers/{instructor}', [\App\Http\Controllers\Student\LearnHubController::class, 'teacher'])->name('student.learn.teacher');

        Route::get('/progress', [\App\Http\Controllers\Student\ProgressHubController::class, 'index'])->name('student.progress.index');
        Route::post('/progress/refresh-adaptive', [\App\Http\Controllers\Student\ProgressHubController::class, 'refreshAdaptive'])->name('student.progress.refresh-adaptive');
        Route::post('/progress/send-family', [\App\Http\Controllers\Student\ProgressHubController::class, 'sendFamilyPreview'])->name('student.progress.send-family');

        Route::get('/one-to-one-sessions', [\App\Http\Controllers\Student\OneToOneSessionController::class, 'index'])->name('student.one-to-one-sessions.index');
        Route::get('/one-to-one-sessions/{oneToOneSession}', [\App\Http\Controllers\Student\OneToOneSessionController::class, 'show'])->name('student.one-to-one-sessions.show');
        Route::post('/one-to-one-sessions/{oneToOneSession}/book', [\App\Http\Controllers\Student\OneToOneSessionController::class, 'book'])->name('student.one-to-one-sessions.book');
        Route::post('/instructors/{instructor}/book-slot', [\App\Http\Controllers\Student\OneToOneSessionController::class, 'bookWithInstructor'])->name('student.one-to-one-sessions.book-instructor');

        // كورسات بريفيت — محاضرات خاصة + رسائل مع المعلم
        Route::get('/private-lectures', [\App\Http\Controllers\Student\PrivateLecturesController::class, 'index'])->name('student.private-lectures.index');
        Route::get('/private-messages', [\App\Http\Controllers\Student\PrivateLecturesController::class, 'messagesIndex'])->name('student.private-messages.index');
        Route::get('/private-messages/with/{instructor}', [\App\Http\Controllers\Student\PrivateLecturesController::class, 'openWith'])->name('student.private-messages.with');
        Route::get('/private-messages/{thread}', [\App\Http\Controllers\Student\PrivateLecturesController::class, 'messages'])->name('student.private-messages.show');
        Route::post('/private-messages/{thread}', [\App\Http\Controllers\Student\PrivateLecturesController::class, 'sendMessage'])->name('student.private-messages.send');

        Route::get('/my-school', [\App\Http\Controllers\Student\SchoolController::class, 'index'])->name('student.school.index');

        Route::get('/schedule/join/{type}/{id}', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'join'])
            ->whereIn('type', ['private', 'class', 'booking'])
            ->name('student.schedule.join');
        Route::get('/library', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'libraryHome'])->name('student.library.home');
        Route::get('/library/files', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'files'])->name('student.library.files');
        Route::get('/library/curriculum', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'index'])->name('student.library.curriculum');
        Route::get('/library/materials', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'materials'])->name('student.library.materials');
        Route::get('/library/materials/{material}/download', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'downloadMaterial'])->name('student.library.materials.download');
        Route::get('/library/materials/{material}/experience', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'experienceMaterial'])->name('student.library.materials.experience');
        Route::get('/library/materials/{material}/experience/raw', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'experienceMaterialRaw'])->name('student.library.materials.experience.raw');
        Route::get('/library/videos', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'videos'])->name('student.library.videos');
        Route::get('/library/videos/{libraryVideo}', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'watchLibraryVideo'])->name('student.library.videos.show');
        Route::get('/library/lecture-recordings/{lecture}', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'watchLectureRecording'])->name('student.library.lecture-recordings.show');
        Route::get('/my-lectures', [\App\Http\Controllers\Student\StudentHomeExtrasController::class, 'lectures'])->name('student.lectures.index');

        // مكتبة المناهج التفاعلية (Manahij X) — فهرس/عرض للطلاب فقط
        Route::get('/curriculum-library', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'index'])->name('curriculum-library.index');
        Route::get('/curriculum-library/{item:slug}', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'show'])->name('curriculum-library.show');

        Route::get('/service-entitlements', [\App\Http\Controllers\Student\ServiceEntitlementController::class, 'index'])->name('student.service-entitlements.index');

        // فصول حصتك — الطالب: دخول الغرفة فقط (بدون إنشاء/إدارة اجتماعات)
        $studentMeetingDenied = 'لا يمكن للطلاب إنشاء أو إدارة اجتماعات مباشرة. انضم من جدولك أو من حصصك/فصولك.';

        Route::get('/classroom/room/{meeting}', [\App\Http\Controllers\Student\ClassroomController::class, 'room'])->name('student.classroom.room');
        Route::get('/classroom/room/{meeting}/status', [\App\Http\Controllers\Student\ClassroomController::class, 'roomStatus'])->name('student.classroom.room.status');
        Route::get('/classroom/room/{meeting}/recording', [\App\Http\Controllers\Student\ClassroomController::class, 'watchRecording'])->name('student.classroom.recording');
        Route::get('/classroom/room/{meeting}/recording-status', [\App\Http\Controllers\Student\ClassroomController::class, 'recordingStatus'])->name('student.classroom.recording.status');
        Route::get('/classroom/{meeting}/share-annotations', [\App\Http\Controllers\Student\ClassroomController::class, 'shareAnnotations'])->name('student.classroom.share-annotations');
        Route::post('/classroom/{meeting}/share-annotation', [\App\Http\Controllers\Student\ClassroomController::class, 'pushShareAnnotation'])->name('student.classroom.share-annotation');
        Route::get('/classroom/{meeting}/whiteboard/state', [\App\Http\Controllers\Student\ClassroomController::class, 'whiteboardState'])->name('student.classroom.whiteboard.state');
        Route::post('/classroom/{meeting}/whiteboard/state', [\App\Http\Controllers\Student\ClassroomController::class, 'pushWhiteboardState'])->name('student.classroom.whiteboard.push');
        Route::get('/classroom/{meeting}/curriculum/state', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumState'])->name('student.classroom.curriculum.state');
        Route::get('/classroom/{meeting}/curriculum/{sessionId}/slide/{slide}', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumSlide'])
            ->whereNumber('slide')
            ->name('student.classroom.curriculum.slide');
        Route::get('/classroom/{meeting}/curriculum/{sessionId}/thumb/{slide}', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumThumb'])
            ->whereNumber('slide')
            ->name('student.classroom.curriculum.thumb');

        // مسارات إدارة الغرفة — مسجّلة حتى يعمل room.blade.php (route generation)
        // وممنوعة على الطالب عبر student.no-meeting-host + حارس الـ controller
        Route::middleware('student.no-meeting-host')->group(function () use ($studentMeetingDenied) {
            Route::get('/classroom', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->name('student.classroom.index');
            Route::get('/classroom/create', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->name('student.classroom.create');
            Route::post('/classroom', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->name('student.classroom.store');
            Route::get('/classroom/whiteboard', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->name('student.classroom.whiteboard');
            Route::post('/classroom/start', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->name('student.classroom.start');
            Route::get('/classroom/{meeting}', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->whereNumber('meeting')->name('student.classroom.show');
            Route::get('/classroom/{meeting}/edit', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->whereNumber('meeting')->name('student.classroom.edit');
            Route::put('/classroom/{meeting}', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->whereNumber('meeting')->name('student.classroom.update');
            Route::delete('/classroom/{meeting}', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->whereNumber('meeting')->name('student.classroom.destroy');
            Route::post('/classroom/{meeting}/start', fn () => redirect()->route('dashboard')->with('error', $studentMeetingDenied))->whereNumber('meeting')->name('student.classroom.start-meeting');

            Route::post('/classroom/room/{meeting}/end', [\App\Http\Controllers\Student\ClassroomController::class, 'end'])->name('student.classroom.end');
            Route::get('/classroom/room/{meeting}/recording-upload', [\App\Http\Controllers\Student\ClassroomController::class, 'recordingUploadTab'])->name('student.classroom.recording.upload-tab');
            Route::post('/classroom/{meeting}/participant-whiteboard', [\App\Http\Controllers\Student\ClassroomController::class, 'updateParticipantWhiteboard'])->name('student.classroom.participant-whiteboard');
            Route::post('/classroom/{meeting}/guest-join', [\App\Http\Controllers\Student\ClassroomController::class, 'updateGuestJoin'])->name('student.classroom.guest-join');
            Route::post('/classroom/{meeting}/recording/upload', [\App\Http\Controllers\Student\ClassroomController::class, 'uploadRecording'])->name('student.classroom.recording.upload');
            Route::post('/classroom/{meeting}/recording/presign', [\App\Http\Controllers\Student\ClassroomController::class, 'presignRecordingUpload'])->name('student.classroom.recording.presign');
            Route::post('/classroom/{meeting}/recording/complete', [\App\Http\Controllers\Student\ClassroomController::class, 'completeDirectRecordingUpload'])->name('student.classroom.recording.complete');
            Route::post('/classroom/{meeting}/recording-audio/presign', [\App\Http\Controllers\Student\ClassroomController::class, 'presignAudioUpload'])->name('student.classroom.recording-audio.presign');
            Route::post('/classroom/{meeting}/recording-audio/upload', [\App\Http\Controllers\Student\ClassroomController::class, 'uploadAudioRecording'])->name('student.classroom.recording-audio.upload');
            Route::post('/classroom/{meeting}/recording-audio/complete', [\App\Http\Controllers\Student\ClassroomController::class, 'completeDirectAudioUpload'])->name('student.classroom.recording-audio.complete');
            Route::post('/classroom/{meeting}/ai-report', [\App\Http\Controllers\Student\ClassroomController::class, 'generateAiReport'])->name('student.classroom.ai-report');
            Route::get('/classroom/{meeting}/curriculum/catalog', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumCatalog'])->name('student.classroom.curriculum.catalog');
            Route::post('/classroom/{meeting}/curriculum/present', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumPresent'])->name('student.classroom.curriculum.present');
            Route::post('/classroom/{meeting}/curriculum/slide', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumUpdateSlide'])->name('student.classroom.curriculum.slide.update');
            Route::post('/classroom/{meeting}/curriculum/stop', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumStop'])->name('student.classroom.curriculum.stop');
        });
    });

    // ملفات مناهج X (عرض/تحميل/شرائح) — طالب أو معلم معتمد
    Route::middleware(['curriculum.viewer'])->group(function () {
        Route::get('/curriculum-library/{item:slug}/m/{material}/download', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'downloadMaterial'])->name('curriculum-library.material.download');
        Route::get('/curriculum-library/{item:slug}/m/{material}/html', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialHtml'])->name('curriculum-library.material.html');
        Route::get('/curriculum-library/{item:slug}/m/{material}/pdf', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialPdf'])->name('curriculum-library.material.pdf');
        Route::get('/curriculum-library/{item:slug}/m/{material}/presentation', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialPresentation'])->name('curriculum-library.material.presentation');
        Route::get('/curriculum-library/{item:slug}/m/{material}/animation-video', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialAnimationVideo'])->name('curriculum-library.material.animation-video');
        Route::get('/curriculum-library/{item:slug}/m/{material}/slides/manifest', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialSlidesManifest'])->name('curriculum-library.material.slides.manifest');
        Route::get('/curriculum-library/{item:slug}/m/{material}/slides/{slide}', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialSlideImage'])
            ->whereNumber('slide')
            ->name('curriculum-library.material.slides.image');
        Route::get('/curriculum-library/{item:slug}/m/{material}/slides/{slide}/thumb', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewMaterialSlideThumb'])
            ->whereNumber('slide')
            ->name('curriculum-library.material.slides.thumb');
        Route::get('/curriculum-library/{item:slug}/file/{file}/download', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'download'])->name('curriculum-library.file.download');
        Route::get('/curriculum-library/{item:slug}/file/{file}/view', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewHtml'])->name('curriculum-library.file.view');
        Route::get('/curriculum-library/{item:slug}/file/{file}/pdf', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewPdf'])->name('curriculum-library.file.pdf');
        Route::get('/curriculum-library/{item:slug}/file/{file}/presentation', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewPresentation'])->name('curriculum-library.file.presentation');
        Route::get('/curriculum-library/{item:slug}/file/{file}/slides/manifest', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewFileSlidesManifest'])->name('curriculum-library.file.slides.manifest');
        Route::get('/curriculum-library/{item:slug}/file/{file}/slides/{slide}', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewFileSlideImage'])
            ->whereNumber('slide')
            ->name('curriculum-library.file.slides.image');
        Route::get('/curriculum-library/{item:slug}/file/{file}/slides/{slide}/thumb', [\App\Http\Controllers\Student\CurriculumLibraryController::class, 'viewFileSlideThumb'])
            ->whereNumber('slide')
            ->name('curriculum-library.file.slides.thumb');
    });

    // دعم المتعلمين والمدربين
    Route::middleware(['role:student|instructor|teacher'])->group(function () {
        Route::get('/support', [\App\Http\Controllers\Student\SupportTicketController::class, 'index'])->name('student.support.index');
        Route::post('/support', [\App\Http\Controllers\Student\SupportTicketController::class, 'store'])->name('student.support.store');
        Route::get('/support/{ticket}', [\App\Http\Controllers\Student\SupportTicketController::class, 'show'])->name('student.support.show');
        Route::post('/support/{ticket}/reply', [\App\Http\Controllers\Student\SupportTicketController::class, 'reply'])->name('student.support.reply');
    });

    // لوحة الموظفين — عناصر القائمة تُحدَّد بصلاحيات الوظيفة (employee_jobs.permissions)
    Route::prefix('employee')->name('employee.')->middleware(['auth'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Employee\EmployeeController::class, 'dashboard'])->middleware('employee.can:dashboard')->name('dashboard');

        Route::get('/desk/accountant', [\App\Http\Controllers\Employee\EmployeeAccountantDeskController::class, 'index'])->middleware('employee.can:desk_accountant')->name('accountant-desk.index');
        Route::prefix('sales')->name('sales.')->middleware('employee.can:sales_desk')->group(function () {
            Route::get('/desk', [\App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class, 'desk'])->name('desk');
            Route::get('/orders', [\App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class, 'ordersIndex'])->name('orders.index');
            Route::get('/orders/{order}', [\App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class, 'orderShow'])->name('orders.show');
            Route::post('/orders/{order}/notes', [\App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class, 'storeNote'])->name('orders.notes.store');
            Route::post('/orders/{order}/claim', [\App\Http\Controllers\Employee\EmployeeSalesWorkspaceController::class, 'claim'])->name('orders.claim');

            Route::get('/leads', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/create', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'create'])->name('leads.create');
            Route::post('/leads', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'store'])->name('leads.store');
            Route::get('/leads/{salesLead}', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'show'])->name('leads.show');
            Route::get('/leads/{salesLead}/edit', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'edit'])->name('leads.edit');
            Route::put('/leads/{salesLead}', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'update'])->name('leads.update');
            Route::post('/leads/{salesLead}/assign-me', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'assignToMe'])->name('leads.assign-me');
            Route::post('/leads/{salesLead}/convert', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'convert'])->name('leads.convert');
            Route::post('/leads/{salesLead}/lost', [\App\Http\Controllers\Employee\EmployeeSalesLeadController::class, 'markLost'])->name('leads.lost');
        });

        Route::prefix('crm')->name('crm.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'dashboard'])->name('dashboard');
            Route::get('/marketing-desk', [\App\Http\Controllers\Employee\CrmMarketingController::class, 'desk'])->name('marketing.desk');
            Route::get('/marketing-inbox', [\App\Http\Controllers\Employee\CrmSalesInboxController::class, 'index'])->name('marketing-inbox.index');
            Route::post('/marketing-inbox/{salesLead}/claim', [\App\Http\Controllers\Employee\CrmSalesInboxController::class, 'claim'])->name('marketing-inbox.claim');
            Route::get('/leads', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'leadsIndex'])->name('leads.index');
            Route::get('/leads/create', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'leadsCreate'])->name('leads.create');
            Route::post('/leads', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'leadsStore'])->name('leads.store');
            Route::get('/leads/{salesLead}', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'leadsShow'])->name('leads.show');
            Route::put('/leads/{salesLead}', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'leadsUpdate'])->name('leads.update');
            Route::post('/leads/{salesLead}/transition', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'transition'])->name('leads.transition');
            Route::post('/leads/{salesLead}/note', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'addNote'])->name('leads.note');
            Route::post('/leads/{salesLead}/assign', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'assignLead'])->name('leads.assign');
            Route::post('/leads/{salesLead}/submit-to-sales', [\App\Http\Controllers\Employee\CrmMarketingController::class, 'submitToSales'])->name('leads.submit-to-sales');
            Route::post('/leads/{salesLead}/confirm-payment', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'confirmPayment'])->name('leads.confirm-payment');
            Route::get('/team', [\App\Http\Controllers\Employee\CrmTeamController::class, 'index'])->name('team.index');
            Route::post('/team/{group}/members', [\App\Http\Controllers\Employee\CrmTeamController::class, 'addMember'])->name('team.members.store');
            Route::delete('/team/{group}/members/{member}', [\App\Http\Controllers\Employee\CrmTeamController::class, 'removeMember'])->name('team.members.destroy');
            Route::get('/reports', [\App\Http\Controllers\Employee\CrmReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/create', [\App\Http\Controllers\Employee\CrmReportController::class, 'create'])->name('reports.create');
            Route::post('/reports', [\App\Http\Controllers\Employee\CrmReportController::class, 'store'])->name('reports.store');
            Route::get('/reports/{report}/download', [\App\Http\Controllers\Employee\CrmReportController::class, 'download'])->name('reports.download');
            Route::get('/messages', [\App\Http\Controllers\Employee\CrmMessageController::class, 'index'])->name('messages.index');
            Route::post('/messages', [\App\Http\Controllers\Employee\CrmMessageController::class, 'store'])->name('messages.store');
            Route::get('/messages/leads/{salesLead}', [\App\Http\Controllers\Employee\CrmMessageController::class, 'leadThread'])->name('messages.lead');
            Route::get('/messages/{message}/attachment', [\App\Http\Controllers\Employee\CrmMessageController::class, 'downloadAttachment'])->name('messages.attachment');
            Route::get('/commissions', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'commissions'])->name('commissions');
            Route::post('/commissions/{commission}/approve', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'approveCommission'])->name('commissions.approve');
            Route::get('/orders', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'ordersIndex'])->name('orders');
            Route::get('/sales-financial', [\App\Http\Controllers\Employee\CrmSalesFinancialController::class, 'index'])->name('sales-financial');
            Route::post('/orders/{order}/approve', [\App\Http\Controllers\Employee\CrmWorkspaceController::class, 'approveOrder'])->name('orders.approve');
        });

        Route::get('/desk/hr', [\App\Http\Controllers\Employee\EmployeeHrDeskController::class, 'index'])->middleware('employee.can:hr_desk')->name('hr-desk.index');
        Route::prefix('hr')->name('hr.')->middleware('employee.can:hr_desk')->group(function () {
            Route::get('/leaves', [\App\Http\Controllers\Employee\EmployeeHrLeaveController::class, 'index'])->name('leaves.index');
            Route::get('/leaves/{leave}', [\App\Http\Controllers\Employee\EmployeeHrLeaveController::class, 'show'])->name('leaves.show');
            Route::post('/leaves/{leave}/approve', [\App\Http\Controllers\Employee\EmployeeHrLeaveController::class, 'approve'])->name('leaves.approve');
            Route::post('/leaves/{leave}/reject', [\App\Http\Controllers\Employee\EmployeeHrLeaveController::class, 'reject'])->name('leaves.reject');
            Route::get('/employees', [\App\Http\Controllers\Employee\EmployeeHrDirectoryController::class, 'index'])->name('employees.index');
            Route::get('/employees/{employee}', [\App\Http\Controllers\Employee\EmployeeHrDirectoryController::class, 'show'])->name('employees.show');
            Route::post('/employees/{employee}/hr-events', [\App\Http\Controllers\Employee\EmployeeHrDirectoryController::class, 'storeEvent'])->name('employees.hr-events.store');

            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Employee\EmployeeHrRecruitmentController::class, 'index'])->name('index');
                Route::resource('openings', \App\Http\Controllers\Employee\EmployeeHrJobOpeningController::class);
                Route::resource('candidates', \App\Http\Controllers\Employee\EmployeeHrCandidateController::class);
                Route::get('/applications/{hr_job_application}', [\App\Http\Controllers\Employee\EmployeeHrJobApplicationController::class, 'show'])->name('applications.show');
                Route::patch('/applications/{hr_job_application}', [\App\Http\Controllers\Employee\EmployeeHrJobApplicationController::class, 'update'])->name('applications.update');
                Route::post('/applications', [\App\Http\Controllers\Employee\EmployeeHrJobApplicationController::class, 'store'])->name('applications.store');
                Route::post('/applications/{hr_job_application}/interviews', [\App\Http\Controllers\Employee\EmployeeHrInterviewController::class, 'store'])->name('applications.interviews.store');
                Route::put('/applications/{hr_job_application}/interviews/{hr_interview}', [\App\Http\Controllers\Employee\EmployeeHrInterviewController::class, 'update'])->name('applications.interviews.update');
                Route::delete('/applications/{hr_job_application}/interviews/{hr_interview}', [\App\Http\Controllers\Employee\EmployeeHrInterviewController::class, 'destroy'])->name('applications.interviews.destroy');
            });
        });
        Route::get('/desk/supervision', [\App\Http\Controllers\Employee\EmployeeSupervisionDeskController::class, 'index'])->middleware('employee.can:supervision_desk')->name('supervision-desk.index');

        Route::get('/desk/academic-supervision', [\App\Http\Controllers\Employee\AcademicSupervisionController::class, 'index'])->middleware('employee.can:academic_supervision_desk')->name('academic-supervision.index');
        Route::get('/desk/academic-supervision/students/{student}', [\App\Http\Controllers\Employee\AcademicSupervisionController::class, 'show'])->middleware('employee.can:academic_supervision_desk')->name('academic-supervision.show');
        Route::get('/desk/academic-supervision/meetings/{meeting}/observe', [\App\Http\Controllers\Employee\AcademicSupervisionController::class, 'observerRoom'])->middleware('employee.can:academic_supervision_desk')->name('academic-supervision.meeting.observe');

        Route::get('/tasks', [\App\Http\Controllers\Employee\EmployeeTaskController::class, 'index'])->middleware('employee.can:tasks')->name('tasks.index');
        Route::get('/tasks/{task}', [\App\Http\Controllers\Employee\EmployeeTaskController::class, 'show'])->middleware('employee.can:tasks')->name('tasks.show');
        Route::put('/tasks/{task}/status', [\App\Http\Controllers\Employee\EmployeeTaskController::class, 'updateStatus'])->middleware('employee.can:tasks')->name('tasks.update-status');
        Route::post('/tasks/{task}/deliverables', [\App\Http\Controllers\Employee\EmployeeTaskController::class, 'submitDeliverable'])->middleware('employee.can:tasks')->name('tasks.submit-deliverable');

        Route::resource('leaves', \App\Http\Controllers\Employee\EmployeeLeaveController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->middleware('employee.can:leaves');

        Route::get('/accounting', [\App\Http\Controllers\Employee\AccountingController::class, 'index'])->middleware('employee.can:accounting')->name('accounting.index');
        Route::post('/accounting/bank-account', [\App\Http\Controllers\Employee\AccountingController::class, 'updateBankAccount'])->middleware('employee.can:accounting')->name('accounting.update-bank');

        Route::get('/agreements', [\App\Http\Controllers\Employee\AgreementController::class, 'index'])->middleware('employee.can:agreements')->name('agreements.index');
        Route::get('/agreements/{agreement}', [\App\Http\Controllers\Employee\AgreementController::class, 'show'])->middleware('employee.can:agreements')->name('agreements.show');

        Route::get('/profile', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'index'])->middleware('employee.can:profile')->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Employee\EmployeeProfileController::class, 'update'])->middleware('employee.can:profile')->name('profile.update');

        Route::get('/notifications', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'index'])->middleware('employee.can:notifications')->name('notifications');
        Route::get('/notifications/{notification}/go', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'go'])->middleware('employee.can:notifications')->name('notifications.go');
        Route::get('/notifications/{notification}', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'show'])->middleware('employee.can:notifications')->name('notifications.show');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'markAsRead'])->middleware('employee.can:notifications')->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'markAllAsRead'])->middleware('employee.can:notifications')->name('notifications.mark-all-read');

        Route::get('/calendar', [\App\Http\Controllers\Employee\EmployeeCalendarController::class, 'index'])->middleware('employee.can:calendar')->name('calendar');
        Route::get('/api/calendar/events', [\App\Http\Controllers\Employee\EmployeeCalendarController::class, 'getEvents'])->middleware('employee.can:calendar')->name('calendar.events');

        Route::get('/reports', [\App\Http\Controllers\Employee\EmployeeReportController::class, 'index'])->middleware('employee.can:reports')->name('reports');

        Route::get('/settings', [\App\Http\Controllers\Employee\EmployeeSettingsController::class, 'index'])->middleware('employee.can:settings')->name('settings');
        Route::put('/settings', [\App\Http\Controllers\Employee\EmployeeSettingsController::class, 'update'])->middleware('employee.can:settings')->name('settings.update');

        Route::get('/api/nav-notifications', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'navPoll'])
            ->middleware(ThrottleRequests::using('employee-nav-poll'))
            ->name('api.nav-notifications');

        Route::get('/api/notifications/unread', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'getUnread'])->middleware('employee.can:notifications')->name('notifications.unread');
        Route::post('/api/notifications/{notification}/mark-read', [\App\Http\Controllers\Employee\EmployeeNotificationController::class, 'markAsRead'])->middleware('employee.can:notifications')->name('notifications.api.mark-read');
    });

    // مسارات الإدارة - محمية بصلاحية admin.access (مع تجاوز super_admin داخل EnsurePermission)
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'permission:admin.access', 'rbac.strict.admin'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/ops-guide', [\App\Http\Controllers\Admin\OpsGuideController::class, 'index'])->name('ops-guide.index');

        Route::get('/academy-insights', [\App\Http\Controllers\Admin\AcademyInsightsController::class, 'index'])->name('academy-insights.index');
        Route::get('/api/academy-insights', [\App\Http\Controllers\Admin\AcademyInsightsController::class, 'poll'])
            ->middleware(ThrottleRequests::using('admin-nav-poll'))
            ->name('academy-insights.poll');

        Route::get('/api/nav-notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'navPoll'])
            ->middleware(ThrottleRequests::using('admin-nav-poll'))
            ->name('api.nav-notifications');

        // بروفايل الأدمن
        Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

        // إدارة المستخدمين
        Route::get('/users', [\App\Http\Controllers\Admin\AdminController::class, 'users'])->name('users.index');
        Route::get('/students-accounts', [\App\Http\Controllers\Admin\AdminController::class, 'studentsAccounts'])->name('students-accounts.index');

        Route::get('/users/create', [\App\Http\Controllers\Admin\AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\Admin\AdminController::class, 'storeUser'])
            ->middleware('throttle:20,1')
            ->name('users.store');
        Route::get('/users/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'showUser'])->name('users.show')->whereUuid('user');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\Admin\AdminController::class, 'editUser'])->name('users.edit')->whereUuid('user');
        // دعم fallback لـ POST في حالة فشل method spoof (_method=PUT) على بعض البيئات/المتصفحات
        Route::post('/users/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'updateUser'])->whereUuid('user');
        Route::put('/users/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'updateUser'])->name('users.update')->whereUuid('user');
        Route::delete('/users/{user}', [\App\Http\Controllers\Admin\AdminController::class, 'deleteUser'])->name('users.delete')->whereUuid('user');

        // مسارات التعلم (السنوات الدراسية)
        Route::resource('academic-years', \App\Http\Controllers\Admin\AcademicYearController::class)->except(['show']);
        Route::post('/academic-years/{academicYear}/toggle-status', [\App\Http\Controllers\Admin\AcademicYearController::class, 'toggleStatus'])->name('academic-years.toggle-status');
        Route::post('/academic-years/reorder', [\App\Http\Controllers\Admin\AcademicYearController::class, 'reorder'])->name('academic-years.reorder');
        Route::post('/academic-years/{academicYear}/add-course', [\App\Http\Controllers\Admin\AcademicYearController::class, 'addCourse'])->name('academic-years.add-course');
        Route::delete('/academic-years/{academicYear}/remove-course/{course}', [\App\Http\Controllers\Admin\AcademicYearController::class, 'removeCourse'])->name('academic-years.remove-course');
        Route::post('/academic-years/{academicYear}/add-instructor', [\App\Http\Controllers\Admin\AcademicYearController::class, 'addInstructor'])->name('academic-years.add-instructor');
        Route::delete('/academic-years/{academicYear}/remove-instructor/{instructor}', [\App\Http\Controllers\Admin\AcademicYearController::class, 'removeInstructor'])->name('academic-years.remove-instructor');

        Route::resource('academic-subjects', \App\Http\Controllers\Admin\AcademicSubjectController::class);
        Route::post('/academic-subjects/{academicSubject}/toggle-status', [\App\Http\Controllers\Admin\AcademicSubjectController::class, 'toggleStatus'])->name('academic-subjects.toggle-status');
        Route::post('/academic-subjects/reorder', [\App\Http\Controllers\Admin\AcademicSubjectController::class, 'reorder'])->name('academic-subjects.reorder');
        Route::post('/academic-subjects/{academicSubject}/attach-course', [\App\Http\Controllers\Admin\AcademicSubjectController::class, 'attachCourse'])->name('academic-subjects.attach-course');
        Route::delete('/academic-subjects/{academicSubject}/detach-course/{course}', [\App\Http\Controllers\Admin\AcademicSubjectController::class, 'detachCourse'])->name('academic-subjects.detach-course');

        // أكاديمية — توصيف المدربين للطلاب وتغطية المجموعات
        Route::get('/academy-instructors', [\App\Http\Controllers\Admin\AcademyInstructorController::class, 'index'])->name('academy-instructors.index');
        Route::get('/academy-instructors/{instructor}', [\App\Http\Controllers\Admin\AcademyInstructorController::class, 'show'])->name('academy-instructors.show');
        Route::post('/academy-instructors/assignments', [\App\Http\Controllers\Admin\AcademyInstructorController::class, 'storeAssignment'])->name('academy-instructors.assignments.store');
        Route::patch('/academy-instructors/assignments/{assignment}/status', [\App\Http\Controllers\Admin\AcademyInstructorController::class, 'updateAssignmentStatus'])->name('academy-instructors.assignments.status');
        Route::delete('/academy-instructors/assignments/{assignment}', [\App\Http\Controllers\Admin\AcademyInstructorController::class, 'destroyAssignment'])->name('academy-instructors.assignments.destroy');

        // مركز تحكم المعلم (بيانات + جدول + 1:1 + حجوزات)
        Route::get('/teachers', [\App\Http\Controllers\Admin\TeacherControlController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacher}', [\App\Http\Controllers\Admin\TeacherControlController::class, 'show'])->name('teachers.show');
        Route::get('/teachers/{teacher}/calendar-events', [\App\Http\Controllers\Admin\TeacherControlController::class, 'calendarEvents'])->name('teachers.calendar-events');
        Route::put('/teachers/{teacher}/profile', [\App\Http\Controllers\Admin\TeacherControlController::class, 'updateProfile'])->name('teachers.update-profile');
        Route::post('/teachers/{teacher}/work-schedule', [\App\Http\Controllers\Admin\TeacherControlController::class, 'syncWorkSchedule'])->name('teachers.sync-work-schedule');
        Route::post('/teachers/{teacher}/one-to-one-availability', [\App\Http\Controllers\Admin\TeacherControlController::class, 'syncOneToOneAvailability'])->name('teachers.sync-oto-availability');
        Route::post('/teachers/{teacher}/sessions/{session}/schedule', [\App\Http\Controllers\Admin\TeacherControlController::class, 'scheduleOneToOne'])->name('teachers.sessions.schedule');
        Route::post('/teachers/{teacher}/sessions/{session}/complete', [\App\Http\Controllers\Admin\TeacherControlController::class, 'completeOneToOne'])->name('teachers.sessions.complete');
        Route::post('/teachers/{teacher}/sessions/{session}/cancel', [\App\Http\Controllers\Admin\TeacherControlController::class, 'cancelOneToOne'])->name('teachers.sessions.cancel');
        Route::post('/teachers/{teacher}/sessions/{session}/reassign', [\App\Http\Controllers\Admin\TeacherControlController::class, 'reassignOneToOne'])->name('teachers.sessions.reassign');
        Route::patch('/teachers/{teacher}/bookings/{booking}/status', [\App\Http\Controllers\Admin\TeacherControlController::class, 'updateBookingStatus'])->name('teachers.bookings.status');

        // مسارات الكورسات (تصفية صفحة /courses العامة)
        Route::resource('course-categories', \App\Http\Controllers\Admin\CourseCategoryController::class)->except(['show', 'create']);

        // مواد المدرسة مدمجة في academic-subjects

        // إدارة الكورسات المتطورة
        Route::resource('advanced-courses', \App\Http\Controllers\Admin\AdvancedCourseController::class);
        Route::post('/advanced-courses/{advancedCourse}/activate-student', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'activateStudent'])->name('advanced-courses.activate-student');
        Route::get('/advanced-courses/{advancedCourse}/students', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'students'])->name('advanced-courses.students');
        Route::post('/advanced-courses/{advancedCourse}/toggle-status', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'toggleStatus'])->name('advanced-courses.toggle-status');
        Route::post('/advanced-courses/{advancedCourse}/toggle-featured', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'toggleFeatured'])->name('advanced-courses.toggle-featured');
        Route::get('/advanced-courses/{advancedCourse}/orders', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'orders'])->name('advanced-courses.orders');
        Route::get('/advanced-courses/{advancedCourse}/statistics', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'statistics'])->name('advanced-courses.statistics');
        Route::post('/advanced-courses/{advancedCourse}/duplicate', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'duplicate'])->name('advanced-courses.duplicate');
        Route::get('/get-subjects-by-year', [\App\Http\Controllers\Admin\AdvancedCourseController::class, 'getSubjectsByYear'])->name('advanced-courses.get-subjects-by-year');
        Route::get('/courses/{course}/lessons-list', function (\App\Models\AdvancedCourse $course) {
            return response()->json($course->lessons()->active()->select('id', 'title')->get());
        });

        // إدارة دروس الكورسات
        Route::prefix('courses/{course}/lessons')->name('courses.lessons.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CourseLessonController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\CourseLessonController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\CourseLessonController::class, 'store'])->name('store');
            Route::get('/{lesson}', [\App\Http\Controllers\Admin\CourseLessonController::class, 'show'])->name('show');
            Route::get('/{lesson}/edit', [\App\Http\Controllers\Admin\CourseLessonController::class, 'edit'])->name('edit');
            Route::put('/{lesson}', [\App\Http\Controllers\Admin\CourseLessonController::class, 'update'])->name('update');
            Route::delete('/{lesson}', [\App\Http\Controllers\Admin\CourseLessonController::class, 'destroy'])->name('destroy');
            Route::post('/{lesson}/toggle-status', [\App\Http\Controllers\Admin\CourseLessonController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/reorder', [\App\Http\Controllers\Admin\CourseLessonController::class, 'reorder'])->name('reorder');
        });

        // إدارة بنك الأسئلة
        Route::prefix('question-bank')->name('question-bank.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QuestionBankController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\QuestionBankController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\QuestionBankController::class, 'store'])->name('store');
            Route::get('/{question}', [\App\Http\Controllers\Admin\QuestionBankController::class, 'show'])->name('show');
            Route::get('/{question}/edit', [\App\Http\Controllers\Admin\QuestionBankController::class, 'edit'])->name('edit');
            Route::put('/{question}', [\App\Http\Controllers\Admin\QuestionBankController::class, 'update'])->name('update');
            Route::delete('/{question}', [\App\Http\Controllers\Admin\QuestionBankController::class, 'destroy'])->name('destroy');
            Route::post('/{question}/duplicate', [\App\Http\Controllers\Admin\QuestionBankController::class, 'duplicate'])->name('duplicate');
            Route::post('/export', [\App\Http\Controllers\Admin\QuestionBankController::class, 'export'])->name('export');
            Route::post('/import', [\App\Http\Controllers\Admin\QuestionBankController::class, 'import'])->name('import');
        });

        // إدارة تصنيفات الأسئلة
        Route::prefix('question-categories')->name('question-categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'store'])->name('store');
            Route::get('/{questionCategory}', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'show'])->name('show');
            Route::get('/{questionCategory}/edit', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'edit'])->name('edit');
            Route::put('/{questionCategory}', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'update'])->name('update');
            Route::delete('/{questionCategory}', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'destroy'])->name('destroy');
            Route::post('/reorder', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'reorder'])->name('reorder');
            Route::get('/subjects-by-year/{year}', [\App\Http\Controllers\Admin\QuestionCategoryController::class, 'getSubjectsByYear'])->name('subjects-by-year');
        });

        // إدارة الامتحانات (مسار الكورس قبل المسارات الأخرى لتفادي التعارض)
        Route::prefix('exams')->name('exams.')->group(function () {
            Route::get('/course/{course}', [\App\Http\Controllers\Admin\ExamController::class, 'indexByCourse'])->name('by-course');
            Route::get('/', [\App\Http\Controllers\Admin\ExamController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\ExamController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\ExamController::class, 'store'])->name('store');
            Route::get('/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'show'])->name('show');
            Route::get('/{exam}/edit', [\App\Http\Controllers\Admin\ExamController::class, 'edit'])->name('edit');
            Route::put('/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'update'])->name('update');
            Route::delete('/{exam}', [\App\Http\Controllers\Admin\ExamController::class, 'destroy'])->name('destroy');
            Route::get('/{exam}/questions', [\App\Http\Controllers\Admin\ExamController::class, 'manageQuestions'])->name('questions.manage');
            Route::post('/{exam}/questions', [\App\Http\Controllers\Admin\ExamController::class, 'addQuestion'])->name('questions.add');
            Route::delete('/{exam}/questions/{examQuestion}', [\App\Http\Controllers\Admin\ExamController::class, 'removeQuestion'])->name('questions.remove');
            Route::post('/{exam}/questions/reorder', [\App\Http\Controllers\Admin\ExamController::class, 'reorderQuestions'])->name('questions.reorder');
            Route::post('/{exam}/toggle-publish', [\App\Http\Controllers\Admin\ExamController::class, 'togglePublish'])->name('toggle-publish');
            Route::post('/{exam}/toggle-status', [\App\Http\Controllers\Admin\ExamController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{exam}/statistics', [\App\Http\Controllers\Admin\ExamController::class, 'statistics'])->name('statistics');
            Route::get('/{exam}/preview', [\App\Http\Controllers\Admin\ExamController::class, 'preview'])->name('preview');
            Route::post('/{exam}/duplicate', [\App\Http\Controllers\Admin\ExamController::class, 'duplicate'])->name('duplicate');
        });

        // إدارة المواد الدراسية القديمة
        Route::resource('subjects', \App\Http\Controllers\Admin\SubjectController::class);

        // إدارة الكورسات القديمة
        Route::resource('courses', \App\Http\Controllers\Admin\CourseController::class);

        // سجل النشاطات
        Route::get('/activity-log', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-log');
        Route::get('/activity-log/{activityLog}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('activity-log.show');
        Route::post('/activity-log/clear', [\App\Http\Controllers\Admin\ActivityLogController::class, 'destroy'])->name('activity-log.destroy');

        // سجلات التحقق الثنائي (2FA)
        Route::get('/two-factor-logs', [\App\Http\Controllers\Admin\TwoFactorLogController::class, 'index'])->name('two-factor-logs.index');

        // الإحصائيات
        Route::get('/statistics', [\App\Http\Controllers\Admin\StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('/statistics/users', [\App\Http\Controllers\Admin\StatisticsController::class, 'users'])->name('statistics.users');
        Route::get('/statistics/courses', [\App\Http\Controllers\Admin\StatisticsController::class, 'courses'])->name('statistics.courses');

        // CRM حصتك
        Route::prefix('crm')->name('crm.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CrmDashboardController::class, 'index'])->name('dashboard');
            Route::get('/pipeline', [\App\Http\Controllers\Admin\CrmPipelineController::class, 'index'])->name('pipeline');
            Route::get('/leads', [\App\Http\Controllers\Admin\CrmLeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/create', [\App\Http\Controllers\Admin\CrmLeadController::class, 'create'])->name('leads.create');
            Route::post('/leads', [\App\Http\Controllers\Admin\CrmLeadController::class, 'store'])->name('leads.store');
            Route::get('/leads/{salesLead}', [\App\Http\Controllers\Admin\CrmLeadController::class, 'show'])->name('leads.show');
            Route::get('/leads/{salesLead}/edit', [\App\Http\Controllers\Admin\CrmLeadController::class, 'edit'])->name('leads.edit');
            Route::put('/leads/{salesLead}', [\App\Http\Controllers\Admin\CrmLeadController::class, 'update'])->name('leads.update');
            Route::delete('/leads/{salesLead}', [\App\Http\Controllers\Admin\CrmLeadController::class, 'destroy'])->name('leads.destroy');
            Route::post('/leads/{salesLead}/assign', [\App\Http\Controllers\Admin\CrmLeadController::class, 'assign'])->name('leads.assign');
            Route::post('/leads/{salesLead}/transition', [\App\Http\Controllers\Admin\CrmLeadController::class, 'transition'])->name('leads.transition');
            Route::post('/leads/{salesLead}/note', [\App\Http\Controllers\Admin\CrmLeadController::class, 'addNote'])->name('leads.note');
            Route::get('/commissions', [\App\Http\Controllers\Admin\CrmCommissionController::class, 'index'])->name('commissions.index');
            Route::post('/commissions/{commission}/approve', [\App\Http\Controllers\Admin\CrmCommissionController::class, 'approve'])->name('commissions.approve');
            Route::get('/audit', [\App\Http\Controllers\Admin\CrmAuditLogController::class, 'index'])->name('audit.index');
            Route::get('/groups', [\App\Http\Controllers\Admin\CrmGroupController::class, 'index'])->name('groups.index');
            Route::get('/groups/create', [\App\Http\Controllers\Admin\CrmGroupController::class, 'create'])->name('groups.create');
            Route::post('/groups', [\App\Http\Controllers\Admin\CrmGroupController::class, 'store'])->name('groups.store');
            Route::get('/groups/{group}/edit', [\App\Http\Controllers\Admin\CrmGroupController::class, 'edit'])->name('groups.edit');
            Route::put('/groups/{group}', [\App\Http\Controllers\Admin\CrmGroupController::class, 'update'])->name('groups.update');
            Route::post('/groups/{group}/members', [\App\Http\Controllers\Admin\CrmGroupController::class, 'addMember'])->name('groups.members.store');
            Route::delete('/groups/{group}/members/{member}', [\App\Http\Controllers\Admin\CrmGroupController::class, 'removeMember'])->name('groups.members.destroy');
            Route::get('/reports', [\App\Http\Controllers\Admin\CrmReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [\App\Http\Controllers\Admin\CrmReportController::class, 'show'])->name('reports.show');
            Route::post('/reports/{report}/review', [\App\Http\Controllers\Admin\CrmReportController::class, 'review'])->name('reports.review');
            Route::get('/reports/{report}/download', [\App\Http\Controllers\Admin\CrmReportController::class, 'download'])->name('reports.download');
            Route::get('/sales-permissions', [\App\Http\Controllers\Admin\CrmSalesPermissionController::class, 'index'])->name('sales-permissions.index');
            Route::get('/sales-permissions/{employee}/edit', [\App\Http\Controllers\Admin\CrmSalesPermissionController::class, 'edit'])->name('sales-permissions.edit');
            Route::put('/sales-permissions/{employee}', [\App\Http\Controllers\Admin\CrmSalesPermissionController::class, 'update'])->name('sales-permissions.update');
        });

        // العملاء المحتملون ثم تحليلات المبيعات (مسارات تحت /sales)
        Route::get('/sales/leads', [\App\Http\Controllers\Admin\SalesLeadController::class, 'index'])->name('sales.leads.index');
        Route::get('/sales/leads/{salesLead}', [\App\Http\Controllers\Admin\SalesLeadController::class, 'show'])->name('sales.leads.show');
        Route::get('/sales', [\App\Http\Controllers\Admin\SalesAnalyticsController::class, 'index'])->name('sales.index');

        // إدارة الطلبات
        Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/sales-assign', [\App\Http\Controllers\Admin\OrderController::class, 'assignSalesOwner'])->name('orders.sales-assign');
        Route::post('/orders/{order}/sales-notes', [\App\Http\Controllers\Admin\OrderController::class, 'storeSalesNote'])->name('orders.sales-notes.store');
        Route::patch('/orders/{order}/receiving-wallet', [\App\Http\Controllers\Admin\OrderController::class, 'updateReceivingWallet'])->name('orders.receiving-wallet');
        Route::post('/orders/{order}/approve', [\App\Http\Controllers\Admin\OrderController::class, 'approve'])
            ->middleware('throttle:10,1')
            ->name('orders.approve');
        Route::post('/orders/{order}/refulfill-tutoring', [\App\Http\Controllers\Admin\OrderController::class, 'refulfillTutoring'])
            ->middleware('throttle:10,1')
            ->name('orders.refulfill-tutoring');
        Route::post('/orders/{order}/reconcile-fawaterak', [\App\Http\Controllers\Admin\OrderController::class, 'reconcileFawaterak'])
            ->middleware('throttle:10,1')
            ->name('orders.reconcile-fawaterak');
        Route::post('/orders/{order}/reject', [\App\Http\Controllers\Admin\OrderController::class, 'reject'])
            ->middleware('throttle:10,1')
            ->name('orders.reject');

        // إدارة الصلاحيات والأدوار
        Route::middleware('permission:users.permissions')->group(function () {
            Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
            Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
            Route::post('/roles/{role}/permissions', [\App\Http\Controllers\Admin\RoleController::class, 'updatePermissions'])->name('roles.update-permissions');

            // إدارة صلاحيات المستخدمين
            Route::get('/user-permissions', [\App\Http\Controllers\Admin\UserPermissionController::class, 'index'])->name('user-permissions.index');
            Route::get('/user-permissions/{user}', [\App\Http\Controllers\Admin\UserPermissionController::class, 'show'])->name('user-permissions.show');
            Route::put('/user-permissions/{user}', [\App\Http\Controllers\Admin\UserPermissionController::class, 'update'])->name('user-permissions.update');
            Route::put('/user-permissions/{user}/roles', [\App\Http\Controllers\Admin\UserPermissionController::class, 'updateRoles'])->name('user-permissions.update-roles');
            Route::post('/user-permissions/{user}/attach', [\App\Http\Controllers\Admin\UserPermissionController::class, 'attachPermission'])->name('user-permissions.attach');
            Route::post('/user-permissions/{user}/detach', [\App\Http\Controllers\Admin\UserPermissionController::class, 'detachPermission'])->name('user-permissions.detach');
        });

        // إدارة المحافظ الذكية
        Route::resource('wallets', \App\Http\Controllers\Admin\WalletController::class);
        Route::post('/wallets/transfer', [\App\Http\Controllers\Admin\WalletController::class, 'transfer'])->name('wallets.transfer');
        Route::get('/wallets/{wallet}/transactions', [\App\Http\Controllers\Admin\WalletController::class, 'transactions'])->name('wallets.transactions');
        Route::get('/wallets/{wallet}/reports', [\App\Http\Controllers\Admin\WalletController::class, 'reports'])->name('wallets.reports');
        Route::post('/wallets/{wallet}/generate-report', [\App\Http\Controllers\Admin\WalletController::class, 'generateReport'])->name('wallets.generate-report');

        // إدارة المحاضرات والجروبات
        Route::resource('lectures', \App\Http\Controllers\Admin\LectureController::class);
        Route::post('/lectures/{lecture}/sync-teams-attendance', [\App\Http\Controllers\Admin\LectureController::class, 'syncTeamsAttendance'])->name('lectures.sync-teams-attendance');

        // مركز المكتبات والمناهج (ماتريال + فيديو + مناهج الطلاب)
        Route::prefix('libraries')->name('libraries.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LibraryHubController::class, 'index'])->name('index');

            Route::prefix('materials')->name('materials.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'store'])->name('store');
                Route::post('/presign-upload', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'presignUpload'])->name('presign');
                Route::post('/complete-upload', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'completeUpload'])->name('complete');
                Route::post('/proxy-upload', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'proxyUpload'])->name('proxy');
                Route::post('/bulk-visibility', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'bulkVisibility'])->name('bulk-visibility');
                Route::get('/{material}/download', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'download'])->name('download');
                Route::get('/{material}/edit', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'edit'])->name('edit');
                Route::put('/{material}', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'update'])->name('update');
                Route::post('/{material}/toggle', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'toggleVisibility'])->name('toggle');
                Route::delete('/{material}', [\App\Http\Controllers\Admin\LibraryMaterialController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('folders')->name('folders.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'store'])->name('store');
                Route::get('/{folder}/edit', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'edit'])->name('edit');
                Route::put('/{folder}', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'update'])->name('update');
                Route::delete('/{folder}', [\App\Http\Controllers\Admin\LibraryFolderController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('videos')->name('videos.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'store'])->name('store');
                Route::post('/presign-upload', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'presignUpload'])->name('presign');
                Route::post('/complete-upload', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'completeUpload'])->name('complete');
                Route::post('/proxy-upload', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'proxyUpload'])->name('proxy');
                Route::get('/{libraryVideo}/edit', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'edit'])->name('edit');
                Route::put('/{libraryVideo}', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'update'])->name('update');
                Route::post('/{libraryVideo}/toggle', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'togglePublish'])->name('toggle');
                Route::delete('/{libraryVideo}', [\App\Http\Controllers\Admin\LibraryVideoController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('curriculum')->name('curriculum.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\CurriculumHubController::class, 'index'])->name('index');
                Route::get('/courses/{course}', [\App\Http\Controllers\Admin\CurriculumHubController::class, 'showCourse'])->name('course');
            });
        });

        // مكتبة المناهج التفاعلية (Manahij X) — بجانب هيكل الكورسات الحالي
        Route::prefix('curriculum-library')->name('curriculum-library.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'index'])->name('index');
            Route::get('/categories', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'categories'])->name('categories');
            Route::get('/categories/create', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'createCategory'])->name('categories.create');
            Route::post('/categories', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'storeCategory'])->name('categories.store');
            Route::get('/categories/{category}/edit', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'editCategory'])->name('categories.edit');
            Route::put('/categories/{category}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'destroyCategory'])->name('categories.destroy');
            Route::get('/items/create', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'createItem'])->name('items.create');
            Route::post('/items', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'storeItem'])->name('items.store');
            Route::get('/items/{item}/structure', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'show'])->name('items.structure');
            Route::post('/items/{item}/sections', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'storeSection'])->name('items.sections.store');
            Route::put('/items/{item}/sections/{section}', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'updateSection'])->name('items.sections.update');
            Route::delete('/items/{item}/sections/{section}', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'destroySection'])->name('items.sections.destroy');
            Route::post('/items/{item}/sections/{section}/materials/presign-upload', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'presignMaterialUpload'])->name('items.materials.presign-upload');
            Route::post('/items/{item}/sections/{section}/materials/proxy-upload', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'proxyMaterialUpload'])->name('items.materials.proxy-upload');
            Route::post('/items/{item}/sections/{section}/materials/complete-direct', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'completeMaterialDirectUpload'])->name('items.materials.complete-direct');
            Route::post('/items/{item}/sections/{section}/materials/multipart-init', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'multipartInitMaterial'])->name('items.materials.multipart-init');
            Route::post('/items/{item}/sections/{section}/materials/multipart-sign-part', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'multipartSignPartMaterial'])->name('items.materials.multipart-sign-part');
            Route::post('/items/{item}/sections/{section}/materials/multipart-complete', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'multipartCompleteMaterial'])->name('items.materials.multipart-complete');
            Route::post('/items/{item}/sections/{section}/materials/multipart-abort', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'multipartAbortMaterial'])->name('items.materials.multipart-abort');
            Route::post('/items/{item}/sections/{section}/materials/multipart-proxy-part', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'multipartProxyUploadPartMaterial'])->name('items.materials.multipart-proxy-part');
            Route::post('/items/{item}/sections/{section}/materials', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'storeMaterial'])->name('items.materials.store');
            Route::put('/items/{item}/materials/{material}', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'updateMaterial'])->name('items.materials.update');
            Route::get('/items/{item}/materials/{material}/conversion-status', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'materialPresentationConversionStatus'])->name('items.materials.conversion-status');
            Route::post('/items/{item}/materials/{material}/retry-conversion', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'retryMaterialPresentationConversion'])->name('items.materials.retry-conversion');
            Route::post('/items/{item}/materials/{material}/animation-video', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'storeMaterialAnimationVideo'])->name('items.materials.animation-video.store');
            Route::delete('/items/{item}/materials/{material}/animation-video', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'destroyMaterialAnimationVideo'])->name('items.materials.animation-video.destroy');
            Route::delete('/items/{item}/materials/{material}', [\App\Http\Controllers\Admin\CurriculumLibraryStructureController::class, 'destroyMaterial'])->name('items.materials.destroy');
            Route::get('/items/{item}/edit', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'editItem'])->name('items.edit');
            Route::post('/items/{item}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'updateItem']);
            Route::put('/items/{item}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'updateItem'])->name('items.update');
            Route::delete('/items/{item}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'destroyItem'])->name('items.destroy');
            Route::delete('/items/{item}/files/{file}', [\App\Http\Controllers\Admin\CurriculumLibraryController::class, 'destroyFile'])->name('items.files.destroy');
        });

        // إدارة الواجبات والمشاريع (مسار الكورس قبل المسارات الأخرى لتفادي التعارض)
        Route::get('/assignments/course/{course}', [\App\Http\Controllers\Admin\AssignmentController::class, 'indexByCourse'])->name('assignments.by-course');
        Route::resource('assignments', \App\Http\Controllers\Admin\AssignmentController::class);
        Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\Admin\AssignmentController::class, 'submissions'])->name('assignments.submissions');
        Route::post('/assignments/{assignment}/grade/{submission}', [\App\Http\Controllers\Admin\AssignmentController::class, 'grade'])->name('assignments.grade');

        // إدارة المهام
        Route::resource('tasks', \App\Http\Controllers\Admin\TaskController::class);
        Route::post('/tasks/{task}/complete', [\App\Http\Controllers\Admin\TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/comments', [\App\Http\Controllers\Admin\TaskController::class, 'addComment'])->name('tasks.add-comment');
        Route::post('/tasks/{task}/deliverables/{deliverable}/review', [\App\Http\Controllers\Admin\TaskController::class, 'reviewDeliverable'])->name('tasks.review-deliverable');

        // تم إيقاف مجتمع البيانات والذكاء الاصطناعي في لوحة الإدارة، لذا أزيلت جميع مساراته.

        // الإدارة العليا (من نحن وغيرها)
        Route::get('about', [\App\Http\Controllers\Admin\AboutPageController::class, 'index'])->name('about.index');
        Route::get('about/view', [\App\Http\Controllers\Admin\AboutPageController::class, 'viewPublic'])->name('about.view-public');

        Route::resource('contact-messages', \App\Http\Controllers\Admin\ContactMessageController::class)
            ->except(['create', 'store', 'edit', 'update']);
        Route::post('/contact-messages/{contactMessage}/mark-as-read', [\App\Http\Controllers\Admin\ContactMessageController::class, 'markAsRead'])->name('contact-messages.mark-as-read');
        Route::post('/contact-messages/{contactMessage}/mark-as-unread', [\App\Http\Controllers\Admin\ContactMessageController::class, 'markAsUnread'])->name('contact-messages.mark-as-unread');

        Route::get('/tutor-applications', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'hub'])->name('tutor-applications.hub');
        Route::get('/tutor-applications/list', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'index'])->name('tutor-applications.index');
        Route::get('/tutor-applications/activated', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'activated'])->name('tutor-applications.activated');
        Route::post('/tutor-applications/hire-manually', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'hireManually'])
            ->middleware('throttle:20,1')
            ->name('tutor-applications.hire-manually');
        Route::get('/tutor-applications/{tutorApplication}/files/{kind}', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'file'])
            ->where('kind', 'photo|id|certificate|video')
            ->name('tutor-applications.file');
        Route::get('/tutor-applications/{tutorApplication}', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'show'])->name('tutor-applications.show');
        
        Route::post('/tutor-applications/{tutorApplication}/invite-interview', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'inviteInterview'])->name('tutor-applications.invite-interview');
        Route::post('/tutor-applications/{tutorApplication}/interview-passed', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'markInterviewPassed'])->name('tutor-applications.interview-passed');
        Route::post('/tutor-applications/{tutorApplication}/unblock', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'unblock'])->name('tutor-applications.unblock');
        Route::post('/tutor-applications/{tutorApplication}/offer-contract', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'offerContract'])->name('tutor-applications.offer-contract');

        Route::get('/tutor-interview-slots', [\App\Http\Controllers\Admin\TutorInterviewSlotController::class, 'index'])->name('tutor-interview-slots.index');
        Route::post('/tutor-interview-slots', [\App\Http\Controllers\Admin\TutorInterviewSlotController::class, 'store'])->name('tutor-interview-slots.store');
        Route::put('/tutor-interview-slots/{slot}', [\App\Http\Controllers\Admin\TutorInterviewSlotController::class, 'update'])->name('tutor-interview-slots.update');
        Route::delete('/tutor-interview-slots/{slot}', [\App\Http\Controllers\Admin\TutorInterviewSlotController::class, 'destroy'])->name('tutor-interview-slots.destroy');

        Route::get('/hiring/settings', [\App\Http\Controllers\Admin\TutorHiringProcessSettingsController::class, 'edit'])->name('hiring.settings.edit');
        Route::put('/hiring/settings', [\App\Http\Controllers\Admin\TutorHiringProcessSettingsController::class, 'update'])->name('hiring.settings.update');

        Route::post('/tutor-applications/{tutorApplication}/approve', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'approve'])->name('tutor-applications.approve');
        Route::post('/tutor-applications/{tutorApplication}/activate', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'activate'])->name('tutor-applications.activate');
        Route::post('/tutor-applications/{tutorApplication}/reject', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'reject'])->name('tutor-applications.reject');
        Route::delete('/tutor-applications/{tutorApplication}', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'destroy'])->name('tutor-applications.destroy');

        // Legacy alias: older links used instructor-applications
        Route::redirect('/instructor-applications', '/admin/tutor-applications', 301);
        Route::redirect('/instructor-applications/list', '/admin/tutor-applications/list', 301);
        Route::redirect('/instructor-applications/activated', '/admin/tutor-applications/activated', 301);
        Route::get('/instructor-applications/{tutorApplication}', function (\App\Models\TutorApplication $tutorApplication) {
            return redirect()->route('admin.tutor-applications.show', $tutorApplication, 301);
        });
        Route::post('/instructor-applications/{tutorApplication}/approve', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'approve']);
        Route::post('/instructor-applications/{tutorApplication}/activate', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'activate']);
        Route::post('/instructor-applications/{tutorApplication}/reject', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'reject']);
        Route::delete('/instructor-applications/{tutorApplication}', [\App\Http\Controllers\Admin\TutorApplicationController::class, 'destroy']);

        Route::get('/hiring-form', [\App\Http\Controllers\Admin\HiringFormController::class, 'edit'])->name('hiring-form.edit');
        Route::put('/hiring-form', [\App\Http\Controllers\Admin\HiringFormController::class, 'update'])->name('hiring-form.update');
        Route::post('/hiring-form/fields', [\App\Http\Controllers\Admin\HiringFormController::class, 'storeField'])->name('hiring-form.fields.store');
        Route::put('/hiring-form/fields/{field}', [\App\Http\Controllers\Admin\HiringFormController::class, 'updateField'])->name('hiring-form.fields.update');
        Route::delete('/hiring-form/fields/{field}', [\App\Http\Controllers\Admin\HiringFormController::class, 'destroyField'])->name('hiring-form.fields.destroy');
        Route::post('/hiring-form/fields/{field}/move/{direction}', [\App\Http\Controllers\Admin\HiringFormController::class, 'move'])
            ->whereIn('direction', ['up', 'down'])
            ->name('hiring-form.fields.move');
        Route::post('/hiring-form/reorder', [\App\Http\Controllers\Admin\HiringFormController::class, 'reorder'])->name('hiring-form.reorder');

        Route::get('free-trial-bookings/create', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'create'])->name('free-trial-bookings.create');
        Route::post('free-trial-bookings', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'store'])->name('free-trial-bookings.store');
        Route::patch('free-trial-bookings/{freeTrialBooking}/status', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'updateStatus'])->name('free-trial-bookings.update-status');
        Route::get('free-trial-bookings', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'index'])->name('free-trial-bookings.index');
        Route::get('free-trial-bookings/{freeTrialBooking}', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'show'])->name('free-trial-bookings.show');
        Route::delete('free-trial-bookings/{freeTrialBooking}', [\App\Http\Controllers\Admin\FreeTrialBookingController::class, 'destroy'])->name('free-trial-bookings.destroy');

        Route::resource('faq', \App\Http\Controllers\Admin\FAQController::class);

        Route::resource('site-services', \App\Http\Controllers\Admin\SiteServiceController::class)->except(['show']);
        Route::resource('site-testimonials', \App\Http\Controllers\Admin\SiteTestimonialController::class)->except(['show']);

        Route::get('/system-settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'edit'])->name('system-settings.edit');
        Route::put('/system-settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'update'])->name('system-settings.update');
        Route::get('/payment-gateways', [\App\Http\Controllers\Admin\PaymentGatewaysController::class, 'index'])->name('payment-gateways.index');
        Route::put('/payment-gateways', [\App\Http\Controllers\Admin\PaymentGatewaysController::class, 'update'])->name('payment-gateways.update');
        Route::post('/payment-gateways/paypal/test', [\App\Http\Controllers\Admin\PaymentGatewaysController::class, 'testPaypal'])
            ->middleware('throttle:8,1')
            ->name('payment-gateways.paypal.test');
        Route::post('/system-settings/paypal/test', [\App\Http\Controllers\Admin\PaymentGatewaysController::class, 'testPaypal'])
            ->middleware('throttle:8,1')
            ->name('system-settings.paypal.test');
        Route::post('/system-settings/two-factor/enable-request', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'requestTwoFactorEnable'])
            ->middleware('throttle:10,1')
            ->name('system-settings.two-factor.enable-request');
        Route::get('/system-settings/two-factor/confirm', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'showTwoFactorConfirm'])
            ->name('system-settings.two-factor.confirm');
        Route::post('/system-settings/two-factor/confirm', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'confirmTwoFactorEnable'])
            ->middleware('throttle:20,1')
            ->name('system-settings.two-factor.confirm.submit');
        Route::post('/system-settings/two-factor/resend', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'resendTwoFactorEnableCode'])
            ->middleware('throttle:5,1')
            ->name('system-settings.two-factor.resend');
        Route::post('/system-settings/two-factor/disable', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'disablePlatformTwoFactor'])
            ->middleware('throttle:10,1')
            ->name('system-settings.two-factor.disable');
        Route::post('/system-settings/wipe-site-data', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'wipeSiteData'])
            ->middleware('throttle:3,10')
            ->name('system-settings.wipe-site-data');

        // إدارة الأسعار والباقات
        Route::resource('packages', \App\Http\Controllers\Admin\PackageController::class);
        Route::post('/packages/{course}/update-price', [\App\Http\Controllers\Admin\PackageController::class, 'updatePrice'])->name('packages.update-price');
        Route::post('/packages/bulk-update', [\App\Http\Controllers\Admin\PackageController::class, 'updateBulkPrices'])->name('packages.bulk-update');

        // باقات خدمات الطالب ورصيد الحصص
        Route::resource('service-packages', \App\Http\Controllers\Admin\ServicePackageController::class)->except(['show']);
        Route::get('/service-packages-grant', [\App\Http\Controllers\Admin\ServicePackageController::class, 'grantForm'])->name('service-packages.grant');
        Route::post('/service-packages-grant', [\App\Http\Controllers\Admin\ServicePackageController::class, 'grantStore'])->name('service-packages.grant.store');
        Route::post('/service-packages/{servicePackage}/toggle-status', [\App\Http\Controllers\Admin\ServicePackageController::class, 'toggleStatus'])->name('service-packages.toggle-status');
        Route::resource('service-package-pricing-rules', \App\Http\Controllers\Admin\ServicePackagePricingRuleController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::get('/service-session-rates', [\App\Http\Controllers\Admin\ServiceSessionRateController::class, 'index'])
            ->name('service-session-rates.index');
        Route::post('/service-session-rates', [\App\Http\Controllers\Admin\ServiceSessionRateController::class, 'save'])
            ->name('service-session-rates.save');
        Route::get('/student-entitlements', [\App\Http\Controllers\Admin\StudentEntitlementController::class, 'index'])->name('student-entitlements.index');
        Route::get('/student-entitlements/create', [\App\Http\Controllers\Admin\StudentEntitlementController::class, 'create'])->name('student-entitlements.create');
        Route::post('/student-entitlements', [\App\Http\Controllers\Admin\StudentEntitlementController::class, 'store'])->name('student-entitlements.store');
        Route::post('/student-entitlements/{studentEntitlement}/adjust', [\App\Http\Controllers\Admin\StudentEntitlementController::class, 'adjust'])->name('student-entitlements.adjust');
        // إدارة الإشعارات
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('index');
            Route::get('/inbox', [\App\Http\Controllers\Admin\NotificationController::class, 'inbox'])->name('inbox');
            Route::post('/inbox/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'inboxMarkAllRead'])
                ->middleware('throttle:30,1')
                ->name('inbox.mark-all-read');
            Route::get('/create', [\App\Http\Controllers\Admin\NotificationController::class, 'create'])->name('create');
            Route::get('/{notification}/open-support-ticket', [\App\Http\Controllers\Admin\NotificationController::class, 'openSupportTicket'])
                ->middleware('throttle:60,1')
                ->name('open-support-ticket');
            Route::post('/', [\App\Http\Controllers\Admin\NotificationController::class, 'store'])
                ->middleware('throttle:20,5')
                ->name('store');
            Route::get('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'show'])->name('show');
            Route::delete('/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])
                ->middleware('throttle:30,1')
                ->name('destroy');
            Route::post('/quick-send', [\App\Http\Controllers\Admin\NotificationController::class, 'quickSend'])
                ->middleware('throttle:30,5')
                ->name('quick-send');
            Route::get('/target-count', [\App\Http\Controllers\Admin\NotificationController::class, 'getTargetCount'])
                ->middleware('throttle:60,1')
                ->name('target-count');
            Route::post('/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])
                ->middleware('throttle:10,1')
                ->name('mark-all-read');
            Route::post('/cleanup', [\App\Http\Controllers\Admin\NotificationController::class, 'cleanup'])
                ->middleware('throttle:5,10')
                ->name('cleanup');
            Route::get('/statistics', [\App\Http\Controllers\Admin\NotificationController::class, 'statistics'])->name('statistics');
        });

        // إشعارات الموظفين
        Route::prefix('employee-notifications')->name('employee-notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EmployeeNotificationController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\EmployeeNotificationController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\EmployeeNotificationController::class, 'store'])
                ->middleware(ThrottleRequests::using('admin-employee-notification-store'))
                ->name('store');
            Route::get('/{notification}', [\App\Http\Controllers\Admin\EmployeeNotificationController::class, 'show'])->name('show');
        });

        // إشعارات البريد (Gmail) — حملات بريدية
        Route::prefix('email-notifications')->name('email-broadcasts.')->group(function () {
            Route::get('/{audience}', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'index'])->name('index');
            Route::get('/{audience}/create', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'create'])->name('create');
            Route::post('/{audience}', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'store'])->name('store');
            Route::get('/{audience}/{email_broadcast}', [\App\Http\Controllers\Admin\EmailBroadcastController::class, 'show'])->name('show');
        });

        // إدارة تسجيل الطلاب في الكورسات الأونلاين
        Route::prefix('online-enrollments')->name('online-enrollments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'store'])->name('store');
            Route::post('/quick-activate', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'quickActivate'])->name('quick-activate');
            Route::get('/search/by-phone', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'searchStudentByPhone'])->name('search-by-phone');
            Route::get('/{enrollment}', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'show'])->name('show');
            Route::post('/{enrollment}/activate', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'activate'])->name('activate');
            Route::post('/{enrollment}/deactivate', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'deactivate'])->name('deactivate');
            Route::post('/{enrollment}/update-progress', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'updateProgress'])->name('update-progress');
            Route::post('/{enrollment}/update-notes', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'updateNotes'])->name('update-notes');
            Route::delete('/{enrollment}', [\App\Http\Controllers\Admin\StudentEnrollmentController::class, 'destroy'])->name('destroy');
        });

        // إدارة مصادر الفيديو (Bunny وغيرها)
        Route::prefix('video-providers')->name('video-providers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\VideoProviderController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\VideoProviderController::class, 'store'])->name('store');
            Route::put('/{videoProvider}', [\App\Http\Controllers\Admin\VideoProviderController::class, 'update'])->name('update');
        });

        // إدارة الموظفين
        Route::resource('employees', \App\Http\Controllers\Admin\EmployeeController::class);
        Route::resource('employee-jobs', \App\Http\Controllers\Admin\EmployeeJobController::class);
        Route::prefix('academic-supervision')->name('academic-supervision.')->middleware('permission:academic_supervision.manage')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'index'])->name('index');
            Route::get('/search-students', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'searchStudents'])->name('search-students');
            Route::get('/supervisors/{supervisor}', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'show'])->name('supervisors.show');
            Route::post('/supervisors/{supervisor}/students', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'attachStudent'])->name('supervisors.students.attach');
            Route::delete('/supervisors/{supervisor}/students/{student}', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'detachStudent'])->name('supervisors.students.detach');
            Route::get('/supervisors/{supervisor}/students/{student}', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'studentShow'])->name('supervisors.students.show');
            Route::get('/supervisors/{supervisor}/meetings/{meeting}/observe', [\App\Http\Controllers\Admin\AcademicSupervisionController::class, 'observeMeeting'])->name('supervisors.meetings.observe');
        });
        Route::resource('employee-tasks', \App\Http\Controllers\Admin\EmployeeTaskController::class);

        // إدارة الإجازات
        Route::get('/leaves', [\App\Http\Controllers\Admin\AdminLeaveController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/{leave}', [\App\Http\Controllers\Admin\AdminLeaveController::class, 'show'])->name('leaves.show');
        Route::post('/leaves/{leave}/approve', [\App\Http\Controllers\Admin\AdminLeaveController::class, 'approve'])->name('leaves.approve');
        Route::post('/leaves/{leave}/reject', [\App\Http\Controllers\Admin\AdminLeaveController::class, 'reject'])->name('leaves.reject');

        // طلبات المدربين للإدارة
        Route::prefix('instructor-requests')->name('instructor-requests.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\InstructorRequestController::class, 'index'])->name('index');
            Route::get('/{instructorRequest}', [\App\Http\Controllers\Admin\InstructorRequestController::class, 'show'])->name('show');
            Route::post('/{instructorRequest}/respond', [\App\Http\Controllers\Admin\InstructorRequestController::class, 'respond'])->name('respond');
        });

        // الرقابة والجودة
        Route::prefix('quality-control')->name('quality-control.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\QualityControlController::class, 'index'])->name('index');
            Route::get('/students', [\App\Http\Controllers\Admin\QualityControlController::class, 'students'])->name('students');
            Route::get('/instructors', [\App\Http\Controllers\Admin\QualityControlController::class, 'instructors'])->name('instructors');
            Route::get('/instructors/{instructor}', [\App\Http\Controllers\Admin\QualityControlController::class, 'instructorShow'])->name('instructors.show');
            Route::get('/instructors/{instructor}/export', [\App\Http\Controllers\Admin\QualityControlController::class, 'instructorExport'])->name('instructors.export');
            Route::get('/employees', [\App\Http\Controllers\Admin\QualityControlController::class, 'employees'])->name('employees');
            Route::get('/operations', [\App\Http\Controllers\Admin\QualityControlController::class, 'operations'])->name('operations');
        });

        // إدارة الرسائل والتقارير
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MessagesController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\MessagesController::class, 'create'])->name('create');
            Route::post('/send-single', [\App\Http\Controllers\Admin\MessagesController::class, 'sendSingle'])->name('send-single');
            Route::post('/send-bulk', [\App\Http\Controllers\Admin\MessagesController::class, 'sendBulk'])->name('send-bulk');

            // التقارير الشهرية
            Route::get('/monthly-reports', [\App\Http\Controllers\Admin\MessagesController::class, 'monthlyReports'])->name('monthly-reports');
            Route::post('/generate-monthly-reports', [\App\Http\Controllers\Admin\MessagesController::class, 'generateMonthlyReports'])->name('generate-monthly-reports');

            // قوالب الرسائل
            Route::get('/templates', [\App\Http\Controllers\Admin\MessagesController::class, 'templates'])->name('templates');
            Route::post('/templates', [\App\Http\Controllers\Admin\MessagesController::class, 'storeTemplate'])->name('templates.store');
            Route::delete('/templates/{template}', [\App\Http\Controllers\Admin\MessagesController::class, 'destroyTemplate'])->name('templates.destroy');

            // إعدادات WhatsApp API
            Route::get('/settings', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'settings'])->name('settings');
            Route::post('/save-api-settings', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'saveApiSettings'])->name('save-api-settings');
            Route::post('/test-api', [\App\Http\Controllers\Admin\WhatsAppSettingsController::class, 'testApi'])->name('test-api');

            // مسارات عرض الرسائل الفردية يجب أن تأتي في النهاية حتى لا تعترض المسارات الثابتة
            Route::get('/{message}', [\App\Http\Controllers\Admin\MessagesController::class, 'show'])->name('show');
            Route::post('/{message}/resend', [\App\Http\Controllers\Admin\MessagesController::class, 'resend'])->name('resend');
            Route::delete('/{message}', [\App\Http\Controllers\Admin\MessagesController::class, 'destroy'])->name('destroy');
        });

        // إدارة المحاسبة
        Route::resource('invoices', \App\Http\Controllers\Admin\InvoiceController::class)
            ->middleware('throttle:60,1')
            ->except(['update', 'destroy']);
        Route::match(['post', 'put', 'patch'], '/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'update'])->middleware('throttle:20,5')->name('invoices.update');
        Route::delete('/invoices/{invoice}', [\App\Http\Controllers\Admin\InvoiceController::class, 'destroy'])->middleware('throttle:10,1')->name('invoices.destroy');

        Route::resource('payments', \App\Http\Controllers\Admin\PaymentController::class)
            ->middleware('throttle:60,1')
            ->except(['update', 'destroy']);
        Route::post('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'update'])->middleware('throttle:20,5')->name('payments.update');
        Route::delete('/payments/{payment}', [\App\Http\Controllers\Admin\PaymentController::class, 'destroy'])->middleware('throttle:10,1')->name('payments.destroy');

        Route::resource('transactions', \App\Http\Controllers\Admin\TransactionController::class)
            ->middleware('throttle:60,1')
            ->except(['update', 'destroy']);
        Route::post('/transactions/{transaction}', [\App\Http\Controllers\Admin\TransactionController::class, 'update'])->middleware('throttle:20,5')->name('transactions.update');
        Route::delete('/transactions/{transaction}', [\App\Http\Controllers\Admin\TransactionController::class, 'destroy'])->middleware('throttle:10,1')->name('transactions.destroy');

        Route::resource('wallets', \App\Http\Controllers\Admin\WalletController::class)
            ->middleware('throttle:60,1')
            ->except(['update', 'destroy']);
        Route::post('/wallets/transfer', [\App\Http\Controllers\Admin\WalletController::class, 'transfer'])->middleware('throttle:20,5')->name('wallets.transfer');
        Route::post('/wallets/{wallet}', [\App\Http\Controllers\Admin\WalletController::class, 'update'])->middleware('throttle:20,5')->name('wallets.update');
        Route::delete('/wallets/{wallet}', [\App\Http\Controllers\Admin\WalletController::class, 'destroy'])->middleware('throttle:10,1')->name('wallets.destroy');

        Route::resource('expenses', \App\Http\Controllers\Admin\ExpenseController::class)->except(['destroy']);
        Route::post('/expenses/{expense}/approve', [\App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->middleware('throttle:10,1')->name('expenses.approve');
        Route::post('/expenses/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->middleware('throttle:10,1')->name('expenses.reject');
        Route::post('/expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'update'])->middleware('throttle:20,5')->name('expenses.update');
        Route::delete('/expenses/{expense}', [\App\Http\Controllers\Admin\ExpenseController::class, 'destroy'])->middleware('throttle:10,1')->name('expenses.destroy');

        // نظام الدعم الفني
        Route::get('/support-tickets', [\App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::get('/support-tickets/{ticket}', [\App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('support-tickets.show');
        Route::post('/support-tickets/{ticket}/status', [\App\Http\Controllers\Admin\SupportTicketController::class, 'updateStatus'])->name('support-tickets.status');
        Route::post('/support-tickets/{ticket}/reply', [\App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('support-tickets.reply');

        // كورسات بريفيت — رسائل + استقبال
        Route::get('/private-courses/threads', [\App\Http\Controllers\Admin\PrivateCoursesOpsController::class, 'threads'])->name('private-courses.threads');
        Route::get('/private-courses/threads/{thread}', [\App\Http\Controllers\Admin\PrivateCoursesOpsController::class, 'showThread'])->name('private-courses.threads.show');
        Route::post('/private-courses/threads/{thread}/reply', [\App\Http\Controllers\Admin\PrivateCoursesOpsController::class, 'reply'])->name('private-courses.threads.reply');
        Route::get('/private-courses/receptions', [\App\Http\Controllers\Admin\PrivateCoursesOpsController::class, 'receptions'])->name('private-courses.receptions');
        Route::put('/private-courses/receptions/{reception}', [\App\Http\Controllers\Admin\PrivateCoursesOpsController::class, 'updateReception'])->name('private-courses.receptions.update');
        Route::get('/support-inquiry-categories', [\App\Http\Controllers\Admin\SupportInquiryCategoryController::class, 'index'])->name('support-inquiry-categories.index');
        Route::post('/support-inquiry-categories', [\App\Http\Controllers\Admin\SupportInquiryCategoryController::class, 'store'])->name('support-inquiry-categories.store');
        Route::put('/support-inquiry-categories/{support_inquiry_category}', [\App\Http\Controllers\Admin\SupportInquiryCategoryController::class, 'update'])->name('support-inquiry-categories.update');
        Route::delete('/support-inquiry-categories/{support_inquiry_category}', [\App\Http\Controllers\Admin\SupportInquiryCategoryController::class, 'destroy'])->name('support-inquiry-categories.destroy');

        // استشارات المدربين (مدفوعة)
        Route::get('/consultations', [\App\Http\Controllers\Admin\ConsultationController::class, 'index'])->name('consultations.index');
        Route::post('/consultations/settings', [\App\Http\Controllers\Admin\ConsultationController::class, 'updateSettings'])->name('consultations.settings');
        Route::get('/consultations/requests/{consultation}', [\App\Http\Controllers\Admin\ConsultationController::class, 'show'])->name('consultations.show');
        Route::post('/consultations/requests/{consultation}/confirm-payment', [\App\Http\Controllers\Admin\ConsultationController::class, 'confirmPayment'])->name('consultations.confirm-payment');
        Route::post('/consultations/requests/{consultation}/schedule', [\App\Http\Controllers\Admin\ConsultationController::class, 'schedule'])->name('consultations.schedule');
        Route::post('/consultations/requests/{consultation}/notes', [\App\Http\Controllers\Admin\ConsultationController::class, 'updateNotes'])->name('consultations.notes');
        Route::post('/consultations/requests/{consultation}/cancel', [\App\Http\Controllers\Admin\ConsultationController::class, 'cancel'])->name('consultations.cancel');
        Route::post('/consultations/requests/{consultation}/complete', [\App\Http\Controllers\Admin\ConsultationController::class, 'markCompleted'])->name('consultations.complete');
        Route::get('/placement', [\App\Http\Controllers\Admin\PlacementController::class, 'index'])->name('placement.index');
        Route::get('/placement/create', [\App\Http\Controllers\Admin\PlacementController::class, 'create'])->name('placement.create');
        Route::post('/placement', [\App\Http\Controllers\Admin\PlacementController::class, 'store'])->name('placement.store');
        Route::get('/placement/student-context', [\App\Http\Controllers\Admin\PlacementController::class, 'studentContext'])->name('placement.student-context');
        Route::get('/placement/slots', [\App\Http\Controllers\Admin\PlacementController::class, 'slots'])->name('placement.slots');
        Route::delete('/placement/private/{oneToOneSession}', [\App\Http\Controllers\Admin\PlacementController::class, 'destroyPrivate'])->name('placement.destroy-private');
        Route::patch('/placement/private/{oneToOneSession}/schedule', [\App\Http\Controllers\Admin\PlacementController::class, 'updatePrivateSchedule'])->name('placement.update-private-schedule');
        Route::patch('/placement/group/{tutoringGroupBooking}/schedule', [\App\Http\Controllers\Admin\PlacementController::class, 'updateGroupSchedule'])->name('placement.update-group-schedule');
        Route::delete('/placement/group/{tutoringGroupBooking}', [\App\Http\Controllers\Admin\PlacementController::class, 'destroyGroup'])->name('placement.destroy-group');

        Route::get('/student-lesson-cleanup', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'index'])->name('student-lesson-cleanup.index');
        Route::post('/student-lesson-cleanup/bulk', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'bulkDestroy'])->name('student-lesson-cleanup.bulk');
        // POST (not DELETE) so single-row deletes work reliably without method spoof / nested forms.
        Route::post('/student-lesson-cleanup/one-to-one/{oneToOneSession}', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'destroyOneToOne'])->name('student-lesson-cleanup.destroy-one-to-one');
        Route::post('/student-lesson-cleanup/group/{tutoringGroupBooking}', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'destroyGroup'])->name('student-lesson-cleanup.destroy-group');
        Route::post('/student-lesson-cleanup/meetings/{classroomMeeting}', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'destroyMeeting'])->name('student-lesson-cleanup.destroy-meeting');
        Route::post('/student-lesson-cleanup/live/{liveSession}', [\App\Http\Controllers\Admin\StudentLessonCleanupController::class, 'destroyLive'])->name('student-lesson-cleanup.destroy-live');

        Route::get('/one-to-one-sessions', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'index'])->name('one-to-one-sessions.index');
        Route::get('/one-to-one-sessions/create', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'create'])->name('one-to-one-sessions.create');
        Route::post('/one-to-one-sessions', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'store'])->name('one-to-one-sessions.store');
        Route::get('/one-to-one-sessions/{oneToOneSession}', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'show'])->name('one-to-one-sessions.show');
        Route::patch('/one-to-one-sessions/{oneToOneSession}/schedule', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'updateSchedule'])->name('one-to-one-sessions.update-schedule');
        Route::post('/one-to-one-sessions/{oneToOneSession}/unlock-for-student', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'unlockForStudent'])->name('one-to-one-sessions.unlock-for-student');
        Route::post('/one-to-one-sessions/{oneToOneSession}/revoke-unlock', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'revokeUnlockForStudent'])->name('one-to-one-sessions.revoke-unlock');
        Route::delete('/one-to-one-sessions/{oneToOneSession}', [\App\Http\Controllers\Admin\OneToOneSessionController::class, 'destroy'])->name('one-to-one-sessions.destroy');

        Route::get('/one-to-one-report-queue', [\App\Http\Controllers\Admin\OneToOneReportQueueController::class, 'index'])->name('one-to-one-report-queue.index');

        Route::get('/platform-wallets', [\App\Http\Controllers\Admin\PlatformWalletHubController::class, 'index'])->name('platform-wallets.index');

        Route::get('/parents', [\App\Http\Controllers\Admin\ParentChildController::class, 'index'])->name('parents.index');
        Route::get('/parents/{user}', [\App\Http\Controllers\Admin\ParentChildController::class, 'show'])->name('parents.show');
        Route::post('/parents/{user}/link', [\App\Http\Controllers\Admin\ParentChildController::class, 'link'])->name('parents.link');
        Route::post('/parents/{user}/unlink', [\App\Http\Controllers\Admin\ParentChildController::class, 'unlink'])->name('parents.unlink');

        Route::get('/teacher-switch', [\App\Http\Controllers\Admin\TeacherSwitchController::class, 'create'])->name('teacher-switch.create');
        Route::post('/teacher-switch', [\App\Http\Controllers\Admin\TeacherSwitchController::class, 'store'])->name('teacher-switch.store');

        Route::get('/accounting/instructor-accounts', [\App\Http\Controllers\Admin\InstructorAccountController::class, 'index'])->name('accounting.instructor-accounts.index');
        Route::get('/accounting/instructor-accounts/{instructor}', [\App\Http\Controllers\Admin\InstructorAccountController::class, 'show'])->name('accounting.instructor-accounts.show');

        Route::get('/accounting/reports', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'index'])->name('accounting.reports');
        Route::get('/accounting/reports/export', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'export'])->name('accounting.reports.export');
        Route::get('/accounting/reports/invoices', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'invoices'])->name('accounting.reports.invoices');
        Route::get('/accounting/reports/payments', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'payments'])->name('accounting.reports.payments');
        Route::get('/accounting/reports/transactions', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'transactions'])->name('accounting.reports.transactions');
        Route::get('/accounting/reports/expenses', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'expenses'])->name('accounting.reports.expenses');
        Route::get('/accounting/reports/wallets', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'wallets'])->name('accounting.reports.wallets');
        Route::get('/accounting/reports/orders', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'orders'])->name('accounting.reports.orders');
        Route::get('/accounting/reports/payment-gateway', [\App\Http\Controllers\Admin\AccountingReportsController::class, 'paymentGateway'])->name('accounting.reports.payment-gateway');

        // الماليات الخاصة بالمدربين (قائمة المدربين ثم المطلوب دفعه لكل مدرب)
        Route::prefix('salaries')->name('salaries.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SalaryController::class, 'index'])->name('index');
            Route::get('/instructor/{instructor}', [\App\Http\Controllers\Admin\SalaryController::class, 'instructor'])->name('instructor');
            Route::post('/instructor/{instructor}/pay-now/{agreement}', [\App\Http\Controllers\Admin\SalaryController::class, 'payNowFromAgreement'])->name('pay-now-from-agreement');
            Route::get('/pay/{payment}', [\App\Http\Controllers\Admin\SalaryController::class, 'pay'])->name('pay');
            Route::post('/pay/{payment}', [\App\Http\Controllers\Admin\SalaryController::class, 'markPaid'])->name('mark-paid');
        });

        // التقارير الشاملة
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
            Route::get('/users', [\App\Http\Controllers\Admin\ReportsController::class, 'users'])->name('users');
            Route::get('/courses', [\App\Http\Controllers\Admin\ReportsController::class, 'courses'])->name('courses');
            Route::get('/financial', [\App\Http\Controllers\Admin\ReportsController::class, 'financial'])->name('financial');
            Route::get('/academic', [\App\Http\Controllers\Admin\ReportsController::class, 'academic'])->name('academic');
            Route::get('/activities', [\App\Http\Controllers\Admin\ReportsController::class, 'activities'])->name('activities');
            Route::get('/comprehensive', [\App\Http\Controllers\Admin\ReportsController::class, 'comprehensive'])->name('comprehensive');

            // تصدير التقارير
            Route::get('/export/users', [\App\Http\Controllers\Admin\ReportsController::class, 'exportUsers'])
                ->middleware('throttle:10,5')
                ->name('export.users');
            Route::get('/export/courses', [\App\Http\Controllers\Admin\ReportsController::class, 'exportCourses'])
                ->middleware('throttle:10,5')
                ->name('export.courses');
            Route::get('/export/financial', [\App\Http\Controllers\Admin\ReportsController::class, 'exportFinancial'])
                ->middleware('throttle:10,5')
                ->name('export.financial');
            Route::get('/export/academic', [\App\Http\Controllers\Admin\ReportsController::class, 'exportAcademic'])
                ->middleware('throttle:10,5')
                ->name('export.academic');
            Route::get('/export/comprehensive', [\App\Http\Controllers\Admin\ReportsController::class, 'exportComprehensive'])
                ->middleware('throttle:5,10')
                ->name('export.comprehensive');
        });

        // تقارير n8n للبث المباشر
        Route::prefix('n8n')->name('n8n.')->group(function () {
            Route::get('/live-session-reports', [\App\Http\Controllers\Admin\N8nLiveReportsController::class, 'index'])
                ->name('live-session-reports.index');
            Route::get('/settings', [\App\Http\Controllers\Admin\N8nSettingsController::class, 'index'])
                ->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\N8nSettingsController::class, 'update'])
                ->name('settings.update');
            Route::post('/settings/test-connection', [\App\Http\Controllers\Admin\N8nSettingsController::class, 'testConnection'])
                ->middleware('throttle:10,1')
                ->name('settings.test-connection');
        });
        Route::prefix('installments')->name('installments.')->group(function () {
            Route::resource('plans', \App\Http\Controllers\Admin\InstallmentPlanController::class);
            Route::resource('agreements', \App\Http\Controllers\Admin\InstallmentAgreementController::class);
            Route::post('/agreements/payments/{payment}/mark', [\App\Http\Controllers\Admin\InstallmentAgreementController::class, 'markPayment'])
                ->name('agreements.mark-payment');
        });

        // نظام الاتفاقيات للمدربين
        Route::prefix('agreements')->name('agreements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'store'])
                ->middleware('throttle:20,5')
                ->name('store');
            Route::get('/{agreement}', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'show'])->name('show');
            Route::get('/{agreement}/edit', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'edit'])->name('edit');
            Route::put('/{agreement}', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'update'])
                ->middleware('throttle:20,5')
                ->name('update');
            Route::delete('/{agreement}', [\App\Http\Controllers\Admin\InstructorAgreementController::class, 'destroy'])
                ->middleware('throttle:10,1')
                ->name('destroy');
        });

        // نظام اتفاقيات الموظفين
        Route::prefix('employee-agreements')->name('employee-agreements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'store'])
                ->middleware('throttle:20,5')
                ->name('store');
            Route::post('payments/{payment}/mark-paid', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'markPaymentPaid'])->name('payments.mark-paid');
            Route::post('{employeeAgreement}/payments', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'storePayment'])->name('payments.store');
            Route::get('/{employeeAgreement}', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'show'])->name('show');
            Route::get('/{employeeAgreement}/edit', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'edit'])->name('edit');
            Route::put('/{employeeAgreement}', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'update'])
                ->middleware('throttle:20,5')
                ->name('update');
            Route::delete('/{employeeAgreement}', [\App\Http\Controllers\Admin\EmployeeAgreementController::class, 'destroy'])
                ->middleware('throttle:10,1')
                ->name('destroy');
        });

        // إدارة طلبات السحب للمدربين
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\WithdrawalRequestController::class, 'index'])->name('index');
            Route::get('/{withdrawal}', [\App\Http\Controllers\Admin\WithdrawalRequestController::class, 'show'])->name('show');
            Route::post('/{withdrawal}/approve', [\App\Http\Controllers\Admin\WithdrawalRequestController::class, 'approve'])
                ->middleware('throttle:10,1')
                ->name('approve');
            Route::post('/{withdrawal}/reject', [\App\Http\Controllers\Admin\WithdrawalRequestController::class, 'reject'])
                ->middleware('throttle:10,1')
                ->name('reject');
            Route::post('/{withdrawal}/complete', [\App\Http\Controllers\Admin\WithdrawalRequestController::class, 'complete'])
                ->middleware('throttle:10,1')
                ->name('complete');
        });

        // إدارة التسويق
        Route::get('/personal-branding', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'index'])->name('personal-branding.index');
        Route::resource('popup-ads', \App\Http\Controllers\Admin\PopupAdController::class)->except(['show']);
        Route::get('/personal-branding/{personal_branding}/edit', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'edit'])->name('personal-branding.edit');
        Route::put('/personal-branding/{personal_branding}', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('personal-branding.update');
        Route::delete('/personal-branding/{personal_branding}', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'destroy'])
            ->middleware('throttle:20,1')
            ->name('personal-branding.destroy');
        Route::get('/personal-branding/{personal_branding}', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'show'])->name('personal-branding.show');
        Route::post('/personal-branding/{personal_branding}/approve', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'approve'])->name('personal-branding.approve');
        Route::post('/personal-branding/{personal_branding}/reject', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'reject'])->name('personal-branding.reject');
        Route::post('/personal-branding/{personal_branding}/send-back', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'sendBackForReview'])->name('personal-branding.send-back');
        Route::post('/personal-branding/{personal_branding}/consultation-pricing', [\App\Http\Controllers\Admin\InstructorPersonalBrandingController::class, 'updateConsultationPricing'])->name('personal-branding.consultation-pricing');
        Route::get('/curriculum-types', [\App\Http\Controllers\Admin\HesetakCurriculumTypeController::class, 'index'])->name('curriculum-types.index');
        Route::post('/curriculum-types', [\App\Http\Controllers\Admin\HesetakCurriculumTypeController::class, 'store'])->middleware('throttle:30,1')->name('curriculum-types.store');
        Route::put('/curriculum-types/{curriculumType}', [\App\Http\Controllers\Admin\HesetakCurriculumTypeController::class, 'update'])->middleware('throttle:30,1')->name('curriculum-types.update');
        Route::post('/curriculum-types/{curriculumType}/toggle', [\App\Http\Controllers\Admin\HesetakCurriculumTypeController::class, 'toggle'])->name('curriculum-types.toggle');
        Route::delete('/curriculum-types/{curriculumType}', [\App\Http\Controllers\Admin\HesetakCurriculumTypeController::class, 'destroy'])->middleware('throttle:20,1')->name('curriculum-types.destroy');
        Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class);
        Route::get('/marketing/student-wallet-credit', [\App\Http\Controllers\Admin\StudentWalletCreditController::class, 'create'])
            ->name('marketing.student-wallet-credit.create');
        Route::post('/marketing/student-wallet-credit', [\App\Http\Controllers\Admin\StudentWalletCreditController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('marketing.student-wallet-credit.store');
        Route::get('/coupon-commissions', [\App\Http\Controllers\Admin\CouponCommissionController::class, 'index'])->name('coupon-commissions.index');
        Route::post('/coupon-commissions/{accrual}/expense', [\App\Http\Controllers\Admin\CouponCommissionController::class, 'storeExpense'])
            ->middleware('throttle:20,1')
            ->name('coupon-commissions.store-expense');
        // إدارة برامج الإحالات
        Route::resource('referral-programs', \App\Http\Controllers\Admin\ReferralProgramController::class);
        Route::post('/referral-programs/{referralProgram}/set-default', [\App\Http\Controllers\Admin\ReferralProgramController::class, 'setDefault'])
            ->middleware('throttle:30,1')
            ->name('referral-programs.set-default');

        // إدارة الإحالات
        Route::prefix('referrals')->name('referrals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('index');
            Route::get('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'show'])->name('show');
        });
        // إدارة الشهادات والإنجازات (مسارات محددة قبل الـ resource)
        Route::get('certificates/user/{user}/courses', [\App\Http\Controllers\Admin\CertificateController::class, 'userCourses'])
            ->name('certificates.user-courses');
        Route::get('certificates/design', [\App\Http\Controllers\Admin\CertificateController::class, 'design'])
            ->name('certificates.design');
        Route::get('certificates/preview-sample', [\App\Http\Controllers\Admin\CertificateController::class, 'previewSample'])
            ->name('certificates.preview-sample');
        Route::get('certificates/preview-draft', [\App\Http\Controllers\Admin\CertificateController::class, 'previewDraft'])
            ->name('certificates.preview-draft');
        Route::get('certificates/prefill-data', [\App\Http\Controllers\Admin\CertificateController::class, 'prefillData'])
            ->name('certificates.prefill-data');
        Route::get('certificates/{certificate}/file', [\App\Http\Controllers\Admin\CertificateController::class, 'file'])
            ->name('certificates.file');
        Route::get('certificates/{certificate}/download', [\App\Http\Controllers\Admin\CertificateController::class, 'download'])
            ->name('certificates.download');
        Route::resource('certificates', \App\Http\Controllers\Admin\CertificateController::class);
        Route::resource('achievements', \App\Http\Controllers\Admin\AchievementController::class);
        Route::resource('badges', \App\Http\Controllers\Admin\BadgeController::class);
        Route::resource('reviews', \App\Http\Controllers\Admin\ReviewController::class)
            ->except(['create', 'store']);

        // إدارة المحاضرات (مسار الكورس قبل الـ resource لتفادي التعارض)
        Route::get('/lectures/course/{course}', [\App\Http\Controllers\Admin\LectureController::class, 'indexByCourse'])->name('lectures.by-course');
        Route::resource('lectures', \App\Http\Controllers\Admin\LectureController::class);

        // إدارة الحضور
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('index');
            Route::get('/lecture/{lecture}', [\App\Http\Controllers\Admin\AttendanceController::class, 'showLectureAttendance'])->name('lecture');
            Route::post('/lecture/{lecture}/upload-teams', [\App\Http\Controllers\Admin\AttendanceController::class, 'uploadTeamsFile'])->name('upload-teams');
        });

        // إدارة الأداء
        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PerformanceController::class, 'index'])->name('index');
            Route::post('/clear-cache', [\App\Http\Controllers\Admin\PerformanceController::class, 'clearCache'])
                ->middleware('throttle:10,1')
                ->name('clear-cache');
            Route::post('/optimize-cache', [\App\Http\Controllers\Admin\PerformanceController::class, 'optimizeCache'])
                ->middleware('throttle:5,5')
                ->name('optimize-cache');
            Route::post('/clear-temp-files', [\App\Http\Controllers\Admin\PerformanceController::class, 'clearTempFiles'])
                ->middleware('throttle:5,5')
                ->name('clear-temp-files');
            Route::post('/optimize-database', [\App\Http\Controllers\Admin\PerformanceController::class, 'optimizeDatabase'])
                ->middleware('throttle:3,10')
                ->name('optimize-database');
        });

        // ===== نظام البث المباشر (Admin) =====
        Route::prefix('live-sessions')->name('live-sessions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LiveSessionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\LiveSessionController::class, 'create'])->name('create');
            Route::post('/instant', [\App\Http\Controllers\Admin\LiveSessionController::class, 'instant'])->name('instant');
            Route::post('/', [\App\Http\Controllers\Admin\LiveSessionController::class, 'store'])->name('store');
            Route::get('/{liveSession}', [\App\Http\Controllers\Admin\LiveSessionController::class, 'show'])->name('show');
            Route::get('/{liveSession}/edit', [\App\Http\Controllers\Admin\LiveSessionController::class, 'edit'])->name('edit');
            Route::put('/{liveSession}', [\App\Http\Controllers\Admin\LiveSessionController::class, 'update'])->name('update');
            Route::delete('/{liveSession}', [\App\Http\Controllers\Admin\LiveSessionController::class, 'destroy'])->name('destroy');
            Route::post('/{liveSession}/start', [\App\Http\Controllers\Admin\LiveSessionController::class, 'start'])->name('start');
            Route::get('/{liveSession}/room', [\App\Http\Controllers\Admin\LiveSessionController::class, 'room'])->name('room');
            Route::post('/{liveSession}/end', [\App\Http\Controllers\Admin\LiveSessionController::class, 'end'])->name('end');
            Route::post('/{liveSession}/force-end', [\App\Http\Controllers\Admin\LiveSessionController::class, 'forceEnd'])->name('force-end');
            Route::post('/{liveSession}/cancel', [\App\Http\Controllers\Admin\LiveSessionController::class, 'cancel'])->name('cancel');
        });

        Route::prefix('live-servers')->name('live-servers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LiveServerController::class, 'index'])->name('index');
            Route::get('/control', [\App\Http\Controllers\Admin\LiveServerController::class, 'control'])->name('control');
            Route::get('/create', [\App\Http\Controllers\Admin\LiveServerController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\LiveServerController::class, 'store'])->name('store');
            Route::get('/{liveServer}/edit', [\App\Http\Controllers\Admin\LiveServerController::class, 'edit'])->name('edit');
            Route::get('/{liveServer}/ssh-browse', [\App\Http\Controllers\Admin\LiveServerController::class, 'sshBrowse'])->name('ssh-browse');
            Route::get('/{liveServer}/ssh-file', [\App\Http\Controllers\Admin\LiveServerController::class, 'sshFile'])->name('ssh-file');
            Route::put('/{liveServer}', [\App\Http\Controllers\Admin\LiveServerController::class, 'update'])->name('update');
            Route::delete('/{liveServer}', [\App\Http\Controllers\Admin\LiveServerController::class, 'destroy'])->name('destroy');
            Route::post('/{liveServer}/toggle-status', [\App\Http\Controllers\Admin\LiveServerController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{liveServer}/test-connection', [\App\Http\Controllers\Admin\LiveServerController::class, 'testConnection'])->name('test-connection');
            Route::post('/{liveServer}/set-default', [\App\Http\Controllers\Admin\LiveServerController::class, 'setAsDefault'])->name('set-default');
        });

        Route::prefix('live-recordings')->name('live-recordings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'store'])->name('store');
            Route::get('/{liveRecording}', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'show'])->name('show');
            Route::put('/{liveRecording}', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'update'])->name('update');
            Route::post('/{liveRecording}/toggle-publish', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'togglePublish'])->name('toggle-publish');
            Route::delete('/{liveRecording}', [\App\Http\Controllers\Admin\LiveRecordingController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('classroom-recordings')->name('classroom-recordings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ClassroomRecordingController::class, 'index'])->name('index');
            Route::get('/{meeting}/observe', [\App\Http\Controllers\Admin\ClassroomRecordingController::class, 'observe'])->name('observe');
            Route::delete('/{meeting}', [\App\Http\Controllers\Admin\ClassroomRecordingController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('live-settings')->name('live-settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\LiveSettingController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\LiveSettingController::class, 'update'])->name('update');
        });

    });

    // المهام (للجميع)
    Route::resource('tasks', \App\Http\Controllers\TaskController::class);

    // مسارات الطلاب - محمية للطلاب فقط
    Route::prefix('student')->name('student.')->middleware(['role:student'])->group(function () {
        Route::resource('invoices', \App\Http\Controllers\Student\InvoiceController::class)->only(['index', 'show']);
        Route::resource('wallet', \App\Http\Controllers\Student\WalletController::class)->only(['index', 'show']);
        Route::resource('certificates', \App\Http\Controllers\Student\CertificateController::class)->only(['index', 'show']);
        Route::get('certificates/{certificate}/file', [\App\Http\Controllers\Student\CertificateController::class, 'file'])
            ->name('certificates.file');
        Route::resource('achievements', \App\Http\Controllers\Student\AchievementController::class)->only(['index', 'show']);
        Route::resource('assignments', \App\Http\Controllers\Student\AssignmentController::class)->only(['index', 'show']);
        Route::post('/assignments/{assignment}/submit', [\App\Http\Controllers\Student\AssignmentController::class, 'submit'])
            ->middleware(['ownership:assignment,assignment'])
            ->name('assignments.submit');
        Route::post('/assignments/{assignment}/submission/presign-upload', [\App\Http\Controllers\Student\AssignmentController::class, 'presignSubmissionUpload'])
            ->middleware(['ownership:assignment,assignment', 'throttle:45,1'])
            ->name('assignments.submission.presign-upload');
        Route::post('/assignments/{assignment}/submission/complete-upload', [\App\Http\Controllers\Student\AssignmentController::class, 'completeSubmissionDirectUpload'])
            ->middleware(['ownership:assignment,assignment', 'throttle:90,1'])
            ->name('assignments.submission.complete-upload');
        Route::post('/assignments/{assignment}/submission/abandon-upload', [\App\Http\Controllers\Student\AssignmentController::class, 'abandonSubmissionDirectUpload'])
            ->middleware(['ownership:assignment,assignment', 'throttle:60,1'])
            ->name('assignments.submission.abandon-upload');
        Route::delete('/assignments/{assignment}/submission', [\App\Http\Controllers\Student\AssignmentController::class, 'destroySubmission'])
            ->middleware(['ownership:assignment,assignment'])
            ->name('assignments.submission.destroy');
        Route::resource('tasks', \App\Http\Controllers\Student\TaskController::class);

        // ===== البث المباشر (Student) =====
        Route::prefix('live-sessions')->name('live-sessions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Student\LiveSessionController::class, 'index'])->name('index');
            Route::get('/{liveSession}', [\App\Http\Controllers\Student\LiveSessionController::class, 'show'])->name('show');
            Route::post('/{liveSession}/join', [\App\Http\Controllers\Student\LiveSessionController::class, 'join'])->name('join');
            Route::post('/{liveSession}/leave', [\App\Http\Controllers\Student\LiveSessionController::class, 'leave'])->name('leave');
            Route::get('/{liveSession}/status', [\App\Http\Controllers\Student\LiveSessionController::class, 'status'])->name('status');
            Route::post('/{liveSession}/share-annotation', [\App\Http\Controllers\Student\LiveSessionController::class, 'pushShareAnnotation'])
                ->middleware('throttle:90,1')
                ->name('share-annotation');
        });
        // تسجيلات الجلسات (R2 — عرض للمنشور فقط)
        Route::get('/live-recordings', [\App\Http\Controllers\Student\LiveRecordingController::class, 'index'])->name('live-recordings.index');
        Route::get('/live-recordings/{liveRecording}', [\App\Http\Controllers\Student\LiveRecordingController::class, 'show'])->name('live-recordings.show');
    });

    // مسارات المدرسين
    Route::prefix('instructor')->name('instructor.')->middleware(['auth', 'role:instructor|teacher', 'instructor.activated'])->group(function () {
        Route::get('/calendar', [\App\Http\Controllers\Instructor\CalendarController::class, 'index'])->name('calendar');
        Route::get('/api/calendar/events', [\App\Http\Controllers\Instructor\CalendarController::class, 'getEvents'])->name('calendar.events');
        Route::get('/consultations', [\App\Http\Controllers\Instructor\ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/{consultation}', [\App\Http\Controllers\Instructor\ConsultationController::class, 'show'])->name('consultations.show');
        Route::get('/one-to-one-sessions', [\App\Http\Controllers\Instructor\OneToOneSessionController::class, 'index'])->name('one-to-one-sessions.index');
        Route::get('/one-to-one-availability', [\App\Http\Controllers\Instructor\OneToOneAvailabilityController::class, 'index'])->name('one-to-one-availability.index');
        Route::post('/one-to-one-availability', [\App\Http\Controllers\Instructor\OneToOneAvailabilityController::class, 'update'])->name('one-to-one-availability.update');
        Route::get('/free-trial-bookings', [\App\Http\Controllers\Instructor\FreeTrialBookingController::class, 'index'])->name('free-trial-bookings.index');
        Route::get('/free-trial-bookings/{freeTrialBooking}', [\App\Http\Controllers\Instructor\FreeTrialBookingController::class, 'show'])->name('free-trial-bookings.show');

        Route::get('/private-messages', [\App\Http\Controllers\Instructor\PrivateMessagesController::class, 'index'])->name('private-messages.index');
        Route::get('/private-messages/with/{student}', [\App\Http\Controllers\Instructor\PrivateMessagesController::class, 'openWith'])->name('private-messages.with');
        Route::get('/private-messages/{thread}', [\App\Http\Controllers\Instructor\PrivateMessagesController::class, 'show'])->name('private-messages.show');
        Route::post('/private-messages/{thread}', [\App\Http\Controllers\Instructor\PrivateMessagesController::class, 'send'])->name('private-messages.send');

        Route::get('/notifications', [\App\Http\Controllers\Instructor\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/go', [\App\Http\Controllers\Instructor\NotificationController::class, 'go'])->name('notifications.go');
        Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\Instructor\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Instructor\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
        Route::get('/api/notifications/unread-count', [\App\Http\Controllers\Instructor\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');

        // مكتبة المناهج التفاعلية — للمعلمين المعتمدين (عرض كامل بدون باقة)
        Route::get('/libraries/curriculum', [\App\Http\Controllers\Instructor\CurriculumLibraryController::class, 'index'])->name('libraries.curriculum.index');
        Route::get('/libraries/curriculum/courses/{course}', [\App\Http\Controllers\Instructor\CurriculumLibraryController::class, 'showCourse'])->name('libraries.curriculum.course');
        Route::get('/libraries/curriculum/{item:slug}', [\App\Http\Controllers\Instructor\CurriculumLibraryController::class, 'show'])->name('libraries.curriculum.show');

        // مكتبة الماتريال (فولدر معلم×سنة + عرض مجلدات الإدارة)
        Route::get('/libraries/materials', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'index'])->name('libraries.materials.index');
        Route::post('/libraries/materials/folders', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'storeFolder'])->name('libraries.materials.folders.store');
        Route::get('/libraries/materials/folders/{folder}', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'show'])->name('libraries.materials.show');
        Route::post('/libraries/materials/folders/{folder}/upload', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'upload'])->name('libraries.materials.upload');
        Route::get('/libraries/materials/folders/{folder}/materials/{material}/download', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'download'])->name('libraries.materials.download');
        Route::get('/libraries/materials/folders/{folder}/materials/{material}/experience', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'experience'])->name('libraries.materials.experience');
        Route::get('/libraries/materials/folders/{folder}/materials/{material}/experience/raw', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'experienceRaw'])->name('libraries.materials.experience.raw');
        Route::delete('/libraries/materials/folders/{folder}/materials/{material}', [\App\Http\Controllers\Instructor\MaterialLibraryController::class, 'destroyMaterial'])->name('libraries.materials.destroy');

        // مكتبة فيديو المعلم → طلابه + فيديوهات الأكاديمية للعرض
        Route::get('/libraries/videos', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'index'])->name('libraries.videos.index');
        Route::get('/libraries/videos/create', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'create'])->name('libraries.videos.create');
        Route::post('/libraries/videos', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'store'])->name('libraries.videos.store');
        Route::post('/libraries/videos/presign-upload', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'presignUpload'])->name('libraries.videos.presign');
        Route::post('/libraries/videos/complete-upload', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'completeUpload'])->name('libraries.videos.complete');
        Route::post('/libraries/videos/proxy-upload', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'proxyUpload'])->name('libraries.videos.proxy');
        Route::post('/libraries/videos/folders', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'storeFolder'])->name('libraries.videos.folders.store');
        Route::get('/libraries/videos/{libraryVideo}/watch', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'watch'])->name('libraries.videos.watch');
        Route::get('/libraries/videos/{libraryVideo}/edit', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'edit'])->name('libraries.videos.edit');
        Route::put('/libraries/videos/{libraryVideo}', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'update'])->name('libraries.videos.update');
        Route::post('/libraries/videos/{libraryVideo}/toggle', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'togglePublish'])->name('libraries.videos.toggle');
        Route::delete('/libraries/videos/{libraryVideo}', [\App\Http\Controllers\Instructor\VideoLibraryController::class, 'destroy'])->name('libraries.videos.destroy');

        // تسجيل المحاضرات
        Route::get('/lecture-recordings', [\App\Http\Controllers\Instructor\LectureRecordingController::class, 'index'])->name('lecture-recordings.index');
        Route::put('/lecture-recordings/{lecture}', [\App\Http\Controllers\Instructor\LectureRecordingController::class, 'update'])->name('lecture-recordings.update');
        Route::get('/lecture-recordings/{lecture}/preview', [\App\Http\Controllers\Instructor\LectureRecordingController::class, 'preview'])->name('lecture-recordings.preview');

        Route::get('/one-to-one-sessions/{oneToOneSession}', [\App\Http\Controllers\Instructor\OneToOneSessionController::class, 'show'])->name('one-to-one-sessions.show');
        Route::post('/one-to-one-sessions/{oneToOneSession}/schedule', [\App\Http\Controllers\Instructor\OneToOneSessionController::class, 'schedule'])->name('one-to-one-sessions.schedule');
        Route::post('/one-to-one-sessions/{oneToOneSession}/complete', [\App\Http\Controllers\Instructor\OneToOneSessionController::class, 'complete'])->name('one-to-one-sessions.complete');
        Route::get('/classroom/{meeting}', [\App\Http\Controllers\Student\ClassroomController::class, 'show'])->name('classroom.show');
        Route::post('/classroom/{meeting}/start', [\App\Http\Controllers\Student\ClassroomController::class, 'startMeeting'])->name('classroom.start-meeting');
        Route::get('/classroom/room/{meeting}', [\App\Http\Controllers\Student\ClassroomController::class, 'room'])->name('classroom.room');
        Route::get('/classroom/room/{meeting}/status', [\App\Http\Controllers\Student\ClassroomController::class, 'roomStatus'])->name('classroom.room.status');
        Route::get('/classroom/room/{meeting}/recording-upload', [\App\Http\Controllers\Student\ClassroomController::class, 'recordingUploadTab'])->name('classroom.recording.upload-tab');
        Route::post('/classroom/{meeting}/participant-whiteboard', [\App\Http\Controllers\Student\ClassroomController::class, 'updateParticipantWhiteboard'])->name('classroom.participant-whiteboard');
        Route::post('/classroom/{meeting}/guest-join', [\App\Http\Controllers\Student\ClassroomController::class, 'updateGuestJoin'])->name('classroom.guest-join');
        Route::get('/classroom/{meeting}/share-annotations', [\App\Http\Controllers\Student\ClassroomController::class, 'shareAnnotations'])->name('classroom.share-annotations');
        Route::post('/classroom/{meeting}/share-annotation', [\App\Http\Controllers\Student\ClassroomController::class, 'pushShareAnnotation'])->name('classroom.share-annotation');
        Route::get('/classroom/{meeting}/whiteboard/state', [\App\Http\Controllers\Student\ClassroomController::class, 'whiteboardState'])->name('classroom.whiteboard.state');
        Route::post('/classroom/{meeting}/whiteboard/state', [\App\Http\Controllers\Student\ClassroomController::class, 'pushWhiteboardState'])->name('classroom.whiteboard.push');
        Route::post('/classroom/room/{meeting}/end', [\App\Http\Controllers\Student\ClassroomController::class, 'end'])->name('classroom.end');
        Route::post('/classroom/{meeting}/recording/upload', [\App\Http\Controllers\Student\ClassroomController::class, 'uploadRecording'])->name('classroom.recording.upload');
        Route::post('/classroom/{meeting}/recording/presign', [\App\Http\Controllers\Student\ClassroomController::class, 'presignRecordingUpload'])->name('classroom.recording.presign');
        Route::post('/classroom/{meeting}/recording/complete', [\App\Http\Controllers\Student\ClassroomController::class, 'completeDirectRecordingUpload'])->name('classroom.recording.complete');
        Route::post('/classroom/{meeting}/recording-audio/presign', [\App\Http\Controllers\Student\ClassroomController::class, 'presignAudioUpload'])->name('classroom.recording-audio.presign');
        Route::post('/classroom/{meeting}/recording-audio/upload', [\App\Http\Controllers\Student\ClassroomController::class, 'uploadAudioRecording'])->name('classroom.recording-audio.upload');
        Route::post('/classroom/{meeting}/recording-audio/complete', [\App\Http\Controllers\Student\ClassroomController::class, 'completeDirectAudioUpload'])->name('classroom.recording-audio.complete');
        Route::post('/classroom/{meeting}/ai-report', [\App\Http\Controllers\Student\ClassroomController::class, 'generateAiReport'])->name('classroom.ai-report');
        Route::get('/classroom/{meeting}/curriculum/catalog', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumCatalog'])->name('classroom.curriculum.catalog');
        Route::post('/classroom/{meeting}/curriculum/present', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumPresent'])->name('classroom.curriculum.present');
        Route::get('/classroom/{meeting}/curriculum/state', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumState'])->name('classroom.curriculum.state');
        Route::post('/classroom/{meeting}/curriculum/slide', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumUpdateSlide'])->name('classroom.curriculum.slide.update');
        Route::post('/classroom/{meeting}/curriculum/stop', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumStop'])->name('classroom.curriculum.stop');
        Route::get('/classroom/{meeting}/curriculum/{sessionId}/slide/{slide}', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumSlide'])
            ->whereNumber('slide')
            ->name('classroom.curriculum.slide');
        Route::get('/classroom/{meeting}/curriculum/{sessionId}/thumb/{slide}', [\App\Http\Controllers\Student\ClassroomController::class, 'curriculumThumb'])
            ->whereNumber('slide')
            ->name('classroom.curriculum.thumb');

        // بروفايل المدرب
        Route::get('/profile', [\App\Http\Controllers\Instructor\ProfileController::class, 'index'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Instructor\ProfileController::class, 'update'])->name('profile.update');

        // التسويق الشخصي (البراندينغ) — ملف تعريفي للمدرب للمراجعة والنشر
        Route::get('/personal-branding', [\App\Http\Controllers\Instructor\PersonalBrandingController::class, 'edit'])->name('personal-branding.edit');
        Route::put('/personal-branding', [\App\Http\Controllers\Instructor\PersonalBrandingController::class, 'update'])->name('personal-branding.update');
        Route::post('/personal-branding/submit', [\App\Http\Controllers\Instructor\PersonalBrandingController::class, 'submit'])->name('personal-branding.submit');

        Route::resource('courses', \App\Http\Controllers\Instructor\CourseController::class)->only(['index', 'show']);
        Route::get('courses/{course}/curriculum', [\App\Http\Controllers\Instructor\CurriculumController::class, 'index'])->name('courses.curriculum');
        Route::post('courses/{course}/curriculum/exams', [\App\Http\Controllers\Instructor\CurriculumController::class, 'storeExamFromCurriculum'])->name('courses.curriculum.exams.store');
        Route::post('courses/{course}/curriculum/assignments', [\App\Http\Controllers\Instructor\CurriculumController::class, 'storeAssignmentFromCurriculum'])->name('courses.curriculum.assignments.store');
        Route::post('courses/{course}/sections', [\App\Http\Controllers\Instructor\CurriculumController::class, 'storeSection'])->name('courses.sections.store');
        Route::put('sections/{section}', [\App\Http\Controllers\Instructor\CurriculumController::class, 'updateSection'])->name('sections.update');
        Route::delete('sections/{section}', [\App\Http\Controllers\Instructor\CurriculumController::class, 'destroySection'])->name('sections.destroy');
        Route::post('sections/{section}/items', [\App\Http\Controllers\Instructor\CurriculumController::class, 'addItem'])->name('sections.items.store');
        Route::delete('curriculum-items/{item}', [\App\Http\Controllers\Instructor\CurriculumController::class, 'removeItem'])->name('curriculum-items.destroy');
        Route::post('curriculum-items/{item}/move', [\App\Http\Controllers\Instructor\CurriculumController::class, 'moveItem'])->name('curriculum-items.move');
        Route::post('courses/{course}/sections/order', [\App\Http\Controllers\Instructor\CurriculumController::class, 'updateSectionsOrder'])->name('courses.sections.order');
        Route::post('sections/{section}/items/order', [\App\Http\Controllers\Instructor\CurriculumController::class, 'updateItemsOrder'])->name('sections.items.order');
        Route::get('lectures/{lecture}/video-questions', [\App\Http\Controllers\Instructor\LectureVideoQuestionController::class, 'index'])->name('lectures.video-questions.index');
        Route::post('lectures/{lecture}/video-questions', [\App\Http\Controllers\Instructor\LectureVideoQuestionController::class, 'store'])->name('lectures.video-questions.store');
        Route::delete('lectures/{lecture}/video-questions/{videoQuestion}', [\App\Http\Controllers\Instructor\LectureVideoQuestionController::class, 'destroy'])->name('lectures.video-questions.destroy');

        // تم إلغاء نظام الدروس — الاعتماد على المحاضرات فقط (إعادة توجيه الروابط القديمة)
        Route::prefix('courses/{course}/lessons')->name('courses.lessons.')->group(function () {
            Route::get('/', fn ($course) => redirect()->route('instructor.courses.curriculum', $course))->name('index');
            Route::get('/create', fn ($course) => redirect()->route('instructor.lectures.index'))->name('create');
            Route::post('/', fn ($course) => redirect()->route('instructor.courses.curriculum', $course)->with('info', 'تم إلغاء نظام الدروس؛ استخدم المحاضرات.'))->name('store');
            Route::get('/{lesson}', fn ($course) => redirect()->route('instructor.lectures.index'))->name('show');
            Route::get('/{lesson}/edit', fn ($course) => redirect()->route('instructor.lectures.index'))->name('edit');
            Route::put('/{lesson}', fn ($course) => redirect()->route('instructor.courses.curriculum', $course))->name('update');
            Route::delete('/{lesson}', fn ($course) => redirect()->route('instructor.courses.curriculum', $course))->name('destroy');
            Route::post('/{lesson}/toggle-status', fn ($course) => redirect()->route('instructor.courses.curriculum', $course))->name('toggle-status');
            Route::post('/reorder', fn ($course) => redirect()->route('instructor.courses.curriculum', $course))->name('reorder');
        });

        Route::get('/api/courses/{course}/lessons-list', fn ($course) => response()->json([]));

        // API لدروس الكورس للمدرب
        Route::resource('lectures', \App\Http\Controllers\Instructor\LectureController::class);
        Route::post('/lectures/{lecture}/sync-teams-attendance', [\App\Http\Controllers\Instructor\LectureController::class, 'syncTeamsAttendance'])->name('lectures.sync-teams-attendance');
        Route::post('/lectures/{lecture}/update-attendance', [\App\Http\Controllers\Instructor\LectureController::class, 'updateAttendance'])->name('lectures.update-attendance');

        Route::post('/lectures/{lecture}/update-status', [\App\Http\Controllers\Instructor\LectureController::class, 'updateStatus'])->name('lectures.update-status');
        Route::resource('assignments', \App\Http\Controllers\Instructor\AssignmentController::class);
        Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\Instructor\AssignmentController::class, 'submissions'])->name('assignments.submissions');
        Route::post('/assignments/{assignment}/grade/{submission}', [\App\Http\Controllers\Instructor\AssignmentController::class, 'grade'])->name('assignments.grade');
        Route::resource('exams', \App\Http\Controllers\Instructor\ExamController::class);
        Route::get('exams/{exam}/questions', [\App\Http\Controllers\Instructor\ExamQuestionController::class, 'manage'])->name('exams.questions.manage');
        Route::post('exams/{exam}/questions/from-bank', [\App\Http\Controllers\Instructor\ExamQuestionController::class, 'addFromBank'])->name('exams.questions.add-from-bank');
        Route::post('exams/{exam}/questions/new', [\App\Http\Controllers\Instructor\ExamQuestionController::class, 'createNew'])->name('exams.questions.create-new');
        Route::delete('exams/{exam}/questions/{question}', [\App\Http\Controllers\Instructor\ExamQuestionController::class, 'remove'])->name('exams.questions.remove');
        Route::post('exams/{exam}/questions/reorder', [\App\Http\Controllers\Instructor\ExamQuestionController::class, 'reorder'])->name('exams.questions.reorder');

        // بنك الأسئلة
        Route::resource('question-banks', \App\Http\Controllers\Instructor\QuestionBankController::class);
        Route::post('question-banks/{questionBank}/questions', [\App\Http\Controllers\Instructor\QuestionController::class, 'store'])->name('question-banks.questions.store');
        Route::get('question-banks/{questionBank}/questions/create', [\App\Http\Controllers\Instructor\QuestionController::class, 'create'])->name('question-banks.questions.create');
        Route::resource('questions', \App\Http\Controllers\Instructor\QuestionController::class)->except(['create', 'store']);
        Route::get('/attendance', [\App\Http\Controllers\Instructor\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/lecture/{lecture}', [\App\Http\Controllers\Instructor\AttendanceController::class, 'showLecture'])->name('attendance.lecture');
        Route::resource('tasks', \App\Http\Controllers\Instructor\TaskController::class);
        Route::get('/tasks/lectures', [\App\Http\Controllers\Instructor\TaskController::class, 'getLectures'])->name('tasks.lectures');
        Route::post('/tasks/{task}/deliverables', [\App\Http\Controllers\Instructor\TaskController::class, 'submitDeliverable'])->name('tasks.submit-deliverable');
        Route::put('/tasks/{task}/progress', [\App\Http\Controllers\Instructor\TaskController::class, 'updateProgress'])->name('tasks.update-progress');

        // تقديم طلبات للإدارة
        Route::prefix('management-requests')->name('management-requests.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Instructor\ManagementRequestController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Instructor\ManagementRequestController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Instructor\ManagementRequestController::class, 'store'])->name('store');
            Route::get('/{managementRequest}', [\App\Http\Controllers\Instructor\ManagementRequestController::class, 'show'])->name('show');
        });

        // نظام الاتفاقيات للمدرب
        Route::prefix('agreements')->name('agreements.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Instructor\AgreementController::class, 'index'])->name('index');
            Route::get('/{agreement}/export-activations', [\App\Http\Controllers\Instructor\AgreementController::class, 'exportActivations'])->name('export-activations');
            Route::get('/{agreement}', [\App\Http\Controllers\Instructor\AgreementController::class, 'show'])->name('show');
        });

        // حساب التحويل (بيانات استلام المبالغ)
        Route::get('/transfer-account', [\App\Http\Controllers\Instructor\TransferAccountController::class, 'index'])->name('transfer-account.index');
        Route::post('/transfer-account', [\App\Http\Controllers\Instructor\TransferAccountController::class, 'store'])->name('transfer-account.store');

        // طلبات السحب للمدرب
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Instructor\WithdrawalRequestController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Instructor\WithdrawalRequestController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Instructor\WithdrawalRequestController::class, 'store'])->name('store');
            Route::get('/{withdrawal}', [\App\Http\Controllers\Instructor\WithdrawalRequestController::class, 'show'])->name('show');
            Route::post('/{withdrawal}/cancel', [\App\Http\Controllers\Instructor\WithdrawalRequestController::class, 'cancel'])->name('cancel');
        });

        // ===== البث المباشر (Instructor) =====
        Route::prefix('live-sessions')->name('live-sessions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'store'])->name('store');
            Route::get('/{liveSession}', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'show'])->name('show');
            Route::post('/{liveSession}/start', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'start'])->name('start');
            Route::get('/{liveSession}/room', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'room'])->name('room');
            Route::post('/{liveSession}/student-whiteboard', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'updateStudentWhiteboard'])->name('student-whiteboard');
            Route::get('/{liveSession}/share-annotations', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'shareAnnotations'])->name('share-annotations');
            Route::post('/{liveSession}/audio/presign', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'presignAudioUpload'])->name('audio.presign');
            Route::post('/{liveSession}/audio/complete', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'completeAudioUpload'])->name('audio.complete');
            Route::post('/{liveSession}/ai-report', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'generateAiReport'])->name('ai-report');
            Route::post('/{liveSession}/leave-presence', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'leavePresence'])->name('leave-presence');
            Route::post('/{liveSession}/end', [\App\Http\Controllers\Instructor\LiveSessionController::class, 'end'])->name('end');
        });

    });
});
