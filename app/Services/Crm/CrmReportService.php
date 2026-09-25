<?php

namespace App\Services\Crm;

use App\Models\CrmReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class CrmReportService
{
    public static function create(array $data, User $user, ?UploadedFile $file = null): CrmReport
    {
        if (! CrmAccessService::canSubmitReports($user)) {
            throw new InvalidArgumentException('غير مصرح برفع التقارير.');
        }

        self::validatePeriod($data['type'] ?? '', $data['period_start'] ?? null, $data['period_end'] ?? null);

        $path = null;
        $fileName = null;
        if ($file) {
            $fileName = $file->getClientOriginalName();
            $path = \App\Services\PublicMediaStorage::storeFile($file, 'crm/reports');
        }

        $report = CrmReport::create([
            'user_id' => $user->id,
            'crm_group_id' => $data['crm_group_id'] ?? null,
            'type' => $data['type'],
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'file_path' => $path,
            'file_name' => $fileName,
            'status' => CrmReport::STATUS_SUBMITTED,
        ]);

        CrmAuditService::log('crm_report_submitted', null, $user, null, [
            'report_id' => $report->id,
            'type' => $report->type,
            'title' => $report->title,
        ]);

        return $report;
    }

    public static function markReviewed(CrmReport $report, User $admin, ?string $notes = null): CrmReport
    {
        $report->update([
            'status' => CrmReport::STATUS_REVIEWED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        CrmAuditService::log('crm_report_reviewed', null, $admin, null, [
            'report_id' => $report->id,
        ]);

        return $report->fresh();
    }

    public static function suggestedPeriod(string $type): array
    {
        return match ($type) {
            CrmReport::TYPE_WEEKLY => [
                'start' => now()->startOfWeek()->toDateString(),
                'end' => now()->endOfWeek()->toDateString(),
            ],
            CrmReport::TYPE_MONTHLY => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString(),
            ],
            default => [
                'start' => now()->toDateString(),
                'end' => now()->toDateString(),
            ],
        };
    }

    private static function validatePeriod(string $type, ?string $start, ?string $end): void
    {
        if (in_array($type, [CrmReport::TYPE_WEEKLY, CrmReport::TYPE_MONTHLY], true)) {
            if (! $start || ! $end) {
                throw new InvalidArgumentException('يجب تحديد فترة التقرير.');
            }
        }
    }
}
