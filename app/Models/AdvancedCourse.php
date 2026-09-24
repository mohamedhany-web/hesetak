<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdvancedCourse extends Model
{
    use HasFactory;

    /**
     * حذف السجلات المرتبطة بالكورس باستعلامات SQL مباشرة (بدون تحميل نماذج أو أحداث).
     * الترتيب حسب تبعيات المفاتيح الأجنبية: الأبناء قبل الآباء.
     * يُستدعى من الكونترولر قبل حذف سجل الكورس.
     */
    public static function deleteRelatedRecords(int $courseId): void
    {
        $steps = [
            ['table' => 'lesson_progress', 'column' => 'course_lesson_id', 'parent' => 'course_lessons', 'parent_column' => 'advanced_course_id'],
            ['table' => 'curriculum_items', 'column' => 'course_section_id', 'parent' => 'course_sections', 'parent_column' => 'advanced_course_id'],
            ['table' => 'attendance_records', 'column' => 'lecture_id', 'parent' => 'lectures', 'parent_column' => 'course_id'],
            ['table' => 'teams_attendance_files', 'column' => 'lecture_id', 'parent' => 'lectures', 'parent_column' => 'course_id'],
            ['table' => 'lectures', 'column' => 'course_id', 'direct' => true],
            ['table' => 'course_lessons', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'assignment_submissions', 'column' => 'assignment_id', 'parent' => 'assignments', 'parent_column' => 'advanced_course_id'],
            ['table' => 'assignments', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'review_helpful', 'column' => 'review_id', 'parent' => 'course_reviews', 'parent_column' => 'course_id'],
            ['table' => 'course_reviews', 'column' => 'course_id', 'direct' => true],
            ['table' => 'student_course_enrollments', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'installment_agreements', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'installment_plans', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'course_sections', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'exam_anti_cheat_logs', 'column' => 'exam_id', 'parent' => 'exams', 'parent_column' => 'advanced_course_id'],
            ['table' => 'exam_tab_switch_logs', 'column' => 'exam_id', 'parent' => 'exams', 'parent_column' => 'advanced_course_id'],
            ['table' => 'exam_activity_logs', 'column' => 'exam_id', 'parent' => 'exams', 'parent_column' => 'advanced_course_id'],
            ['table' => 'exam_attempts', 'column' => 'exam_id', 'parent' => 'exams', 'parent_column' => 'advanced_course_id'],
            ['table' => 'exam_questions', 'column' => 'exam_id', 'parent' => 'exams', 'parent_column' => 'advanced_course_id'],
            ['table' => 'exams', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'package_course', 'column' => 'course_id', 'direct' => true],
            ['table' => 'attendance_statistics', 'column' => 'course_id', 'direct' => true],
            ['table' => 'academic_year_courses', 'column' => 'advanced_course_id', 'direct' => true],
            ['table' => 'calendar_events', 'column' => 'advanced_course_id', 'direct' => true],
        ];

        foreach ($steps as $def) {
            try {
                if (! Schema::hasTable($def['table'])) {
                    continue;
                }
                if (! empty($def['direct'])) {
                    DB::table($def['table'])->where($def['column'], $courseId)->delete();
                } else {
                    if (! Schema::hasTable($def['parent'])) {
                        continue;
                    }
                    $parentIds = DB::table($def['parent'])->where($def['parent_column'], $courseId)->pluck('id');
                    if ($parentIds->isNotEmpty()) {
                        DB::table($def['table'])->whereIn($def['column'], $parentIds)->delete();
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    protected $fillable = [
        'instructor_id',
        'title',
        'academic_year_id',
        'academic_subject_id',
        'course_category_id',
        'programming_language',
        'framework',
        'category',
        'description',
        'video_url',
        'objectives',
        'level',
        'duration_hours',
        'duration_minutes',
        'price',
        'price_after_discount',
        'price_egp',
        'price_egp_after_discount',
        'price_usd',
        'price_usd_after_discount',
        'thumbnail',
        'requirements',
        'prerequisites',
        'what_you_learn',
        'skills',
        'language',
        'students_count',
        'rating',
        'reviews_count',
        'is_active',
        'is_featured',
        'is_free',
        'delivery_type',
        'product_track',
        'billing_mode',
        'monthly_price',
        'monthly_price_after_discount',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_free' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
        'price_after_discount' => 'decimal:2',
        'price_egp' => 'decimal:2',
        'price_egp_after_discount' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'price_usd_after_discount' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'monthly_price_after_discount' => 'decimal:2',
        'rating' => 'decimal:2',
        'skills' => 'array',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicSubject()
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function courseCategory()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // للحفاظ على التوافق مع الكود القديم
    public function teacher()
    {
        return $this->instructor();
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function lectures()
    {
        return $this->hasMany(Lecture::class, 'course_id');
    }

    public function activations()
    {
        return $this->hasMany(CourseActivation::class);
    }

    public function exams()
    {
        return $this->hasMany(AdvancedExam::class, 'advanced_course_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'advanced_course_id');
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class, 'advanced_course_id')->orderBy('order');
    }

    public function activeSections()
    {
        return $this->hasMany(CourseSection::class, 'advanced_course_id')
            ->where('is_active', true)
            ->orderBy('order');
    }

    public function installmentPlans()
    {
        return $this->hasMany(InstallmentPlan::class, 'advanced_course_id');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_course', 'course_id', 'package_id')
            ->withPivot('order')
            ->orderBy('package_course.order')
            ->withTimestamps();
    }

    public function installmentAgreements()
    {
        return $this->hasMany(InstallmentAgreement::class, 'advanced_course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentCourseEnrollment::class, 'advanced_course_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /** رابط صورة الغلاف للواجهة العامة */
    public function getThumbnailUrlAttribute(): ?string
    {
        return storage_public_url($this->thumbnail);
    }

    /**
     * علاقة مع الطلاب المسجلين
     */
    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'student_course_enrollments', 'advanced_course_id', 'user_id')
            ->withPivot(['status', 'progress', 'enrolled_at', 'activated_at']);
    }

    /**
     * علاقة مع الطلاب النشطين فقط
     */
    public function activeStudents()
    {
        return $this->belongsToMany(User::class, 'student_course_enrollments', 'advanced_course_id', 'user_id')
            ->wherePivot('status', 'active')
            ->withPivot(['status', 'progress', 'enrolled_at', 'activated_at']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function getTotalLessonsAttribute()
    {
        return $this->lessons()->count();
    }

    public function getActivatedStudentsCountAttribute()
    {
        return $this->activations()->where('is_active', true)->count();
    }

    public function isActivatedForUser($userId)
    {
        return $this->activations()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function getProgressForUser($userId)
    {
        $totalLessons = $this->lessons()->count();
        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = LessonProgress::where('user_id', $userId)
            ->whereIn('course_lesson_id', $this->lessons()->pluck('id'))
            ->where('is_completed', true)
            ->count();

        return round(($completedLessons / $totalLessons) * 100, 2);
    }

    public function getLevelBadgeAttribute()
    {
        $badges = [
            'beginner' => ['text' => 'مبتدئ', 'color' => 'green'],
            'intermediate' => ['text' => 'متوسط', 'color' => 'yellow'],
            'advanced' => ['text' => 'متقدم', 'color' => 'red'],
        ];

        return $badges[$this->level] ?? $badges['beginner'];
    }

    /**
     * السعر الأساسي المعروض كـ «قبل الخصم» على البطاقات (حقل price).
     */
    public function listPriceAmount(?string $currency = null): float
    {
        $currency = strtoupper((string) ($currency ?: platform_currency()));
        if (in_array($currency, ['SAR', 'USD'], true)) {
            $v = $this->price_usd;
            if ($v !== null && $v !== '') {
                return round(max(0, (float) $v), 2);
            }
            // توافق: إن لم يُملأ حقل السعر استخدم السعر الأساسي أو الحقل القديم
            $fallback = $this->price ?? $this->price_egp;
            if ($fallback !== null && $fallback !== '') {
                return round(max(0, (float) $fallback), 2);
            }
        }
        if ($currency === 'EGP') {
            $v = $this->price_egp;
            if ($v !== null && $v !== '') {
                return round(max(0, (float) $v), 2);
            }
        }

        return round(max(0, (float) ($this->price ?? 0)), 2);
    }

    /**
     * السعر الفعلي للشراء قبل كوبون المنصة/المحفظة: بعد الخصم الترويجي إن وُجد، وإلا السعر الأساسي.
     */
    public function effectivePurchasePrice(?string $currency = null): float
    {
        $currency = strtoupper((string) ($currency ?: platform_currency()));
        $list = $this->listPriceAmount($currency);
        if ($list <= 0) {
            return 0.0;
        }

        $sale = match ($currency) {
            'SAR', 'USD' => $this->price_usd_after_discount,
            default => $this->price_egp_after_discount,
        };
        // توافق مع الحقل القديم إن لم تُملأ الأسعار المزدوجة
        if (($sale === null || $sale === '') && in_array($currency, ['SAR', 'USD'], true)) {
            $sale = $this->price_after_discount ?? $this->price_egp_after_discount;
        }
        if (($sale === null || $sale === '') && $currency === 'EGP' && ($this->price_egp === null || $this->price_egp === '')) {
            $sale = $this->price_after_discount;
        }

        if ($sale === null || $sale === '') {
            return $list;
        }
        $s = round((float) $sale, 2);
        if ($s <= 0 || $s >= $list) {
            return $list;
        }

        return $s;
    }

    /**
     * هل يُعرض على البطاقة سعران (قبل وبعد خصم ترويجي)؟
     */
    public function hasPromotionalPrice(?string $currency = null): bool
    {
        $currency = strtoupper((string) ($currency ?: platform_currency()));
        $list = $this->listPriceAmount($currency);
        if ($list <= 0) {
            return false;
        }
        $sale = match ($currency) {
            'SAR', 'USD' => $this->price_usd_after_discount,
            default => ($this->price_egp_after_discount !== null && $this->price_egp_after_discount !== '')
                ? $this->price_egp_after_discount
                : $this->price_after_discount,
        };
        if (($sale === null || $sale === '') && in_array($currency, ['SAR', 'USD'], true)) {
            $sale = $this->price_after_discount ?? $this->price_egp_after_discount;
        }
        if ($sale === null || $sale === '') {
            return false;
        }
        $s = round((float) $sale, 2);

        return $s > 0 && $s < $list;
    }

    public function isMonthlyBilling(): bool
    {
        return ($this->billing_mode ?? 'one_time') === \App\Services\CourseSubscriptionService::BILLING_MONTHLY;
    }

    public function isOneToOne(): bool
    {
        return ($this->delivery_type ?? 'group') === \App\Services\CourseSubscriptionService::DELIVERY_ONE_TO_ONE;
    }

    public function isRecordedTrack(): bool
    {
        return ($this->product_track ?? null) === \App\Services\AdaptiveLearningService::TRACK_RECORDED;
    }

    public function isBookTrack(): bool
    {
        return ($this->product_track ?? null) === \App\Services\AdaptiveLearningService::TRACK_BOOK;
    }

    public function productTrackLabel(): string
    {
        return match ($this->product_track) {
            \App\Services\AdaptiveLearningService::TRACK_RECORDED => app()->getLocale() === 'ar' ? 'كورس مسجّل' : 'Recorded',
            \App\Services\AdaptiveLearningService::TRACK_BOOK => app()->getLocale() === 'ar' ? 'كتاب للقراءة' : 'Book',
            default => app()->getLocale() === 'ar' ? 'مباشر' : 'Live',
        };
    }

    public function monthlyListPrice(): float
    {
        return round(max(0, (float) ($this->monthly_price ?? 0)), 2);
    }

    public function effectiveMonthlyPrice(): float
    {
        $list = $this->monthlyListPrice();
        if ($list <= 0) {
            return 0.0;
        }
        $sale = $this->monthly_price_after_discount;
        if ($sale === null || $sale === '') {
            return $list;
        }
        $s = round((float) $sale, 2);
        if ($s <= 0 || $s >= $list) {
            return $list;
        }

        return $s;
    }

    /**
     * السعر المعروض عند الدفع حسب نمط الفوترة.
     */
    public function effectiveCheckoutPrice(?string $currency = null): float
    {
        if ($this->isMonthlyBilling()) {
            return $this->effectiveMonthlyPrice();
        }

        return $this->effectivePurchasePrice($currency);
    }

    /**
     * بيانات الكورس لصفحة الكتالوج العام (JSON / Alpine).
     *
     * @return array<string, mixed>
     */
    public function toPublicCatalogArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?? 'بدون عنوان',
            'description' => $this->description ?? '',
            'level' => $this->level ?? 'beginner',
            'price' => (float) ($this->price ?? 0),
            'sale_price' => $this->effectivePurchasePrice(),
            'monthly_price' => $this->effectiveMonthlyPrice(),
            'checkout_price' => $this->effectiveCheckoutPrice(),
            'has_promo_price' => $this->hasPromotionalPrice(),
            'duration_hours' => (int) ($this->duration_hours ?? 0),
            'is_featured' => (bool) ($this->is_featured ?? false),
            'is_free' => (bool) ($this->is_free ?? false),
            'delivery_type' => $this->delivery_type ?? 'group',
            'billing_mode' => $this->billing_mode ?? 'one_time',
            'is_one_to_one' => $this->isOneToOne(),
            'is_monthly' => $this->isMonthlyBilling(),
            'lectures_count' => (int) ($this->lectures_count ?? $this->lessons_count ?? 0),
            'thumbnail' => $this->thumbnail_url,
            'academic_subject_id' => $this->academic_subject_id ? (int) $this->academic_subject_id : null,
            'academic_subject' => $this->academicSubject ? [
                'name' => $this->academicSubject->name ?? 'غير محدد',
            ] : null,
            'course_category_id' => $this->course_category_id ? (int) $this->course_category_id : null,
            'course_category' => $this->courseCategory ? [
                'name' => $this->courseCategory->name ?? '',
            ] : null,
            'instructor' => $this->instructor ? [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
            ] : null,
        ];
    }
}
