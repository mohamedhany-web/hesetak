<?php

namespace App\Support;

use App\Models\ClassroomMeetingReport;
use App\Models\OneToOneSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class OneToOneReportGate
{
    /**
     * Sessions that block instructor payout until a classroom meeting report exists.
     */
    public static function overdueQuery(?int $instructorId = null): Builder
    {
        $query = OneToOneSession::query()
            ->with(['student:id,name', 'instructor:id,name', 'course:id,title', 'classroomMeeting'])
            ->where(function (Builder $outer) {
                $outer->where('status', OneToOneSession::STATUS_COMPLETED)
                    ->orWhere(function (Builder $past) {
                        $past->where('status', OneToOneSession::STATUS_SCHEDULED)
                            ->whereNotNull('scheduled_at')
                            ->where('scheduled_at', '<', now()->subHour());
                    });
            })
            ->where(function (Builder $q) {
                // No meeting linked, or meeting has no usable report.
                $q->whereNull('classroom_meeting_id')
                    ->orWhereDoesntHave('classroomMeeting', function (Builder $m) {
                        $m->whereHas('reports', function (Builder $r) {
                            $r->where(function (Builder $filled) {
                                $filled->where(function (Builder $s) {
                                    $s->whereNotNull('summary')->where('summary', '!=', '');
                                })->orWhere(function (Builder $t) {
                                    $t->whereNotNull('title')->where('title', '!=', '');
                                });
                            });
                        });
                    });
            });

        if ($instructorId) {
            $query->where('instructor_id', $instructorId);
        }

        return $query->orderByDesc('scheduled_at')->orderByDesc('id');
    }

    public static function instructorHasOverdueReports(int $instructorId): bool
    {
        return self::overdueQuery($instructorId)->exists();
    }

    public static function overdueCountForInstructor(int $instructorId): int
    {
        return self::overdueQuery($instructorId)->count();
    }

    /**
     * @return array<int, int> instructor_id => overdue count
     */
    public static function overdueCountsByInstructorIds(array $instructorIds): array
    {
        $instructorIds = array_values(array_unique(array_filter(array_map('intval', $instructorIds))));
        if ($instructorIds === []) {
            return [];
        }

        $counts = [];
        foreach ($instructorIds as $id) {
            $counts[$id] = self::overdueCountForInstructor($id);
        }

        return $counts;
    }

    public static function markReportRequired(OneToOneSession $session): void
    {
        if (! Schema::hasColumn('one_to_one_sessions', 'report_required_at')) {
            return;
        }
        if ($session->report_required_at) {
            return;
        }
        $session->forceFill(['report_required_at' => now()])->save();
    }

    public static function sessionHasUsableReport(OneToOneSession $session): bool
    {
        $session->loadMissing('classroomMeeting.reports');
        $meeting = $session->classroomMeeting;
        if (! $meeting) {
            return false;
        }

        /** @var \Illuminate\Support\Collection<int, ClassroomMeetingReport> $reports */
        $reports = $meeting->reports ?? collect();
        foreach ($reports as $report) {
            $title = trim((string) ($report->title ?? ''));
            $summary = trim((string) ($report->summary ?? ''));
            if ($title !== '' || $summary !== '') {
                return true;
            }
        }

        return false;
    }
}
