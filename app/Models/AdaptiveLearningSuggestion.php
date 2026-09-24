<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdaptiveLearningSuggestion extends Model
{
    public const KIND_REVIEW_LESSON = 'review_lesson';

    public const KIND_BOOK_SESSION = 'book_session';

    public const KIND_RECORDED_COURSE = 'recorded_course';

    public const KIND_BOOK_MATERIAL = 'book_material';

    protected $fillable = [
        'user_id',
        'kind',
        'title',
        'reason',
        'priority',
        'source',
        'suggestable_type',
        'suggestable_id',
        'action_url',
        'meta',
        'is_active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suggestable(): MorphTo
    {
        return $this->morphTo();
    }
}
