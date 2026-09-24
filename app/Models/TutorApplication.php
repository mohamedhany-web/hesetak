<?php

namespace App\Models;

use App\Services\TutorApplicationStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TutorApplication extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_INTERVIEW_PENDING = 'interview_pending';

    public const STATUS_INTERVIEW_SCHEDULED = 'interview_scheduled';

    public const STATUS_INTERVIEW_PASSED = 'interview_passed';

    public const STATUS_BLOCKED_NO_SHOW = 'blocked_no_show';

    public const STATUS_CONTRACT_PENDING = 'contract_pending';

    public const STATUS_CONTRACT_SIGNED = 'contract_signed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ACTIVATED = 'activated';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'uuid',
        'hiring_form_id',
        'full_name',
        'email',
        'phone',
        'nationality',
        'city',
        'gender',
        'headline',
        'bio',
        'experience',
        'education',
        'years_experience',
        'photo_path',
        'id_document_path',
        'certificate_path',
        'intro_video_path',
        'intro_video_url',
        'answers',
        'teaching_subject_ids',
        'academic_year_ids',
        'curriculum_types',
        'status',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
        'user_id',
        'activated_at',
        'activated_by',
        'blocked_at',
        'blocked_reason',
    ];

    protected function casts(): array
    {
        return [
            'years_experience' => 'integer',
            'answers' => 'array',
            'teaching_subject_ids' => 'array',
            'academic_year_ids' => 'array',
            'curriculum_types' => 'array',
            'reviewed_at' => 'datetime',
            'activated_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TutorApplication $application) {
            if (blank($application->uuid)) {
                $application->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getRouteKey()
    {
        $uuid = trim((string) ($this->uuid ?? ''));

        return $uuid !== '' ? $uuid : (string) $this->getKey();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        return static::query()->where($field, $value)->first();
    }

    public function hiringForm(): BelongsTo
    {
        return $this->belongsTo(HiringForm::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function activatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(TutorInterview::class);
    }

    public function latestInterview(): HasOne
    {
        return $this->hasOne(TutorInterview::class)->latestOfMany();
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(InstructorAgreement::class, 'tutor_application_id');
    }

    public function latestAgreement(): HasOne
    {
        return $this->hasOne(InstructorAgreement::class, 'tutor_application_id')->latestOfMany();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeAwaitingActivation($query)
    {
        return $query->where('status', self::STATUS_APPROVED)->whereNotNull('user_id');
    }

    public function scopeActivated($query)
    {
        return $query->where('status', self::STATUS_ACTIVATED);
    }

    public function isActivated(): bool
    {
        return $this->status === self::STATUS_ACTIVATED;
    }

    public function canActivateAccount(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->user_id !== null;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED_NO_SHOW;
    }

    /**
     * @return list<string>
     */
    public function curriculumTypeKeys(): array
    {
        $raw = $this->curriculum_types;
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $raw)));
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'مسودة — لم يُكمل البيانات',
            self::STATUS_PENDING => 'قيد المراجعة',
            self::STATUS_INTERVIEW_PENDING => 'بانتظار اختيار موعد مقابلة',
            self::STATUS_INTERVIEW_SCHEDULED => 'مقابلة مجدولة',
            self::STATUS_INTERVIEW_PASSED => 'اجتاز المقابلة',
            self::STATUS_BLOCKED_NO_SHOW => 'محجوب — تغيب عن المقابلة',
            self::STATUS_CONTRACT_PENDING => 'بانتظار توقيع العقد',
            self::STATUS_CONTRACT_SIGNED => 'تم توقيع العقد',
            self::STATUS_APPROVED => 'مقبول — بانتظار التفعيل العام',
            self::STATUS_ACTIVATED => 'مفعّل (لوحة المعلم مفتوحة)',
            self::STATUS_REJECTED => 'مرفوض',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function photoUrl(): ?string
    {
        return TutorApplicationStorage::publicUrl($this->photo_path);
    }

    public function idDocumentUrl(): ?string
    {
        return TutorApplicationStorage::publicUrl($this->id_document_path);
    }

    public function certificateUrl(): ?string
    {
        return TutorApplicationStorage::publicUrl($this->certificate_path);
    }

    public function introVideoFileUrl(): ?string
    {
        return TutorApplicationStorage::publicUrl($this->intro_video_path);
    }

    public function introVideoDisplayUrl(): ?string
    {
        if (filled($this->intro_video_url)) {
            return $this->intro_video_url;
        }

        return $this->introVideoFileUrl();
    }

    public function idDocumentIsPdf(): bool
    {
        return TutorApplicationStorage::isPdf($this->id_document_path);
    }

    public function certificateIsPdf(): bool
    {
        return TutorApplicationStorage::isPdf($this->certificate_path);
    }
}
