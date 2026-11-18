<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AcademicSubject extends Model
{
    use HasFactory;

    public const CURRICULA_TABLE = 'academic_subject_curricula';

    protected $fillable = [
        'academic_year_id',
        'curriculum_type',
        'name',
        'code',
        'slug',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function years(): BelongsToMany
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_subject_years');
    }

    public function tutoringGroups(): HasMany
    {
        return $this->hasMany(TutoringGroup::class, 'academic_subject_id');
    }

    public function courses()
    {
        return $this->hasMany(AdvancedCourse::class, 'academic_subject_id');
    }

    public function advancedCourses()
    {
        return $this->hasMany(AdvancedCourse::class, 'academic_subject_id');
    }

    public function questionCategories()
    {
        return $this->hasMany(QuestionCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function scopeGlobalCatalog($query)
    {
        return $query->whereNull('academic_year_id');
    }

    /** مواد مرحلة معيّنة عبر العمود أو جدول الربط المتعدد. */
    public function scopeForYear($query, ?int $yearId)
    {
        if (! $yearId) {
            return $query->whereNull('academic_year_id');
        }

        return $query->where(function ($q) use ($yearId) {
            $q->where('academic_year_id', $yearId);
            if (\Illuminate\Support\Facades\Schema::hasTable('academic_subject_years')) {
                $q->orWhereHas('years', fn ($yq) => $yq->where('academic_years.id', $yearId));
            }
        });
    }

    /**
     * المادة تظهر مع المنهج إذا: لا منهج محدد (مشتركة) أو العمود الأساسي يطابق أو أحد المناهج المربوطة يطابق.
     */
    public function scopeForCurriculumType($query, ?string $curriculumType)
    {
        $track = strtolower(trim((string) $curriculumType));
        if ($track === '' || ! Schema::hasColumn('academic_subjects', 'curriculum_type')) {
            return $query;
        }

        $hasPivot = Schema::hasTable(self::CURRICULA_TABLE);

        return $query->where(function ($q) use ($track, $hasPivot) {
            $q->whereNull('academic_subjects.curriculum_type')
                ->orWhere('academic_subjects.curriculum_type', $track);

            if ($hasPivot) {
                $q->orWhereExists(function ($sub) use ($track) {
                    $sub->select(DB::raw(1))
                        ->from(self::CURRICULA_TABLE)
                        ->whereColumn(self::CURRICULA_TABLE.'.academic_subject_id', 'academic_subjects.id')
                        ->where(self::CURRICULA_TABLE.'.curriculum_type', $track);
                });
            }
        });
    }

    /**
     * كل مفاتيح المناهج المربوطة بالمادة (فارغ = مشتركة لكل المناهج).
     *
     * @return list<string>
     */
    public function curriculumTypeKeys(): array
    {
        $keys = [];
        if (Schema::hasTable(self::CURRICULA_TABLE)) {
            $keys = DB::table(self::CURRICULA_TABLE)
                ->where('academic_subject_id', $this->id)
                ->pluck('curriculum_type')
                ->map(fn ($k) => strtolower(trim((string) $k)))
                ->all();
        }
        $primary = strtolower(trim((string) $this->curriculum_type));
        if ($primary !== '') {
            $keys[] = $primary;
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public function matchesCurriculumType(?string $curriculumType): bool
    {
        $track = strtolower(trim((string) $curriculumType));
        if ($track === '') {
            return true;
        }
        $keys = $this->curriculumTypeKeys();

        return $keys === [] || in_array($track, $keys, true);
    }

    /**
     * يحفظ المناهج المتعددة؛ العمود curriculum_type يحمل أول منهج للتوافق مع الكود القديم.
     *
     * @param  list<string>  $keys
     */
    public function syncCurriculumTypes(array $keys): void
    {
        $keys = array_values(array_unique(array_filter(array_map(
            fn ($k) => strtolower(trim((string) $k)),
            $keys
        ))));

        if (Schema::hasColumn('academic_subjects', 'curriculum_type')) {
            $this->forceFill(['curriculum_type' => $keys[0] ?? null])->save();
        }

        if (! Schema::hasTable(self::CURRICULA_TABLE)) {
            return;
        }

        DB::table(self::CURRICULA_TABLE)->where('academic_subject_id', $this->id)->delete();
        foreach ($keys as $key) {
            DB::table(self::CURRICULA_TABLE)->insert([
                'academic_subject_id' => $this->id,
                'curriculum_type' => $key,
            ]);
        }
    }

    public function faIcon(): string
    {
        $icon = trim((string) $this->icon);
        if ($icon === '') {
            return 'fa-book-open';
        }
        if (str_starts_with($icon, 'fa-') && ! str_contains($icon, ' ')) {
            return $icon;
        }
        if (str_contains($icon, ' ')) {
            $parts = preg_split('/\s+/', $icon) ?: [];
            foreach (array_reverse($parts) as $part) {
                if (str_starts_with($part, 'fa-')) {
                    return $part;
                }
            }
        }
        if (! str_starts_with($icon, 'fa')) {
            return 'fa-'.$icon;
        }

        return 'fa-book-open';
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'subject';
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

    public function getActiveCoursesCountAttribute()
    {
        return $this->advancedCourses()->where('is_active', true)->count();
    }

    public function getFullNameAttribute()
    {
        $yearName = $this->academicYear?->name;

        return $yearName ? ($yearName.' - '.$this->name) : $this->name;
    }
}
