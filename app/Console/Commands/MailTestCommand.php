<?php

namespace App\Console\Commands;

use App\Mail\InAppNotificationMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\PlatformNotifier;
use App\Support\EmailBranding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test
        {email? : عنوان Gmail أو أي بريد للاختبار}
        {--notification : إنشاء إشعار داخل التطبيق أيضاً}
        {--branded : إرسال قالب الإشعار باللوجو دون إنشاء سجل دائم إن لم يوجد مستخدم}';

    protected $description = 'اختبار إرسال بريد SMTP من حصتك (ومسار إشعار الطالب إن طُلب)';

    public function handle(PlatformNotifier $notifier): int
    {
        $email = trim((string) ($this->argument('email') ?: ''));
        if ($email === '') {
            $email = (string) (User::query()->where('email', 'like', '%@gmail.com')->value('email') ?: '');
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('يرجى تمرير بريد صالح: php artisan mail:test you@gmail.com');

            return self::FAILURE;
        }

        $this->info('Mailer: '.config('mail.default'));
        $this->info('From: '.config('mail.from.address').' / '.EmailBranding::brandName());
        $this->info('Logo: '.EmailBranding::logoUrl());
        $this->info('To: '.$email);

        // قالب الهوية الجديد (HTML + نص) — يمنع الاعتماد على Mail::raw الذي يبدو spam
        $demo = new Notification([
            'title' => 'مرحباً بك في حصتك',
            'message' => "هذه رسالة تجريبية بتصميم المنصة والشعار.\nإن ظهرت بالوارد وليست في السبام، فالهوية والصلاحية تعملان بشكل صحيح.",
            'type' => 'system',
            'priority' => 'normal',
            'action_url' => '/notifications',
            'action_text' => 'فتح الإشعارات',
            'data' => ['skip_email' => true, 'source' => 'mail:test-branded'],
        ]);
        $demo->id = 0;

        try {
            Mail::to($email)->send(new InAppNotificationMail($demo, 'صديق حصتك'));
            $this->info('✓ أُرسل القالب باللوجو بنجاح.');
        } catch (\Throwable $e) {
            $this->error('✗ فشل القالب باللوجو: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('notification')) {
            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->warn('لا يوجد مستخدم بهذا البريد — تخطّي إشعار التطبيق.');

                return self::SUCCESS;
            }

            $notification = $notifier->notify($user, [
                'title' => 'اختبار إشعار حصتك',
                'message' => 'وصلك هذا الإشعار داخل التطبيق ومعه نسخة بريدية بالتصميم الجديد. إن رأيت الاثنين فالنظام يعمل.',
                'type' => 'system',
                'priority' => 'normal',
                'action_url' => '/notifications',
                'action_text' => 'فتح الإشعارات',
                'data' => ['source' => 'mail:test'],
            ]);

            if ($notification) {
                $this->info('✓ إشعار داخل التطبيق #'.$notification->id.' (ونسخة بريد عبر المسار التلقائي).');
            } else {
                $this->error('✗ تعذّر إنشاء الإشعار.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
