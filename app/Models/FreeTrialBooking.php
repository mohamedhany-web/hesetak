<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeTrialBooking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const GOAL_CONSULTATION = 'consultation';

    public const GOAL_TRIAL = 'trial';

    public const GOAL_PLACEMENT = 'placement';

    public const GOAL_FREE_SESSION = 'free_session';

    protected $fillable = [
        'name', 'email', 'phone', 'country_code', 'goal', 'user_id', 'instructor_id',
        'starts_at', 'ends_at', 'duration_minutes', 'status', 'notes',
        'timezone', 'us_state',
        'recommended_academic_year_id', 'admin_notes',
        'one_to_one_session_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return array<string, array{ar:string,en:string}>
     */
    public static function goalOptions(): array
    {
        return [
            self::GOAL_FREE_SESSION => [
                'ar' => 'حصة مجانية',
                'en' => 'Free session',
            ],
            self::GOAL_TRIAL => [
                'ar' => 'حصة تجريبية',
                'en' => 'Trial lesson',
            ],
            self::GOAL_CONSULTATION => [
                'ar' => 'استشارة',
                'en' => 'Consultation',
            ],
            self::GOAL_PLACEMENT => [
                'ar' => 'تحديد مستوى',
                'en' => 'Placement test',
            ],
        ];
    }

    public function goalLabel(?string $locale = null): string
    {
        $locale = $locale ?: (app()->getLocale() === 'ar' ? 'ar' : 'en');
        $goal = (string) ($this->goal ?? '');
        $options = self::goalOptions();

        if (isset($options[$goal][$locale])) {
            return $options[$goal][$locale];
        }

        return $goal !== '' ? $goal : '—';
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->phone ?? ''));
        if ($digits === null || $digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function oneToOneSession(): BelongsTo
    {
        return $this->belongsTo(OneToOneSession::class, 'one_to_one_session_id');
    }

    public function recommendedAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'recommended_academic_year_id');
    }

    /** Alias for school-program wording */
    public function recommendedSchoolYear(): BelongsTo
    {
        return $this->recommendedAcademicYear();
    }
}
