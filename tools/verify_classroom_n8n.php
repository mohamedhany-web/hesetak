<?php

declare(strict_types=1);

/**
 * تحقق محلي من: إعدادات n8n، رابط التحميل المؤقت R2، إنشاء تقرير، واستدعاء callback.
 *
 *   php tools/verify_classroom_n8n.php [meeting_id]
 */

$meetingId = (int) ($argv[1] ?? 29);

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ClassroomMeeting;
use App\Models\ClassroomMeetingReport;
use App\Models\IntegrationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

echo "=== فصول حصتك + n8n verification ===\n";
echo "Meeting ID: {$meetingId}\n\n";

$token = IntegrationSetting::get('n8n_token', config('services.n8n.token'));
$webhook = IntegrationSetting::get('n8n_live_session_report_webhook', config('services.n8n.live_session_report_webhook'));

echo "[1] Integration / config\n";
echo '    n8n_token: '.($token !== null && $token !== '' ? '(set, len='.strlen($token).')' : '(empty)')."\n";
echo '    webhook: '.($webhook ?: '(empty — set in Admin → n8n or .env N8N_LIVE_SESSION_REPORT_WEBHOOK)')."\n\n";

$meeting = ClassroomMeeting::query()->find($meetingId);
if (! $meeting) {
    fwrite(STDERR, "Meeting {$meetingId} not found.\n");
    exit(1);
}

echo "[2] Meeting recording\n";
echo '    disk: '.($meeting->recording_disk ?? 'null')."\n";
echo '    audio_path: '.($meeting->recording_audio_path ?? 'null')."\n";
echo '    video_path: '.($meeting->recording_path ?? 'null')."\n";
echo '    ended_at: '.($meeting->ended_at ? $meeting->ended_at->toIso8601String() : 'null')."\n\n";

$audioUrl = $meeting->recording_audio_download_url;
$videoUrl = $meeting->recording_download_url;
$primary = $audioUrl ?? $videoUrl;

echo "[3] Presigned URLs (R2 / live_recordings_r2)\n";
echo '    audio_download_url: '.($audioUrl ? 'OK ('.strlen($audioUrl).' chars)' : 'NULL')."\n";
echo '    video_download_url: '.($videoUrl ? 'OK' : 'NULL')."\n";
echo '    primary for n8n: '.($primary ? 'OK' : 'FAIL — cannot call n8n without URL')."\n\n";

if (! $primary) {
    fwrite(STDERR, "Abort: no download URL. Check R2 disk config and meeting paths.\n");
    exit(1);
}

echo "[4] Test outbound POST to n8n webhook (short timeout)\n";
if (! $webhook || ! $token) {
    echo "    SKIP — webhook or token missing.\n\n";
} else {
    try {
        $probe = Http::timeout(8)->connectTimeout(5)->withHeaders([
            'X-N8N-Token' => $token,
            'Accept' => 'application/json',
        ])->post($webhook, ['probe' => true, 'classroom_meeting_id' => $meeting->id]);

        echo '    HTTP '.$probe->status()."\n";
        $body = $probe->body();
        echo '    body (first 400 chars): '.substr($body, 0, 400).(strlen($body) > 400 ? '…' : '')."\n\n";
    } catch (Throwable $e) {
        echo '    ERROR: '.$e->getMessage()."\n\n";
    }
}

echo "[5] Callback endpoint (create temp report + PATCH)\n";
$userId = (int) $meeting->user_id;
$report = ClassroomMeetingReport::query()->create([
    'classroom_meeting_id' => $meeting->id,
    'user_id' => $userId,
    'title' => 'verify_classroom_n8n.php — '.date('c'),
    'status' => 'processing',
    'audio_path' => $meeting->recording_audio_path,
    'storage_disk' => $meeting->recording_disk,
]);
echo '    created report id='.$report->id."\n";

if (! $token) {
    echo "    SKIP callback — no n8n_token in DB/config.\n";
    exit(0);
}

$path = '/api/n8n/classroom-meeting-reports/'.$report->id;
$payload = [
    'status' => 'completed',
    'title' => 'تحقق آلي',
    'summary' => "تقرير تجريبي من tools/verify_classroom_n8n.php في ".date('Y-m-d H:i:s')." للاجتماع #{$meeting->id}.",
    'n8n_execution_id' => 'verify-n8n-run',
];

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::create(
    $path,
    'PATCH',
    [],
    [],
    [],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_CONTENT_TYPE' => 'application/json',
        'HTTP_X_N8N_TOKEN' => $token,
    ],
    json_encode($payload, JSON_UNESCAPED_UNICODE)
);
$response = $kernel->handle($request);
$code = $response->getStatusCode();
$body = $response->getContent();
$kernel->terminate($request, $response);

echo "    internal PATCH {$path}\n";
echo "    HTTP {$code}\n";
echo '    body: '.$body."\n";

$report->refresh();
echo '    DB status='.$report->status.' summary_len='.strlen((string) $report->summary)."\n";

$rid = $report->id;
if ($code >= 200 && $code < 300 && $report->status === 'completed') {
    echo "\nOK: callback path works with current n8n_token.\n";
    $report->delete();
    echo "    (temp report #{$rid} deleted)\n";
    exit(0);
}

$report->delete();
echo "\nFAIL: fix route / token / validation. Temp report #{$rid} deleted.\n";
exit(1);
