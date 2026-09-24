<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgressSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'period_key',
        'period_start',
        'period_end',
        'exam_average',
        'attendance_percent',
        'course_progress_percent',
        'sessions_completed',
        'xp_total',
        'strengths',
        'improvements',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'strengths' => 'array',
            'improvements' => 'array',
            'metrics' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
