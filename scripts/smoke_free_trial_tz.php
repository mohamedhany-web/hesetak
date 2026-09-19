<?php

/**
 * Live smoke for free-trial request + timezone helpers (run: php scripts/smoke_free_trial_tz.php)
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FreeTrialBookingService;
use App\Support\AppTimezone;
use Carbon\Carbon;

config(['app.timezone' => 'UTC', 'platform.academy_timezone' => 'Africa/Cairo']);
Carbon::setTestNow(Carbon::parse('2026-03-10 12:00:00', 'UTC'));

$errors = [];

foreach ([
    [9, 'good'], [19, 'good'], [6, 'caution'], [21, 'caution'], [2, 'poor'], [23, 'poor'],
] as [$h, $expect]) {
    $got = AppTimezone::slotQualityForHour($h);
    if ($got !== $expect) {
        $errors[] = "quality hour {$h}: expected {$expect}, got {$got}";
    }
}

if (AppTimezone::timezoneForUsState('نيويورك') !== 'America/New_York') {
    $errors[] = 'NY state map failed';
}
if (AppTimezone::timezoneForUsState('كاليفورنيا') !== 'America/Los_Angeles') {
    $errors[] = 'CA state map failed';
}

// Without instructor_id → no admin weekly windows anymore
$slots = FreeTrialBookingService::availableSlots(
    Carbon::parse('2026-03-10 12:00:00', 'UTC'),
    Carbon::parse('2026-03-17 23:59:59', 'UTC'),
    'America/New_York'
);
echo 'slots_without_instructor='.$slots->count().PHP_EOL;
if (! $slots->isEmpty()) {
    $errors[] = 'Expected empty slots without instructor_id';
}

try {
    $booking = FreeTrialBookingService::book([
        'name' => 'Smoke Parent',
        'email' => 'smoke-tz-'.uniqid().'@example.com',
        'phone' => '512345678',
        'country_code' => '+966',
        'goal' => 'trial',
        'starts_at' => '2026-03-15T18:00:00+02:00',
        'timezone' => 'America/New_York',
        'us_state' => 'نيويورك',
    ]);
    echo 'booked_id='.$booking->id.' status='.$booking->status.' tz='.$booking->timezone.PHP_EOL;
    if ($booking->status !== 'pending') {
        $errors[] = 'expected pending request status';
    }
    if ($booking->timezone !== 'America/New_York') {
        $errors[] = 'booking timezone not saved';
    }
    $booking->delete();
} catch (Throwable $e) {
    $errors[] = 'book failed: '.$e->getMessage();
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/free-trial/slots?days=7&timezone=America/Chicago', 'GET');
$response = $kernel->handle($request);
echo 'http_slots_status='.$response->getStatusCode().PHP_EOL;
if ($response->getStatusCode() !== 422) {
    $errors[] = 'HTTP slots without instructor should be 422, got '.$response->getStatusCode();
}
$kernel->terminate($request, $response);

Carbon::setTestNow();

if ($errors) {
    echo "FAIL\n";
    foreach ($errors as $e) {
        echo " - {$e}\n";
    }
    exit(1);
}

echo "OK\n";
