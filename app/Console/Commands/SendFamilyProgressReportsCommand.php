<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FamilyProgressReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SendFamilyProgressReportsCommand extends Command
{
    protected $signature = 'reports:send-family-progress
        {--channel=email : email|whatsapp|both}
        {--student= : Optional student user id}
        {--limit=200 : Max students}';

    protected $description = 'إرسال تقارير العائلة الدورية المبسّطة (نقاط قوة + مؤشرات تحسّن)';

    public function handle(FamilyProgressReportService $family): int
    {
        if (! Schema::hasTable('family_progress_reports')) {
            $this->error('family_progress_reports table missing — run migrations.');

            return self::FAILURE;
        }

        $channel = (string) $this->option('channel');
        if (! in_array($channel, ['email', 'whatsapp', 'both'], true)) {
            $channel = 'email';
        }

        $query = User::query()
            ->where('role', 'student')
            ->where('is_active', true)
            ->orderBy('id');

        if ($this->option('student')) {
            $query->where('id', (int) $this->option('student'));
        }

        $limit = max(1, min(1000, (int) $this->option('limit')));
        $sent = 0;
        $failed = 0;

        $query->limit($limit)->chunkById(50, function ($students) use ($family, $channel, &$sent, &$failed) {
            foreach ($students as $student) {
                $report = $family->sendForStudent($student, $channel);
                if ($report && $report->status === 'sent') {
                    $sent++;
                } else {
                    $failed++;
                }
            }
        });

        $this->info("Family progress reports — sent: {$sent}, failed/skipped: {$failed}");

        return self::SUCCESS;
    }
}
