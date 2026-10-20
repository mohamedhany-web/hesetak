<?php

namespace App\Console\Commands;

use App\Models\LiveSession;
use App\Models\Notification;
use App\Models\User;
use App\Services\PlatformNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendLiveSessionRemindersCommand extends Command
{
    protected $signature = 'live:send-reminders {--minutes=10 : Minutes before session to send reminder}';

    protected $description = 'Send reminder notifications to enrolled students before upcoming live sessions';

    public function handle(PlatformNotifier $notifier): int
    {
        $minutes = (int) $this->option('minutes');

        $sessions = LiveSession::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                now()->addMinutes($minutes - 1),
                now()->addMinutes($minutes + 1),
            ])
            ->with(['course', 'instructor'])
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No sessions starting in ~'.$minutes.' minutes.');

            return self::SUCCESS;
        }

        $totalNotified = 0;

        foreach ($sessions as $session) {
            $sessionNotified = 0;
            $students = $this->getEligibleStudents($session);
            $dedupe = 'live_reminder:'.$session->id.':'.$minutes;

            foreach ($students as $student) {
                try {
                    $alreadySent = Notification::query()
                        ->where('user_id', $student->id)
                        ->where('type', 'reminder')
                        ->where(function ($q) use ($dedupe, $session) {
                            $q->where('data->dedupe', $dedupe)
                                ->orWhere('data->session_id', $session->id);
                        })
                        ->where('created_at', '>=', now()->subHours(6))
                        ->exists();

                    if ($alreadySent) {
                        continue;
                    }

                    $courseName = $session->course?->title ?? 'جلسة عامة';
                    $title = 'تذكير: بث مباشر بعد '.$minutes.' دقائق';
                    $message = 'جلسة «'.$session->title.'» ('.$courseName.') تبدأ بعد '.$minutes.' دقيقة.';

                    $created = $notifier->notify($student, [
                        'title' => $title,
                        'message' => $message,
                        'type' => 'reminder',
                        'priority' => 'high',
                        'action_url' => '/student/live-sessions/'.$session->id,
                        'action_text' => 'دخول البث',
                        'sender_id' => null,
                        'data' => [
                            'dedupe' => $dedupe,
                            'session_id' => $session->id,
                            'source' => 'live:send-reminders',
                        ],
                    ]);

                    if ($created) {
                        $sessionNotified++;
                        $totalNotified++;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Failed to send live session reminder to user {$student->id}: ".$e->getMessage());
                }
            }

            $this->info('Session "'.$session->title.'": notified '.$sessionNotified.' students.');
        }

        $this->info('Done. Total notifications sent: '.$totalNotified);

        return self::SUCCESS;
    }

    private function getEligibleStudents(LiveSession $session)
    {
        if ($session->course_id && $session->require_enrollment) {
            $enrolledUserIds = DB::table('online_enrollments')
                ->where('advanced_course_id', $session->course_id)
                ->where('is_active', true)
                ->pluck('user_id');

            return User::whereIn('id', $enrolledUserIds)->get();
        }

        if ($session->course_id && ! $session->require_enrollment) {
            return User::where('role', 'student')->where('is_active', true)->get();
        }

        if (! $session->course_id) {
            return User::where('role', 'student')->where('is_active', true)->get();
        }

        return collect();
    }
}
