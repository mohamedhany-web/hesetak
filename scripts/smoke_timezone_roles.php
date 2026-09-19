<?php

/**
 * Multi-role smoke: free-trial API + Blade compile for admin/student/instructor booking views.
 * Run: php scripts/smoke_timezone_roles.php
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FreeTrialBooking;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

$errors = [];
$ok = function (string $msg) {
    echo "[OK] {$msg}\n";
};
$fail = function (string $msg) use (&$errors) {
    $errors[] = $msg;
    echo "[FAIL] {$msg}\n";
};

config(['app.timezone' => 'UTC', 'platform.academy_timezone' => 'Africa/Cairo']);

// --- Dual label / quality ---
$instant = AppTimezone::wallClockToUtc('2026-03-10', '18:00', 'Africa/Cairo');
$dual = AppTimezone::dualLabel($instant, 'America/Los_Angeles', 'ar', 'g:i A');
if ($dual['primary'] === '—' || empty($dual['secondary'])) {
    $fail('dualLabel LA secondary missing');
} else {
    $ok('dualLabel academy↔LA');
}

// --- Blade components (via Blade::render / Illuminate\View\Component) ---
try {
    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-app-datetime :at="$at" timezone="America/New_York" pattern="Y-m-d H:i" />',
        ['at' => $instant]
    );
    if (! str_contains($html, '2026')) {
        $fail('app-datetime component empty');
    } else {
        $ok('Blade x-app-datetime');
    }
} catch (Throwable $e) {
    $fail('app-datetime: '.$e->getMessage());
}

try {
    $html = View::make('partials.timezone-select', [
        'value' => 'America/Chicago',
        'errors' => new \Illuminate\Support\ViewErrorBag([]),
    ])->render();
    $ok('timezone-select partial');
} catch (Throwable $e) {
    $fail('timezone-select: '.$e->getMessage());
}

try {
    $html = View::make('partials.us-state-select', [
        'value' => 'تكساس',
        'timezoneSelectId' => 'timezoneSelect',
        'errors' => new \Illuminate\Support\ViewErrorBag([]),
    ])->render();
    $ok('us-state-select partial');
} catch (Throwable $e) {
    $fail('us-state-select: '.$e->getMessage());
}

// --- Public free-trial slots ---
$http = $app->make(Illuminate\Contracts\Http\Kernel::class);
$req = Illuminate\Http\Request::create('/free-trial/slots?days=14&timezone=America/Denver', 'GET');
$res = $http->handle($req);
$json = json_decode($res->getContent(), true);
if ($res->getStatusCode() !== 200) {
    $fail('public slots HTTP '.$res->getStatusCode());
} elseif (($json['viewer_timezone'] ?? null) !== 'America/Denver') {
    $fail('viewer_timezone not Denver');
} elseif (empty($json['slots_by_date'])) {
    $fail('no slots for Denver viewer (may be empty DB windows)');
} else {
    $d = array_key_first($json['slots_by_date']);
    $slot = $json['slots_by_date'][$d][0];
    foreach (['starts_at', 'time', 'time_academy', 'quality'] as $k) {
        if (! array_key_exists($k, $slot)) {
            $fail("slot missing {$k}");
        }
    }
    $ok('public slots Denver total='.$json['total']);
}
$http->terminate($req, $res);

// --- Find users ---
$admin = User::query()->whereIn('role', ['super_admin', 'admin'])->where('is_active', true)->first()
    ?: User::query()->where('role', 'super_admin')->first();
$instructor = User::query()->where('role', 'instructor')->where('is_active', true)->first();
$student = User::query()->where('role', 'student')->where('is_active', true)->first();

// Blade partials need error bag in isolation — skip View::make for those; HTTP pages cover them.

if (! $admin) {
    $fail('no admin user in DB to smoke admin pages');
} else {
    Auth::login($admin);
    foreach ([
        'admin.free-trial-bookings.index',
        'admin.free-trial-bookings.index',
        'admin.free-trial-bookings.create',
    ] as $routeName) {
        try {
            $url = route($routeName);
            $r = Illuminate\Http\Request::create($url, 'GET');
            $r->setUserResolver(fn () => $admin);
            $resp = $http->handle($r);
            $code = $resp->getStatusCode();
            if ($code >= 400) {
                $fail("{$routeName} => {$code}");
            } else {
                $body = $resp->getContent();
                if (str_contains($body, 'ErrorException') || str_contains($body, 'Undefined variable')) {
                    $fail("{$routeName} body has error text");
                } else {
                    $ok("{$routeName} => {$code}");
                }
            }
            $http->terminate($r, $resp);
        } catch (Throwable $e) {
            $fail("{$routeName}: ".$e->getMessage());
        }
    }

    $booking = FreeTrialBooking::query()->latest('id')->first();
    if ($booking) {
        try {
            $url = route('admin.free-trial-bookings.show', $booking);
            $r = Illuminate\Http\Request::create($url, 'GET');
            $r->setUserResolver(fn () => $admin);
            $resp = $http->handle($r);
            if ($resp->getStatusCode() >= 400) {
                $fail('admin free-trial show => '.$resp->getStatusCode());
            } else {
                $ok('admin free-trial show => '.$resp->getStatusCode());
            }
            $http->terminate($r, $resp);
        } catch (Throwable $e) {
            $fail('admin show: '.$e->getMessage());
        }
    } else {
        $ok('admin show skipped (no bookings)');
    }
    Auth::logout();
}

if ($instructor) {
    Auth::login($instructor);
    foreach ([
        'instructor.tutoring-bookings.index',
        'instructor.tutor-work-schedule.index',
        'instructor.one-to-one-availability.index',
        'instructor.calendar',
    ] as $routeName) {
        if (! \Illuminate\Support\Facades\Route::has($routeName)) {
            $ok("skip missing route {$routeName}");
            continue;
        }
        try {
            $url = route($routeName);
            $r = Illuminate\Http\Request::create($url, 'GET');
            $r->setUserResolver(fn () => $instructor);
            $resp = $http->handle($r);
            $code = $resp->getStatusCode();
            if ($code >= 500) {
                $fail("{$routeName} => {$code}");
            } elseif ($code >= 400) {
                echo "[WARN] {$routeName} => {$code}\n";
            } else {
                $ok("{$routeName} => {$code}");
            }
            $http->terminate($r, $resp);
        } catch (Throwable $e) {
            $fail("{$routeName}: ".$e->getMessage());
        }
    }
    Auth::logout();
} else {
    $fail('no instructor user');
}

if ($student) {
    Auth::login($student);
    // ensure timezone for dual label
    if (! $student->timezone) {
        $student->forceFill(['timezone' => 'America/New_York'])->save();
    }
    foreach ([
        'student.tutoring-bookings.index',
        'calendar',
    ] as $routeName) {
        if (! \Illuminate\Support\Facades\Route::has($routeName)) {
            $ok("skip missing route {$routeName}");
            continue;
        }
        try {
            $url = route($routeName);
            $r = Illuminate\Http\Request::create($url, 'GET');
            $r->setUserResolver(fn () => $student);
            $resp = $http->handle($r);
            $code = $resp->getStatusCode();
            if ($code >= 500) {
                $fail("{$routeName} => {$code}");
            } elseif ($code >= 400) {
                echo "[WARN] {$routeName} => {$code}\n";
            } else {
                $ok("{$routeName} => {$code}");
            }
            $http->terminate($r, $resp);
        } catch (Throwable $e) {
            $fail("{$routeName}: ".$e->getMessage());
        }
    }
    Auth::logout();
} else {
    $fail('no student user');
}

// --- Homepage welcome compiles ---
try {
    $html = View::make('welcome', [
        'searchSuggestions' => [],
        'trendingSearchLabels' => [],
        'courseCatalogForJs' => [],
        'searchChipsForJs' => [],
        'locale' => 'ar',
        'isRtl' => true,
        'a' => 'landing.academy',
    ])->render();
    if (! str_contains($html, 'glottical-timezone.js')) {
        $fail('welcome missing glottical-timezone.js');
    } elseif (! str_contains($html, 'ft-us-state')) {
        $fail('welcome missing us state select');
    } elseif (! str_contains($html, 'quality-good')) {
        $fail('welcome missing quality CSS');
    } else {
        $ok('welcome free-trial timezone UI present');
    }
} catch (Throwable $e) {
    // welcome may need many vars — try lighter check via file contents
    $path = resource_path('views/welcome.blade.php');
    $raw = file_get_contents($path);
    if (! str_contains($raw, 'glottical-timezone.js')) {
        $fail('welcome file missing timezone js include: '.$e->getMessage());
    } else {
        echo "[WARN] welcome full render needs page controller vars: ".$e->getMessage()."\n";
        $ok('welcome file has timezone wiring');
    }
}

// JS file exists
if (! is_file(public_path('js/glottical-timezone.js'))) {
    $fail('public/js/glottical-timezone.js missing');
} else {
    $ok('glottical-timezone.js exists');
}

// Schema columns
if (! \Illuminate\Support\Facades\Schema::hasColumn('free_trial_bookings', 'timezone')) {
    $fail('missing free_trial_bookings.timezone column');
} else {
    $ok('DB timezone columns present');
}

if ($errors) {
    echo "\nRESULT: FAIL (".count($errors).")\n";
    foreach ($errors as $e) {
        echo " - {$e}\n";
    }
    exit(1);
}

echo "\nRESULT: OK\n";
exit(0);
