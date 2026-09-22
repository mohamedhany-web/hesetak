<?php

namespace App\Models;

use App\Services\PublicStorageUrl;
use App\Services\PublicMediaStorage;
use App\Services\UserProfileImageStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorProfile extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'photo_path',
        'experience',
        'skills',
        'curriculum_types',
        'teaching_subject_ids',
        'social_links',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
        'submitted_at',
        'consultation_price_egp',
        'consultation_duration_minutes',
    ];

    protected $casts = [
        'social_links' => 'array',
        'curriculum_types' => 'array',
        'teaching_subject_ids' => 'array',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'consultation_price_egp' => 'decimal:2',
        'consultation_duration_minutes' => 'integer',
    ];

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

    /**
     * @return list<int>
     */
    public function teachingSubjectIds(): array
    {
        $raw = $this->teaching_subject_ids;
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_REVIEW);
    }

    /**
     * سعر الاستشارة بالريال السعودي: خاص بالمدرب إن وُجد، وإلا السعر الافتراضي من إعدادات المنصة.
     */
    public function effectiveConsultationPriceEgp(): float
    {
        if ($this->consultation_price_egp !== null) {
            return (float) $this->consultation_price_egp;
        }

        return (float) ConsultationSetting::current()->default_price;
    }

    /**
     * مدة الاستشارة بالدقائق: خاصة بالمدرب إن وُجدت، وإلا الافتراضي من الإعدادات.
     */
    public function effectiveConsultationDurationMinutes(): int
    {
        if ($this->consultation_duration_minutes !== null && (int) $this->consultation_duration_minutes > 0) {
            return (int) $this->consultation_duration_minutes;
        }

        return (int) ConsultationSetting::current()->default_duration_minutes;
    }

    public function usesCustomConsultationPrice(): bool
    {
        return $this->consultation_price_egp !== null;
    }

    /**
     * رابط صورة العرض العام — ملف المدرب ثم صورة المستخدم/البورتفوليو (نفس منطق باقي الموقع).
     */
    public function getPhotoUrlAttribute(): ?string
    {
        $path = $this->normalizeStoredMediaPath($this->photo_path);

        if ($path !== null && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
            return $path;
        }

        if ($path !== null) {
            $base = PublicStorageUrl::fromPathStable($path, PublicMediaStorage::resolvedDisk())
                ?? UserProfileImageStorage::publicUrl($path)
                ?? storage_public_url($path);

            if ($base) {
                $ts = $this->updated_at?->timestamp ?? 0;

                return $base.(str_contains($base, '?') ? '&' : '?').'v='.$ts;
            }
        }

        $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();
        if (! $user) {
            return null;
        }

        return $user->public_portfolio_marketing_photo_url
            ?? $user->profile_image_url;
    }

    public function hasDisplayPhoto(): bool
    {
        return filled($this->photo_url);
    }

    private function normalizeStoredMediaPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        foreach (['storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return $path !== '' ? $path : null;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_PENDING_REVIEW => 'قيد المراجعة',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
            default => $status,
        };
    }

    /**
     * تنظيف نصوص الملف (كيانات HTML مزدوجة الترميز، وسوم، مسافات).
     */
    public function sanitizedText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function getBioCleanAttribute(): string
    {
        return $this->sanitizedText($this->bio);
    }

    public function getHeadlineCleanAttribute(): string
    {
        return $this->sanitizedText($this->headline);
    }

    /**
     * المهارات كقائمة مرتبة (سطر لكل مهارة أو مفصولة بفاصلة / إيموجي)
     */
    public function getSkillsListAttribute(): array
    {
        if (empty($this->skills)) {
            return [];
        }

        $text = $this->sanitizedText($this->skills);
        $raw = preg_split('/[\r\n,،|]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $list = array_values(array_filter(array_map('trim', $raw)));

        if (count($list) === 1 && mb_strlen($list[0]) > 60) {
            $emojiSplit = preg_split('/(?=\p{Extended_Pictographic})/u', $list[0], -1, PREG_SPLIT_NO_EMPTY);
            $emojiSplit = array_values(array_filter(array_map('trim', $emojiSplit)));
            if (count($emojiSplit) > 1) {
                return $emojiSplit;
            }
        }

        return $list;
    }

    /**
     * الخبرات كقائمة (كل سطر = نقطة/فقرة للعرض المنظم)
     */
    public function getExperienceListAttribute(): array
    {
        if (empty($this->experience)) {
            return [];
        }

        $text = $this->sanitizedText($this->experience);
        $lines = preg_split('/\r\n|\r|\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $list = array_values(array_filter(array_map('trim', $lines)));

        if (count($list) === 1 && mb_strlen($list[0]) > 120) {
            $parts = preg_split('/(?<=[.!?؟])\s+/u', $list[0], -1, PREG_SPLIT_NO_EMPTY);
            $parts = array_values(array_filter(array_map('trim', $parts)));
            if (count($parts) > 1) {
                return $parts;
            }
        }

        return $list;
    }
}
