<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * مواعيد يقترحها الطالب على معلم ضمن باقة — يقبلها المعلم (كلها أو بعضها) أو يرفضها.
 */
class PackageSlotProposal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'student_id',
        'instructor_id',
        'service_package_id',
        'academic_subject_id',
        'academic_year_id',
        'proposed_slots',
        'accepted_slots',
        'timezone',
        'student_notes',
        'status',
        'response_note',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_slots' => 'array',
            'accepted_slots' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function academicSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'مقبول',
            self::STATUS_REJECTED => 'مرفوض',
            default => 'بانتظار ردّ المعلم',
        };
    }
}
