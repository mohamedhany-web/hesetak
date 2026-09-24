<?php

namespace App\Services;

use App\Mail\FamilyProgressReportMail;
use App\Models\FamilyProgressReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class FamilyProgressReportService
{
    public function __construct(
        protected StudentProgressAnalyticsService $analytics,
        protected AdaptiveLearningService $adaptive,
    ) {}

    /**
     * ابنِ تقريراً مبسّطاً لولي الأمر (نقاط قوة + مؤشرات تحسّن).
     *
     * @return array<string, mixed>
     */
    public function buildFamilyPayload(User $student): array
    {
        $comparison = $this->analytics->comparePeriods($student);
        $suggestions = $this->adaptive->activeFor($student)->take(5)->map(fn ($s) => [
            'title' => $s->title,
            'reason' => $s->reason,
            'priority' => $s->priority,
            'action_url' => $s->action_url,
        ])->values()->all();

        $shareUrl = method_exists($student, 'progressShareUrl')
            ? $student->progressShareUrl()
            : route('public.parent-progress');

        return [
            'student_name' => $student->name,
            'period' => $comparison['period'],
            'previous_period' => $comparison['previous_period'],
            'trend' => $comparison['trend'],
            'current' => $comparison['current'],
            'previous' => $comparison['previous'],
            'deltas' => $comparison['deltas'],
            'strengths' => $comparison['strengths'],
            'improvements' => $comparison['improvements'],
            'suggestions' => $suggestions,
            'share_url' => $shareUrl,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function sendForStudent(User $student, string $channel = 'email'): ?FamilyProgressReport
    {
        if (! Schema::hasTable('family_progress_reports')) {
            return null;
        }

        $parent = $this->resolveParent($student);
        $payload = $this->buildFamilyPayload($student);
        $periodKey = (string) ($payload['period']['key'] ?? now()->format('Y-m'));

        $report = FamilyProgressReport::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'period_key' => $periodKey,
            ],
            [
                'parent_id' => $parent?->id,
                'payload' => $payload,
                'delivery_channel' => $channel,
                'status' => FamilyProgressReport::STATUS_PENDING,
            ]
        );

        $sent = false;

        if (in_array($channel, ['email', 'both'], true)) {
            $emails = collect([
                $parent?->email,
                $student->email,
            ])->filter()->unique()->values();

            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new FamilyProgressReportMail($student, $payload));
                    $sent = true;
                } catch (\Throwable $e) {
                    Log::warning('Family progress email failed', [
                        'student_id' => $student->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (in_array($channel, ['whatsapp', 'both'], true) && $parent && filled($parent->phone)) {
            try {
                $wa = app(WhatsAppService::class);
                if (method_exists($wa, 'sendMessage')) {
                    $lines = [
                        'تقرير عائلة حصتك — '.$student->name,
                        'الفترة: '.($payload['period']['label'] ?? ''),
                        'نقاط قوة: '.implode(' | ', array_slice($payload['strengths'] ?? [], 0, 2)),
                        'للتحسين: '.implode(' | ', array_slice($payload['improvements'] ?? [], 0, 2)),
                        'التفاصيل: '.$payload['share_url'],
                    ];
                    $wa->sendMessage((string) $parent->phone, implode("\n", $lines));
                    $sent = true;
                }
            } catch (\Throwable $e) {
                Log::debug('Family WhatsApp skipped', ['error' => $e->getMessage()]);
            }
        }

        $report->update([
            'status' => $sent ? FamilyProgressReport::STATUS_SENT : FamilyProgressReport::STATUS_FAILED,
            'sent_at' => $sent ? now() : null,
        ]);

        return $report->fresh();
    }

    protected function resolveParent(User $student): ?User
    {
        if (Schema::hasColumn('users', 'parent_id') && $student->parent_id) {
            return User::query()->find($student->parent_id);
        }

        if (! method_exists($student, 'parents') && ! method_exists(User::class, 'children')) {
            return null;
        }

        try {
            return User::query()
                ->where('role', 'parent')
                ->whereHas('children', fn ($q) => $q->where('users.id', $student->id))
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }
}
