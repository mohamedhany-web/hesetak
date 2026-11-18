<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PackageSlotProposal;
use App\Models\User;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PackageSlotProposalService
{
    public const MAX_SLOTS = 12;

    /**
     * يحوّل صفوف «اقترح موعداً» (تاريخ + ساعة + دقيقة + AM/PM بتوقيت الزائر) إلى مواعيد UTC مستقبلية.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return Collection<int, Carbon>
     */
    public static function parseCustomSlots(array $rows, string $timezone): Collection
    {
        return collect($rows)
            ->map(function ($row) use ($timezone) {
                if (! is_array($row)) {
                    return null;
                }
                $date = trim((string) ($row['date'] ?? ''));
                $hour = (int) ($row['hour'] ?? 0);
                $minute = (string) ($row['minute'] ?? '00');
                $period = strtoupper((string) ($row['period'] ?? ''));
                if ($date === '' || $hour < 1 || $hour > 12 || ! in_array($period, ['AM', 'PM'], true)) {
                    return null;
                }
                if (! in_array($minute, ['00', '15', '30', '45'], true)) {
                    return null;
                }

                try {
                    $local = Carbon::createFromFormat('Y-m-d g:i A', sprintf('%s %d:%s %s', $date, $hour, $minute, $period), $timezone);
                } catch (\Throwable) {
                    return null;
                }
                if (! $local) {
                    return null;
                }

                return $local->utc();
            })
            ->filter(fn ($slot) => $slot instanceof Carbon && $slot->gt(now()))
            ->values();
    }

    /**
     * @param  list<string>  $isoSlots  مواعيد ISO8601 (متاحة للمعلم أو مقترحة)
     */
    public static function createProposal(
        User $student,
        User $instructor,
        array $isoSlots,
        ?int $packageId = null,
        ?int $subjectId = null,
        ?int $yearId = null,
        ?string $notes = null,
        ?string $timezone = null,
    ): PackageSlotProposal {
        if (! $student->isStudent()) {
            throw new InvalidArgumentException('إرسال المواعيد للمعلم متاح لحسابات الطلاب فقط.');
        }

        $slots = collect($isoSlots)
            ->map(function ($iso) {
                try {
                    return Carbon::parse((string) $iso)->utc();
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter(fn ($slot) => $slot instanceof Carbon && $slot->gt(now()))
            ->unique(fn (Carbon $slot) => $slot->toIso8601String())
            ->sort()
            ->take(self::MAX_SLOTS)
            ->map(fn (Carbon $slot) => $slot->toIso8601String())
            ->values()
            ->all();

        if ($slots === []) {
            throw new InvalidArgumentException('اختر أو اقترح موعداً واحداً على الأقل في المستقبل.');
        }

        $proposal = PackageSlotProposal::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'service_package_id' => $packageId,
            'academic_subject_id' => $subjectId,
            'academic_year_id' => $yearId,
            'proposed_slots' => $slots,
            'timezone' => $timezone,
            'student_notes' => $notes !== null && trim($notes) !== '' ? trim($notes) : null,
            'status' => PackageSlotProposal::STATUS_PENDING,
        ]);

        self::notify(
            $instructor->id,
            $student->id,
            'طلب مواعيد جديد من طالب',
            'اقترح '.$student->name.' '.count($slots).' موعد/مواعيد — راجعها واقبل المناسب أو ارفض.',
            route('instructor.package-slot-proposals.index'),
            'مراجعة المواعيد',
            'instructor'
        );

        return $proposal;
    }

    /**
     * @param  list<string>  $acceptedIso  المواعيد المقبولة (جزء من المقترحة)؛ فارغ = رفض.
     */
    public static function respond(
        PackageSlotProposal $proposal,
        User $instructor,
        array $acceptedIso,
        ?string $note = null,
    ): PackageSlotProposal {
        if ((int) $proposal->instructor_id !== (int) $instructor->id) {
            throw new InvalidArgumentException('هذا الطلب غير مخصص لك.');
        }
        if (! $proposal->isPending()) {
            throw new InvalidArgumentException('تم الرد على هذا الطلب مسبقاً.');
        }

        $proposed = collect($proposal->proposed_slots ?? []);
        $accepted = $proposed->intersect($acceptedIso)->values()->all();
        $note = $note !== null && trim($note) !== '' ? trim($note) : null;

        $proposal->update([
            'status' => $accepted === [] ? PackageSlotProposal::STATUS_REJECTED : PackageSlotProposal::STATUS_ACCEPTED,
            'accepted_slots' => $accepted === [] ? null : $accepted,
            'response_note' => $note,
            'responded_at' => now(),
        ]);

        $tz = $proposal->timezone ?: AppTimezone::academy();
        $times = collect($accepted)
            ->map(fn ($iso) => Carbon::parse($iso)->timezone($tz)->format('Y-m-d g:i A'))
            ->implode(' ، ');

        self::notify(
            $proposal->student_id,
            $instructor->id,
            $accepted === [] ? 'المعلم لم يقبل المواعيد المقترحة' : 'المعلم قبل مواعيدك',
            $accepted === []
                ? ($note ?: 'المعلم لم يتمكن من قبول المواعيد المقترحة. يمكنك اقتراح مواعيد أخرى.')
                : 'المواعيد المقبولة: '.$times.($note ? ' — '.$note : ''),
            route('dashboard'),
            'عرض',
            'student'
        );

        return $proposal->fresh();
    }

    private static function notify(int $userId, int $senderId, string $title, string $message, string $url, string $actionText, string $audience): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'sender_id' => $senderId,
                'type' => 'general',
                'title' => $title,
                'message' => $message,
                'action_url' => $url,
                'action_text' => $actionText,
                'priority' => 'high',
                'audience' => $audience,
                'is_read' => false,
            ]);
        } catch (\Throwable) {
            // الإشعار لا يُفشل العملية الأساسية
        }
    }
}
