<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorInterview extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const RESULT_PENDING = 'pending';

    public const RESULT_PASS = 'pass';

    public const RESULT_FAIL = 'fail';

    public const RESULT_HOLD = 'hold';

    protected $fillable = [
        'tutor_application_id',
        'tutor_interview_slot_id',
        'scheduled_at',
        'ends_at',
        'meeting_mode',
        'room_name',
        'join_url',
        'external_url',
        'status',
        'result',
        'notes',
        'completed_at',
        'no_show_marked_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_show_marked_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(TutorApplication::class, 'tutor_application_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TutorInterviewSlot::class, 'tutor_interview_slot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function effectiveJoinUrl(): string
    {
        if ($this->meeting_mode === TutorInterviewSlot::MODE_EXTERNAL && filled($this->external_url)) {
            return (string) $this->external_url;
        }

        return (string) ($this->join_url ?: '');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => 'مجدولة',
            self::STATUS_COMPLETED => 'اكتملت',
            self::STATUS_CANCELLED => 'ملغاة',
            self::STATUS_NO_SHOW => 'تغيب',
            default => $this->status,
        };
    }

    public function resultLabel(): string
    {
        return match ($this->result) {
            self::RESULT_PASS => 'نجاح',
            self::RESULT_FAIL => 'عدم اجتياز',
            self::RESULT_HOLD => 'معلّق',
            default => 'قيد التقييم',
        };
    }
}
