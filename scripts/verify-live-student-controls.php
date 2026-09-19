<?php

/**
 * التحقق من إصلاحات غرفة البث للطالب + الإنهاء التلقائي + منع الدخول قبل البدء.
 *
 * Usage: php scripts/verify-live-student-controls.php
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Http\Controllers\ClassroomJoinController;
use App\Http\Controllers\Student\LiveSessionController;
use App\Models\AdvancedCourse;
use App\Models\ClassroomMeeting;
use App\Models\LiveSession;
use App\Models\LiveSetting;
use App\Models\SessionAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

$errors = [];
$checks = [];

function check(string $name, bool $ok, array &$checks, array &$errors, ?string $detail = null): void
{
    $checks[$name] = $ok;
    if (! $ok) {
        $errors[] = $name.($detail ? ': '.$detail : '');
    }
}

function httpFetch($kernel, string $method, string $uri, ?User $user = null, array $data = []): array
{
    if ($user) {
        Auth::login($user);
    }

    $request = Request::create($uri, $method, $data);
    if ($user) {
        $request->setUserResolver(fn () => $user);
    }

    try {
        $response = $kernel->handle($request);
        $body = $response->getContent() ?: '';
        $code = $response->getStatusCode();
        $kernel->terminate($request, $response);
    } catch (\Throwable $e) {
        return ['code' => 500, 'body' => $e->getMessage()];
    } finally {
        Auth::logout();
    }

    return ['code' => $code, 'body' => $body];
}

function renderStudentRoom(User $student, LiveSession $session): array
{
    Auth::login($student);
    try {
        $request = Request::create('/student/live-sessions/'.$session->id.'/join', 'POST');
        $request->setUserResolver(fn () => $student);
        app()->instance('request', $request);
        $response = app(LiveSessionController::class)->join($session);
        $html = $response instanceof \Illuminate\View\View ? $response->render() : (string) $response;

        return ['ok' => true, 'body' => $html];
    } catch (\Throwable $e) {
        return ['ok' => false, 'body' => $e->getMessage()];
    } finally {
        Auth::logout();
    }
}

// ── 1. إعدادات الإنهاء ───────────────────────────────────────
$autoEnd = (int) LiveSetting::get('auto_end_minutes', 0);
$idleEnd = (int) LiveSetting::get('idle_end_minutes', 0);
check('setting_auto_end_lte_90', $autoEnd > 0 && $autoEnd <= 90, $checks, $errors, "auto_end={$autoEnd}");
check('setting_idle_end_exists', $idleEnd >= 5, $checks, $errors, "idle_end={$idleEnd}");

// ── 2. قالب غرفة الطالب يحتوي أدوات الميكروفون ───────────────
$roomBlade = file_get_contents(base_path('resources/views/student/live-sessions/room.blade.php'));
check('student_toolbar_has_microphone', str_contains($roomBlade, "'microphone'"), $checks, $errors);
check('student_toolbar_has_camera', str_contains($roomBlade, "'camera'"), $checks, $errors);
check('student_toolbar_not_empty_array', ! preg_match("/TOOLBAR_BUTTONS:\\s*\\[\\s*\\]/", $roomBlade), $checks, $errors);
check('student_toolbar_always_visible', str_contains($roomBlade, 'TOOLBAR_ALWAYS_VISIBLE: true'), $checks, $errors);

// ── 3. Classroom join يمنع ما قبل البدء في الكود ─────────────
$joinController = file_get_contents(base_path('app/Http/Controllers/ClassroomJoinController.php'));
check('join_blocks_before_start', str_contains($joinController, 'المحاضرة لم تبدأ بعد'), $checks, $errors);

$joinBlade = file_get_contents(base_path('resources/views/classroom/join.blade.php'));
check('join_blade_not_started_ui', str_contains($joinBlade, 'المحاضرة لم تبدأ بعد'), $checks, $errors);

$classroomCtrl = file_get_contents(base_path('app/Http/Controllers/Student/ClassroomController.php'));
check('duration_uses_default_not_max', str_contains($classroomCtrl, 'classroom_default_duration_minutes'), $checks, $errors);

// ── 4. أمر الإنهاء التلقائي موجود ومسجّل ─────────────────────
check('command_registered', array_key_exists('live:auto-end-sessions', Artisan::all()), $checks, $errors);
$cmdSrc = file_get_contents(base_path('app/Console/Commands/AutoEndLiveSessionsCommand.php'));
check('command_has_idle_logic', str_contains($cmdSrc, 'idle_end_minutes') && str_contains($cmdSrc, 'instructorIsAbsent'), $checks, $errors);

// ── 5. اختبار منطقي: جلسة live بلا مدرب نشط تُنهى بالأمر ─────
$instructor = User::query()->where(function ($q) {
    $q->where('role', 'instructor')->orWhere('role', 'super_admin')->orWhere('role', 'admin');
})->first();

$student = User::query()->where('role', 'student')->where('is_employee', false)->first();
$course = AdvancedCourse::query()->where('is_active', true)->first()
    ?? AdvancedCourse::query()->first();

$createdSession = null;
$createdMeeting = null;

if ($instructor && $course) {
    $createdSession = LiveSession::create([
        'course_id' => $course->id,
        'instructor_id' => $instructor->id,
        'title' => '[VERIFY LIVE] '.Str::random(6),
        'room_name' => 'verify-'.Str::random(8),
        'status' => 'live',
        'scheduled_at' => now()->subHour(),
        'started_at' => now()->subMinutes(max($idleEnd, 25)),
        'allow_chat' => true,
        'allow_screen_share' => true,
        'mute_on_join' => true,
        'video_off_on_join' => true,
        'require_enrollment' => false,
    ]);

    // مدرب غادر منذ فترة أطول من idle
    SessionAttendance::create([
        'session_id' => $createdSession->id,
        'user_id' => $instructor->id,
        'joined_at' => now()->subMinutes(max($idleEnd, 25) + 5),
        'left_at' => now()->subMinutes(max($idleEnd, 25)),
        'duration_seconds' => 300,
        'role_in_session' => 'instructor',
    ]);

    Artisan::call('live:auto-end-sessions');
    $createdSession->refresh();
    check('idle_session_auto_ended', $createdSession->status === 'ended', $checks, $errors, 'status='.$createdSession->status);

    // جلسة حية مع مدرب ما زال داخلها لا تُنهى بالخمول
    $activeSession = LiveSession::create([
        'course_id' => $course->id,
        'instructor_id' => $instructor->id,
        'title' => '[VERIFY LIVE ACTIVE] '.Str::random(6),
        'room_name' => 'verify-active-'.Str::random(8),
        'status' => 'live',
        'scheduled_at' => now()->subMinutes(10),
        'started_at' => now()->subMinutes(10),
        'allow_chat' => true,
        'allow_screen_share' => true,
        'mute_on_join' => false,
        'video_off_on_join' => false,
        'require_enrollment' => false,
    ]);
    SessionAttendance::create([
        'session_id' => $activeSession->id,
        'user_id' => $instructor->id,
        'joined_at' => now()->subMinutes(10),
        'left_at' => null,
        'role_in_session' => 'instructor',
    ]);
    Artisan::call('live:auto-end-sessions');
    $activeSession->refresh();
    check('active_instructor_session_stays_live', $activeSession->status === 'live', $checks, $errors, 'status='.$activeSession->status);

    // غرفة طالب: رندر مباشر دون CSRF
    if ($student) {
        $room = renderStudentRoom($student, $activeSession);
        check('student_join_render_ok', $room['ok'], $checks, $errors, $room['ok'] ? null : $room['body']);
        if ($room['ok']) {
            check('student_room_html_has_microphone', str_contains($room['body'], 'microphone'), $checks, $errors);
            check('student_room_html_toolbar_not_empty', ! preg_match('/TOOLBAR_BUTTONS:\\s*\\[\\s*\\]/', $room['body']), $checks, $errors);
            check('student_room_html_has_camera', str_contains($room['body'], 'camera'), $checks, $errors);
        }
    } else {
        check('student_account_available', false, $checks, $errors, 'no student user');
    }

    SessionAttendance::where('session_id', $activeSession->id)->delete();
    $activeSession->delete();
} else {
    check('fixtures_instructor_course', false, $checks, $errors, 'missing instructor or course');
}

// ── 6. Classroom: دخول قبل البدء يجب أن يفشل ─────────────────
if ($instructor) {
    $code = strtoupper(Str::random(8));
    $createdMeeting = ClassroomMeeting::create([
        'user_id' => $instructor->id,
        'code' => $code,
        'title' => '[VERIFY CLASSROOM] '.$code,
        'room_name' => 'حصتك-'.$code,
        'started_at' => null,
        'ended_at' => null,
        'planned_duration_minutes' => 60,
        'max_participants' => 10,
    ]);

    $show = httpFetch($kernel, 'GET', '/classroom/join/'.$code);
    check('classroom_join_page_not_started', $show['code'] < 400 && str_contains($show['body'], 'المحاضرة لم تبدأ بعد'), $checks, $errors, 'HTTP '.$show['code']);

    $enterReq = Request::create('/classroom/join/'.$code.'/enter', 'POST', [
        'display_name' => 'Guest Test',
    ]);
    $enterReq->headers->set('Accept', 'application/json');
    app()->instance('request', $enterReq);
    $enterRes = app(ClassroomJoinController::class)->enter($enterReq, $code);
    $enterCode = $enterRes->getStatusCode();
    $enterBody = method_exists($enterRes, 'getData')
        ? $enterRes->getData(true)
        : (json_decode($enterRes->getContent() ?: '{}', true) ?: []);

    check('classroom_enter_blocked_before_start', $enterCode >= 400, $checks, $errors, "HTTP {$enterCode}");
    check('classroom_enter_message_ar', str_contains((string) ($enterBody['message'] ?? ''), 'لم تبدأ'), $checks, $errors, json_encode($enterBody, JSON_UNESCAPED_UNICODE));

    $createdMeeting->update(['started_at' => now()]);
    $planned = (int) $createdMeeting->planned_duration_minutes;
    check('planned_duration_respected', $planned === 60, $checks, $errors, "planned={$planned}");
} else {
    check('classroom_fixtures', false, $checks, $errors, 'no instructor');
}

// ── تنظيف ────────────────────────────────────────────────────
if ($createdSession) {
    SessionAttendance::where('session_id', $createdSession->id)->delete();
    $createdSession->delete();
}
if ($createdMeeting) {
    $createdMeeting->delete();
}

$passed = count(array_filter($checks));
$total = count($checks);
$ok = empty($errors);

$result = [
    'ok' => $ok,
    'passed' => $passed,
    'total' => $total,
    'settings' => [
        'auto_end_minutes' => $autoEnd,
        'idle_end_minutes' => $idleEnd,
    ],
    'failed' => $errors,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($ok ? 0 : 1);
