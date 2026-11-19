<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LibraryFolder extends Model
{
    public const KIND_VIDEOS = 'videos';

    public const KIND_MATERIALS = 'materials';

    public const KIND_BOTH = 'both';

    protected $fillable = [
        'instructor_id',
        'academic_year_id',
        'kind',
        'content_theme',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'requires_library_entitlement',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'requires_library_entitlement' => 'boolean',
    ];

    public const COLORS = ['blue', 'pink', 'orange', 'purple', 'green'];

    protected static function booted(): void
    {
        static::saving(function (self $folder) {
            if (blank($folder->slug)) {
                $folder->slug = self::uniqueSlugFor($folder->name_en ?: $folder->name_ar, $folder->id);
            }
            if (blank($folder->kind)) {
                $folder->kind = self::KIND_VIDEOS;
            }
        });
    }

    /**
     * slug فريد (يمنع خطأ التكرار عند تسمية مجلدين بنفس الاسم).
     */
    public static function uniqueSlugFor(?string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug((string) $name) ?: 'folder';
        $slug = $base;
        $suffix = 2;

        while (self::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(LiveRecording::class, 'library_folder_id');
    }

    public function libraryVideos(): HasMany
    {
        return $this->hasMany(LibraryVideo::class, 'library_folder_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LectureMaterial::class, 'library_folder_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeOfKind($query, string $kind)
    {
        return $query->where(function ($q) use ($kind) {
            $q->where('kind', $kind)->orWhere('kind', self::KIND_BOTH);
        });
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'en' && filled($this->name_en)) {
            return (string) $this->name_en;
        }

        return (string) $this->name_ar;
    }

    public function displayDescription(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();

        if ($locale === 'en' && filled($this->description_en)) {
            return (string) $this->description_en;
        }

        return $this->description_ar ? (string) $this->description_ar : null;
    }

    public function scopeOfTheme($query, string $theme)
    {
        return $query->where('content_theme', $theme);
    }

    public function themeMeta(): array
    {
        return \App\Support\FamilyLibraryThemes::meta($this->content_theme ?: \App\Support\FamilyLibraryThemes::GENERAL);
    }
}
