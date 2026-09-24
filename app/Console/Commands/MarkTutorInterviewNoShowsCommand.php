<?php

namespace App\Console\Commands;

use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Services\TutorInterviewBookingService;
use Illuminate\Console\Command;

class MarkTutorInterviewNoShowsCommand extends Command
{
    protected $signature = 'tutor:interviews-mark-no-shows';

    protected $description = 'حجب المرشحين المتغيبين عن مقابلات التوظيف بعد انتهاء الموعد + فترة السماح';

    public function handle(TutorInterviewBookingService $booking): int
    {
        $grace = TutorHiringSetting::int('no_show_grace_minutes', 15);
        $cutoff = now()->subMinutes(max(0, $grace));

        $query = TutorInterview::query()
            ->where('status', TutorInterview::STATUS_SCHEDULED)
            ->where(function ($q) use ($cutoff) {
                $q->where(function ($inner) use ($cutoff) {
                    $inner->whereNotNull('ends_at')->where('ends_at', '<=', $cutoff);
                })->orWhere(function ($inner) use ($cutoff) {
                    $inner->whereNull('ends_at')->where('scheduled_at', '<=', $cutoff);
                });
            });

        $count = 0;
        $query->orderBy('id')->chunkById(50, function ($rows) use ($booking, &$count) {
            foreach ($rows as $interview) {
                $booking->markNoShow($interview);
                $count++;
            }
        });

        $this->info("Marked {$count} interview(s) as no-show.");

        return self::SUCCESS;
    }
}
