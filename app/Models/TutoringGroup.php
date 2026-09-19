<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TutoringGroup extends Model
{
    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_COLLECTIVE = 'collective';

    public const PATH_ARABIC = 'arabic';

    public const PATH_ENGLISH = 'english';

    protected $fillable = [
        'type',
        'title',
        'slug',
        'description',
        'image_path',
        'instructor_id',
        'price',
        'hourly_rate',
        'sessions_per_month',
        'whatsapp_group_url',
        'learning_path',
        'academic_year_id',
        'academic_subject_id',
        'currency',
        'capacity',
        'duration_minutes',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'sessions_per_month' => 'integer',
            'capacity' => 'integer',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /** Alias: سنوات المدرسة = السنوات الأكاديمية */
    public function schoolYear(): BelongsTo
    {
        return $this->academicYear();
    }

    public function academicSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'academic_subject_id');
    }

    /** Alias: مواد المدرسة = المواد الدراسية */
    public function schoolSubject(): BelongsTo
    {
        return $this->academicSubject();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TutoringGroupBooking::class);
    }

    public function cohorts(): HasMany
    {
        return $this->hasMany(TutoringGroupCohort::class)->orderBy('sort_order')->orderByDesc('starts_at');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(TutoringGroupPackage::class)->orderBy('sort_order')->orderBy('duration_months');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentTutoringSubscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeIndividual(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INDIVIDUAL);
    }

    public function scopeCollective(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COLLECTIVE);
    }

    public function isIndividual(): bool
    {
        return $this->type === self::TYPE_INDIVIDUAL;
    }

    public function isCollective(): bool
    {
        return $this->type === self::TYPE_COLLECTIVE;
    }

    public function typeLabel(): string
    {
        return $this->isIndividual() ? 'مجموعة فردية' : 'مجموعة جماعية';
    }

    public function learningPathLabel(): string
    {
        return match ($this->learning_path) {
            self::PATH_ARABIC => 'عربي / إسلامي',
            self::PATH_ENGLISH => 'إنجليزي',
            default => '—',
        };
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (Str::startsWith($this->image_path, ['http://', 'https://'])) {
            return $this->image_path;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function formattedPrice(): string
    {
        if ($this->price === null) {
            return 'حسب الاتفاق';
        }

        return format_money((float) $this->price, 0);
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'group-'.Str::random(6);
        $slug = $base;
        $i = 1;

        while (
            static::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
