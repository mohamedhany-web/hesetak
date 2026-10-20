<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * نقطة تشغيل الجدولة والطابور على الاستضافة المشتركة (Hostinger)
 * عندما لا يتوفر crontab من SSH — يُستدعى من Cron في hPanel كل دقيقة.
 */
class CronHeartbeatController extends Controller
{
    public function run(Request $request)
    {
        $expected = (string) config('app.cron_token', '');
        $given = (string) ($request->query('token') ?: $request->header('X-Cron-Token') ?: '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            abort(403, 'Forbidden');
        }

        $scheduleExit = Artisan::call('schedule:run');
        $scheduleOutput = trim(Artisan::output());

        $queueExit = Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 45,
            '--tries' => 3,
            '--queue' => 'default,registrations,emails,high',
        ]);
        $queueOutput = trim(Artisan::output());

        Log::info('ops.cron.heartbeat', [
            'schedule_exit' => $scheduleExit,
            'queue_exit' => $queueExit,
        ]);

        return response()->json([
            'ok' => true,
            'at' => now()->toIso8601String(),
            'schedule_exit' => $scheduleExit,
            'queue_exit' => $queueExit,
            'schedule' => mb_substr($scheduleOutput, 0, 500),
            'queue' => mb_substr($queueOutput, 0, 500),
        ]);
    }
}
