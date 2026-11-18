<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تقرير المعلم للطالب بعد كل حصة فردية مكتملة (إلزامي).
 */
class OneToOneSessionReport extends Model
{
    public const LEVEL_EXCELLENT = 'excellent';

    public const LEVEL_GOOD = 'good';

    public const LEVEL_NEEDS_WORK = 'needs_work';

    protected $fillable = [
        'one_to_one_session_id',
        'instructor_id',
        'student_id',
        'summary',
        'topics_covered',
        'performance_level',
        'homework',
        'next_steps',
        'submitted_at',
        'student_read_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'student_read_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function performanceLevels(): array
    {
        return [
            self::LEVEL_EXCELLENT => 'ممتاز',
            self::LEVEL_GOOD => 'جيد',
            self::LEVEL_NEEDS_WORK => 'يحتاج مزيداً من التدريب',
        ];
    }

    public function performanceLabel(): ?string
    {
        return self::performanceLevels()[$this->performance_level] ?? null;
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(OneToOneSession::class, 'one_to_one_session_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
