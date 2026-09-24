<?php

namespace App\Services;

use App\Models\TutorApplication;
use App\Models\TutorHiringSetting;
use App\Models\TutorInterview;
use App\Models\TutorInterviewSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TutorInterviewBookingService
{
    public function __construct(
        protected TutorHiringNotifyService $notify,
    ) {}

    public function inviteToInterview(TutorApplication $application, User $admin): TutorApplication
    {
        TeacherSpecialtyMatcher::assertComplete($application);

        if (! in_array($application->status, [
            TutorApplication::STATUS_PENDING,
            TutorApplication::STATUS_REJECTED,
            TutorApplication::STATUS_BLOCKED_NO_SHOW,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن دعوة هذا الطلب للمقابلة في حالته الحالية.',
            ]);
        }

        $application->update([
            'status' => TutorApplication::STATUS_INTERVIEW_PENDING,
            'blocked_at' => null,
            'blocked_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
            'admin_notes' => $application->admin_notes,
        ]);

        return $application->fresh();
    }

    public function bookSlot(TutorApplication $application, TutorInterviewSlot $slot, ?User $actor = null): TutorInterview
    {
        if (! TutorHiringSetting::bool('require_interview', true)
            && $application->status !== TutorApplication::STATUS_INTERVIEW_PENDING
            && $application->status !== TutorApplication::STATUS_INTERVIEW_SCHEDULED) {
            // still allow booking when invited
        }

        if (! in_array($application->status, [
            TutorApplication::STATUS_INTERVIEW_PENDING,
            TutorApplication::STATUS_INTERVIEW_SCHEDULED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'الطلب غير مفتوح لاختيار موعد مقابلة.',
            ]);
        }

        if (! $slot->isBookable()) {
            throw ValidationException::withMessages([
                'slot' => 'هذا الموعد غير متاح.',
            ]);
        }

        $allowReschedule = TutorHiringSetting::bool('allow_reschedule', true);
        $existing = TutorInterview::query()
            ->where('tutor_application_id', $application->id)
            ->where('status', TutorInterview::STATUS_SCHEDULED)
            ->first();

        if ($existing && ! $allowReschedule) {
            throw ValidationException::withMessages([
                'slot' => 'لديك موعد مجدول مسبقاً ولا يُسمح بإعادة الجدولة.',
            ]);
        }

        return DB::transaction(function () use ($application, $slot, $actor, $existing) {
            if ($existing) {
                $existing->update(['status' => TutorInterview::STATUS_CANCELLED]);
            }

            $roomName = 'tutor-ivw-'.$application->id.'-'.now()->format('YmdHis');
            $mode = $slot->meeting_mode ?: TutorInterviewSlot::MODE_LIVEKIT;
            $external = $mode === TutorInterviewSlot::MODE_EXTERNAL ? $slot->external_url : null;

            $interview = TutorInterview::create([
                'tutor_application_id' => $application->id,
                'tutor_interview_slot_id' => $slot->id,
                'scheduled_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'meeting_mode' => $mode,
                'room_name' => $roomName,
                'external_url' => $external,
                'join_url' => null,
                'status' => TutorInterview::STATUS_SCHEDULED,
                'result' => TutorInterview::RESULT_PENDING,
                'created_by' => $actor?->id,
            ]);

            $joinUrl = $mode === TutorInterviewSlot::MODE_EXTERNAL && filled($external)
                ? (string) $external
                : route('tutor.interview.join', $interview);

            $interview->update(['join_url' => $joinUrl]);

            $application->update([
                'status' => TutorApplication::STATUS_INTERVIEW_SCHEDULED,
            ]);

            $interview = $interview->fresh(['application']);
            $this->notify->notifyInterviewScheduled($interview);

            return $interview;
        });
    }

    public function markPassed(TutorInterview $interview, User $admin, ?string $notes = null): TutorInterview
    {
        $interview->update([
            'status' => TutorInterview::STATUS_COMPLETED,
            'result' => TutorInterview::RESULT_PASS,
            'completed_at' => now(),
            'notes' => $notes ?: $interview->notes,
        ]);

        $interview->application?->update([
            'status' => TutorApplication::STATUS_INTERVIEW_PASSED,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        return $interview->fresh();
    }

    public function markNoShow(TutorInterview $interview): TutorInterview
    {
        if ($interview->status !== TutorInterview::STATUS_SCHEDULED) {
            return $interview;
        }

        return DB::transaction(function () use ($interview) {
            $interview->update([
                'status' => TutorInterview::STATUS_NO_SHOW,
                'no_show_marked_at' => now(),
                'result' => TutorInterview::RESULT_FAIL,
            ]);

            $application = $interview->application;
            if ($application) {
                $application->update([
                    'status' => TutorApplication::STATUS_BLOCKED_NO_SHOW,
                    'blocked_at' => now(),
                    'blocked_reason' => 'interview_no_show',
                ]);

                if ($application->user_id) {
                    User::query()->where('id', $application->user_id)->update(['is_active' => false]);
                }
            }

            return $interview->fresh();
        });
    }

    public function unblock(TutorApplication $application, User $admin): TutorApplication
    {
        $application->update([
            'status' => TutorApplication::STATUS_INTERVIEW_PENDING,
            'blocked_at' => null,
            'blocked_reason' => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        if ($application->user_id) {
            User::query()->where('id', $application->user_id)->update(['is_active' => true]);
        }

        return $application->fresh();
    }
}
