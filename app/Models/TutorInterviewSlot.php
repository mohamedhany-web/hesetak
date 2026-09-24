<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TutorInterviewSlot extends Model
{
    public const MODE_LIVEKIT = 'livekit';

    public const MODE_EXTERNAL = 'external';

    protected $fillable = [
        'starts_at',
        'ends_at',
        'capacity',
        'meeting_mode',
        'external_url',
        'is_open',
        'title',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(TutorInterview::class, 'tutor_interview_slot_id');
    }

    public function bookedCount(): int
    {
        return $this->interviews()
            ->whereIn('status', [TutorInterview::STATUS_SCHEDULED, TutorInterview::STATUS_COMPLETED])
            ->count();
    }

    public function remainingCapacity(): int
    {
        return max(0, (int) $this->capacity - $this->bookedCount());
    }

    public function isBookable(): bool
    {
        return $this->is_open
            && $this->starts_at
            && $this->starts_at->isFuture()
            && $this->remainingCapacity() > 0;
    }

    public function modeLabel(): string
    {
        return match ($this->meeting_mode) {
            self::MODE_EXTERNAL => 'رابط خارجي',
            default => 'Hissatak Meeting',
        };
    }
}
