<?php

namespace App\Notifications;

use App\Models\LiveSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * بريد تذكير البث فقط — الإشعار داخل التطبيق عبر PlatformNotifier / جدول notifications المخصص.
 */
class LiveSessionReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public LiveSession $session,
        public int $minutesBefore = 10
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $courseName = $this->session->course?->title ?? 'جلسة عامة';
        $instructorName = $this->session->instructor?->name ?? '';
        $time = $this->session->scheduled_at?->format('H:i');

        return (new MailMessage)
            ->subject("تذكير: جلسة بث مباشر تبدأ بعد {$this->minutesBefore} دقيقة")
            ->greeting("مرحباً {$notifiable->name}")
            ->line("جلسة البث المباشر «{$this->session->title}» ستبدأ بعد {$this->minutesBefore} دقيقة.")
            ->line("الكورس: {$courseName}")
            ->line("المدرب: {$instructorName}")
            ->line("الموعد: {$time}")
            ->action('دخول البث المباشر', url('/student/live-sessions/'.$this->session->id))
            ->line('لا تفوّت الجلسة — جهّز نفسك الآن!');
    }
}
