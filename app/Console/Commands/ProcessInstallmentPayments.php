<?php

namespace App\Console\Commands;

use App\Models\InstallmentAgreement;
use App\Models\InstallmentPayment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessInstallmentPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:process {--dry-run : استعراض النتائج دون حفظ التغييرات}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تحديث حالات أقساط الطلاب، وتوليد إشعارات التذكير والتأخر.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = Carbon::today();
        $reminderThreshold = $today->copy()->addDays(3);

        $this->info('معالجة الأقساط المستحقة...');

        $overduePayments = InstallmentPayment::query()
            ->where('status', InstallmentPayment::STATUS_PENDING)
            ->whereDate('due_date', '<', $today)
            ->get();

        foreach ($overduePayments as $payment) {
            $agreement = $payment->agreement;

            if (!$agreement) {
                continue;
            }

            if (!$dryRun) {
                $payment->markAsOverdue();
                $agreement->refreshStatus();
            }

            if (!$payment->overdue_notified_at) {
                $this->sendNotification(
                    $agreement,
                    $payment,
                    'installment_overdue',
                    'قسط متأخر',
                    sprintf(
                        'يوجد عليك قسط مستحق بقيمة %s منذ %s. يرجى السداد في أقرب وقت لتجنب إيقاف الخدمة.',
                        format_money($payment->amount),
                        $payment->due_date->format('d/m/Y')
                    ),
                    'urgent',
                    $dryRun
                );

                if (!$dryRun) {
                    $payment->markOverdueNotified();
                }
            }
        }

        $this->info(sprintf('تم رصد %d دفعة متأخرة.', $overduePayments->count()));

        $upcomingPayments = InstallmentPayment::query()
            ->where('status', InstallmentPayment::STATUS_PENDING)
            ->whereDate('due_date', '>=', $today)
            ->whereDate('due_date', '<=', $reminderThreshold)
            ->get();

        foreach ($upcomingPayments as $payment) {
            $agreement = $payment->agreement;
            if (!$agreement || $payment->reminder_sent_at) {
                continue;
            }

            $days = $today->diffInDays($payment->due_date);
            $message = $days === 0
                ? sprintf('اليوم هو موعد سداد قسط بقيمة %s. يرجى السداد لتجنب التأخير.', format_money($payment->amount))
                : sprintf('تبقى %d يوم/أيام على موعد سداد قسط بقيمة %s (%s).', $days, format_money($payment->amount), $payment->due_date->format('d/m/Y'));

            $this->sendNotification(
                $agreement,
                $payment,
                'installment_reminder',
                'تذكير بقسط قادم',
                $message,
                'high',
                $dryRun
            );

            if (!$dryRun) {
                $payment->markReminderSent();
            }
        }

        $this->info(sprintf('تم إرسال %d تذكير للأقساط القادمة.', $upcomingPayments->count()));

        $this->info('اكتملت عملية معالجة الأقساط.');

        return self::SUCCESS;
    }

    protected function sendNotification(
        InstallmentAgreement $agreement,
        InstallmentPayment $payment,
        string $type,
        string $title,
        string $message,
        string $priority = 'normal',
        bool $dryRun = false
    ): void {
        $student = $agreement->student;

        if (!$student) {
            return;
        }

        $data = [
            'sender_id' => auth()->id() ?? null,
            'title' => $title,
            'message' => $message,
            'type' => $type === 'installment_overdue' ? 'warning' : 'reminder',
            'priority' => $priority,
            'audience' => 'student',
            'action_url' => route('student.invoices.index'),
            'action_text' => 'عرض الأقساط',
            'target_type' => 'individual',
            'target_id' => $student->id,
            'data' => [
                'agreement_id' => $agreement->id,
                'payment_id' => $payment->id,
                'due_date' => $payment->due_date?->toDateString(),
                'amount' => $payment->amount,
            ],
        ];

        if ($dryRun) {
            Log::info('Installment notification (dry-run)', $data);
            $this->line(sprintf('سيتم إرسال إشعار إلى %s: %s', $student->name, $title));
            return;
        }

        Notification::sendToUser($student->id, $data);
    }
}


