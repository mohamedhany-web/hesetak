<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_TUTORING_INDIVIDUAL = 'tutoring_individual';

    public const SCOPE_TUTORING_COLLECTIVE = 'tutoring_collective';

    public const SCOPE_PRIVATE_LESSONS = 'private_lessons';

    public const PLAN_SCHOOL = 'school';

    public const PLAN_PRIVATE = 'private';

    public const PLAN_PREMIER = 'premier';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'tagline',
        'badge',
        'scope',
        'plan_type',
        'term_months',
        'weekly_group_sessions',
        'weekly_private_sessions',
        'includes_community',
        'includes_libraries',
        'features',
        'gifts',
        'tutoring_group_id',
        'academic_year_id',
        'academic_subject_id',
        'units_count',
        'session_minutes',
        'duration_days',
        'price',
        'original_price',
        'currency',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'units_count' => 'integer',
            'session_minutes' => 'integer',
            'duration_days' => 'integer',
            'term_months' => 'integer',
            'weekly_group_sessions' => 'integer',
            'weekly_private_sessions' => 'integer',
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'includes_community' => 'boolean',
            'includes_libraries' => 'boolean',
            'sort_order' => 'integer',
            'features' => 'array',
            'gifts' => 'array',
        ];
    }

    public function tutoringGroup(): BelongsTo
    {
        return $this->belongsTo(TutoringGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(StudentServiceEntitlement::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'service_package_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query->active()->whereNull('tutoring_group_id');
    }

    /**
     * كتالوج الواجهة الموحّد: باقات نشطة من الإدارة للحجز الفردي/العام.
     * يستبعد باقات نظام المدرسة (plan school / tutoring_collective).
     * يُستخدم في الصفحة الرئيسية وصفحة الطالب وصفحة التسعير.
     */
    public function scopeStorefront(Builder $query): Builder
    {
        return $query->publicCatalog()
            ->where(function (Builder $q) {
                $q->whereNull('plan_type')
                    ->orWhere('plan_type', '!=', self::PLAN_SCHOOL);
            })
            ->where('scope', '!=', self::SCOPE_TUTORING_COLLECTIVE)
            ->orderByDesc('is_featured')
            ->ordered();
    }

    /**
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function storefrontCatalog(?int $limit = null): \Illuminate\Support\Collection
    {
        $query = static::query()->storefront();

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function scopeCommercial(Builder $query): Builder
    {
        return $query->publicCatalog()
            ->whereNotNull('plan_type')
            ->whereNotNull('term_months')
            ->where('plan_type', '!=', self::PLAN_SCHOOL)
            ->where('scope', '!=', self::SCOPE_TUTORING_COLLECTIVE);
    }

    public function scopePlanType(Builder $query, string $planType): Builder
    {
        return $query->where('plan_type', $planType);
    }

    /**
     * Packages usable within a school year/subject context.
     * Null year/subject on the package = general (works everywhere).
     * Locked packages only match the same year, and subject when set.
     */
    public function scopeForSchoolProgram(Builder $query, ?int $yearId = null, ?int $subjectId = null): Builder
    {
        if (! $yearId && ! $subjectId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($yearId, $subjectId) {
            $q->where(function (Builder $general) {
                $general->whereNull('academic_year_id')->whereNull('academic_subject_id');
            });

            if ($yearId) {
                $q->orWhere(function (Builder $yearMatch) use ($yearId, $subjectId) {
                    $yearMatch->where('academic_year_id', $yearId)
                        ->where(function (Builder $subjectMatch) use ($subjectId) {
                            $subjectMatch->whereNull('academic_subject_id');
                            if ($subjectId) {
                                $subjectMatch->orWhere('academic_subject_id', $subjectId);
                            }
                        });
                });
            } elseif ($subjectId) {
                $q->orWhere('academic_subject_id', $subjectId);
            }
        });
    }

    public function scopeSchoolRelevant(Builder $query): Builder
    {
        return $query->whereIn('scope', [
            self::SCOPE_GLOBAL,
            self::SCOPE_TUTORING_COLLECTIVE,
        ]);
    }

    public function schoolProgramLabel(): ?string
    {
        $parts = [];
        if ($this->academicYear) {
            $parts[] = $this->academicYear->name;
        }
        if ($this->academicSubject) {
            $parts[] = $this->academicSubject->name;
        }

        return $parts === [] ? null : implode(' · ', $parts);
    }

    public function matchesTutoringGroup(TutoringGroup $group): bool
    {
        if ($this->tutoring_group_id && (int) $this->tutoring_group_id !== (int) $group->id) {
            return false;
        }
        if ($this->academic_year_id && (int) $this->academic_year_id !== (int) ($group->academic_year_id ?: 0)) {
            return false;
        }
        if ($this->academic_subject_id && (int) $this->academic_subject_id !== (int) ($group->academic_subject_id ?: 0)) {
            return false;
        }

        return true;
    }

    public function label(): string
    {
        return match ($this->scope) {
            self::SCOPE_TUTORING_INDIVIDUAL => 'حصص فردية',
            self::SCOPE_TUTORING_COLLECTIVE => 'حصص جماعية / مدرسة',
            self::SCOPE_PRIVATE_LESSONS => 'حصص خاصة 1:1',
            default => 'عام (كل التدريس)',
        };
    }

    public function currencyCode(): string
    {
        return strtoupper((string) ($this->currency ?: platform_currency()));
    }

    public function formattedPrice(): string
    {
        if ((float) $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        return format_money((float) $this->price);
    }

    public function formattedOriginalPrice(): ?string
    {
        if (! $this->original_price || (float) $this->original_price <= (float) $this->price) {
            return null;
        }

        return format_money((float) $this->original_price);
    }

    public function savingsAmount(): float
    {
        if (! $this->original_price) {
            return 0.0;
        }

        return max(0, (float) $this->original_price - (float) $this->price);
    }

    public function savingsPercent(): int
    {
        if (! $this->original_price || (float) $this->original_price <= 0) {
            return 0;
        }

        return (int) round($this->savingsAmount() / (float) $this->original_price * 100);
    }

    public function sessionMinutes(): int
    {
        return max(15, (int) ($this->session_minutes ?: 60));
    }

    public function totalMinutes(): int
    {
        return $this->sessionMinutes() * max(1, (int) $this->units_count);
    }

    /**
     * Total teaching hours in the pack, e.g. 8 sessions × 60min = 8 hours.
     */
    public function totalHoursLabel(): string
    {
        $hours = $this->totalMinutes() / 60;
        $value = fmod($hours, 1.0) === 0.0 ? (string) (int) $hours : number_format($hours, 1);

        return app()->getLocale() === 'ar' ? $value.' ساعة' : $value.' hours';
    }

    public function pricePerUnit(): float
    {
        $units = max(1, (int) $this->units_count);

        return round((float) $this->price / $units, 2);
    }

    public function formattedPricePerUnit(): string
    {
        if ((float) $this->price <= 0) {
            return app()->getLocale() === 'ar' ? 'مجاناً' : 'Free';
        }

        return format_money($this->pricePerUnit());
    }

    /**
     * Human validity window, e.g. "60 يوم (شهران)".
     */
    public function validityLabel(): string
    {
        $isRtl = app()->getLocale() === 'ar';

        if (! $this->duration_days) {
            return $isRtl ? 'بدون تاريخ انتهاء' : 'No expiry';
        }

        $days = (int) $this->duration_days;
        $months = (int) round($days / 30);

        if ($days < 30 || $months < 1) {
            return $isRtl ? $days.' يوم' : $days.' days';
        }

        $monthsLabel = $isRtl
            ? match (true) {
                $months === 1 => 'شهر',
                $months === 2 => 'شهران',
                $months <= 10 => $months.' أشهر',
                default => $months.' شهراً',
            }
        : $months.' months';

        return $isRtl
            ? $days.' يوم ('.$monthsLabel.')'
            : $days.' days ('.$monthsLabel.')';
    }

    /**
     * Suggested pace so the pack finishes before expiry, e.g. 4 sessions / month.
     */
    public function sessionsPerMonth(): ?float
    {
        if (! $this->duration_days || (int) $this->duration_days < 7) {
            return null;
        }

        $months = max(1, (int) round((int) $this->duration_days / 30));

        return round(max(1, (int) $this->units_count) / $months, 1);
    }

    public function scopeUsageHint(): string
    {
        $isRtl = app()->getLocale() === 'ar';

        return match ($this->scope) {
            self::SCOPE_TUTORING_INDIVIDUAL => $isRtl
                ? 'تُستخدم في حجز الحصص الفردية مع المعلم'
                : 'Used for one-on-one tutoring bookings',
            self::SCOPE_TUTORING_COLLECTIVE => $isRtl
                ? 'تُستخدم في حصص المجموعات ومواد المدرسة'
                : 'Used for group classes and school subjects',
            self::SCOPE_PRIVATE_LESSONS => $isRtl
                ? 'تُستخدم في الحصص الخاصة 1:1 داخل الكورسات'
                : 'Used for private 1:1 course sessions',
            default => $isRtl
                ? 'تُستخدم في أي خدمة تدريس: فردي، جماعي، أو خاص'
                : 'Works with any tutoring service: individual, group or private',
        };
    }

    public function isCommercialPlan(): bool
    {
        return filled($this->plan_type) && filled($this->term_months);
    }

    public function isPremier(): bool
    {
        return $this->plan_type === self::PLAN_PREMIER;
    }

    public function planLabel(): string
    {
        return self::planTypes()[$this->plan_type] ?? ($this->name ?: 'Plan');
    }

    public function planIcon(): string
    {
        return match ($this->plan_type) {
            self::PLAN_SCHOOL => 'fa-school',
            self::PLAN_PRIVATE => 'fa-user',
            self::PLAN_PREMIER => 'fa-star',
            default => 'fa-box-open',
        };
    }

    public function weeklySessionsTotal(): int
    {
        return max(0, (int) $this->weekly_group_sessions) + max(0, (int) $this->weekly_private_sessions);
    }

    public function computedUnitsForTerm(): int
    {
        $months = max(1, (int) ($this->term_months ?: 1));

        return max(1, $this->weeklySessionsTotal() * 4 * $months);
    }

    public function termLabel(): string
    {
        $isRtl = app()->getLocale() === 'ar';
        $months = (int) ($this->term_months ?: 0);

        return match ($months) {
            1 => $isRtl ? 'شهر' : '1 month',
            3 => $isRtl ? '3 أشهر' : '3 months',
            6 => $isRtl ? '6 أشهر' : '6 months',
            default => $this->validityLabel(),
        };
    }

    public function savingsVsMonthlyLabel(): ?string
    {
        $amount = $this->savingsAmount();
        if ($amount <= 0 || (int) $this->term_months <= 1) {
            return null;
        }

        $isRtl = app()->getLocale() === 'ar';
        $money = number_format($amount, 0).' '.currency_symbol();

        return $isRtl
            ? 'وفر '.$money.' مقارنة بالدفع الشهري لمدة '.$this->term_months.' أشهر'
            : 'Save '.$money.' vs paying monthly for '.$this->term_months.' months';
    }

    public function featureList(): array
    {
        return array_values(array_filter($this->features ?? []));
    }

    public function giftList(): array
    {
        return array_values(array_filter($this->gifts ?? []));
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'package';
        $original = $slug;
        $i = 2;
        while (static::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public static function scopes(): array
    {
        return [
            self::SCOPE_GLOBAL => 'عام (كل التدريس)',
            self::SCOPE_TUTORING_INDIVIDUAL => 'حصص فردية',
            self::SCOPE_TUTORING_COLLECTIVE => 'حصص جماعية / مدرسة',
            self::SCOPE_PRIVATE_LESSONS => 'حصص خاصة 1:1',
        ];
    }

    public static function planTypes(): array
    {
        return [
            self::PLAN_SCHOOL => 'School Plan',
            self::PLAN_PRIVATE => 'Private Plan',
            self::PLAN_PREMIER => 'Premier Plan',
        ];
    }

    public static function termMonthsOptions(): array
    {
        return [1, 3, 6];
    }

    /**
     * @return array<string, array{label: string, tagline: ?string, icon: string, featured: bool, terms: \Illuminate\Support\Collection<int, self>}>
     */
    public static function commercialCatalogMatrix($packages = null): array
    {
        $packages = $packages ?: static::query()->commercial()->ordered()->get();
        $matrix = [];

        foreach (self::planTypes() as $type => $label) {
            $terms = $packages->where('plan_type', $type)->sortBy('term_months')->values();
            if ($terms->isEmpty()) {
                continue;
            }
            $sample = $terms->first();
            $matrix[$type] = [
                'label' => $label,
                'tagline' => $sample->tagline ?: $sample->description,
                'icon' => $sample->planIcon(),
                'featured' => $type === self::PLAN_PREMIER,
                'features' => $sample->featureList(),
                'gifts' => $sample->giftList(),
                'community' => (bool) $sample->includes_community,
                'libraries' => (bool) $sample->includes_libraries,
                'weekly_group' => (int) $sample->weekly_group_sessions,
                'weekly_private' => (int) $sample->weekly_private_sessions,
                'terms' => $terms->keyBy(fn (self $p) => (int) $p->term_months),
            ];
        }

        return $matrix;
    }
}
