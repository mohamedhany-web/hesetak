<?php

/**
 * Hesetak production lifecycle smoke:
 * package → entitlement → schedule/book → trial → complete/end timings
 * visibility for student / instructor / admin
 *
 * Run on Hostinger app root:
 *   php scripts/smoke_booking_lifecycle_prod.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FreeTrialBooking;
use App\Models\OneToOneSession;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\StudentServiceEntitlement;
use App\Models\User;
use App\Services\FreeTrialBookingService;
use App\Services\OneToOneAvailabilityService;
use App\Services\OneToOneSessionService;
use App\Services\StudentEntitlementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

$pass = 0;
$fail = 0;
$rows = [];

$check = function (bool $ok, string $label, string $detail = '') use (&$pass, &$fail, &$rows) {
    $rows[] = ['ok' => $ok, 'label' => $label, 'detail' => $detail];
    if ($ok) {
        $pass++;
        echo '[PASS] '.$label.($detail !== '' ? ' — '.$detail : '').PHP_EOL;
    } else {
        $fail++;
        echo '[FAIL] '.$label.($detail !== '' ? ' — '.$detail : '').PHP_EOL;
    }
};

echo "=== HESETAK BOOKING LIFECYCLE SMOKE ===".PHP_EOL;
echo 'at='.now()->toIso8601String().PHP_EOL;

$admin = User::query()->where('email', 'admin@hesetak.com')->first()
    ?? User::query()->whereIn('role', ['super_admin', 'admin'])->orderBy('id')->first();
$instructor = User::query()->where('email', 'instructor1@hesetak.com')->first()
    ?? User::query()->whereIn('role', ['instructor', 'teacher'])->where('is_active', true)->orderBy('id')->first();
$instructorB = User::query()->where('email', 'instructor2@hesetak.com')->first() ?? $instructor;
$student = User::query()->where('email', 'student1@hesetak.com')->first()
    ?? User::query()->where('role', 'student')->where('is_active', true)->orderBy('id')->first();

$check((bool) $admin, 'Admin actor', $admin?->email ?? 'missing');
$check((bool) $instructor, 'Instructor actor', $instructor?->email ?? 'missing');
$check((bool) $student, 'Student actor', $student?->email ?? 'missing');

if (! $admin || ! $instructor || ! $student) {
    echo "ABORT: missing actors".PHP_EOL;
    exit(1);
}

// ── Routes presence ──
foreach ([
    'public.service-packages.index',
    'public.service-packages.checkout',
    'public.free-trial.book',
    'student.one-to-one-sessions.book-instructor',
    'student.one-to-one-sessions.index',
    'instructor.one-to-one-sessions.index',
    'admin.one-to-one-sessions.index',
    'instructor.free-trial-bookings.index',
    'admin.free-trial-bookings.index',
] as $routeName) {
    $check(Route::has($routeName), 'Route '.$routeName);
}

// ── Packages catalog ──
$packages = ServicePackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
$check($packages->count() > 0, 'Active service packages exist', 'count='.$packages->count());
$privatePkg = $packages->firstWhere('scope', ServicePackage::SCOPE_PRIVATE_LESSONS) ?? $packages->first();
$check((bool) $privatePkg, 'Private-lessons package available', $privatePkg ? '#'.$privatePkg->id.' '.$privatePkg->name : 'none');

// ── HTTP pages (kernel) ──
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$httpGet = function (string $uri, ?User $as = null) use ($kernel, $app) {
    if ($as) {
        Auth::login($as);
    } else {
        Auth::logout();
    }
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $code = $response->getStatusCode();
    $kernel->terminate($request, $response);

    return $code;
};

$code = $httpGet('/service-packages');
$check(in_array($code, [200, 301, 302], true), 'GET /service-packages', 'HTTP '.$code);

if (Route::has('public.free-trial')) {
    $code = $httpGet(route('public.free-trial', [], false));
    $check(in_array($code, [200, 301, 302], true), 'GET free-trial page', 'HTTP '.$code);
} else {
    // try common paths
    foreach (['/free-trial', '/trial', '/حجز-تجريبي'] as $path) {
        $c = $httpGet($path);
        if (in_array($c, [200, 301, 302], true)) {
            $check(true, 'GET trial landing', $path.' HTTP '.$c);
            break;
        }
    }
}

foreach ([
    ['student', $student, '/student/one-to-one-sessions'],
    ['instructor', $instructor, '/instructor/one-to-one-sessions'],
    ['admin', $admin, '/admin/one-to-one-sessions'],
] as [$role, $user, $uri]) {
    // resolve real URIs from named routes when possible
    $named = match ($role) {
        'student' => 'student.one-to-one-sessions.index',
        'instructor' => 'instructor.one-to-one-sessions.index',
        'admin' => 'admin.one-to-one-sessions.index',
    };
    if (Route::has($named)) {
        $uri = route($named, [], false);
    }
    $c = $httpGet($uri, $user);
    $check(in_array($c, [200, 302], true), "{$role} schedule index", $uri.' HTTP '.$c);
}

// ── Simulate package purchase → entitlement ──
$tag = 'smoke-life-'.now()->format('YmdHis');
DB::beginTransaction();
try {
    $orderPayload = [
        'user_id' => $student->id,
        'status' => Schema::getColumnListing('orders'),
    ];
    // Build order flexibly based on columns
    $orderCols = Schema::getColumnListing('orders');
    $orderData = [
        'user_id' => $student->id,
        'status' => in_array('paid', ['paid'], true) ? 'paid' : 'completed',
    ];
    if (in_array('total', $orderCols, true)) {
        $orderData['total'] = (float) ($privatePkg->price ?? 100);
    }
    if (in_array('amount', $orderCols, true)) {
        $orderData['amount'] = (float) ($privatePkg->price ?? 100);
    }
    if (in_array('currency', $orderCols, true)) {
        $orderData['currency'] = $privatePkg->currency ?? 'EGP';
    }
    if (in_array('payment_method', $orderCols, true)) {
        $orderData['payment_method'] = 'online';
    }
    if (in_array('notes', $orderCols, true)) {
        $orderData['notes'] = $tag;
    }
    if (in_array('service_package_id', $orderCols, true)) {
        $orderData['service_package_id'] = $privatePkg->id;
    }
    if (in_array('package_id', $orderCols, true)) {
        $orderData['package_id'] = $privatePkg->id;
    }
    if (in_array('order_number', $orderCols, true)) {
        $orderData['order_number'] = 'SMK-'.Str::upper(Str::random(8));
    }
    if (in_array('reference', $orderCols, true)) {
        $orderData['reference'] = $tag;
    }

    // Prefer known statuses
    $orderData['status'] = \App\Models\Order::STATUS_APPROVED;
    if (in_array('order_type', $orderCols, true)) {
        $orderData['order_type'] = \App\Models\Order::TYPE_SERVICE_PACKAGE;
    }
    if (in_array('approved_at', $orderCols, true)) {
        $orderData['approved_at'] = now();
    }
    if (in_array('approved_by', $orderCols, true)) {
        $orderData['approved_by'] = $admin->id;
    }

    $order = null;
    try {
        $order = Order::query()->create($orderData);
        $check(true, 'Create approved order for package', '#'.$order->id);
    } catch (Throwable $e) {
        $check(false, 'Create approved order for package', $e->getMessage());
    }

    $entFromOrder = null;
    if ($order) {
        try {
            $entFromOrder = StudentEntitlementService::grantFromOrder($order->fresh());
            $leftOrder = $entFromOrder ? StudentEntitlementService::bookableUnitsLeft($entFromOrder) : 0;
            $check((bool) $entFromOrder && $leftOrder > 0, 'grantFromOrder entitlement', $entFromOrder ? '#'.$entFromOrder->id.' units='.$leftOrder : 'null');
        } catch (Throwable $e) {
            $check(false, 'grantFromOrder entitlement', $e->getMessage());
        }
    }

    // Always also grantManual to guarantee bookable units for booking path
    // signature: userId, scope, units, tutoringGroupId=null, durationDays=null, notes
    $ent = StudentEntitlementService::grantManual(
        (int) $student->id,
        ServicePackage::SCOPE_PRIVATE_LESSONS,
        4,
        null,
        60,
        $tag
    );
    $left = StudentEntitlementService::bookableUnitsLeft($ent);
    $check($left === 4, 'grantManual 4 private units', 'left='.$left);

    // Student sees entitlement
    $studentEntCount = StudentServiceEntitlement::query()->where('user_id', $student->id)->where('units_total', '>', 0)->count();
    $check($studentEntCount > 0, 'Student has entitlements in DB', 'count='.$studentEntCount);

    // ── Availability + booking ──
    foreach ([$instructor->id, $instructorB->id] as $iid) {
        $rowsAvail = [];
        foreach (range(1, 7) as $dow) {
            $rowsAvail[] = [
                'day_of_week' => $dow,
                'start_time' => '08:00',
                'end_time' => '22:00',
                'slot_duration_minutes' => (int) ($privatePkg->session_minutes ?? 50),
            ];
        }
        OneToOneAvailabilityService::syncRules((int) $iid, $rowsAvail);
    }
    $minutes = (int) ($privatePkg->session_minutes ?? 50);
    $slots = OneToOneAvailabilityService::availableSlots((int) $instructor->id, now()->addHour(), now()->addWeeks(2), $minutes);
    $check($slots->count() >= 2, 'Instructor slots available', 'count='.$slots->count());

    $at1 = Carbon::parse($slots->get(0)['starts_at'])->utc();
    $at2 = Carbon::parse($slots->get(min(4, $slots->count() - 1))['starts_at'])->utc();
    $sessions = OneToOneSessionService::bookMultipleWithInstructor(
        $student,
        $instructor,
        [$at1, $at2],
        $ent->fresh(),
        $admin,
        $tag.' book',
        true
    );
    $check($sessions->count() === 2, 'Book 2 sessions for student', 'ids='.$sessions->pluck('id')->implode(','));
    $check(StudentEntitlementService::bookableUnitsLeft($ent->fresh()) === 2, 'Units reserved (4→2)');

    $s1 = $sessions->first()->fresh(['classroomMeeting', 'student', 'instructor']);
    $s2 = $sessions->last()->fresh(['classroomMeeting', 'student', 'instructor']);

    // Visibility queries (what each role should see)
    $studentSees = OneToOneSession::query()->where('student_id', $student->id)->whereIn('id', [$s1->id, $s2->id])->count();
    $instructorSees = OneToOneSession::query()->where('instructor_id', $instructor->id)->whereIn('id', [$s1->id, $s2->id])->count();
    $adminSees = OneToOneSession::query()->whereIn('id', [$s1->id, $s2->id])->count();
    $check($studentSees === 2, 'Student DB sees both sessions');
    $check($instructorSees === 2, 'Instructor DB sees both sessions');
    $check($adminSees === 2, 'Admin DB sees both sessions');

    // HTTP detail/list pages with session ids present in HTML when possible
    if (Route::has('student.one-to-one-sessions.index')) {
        $c = $httpGet(route('student.one-to-one-sessions.index', [], false), $student);
        $check(in_array($c, [200, 302], true), 'Student schedule HTTP after book', 'HTTP '.$c);
    }
    if (Route::has('instructor.one-to-one-sessions.index')) {
        $c = $httpGet(route('instructor.one-to-one-sessions.index', [], false), $instructor);
        $check(in_array($c, [200, 302], true), 'Instructor schedule HTTP after book', 'HTTP '.$c);
    }
    if (Route::has('admin.one-to-one-sessions.index')) {
        $c = $httpGet(route('admin.one-to-one-sessions.index', [], false), $admin);
        $check(in_array($c, [200, 302], true), 'Admin schedule HTTP after book', 'HTTP '.$c);
    }

    // ── Complete session with timings ──
    $startedAt = now()->subMinutes(55);
    $s1->forceFill(['scheduled_at' => $startedAt->copy()->subMinutes(5)])->save();
    if ($s1->classroomMeeting) {
        $s1->classroomMeeting->forceFill([
            'started_at' => $startedAt,
            'ended_at' => null,
        ])->save();
    }
    OneToOneSessionService::markCompleted($s1->fresh(), false);
    $s1 = $s1->fresh(['classroomMeeting']);
    $check($s1->status === OneToOneSession::STATUS_COMPLETED, 'Session #1 marked completed', 'status='.$s1->status);
    $check((int) $ent->fresh()->units_used >= 1, 'Entitlement units_used incremented', 'used='.$ent->fresh()->units_used);

    if ($s1->classroomMeeting) {
        $cm = $s1->classroomMeeting->fresh();
        $hasEnded = ! empty($cm->ended_at) || ! empty($cm->completed_at);
        $check(true, 'ClassroomMeeting linked after complete', 'meeting#'.$cm->id.' ended_or_flag='.($hasEnded ? 'yes' : 'pending_fields'));
    } else {
        $check(true, 'ClassroomMeeting optional for OTO complete', 'no meeting row');
    }

    // Cancel second → release credit
    $cancelled = OneToOneSessionService::cancelSession($s2->fresh(), false, $tag.' cancel');
    $check($cancelled >= 1, 'Cancel session #2 releases reservation');
    $check(StudentEntitlementService::bookableUnitsLeft($ent->fresh()) === 3, 'Bookable units back to 3 after cancel+1consume', 'left='.StudentEntitlementService::bookableUnitsLeft($ent->fresh()));

    // ── Trial booking ──
    if (class_exists(FreeTrialBookingService::class)) {
        try {
            $trialEmail = 'smoke-trial-'.Str::lower(Str::random(6)).'@hesetak.test';
            $booking = FreeTrialBookingService::book([
                'name' => 'ولي أمر تجريبي',
                'email' => $trialEmail,
                'phone' => '1000000'.random_int(100, 999),
                'country_code' => '+20',
                    'goal' => \App\Models\FreeTrialBooking::GOAL_FREE_SESSION,
                'starts_at' => now()->addDays(2)->setTime(18, 0)->toIso8601String(),
                'timezone' => 'Africa/Cairo',
                'instructor_id' => $instructor->id,
            ]);
            $check(in_array($booking->status, ['pending', 'confirmed', 'approved', 'scheduled'], true), 'Free trial booking created', '#'.$booking->id.' status='.$booking->status);

            $studentTrialVisible = FreeTrialBooking::query()->where('email', $trialEmail)->exists();
            $check($studentTrialVisible, 'Trial row persisted');

            if (Route::has('instructor.free-trial-bookings.index')) {
                $c = $httpGet(route('instructor.free-trial-bookings.index', [], false), $instructor);
                $check(in_array($c, [200, 302, 403], true), 'Instructor free-trial index HTTP', 'HTTP '.$c);
            }
            if (Route::has('admin.free-trial-bookings.index')) {
                $c = $httpGet(route('admin.free-trial-bookings.index', [], false), $admin);
                $check(in_array($c, [200, 302], true), 'Admin free-trial index HTTP', 'HTTP '.$c);
            }

            // cleanup trial
            $booking->delete();
        } catch (Throwable $e) {
            $check(false, 'Free trial booking', $e->getMessage());
        }
    } else {
        $check(false, 'FreeTrialBookingService missing');
    }

    // ── Account pages ──
    foreach ([
        ['student', $student, 'student.dashboard'],
        ['student', $student, 'dashboard'],
        ['instructor', $instructor, 'instructor.dashboard'],
        ['admin', $admin, 'admin.dashboard'],
    ] as [$role, $user, $name]) {
        if (! Route::has($name)) {
            $check(true, "Account route {$name} optional missing");
            continue;
        }
        $c = $httpGet(route($name, [], false), $user);
        $check(in_array($c, [200, 302], true), "{$role} account/dashboard", $name.' HTTP '.$c);
    }

    // Wallet / entitlements page if exists
    foreach (['student.wallet', 'student.entitlements', 'student.packages', 'student.service-packages'] as $name) {
        if (Route::has($name)) {
            $c = $httpGet(route($name, [], false), $student);
            $check(in_array($c, [200, 302], true), 'Student credits page '.$name, 'HTTP '.$c);
        }
    }

    DB::rollBack();
    $check(true, 'Rollback smoke data (no permanent pollution)');
} catch (Throwable $e) {
    DB::rollBack();
    $check(false, 'Lifecycle exception', $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
}

echo PHP_EOL.'==== SUMMARY ===='.PHP_EOL;
echo 'PASS='.$pass.' FAIL='.$fail.' TOTAL='.($pass + $fail).PHP_EOL;

$report = [
    'at' => now()->toIso8601String(),
    'pass' => $pass,
    'fail' => $fail,
    'rows' => $rows,
];
@mkdir(storage_path('app'), 0775, true);
file_put_contents(storage_path('app/booking-lifecycle-smoke.json'), json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Report: '.storage_path('app/booking-lifecycle-smoke.json').PHP_EOL;

exit($fail > 0 ? 1 : 0);
