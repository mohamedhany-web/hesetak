<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'tagline',
        'level_number',
        'description',
        'video_url',
        'thumbnail',
        'price',
        'icon',
        'color',
        'order',
        'is_active',
        'is_public',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'price' => 'decimal:2',
        'level_number' => 'integer',
        'order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function subjects()
    {
        return $this->hasMany(AcademicSubject::class);
    }

    public function tutoringGroups(): HasMany
    {
        return $this->hasMany(TutoringGroup::class, 'academic_year_id');
    }

    public function freeTrialBookings(): HasMany
    {
        return $this->hasMany(FreeTrialBooking::class, 'recommended_academic_year_id');
    }

    public function imageUrl(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }
        if (str_starts_with($this->thumbnail, 'http')) {
            return $this->thumbnail;
        }

        return Storage::disk('public')->url($this->thumbnail);
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'year';
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

    public function academicSubjects()
    {
        return $this->hasMany(AcademicSubject::class);
    }

    // تم إزالة العلاقة المباشرة مع advanced_courses لأن academic_year_id تم إزالته
    // يمكن الوصول للكورسات من خلال المواد الدراسية: $year->academicSubjects->flatMap->advancedCourses
    public function getAdvancedCoursesAttribute()
    {
        if (!$this->relationLoaded('academicSubjects')) {
            $this->load('academicSubjects.advancedCourses');
        }
        return $this->academicSubjects->flatMap->advancedCourses;
    }

    // Alias للتوافق مع الكود القديم
    // يعيد query builder للكورسات المرتبطة بالمواد الدراسية لهذه السنة
    // ملاحظة: إذا كان academic_subject_id غير موجود، سيعيد query فارغ
    public function courses()
    {
        // التحقق من وجود العمود أولاً
        if (!Schema::hasColumn('advanced_courses', 'academic_subject_id')) {
            // إذا لم يكن موجوداً، نعيد query فارغ
            return AdvancedCourse::where('id', '<', 0);
    }

        $subjectIds = $this->academicSubjects()->pluck('academic_subjects.id')->toArray();
        if (empty($subjectIds)) {
            return AdvancedCourse::where('id', '<', 0);
        }
        return AdvancedCourse::whereIn('academic_subject_id', $subjectIds);
    }

    public function questionCategories()
    {
        return $this->hasMany(QuestionCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublicCatalog($query)
    {
        return $query->where('is_public', true)->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('level_number')->orderBy('id');
    }

    public function getActiveSubjectsCountAttribute()
    {
        return $this->subjects()->active()->count();
    }

    public function getActiveCoursesCountAttribute()
    {
        return $this->academicSubjects->sum(function($subject) {
            return $subject->advancedCourses()->active()->count();
        });
    }

    /**
     * علاقة مع الكورسات المرتبطة مباشرة بالمسار (many-to-many)
     */
    public function linkedCourses()
    {
        return $this->belongsToMany(AdvancedCourse::class, 'academic_year_courses', 'academic_year_id', 'advanced_course_id')
            ->withPivot('order', 'is_required')
            ->orderBy('academic_year_courses.order')
            ->withTimestamps()
            ->select('advanced_courses.*'); // تحديد الأعمدة بشكل صريح لتجنب ambiguous column
    }

    /**
     * علاقة مع المدربين في المسار
     */
    public function instructors()
    {
        return $this->belongsToMany(User::class, 'academic_year_instructors', 'academic_year_id', 'instructor_id')
            ->withPivot('assigned_courses', 'notes')
            ->withTimestamps();
    }

    /**
     * علاقة مع تسجيلات الطلاب في المسار
     */
    public function enrollments()
    {
        return $this->hasMany(LearningPathEnrollment::class, 'academic_year_id');
    }

    /**
     * علاقة مع الطلاب المسجلين في المسار
     */
    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'learning_path_enrollments', 'academic_year_id', 'user_id')
            ->withPivot(['status', 'progress', 'enrolled_at', 'activated_at'])
            ->withTimestamps();
    }
}