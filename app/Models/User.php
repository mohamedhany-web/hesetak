<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Services\UserProfileImageStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'google_id',
        'role',
        'is_community_contributor',
        'community_contributor_type',
        'parent_id',
        'is_active',
        'profile_image',
        'birth_date',
        'address',
        'timezone',
        'bio',
        'gender',
        'portfolio_headline',
        'portfolio_about',
        'portfolio_skills',
        'portfolio_social_links',
        'portfolio_intro_video_url',
        'portfolio_profile_status',
        'portfolio_profile_submitted_at',
        'portfolio_profile_reviewed_at',
        'portfolio_profile_reviewed_by',
        'portfolio_profile_rejected_reason',
        'portfolio_marketing_published',
        'private_teaching_meta',
        'academic_year_id',
        'last_login_at',
        'referral_code',
        'referred_by',
        'referred_at',
        'total_referrals',
        'completed_referrals',
        'employee_job_id',
        'employee_code',
        'hire_date',
        'termination_date',
        'salary',
        'employee_notes',
        'bank_name',
        'bank_branch',
        'bank_account_number',
        'bank_account_holder_name',
        'bank_iban',
        'is_employee',
        'employee_permissions_custom',
        'employee_permissions',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_community_contributor' => 'boolean',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'referred_at' => 'datetime',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'salary' => 'decimal:2',
            'is_employee' => 'boolean',
            'employee_permissions_custom' => 'boolean',
            'employee_permissions' => 'array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'portfolio_social_links' => 'array',
            'portfolio_marketing_published' => 'array',
            'private_teaching_meta' => 'array',
            'portfolio_profile_submitted_at' => 'datetime',
            'portfolio_profile_reviewed_at' => 'datetime',
        ];
    }

    /**
     * منطقة المستخدم الزمنية للعرض (أو توقيت الأكاديمية).
     */
    public function timezoneCode(): string
    {
        return \App\Support\AppTimezone::forUser($this);
    }

    /**
     * @return array{subjects?: list<string>, age_groups?: list<string>, languages?: list<string>, specializations?: list<string>}
     */
    public function privateTeachingMeta(): array
    {
        $meta = $this->private_teaching_meta;

        return is_array($meta) ? $meta : [];
    }

    public function privateTeachingHas(string $key, string $value): bool
    {
        $list = $this->privateTeachingMeta()[$key] ?? [];

        return is_array($list) && in_array($value, $list, true);
    }

    /** ملف التسويق الشخصي (بورتفوليو): بانتظار مراجعة الأدمن */
    public const PORTFOLIO_PROFILE_PENDING = 'pending_review';

    public const PORTFOLIO_PROFILE_APPROVED = 'approved';

    public const PORTFOLIO_PROFILE_REJECTED = 'rejected';

    /** مساهم في مجتمع البيانات فقط */
    public const COMMUNITY_CONTRIBUTOR_TYPE_DATA = 'data';

    /** مساهم في الذكاء الاصطناعي (Model Zoo، نماذج، إلخ) */
    public const COMMUNITY_CONTRIBUTOR_TYPE_AI = 'ai';

    public function isCommunityDataContributor(): bool
    {
        return $this->community_contributor_type === self::COMMUNITY_CONTRIBUTOR_TYPE_DATA;
    }

    public function isCommunityAiContributor(): bool
    {
        return $this->community_contributor_type === self::COMMUNITY_CONTRIBUTOR_TYPE_AI;
    }

    public function contributorProfile()
    {
        return $this->hasOne(ContributorProfile::class);
    }

    public function portfolioProfileReviewedBy()
    {
        return $this->belongsTo(User::class, 'portfolio_profile_reviewed_by');
    }

    /**
     * أثناء pending/rejected يعرض الموقع العلني آخر نسخة معتمدة (portfolio_marketing_published)، وليس المسودة الحالية.
     */
    public function usesPublishedPortfolioMarketingOnPublicSite(): bool
    {
        return in_array($this->portfolio_profile_status, [
            self::PORTFOLIO_PROFILE_PENDING,
            self::PORTFOLIO_PROFILE_REJECTED,
        ], true);
    }

    /**
     * لقطة للحقول المعروضة علناً بعد اعتماد الأدمن.
     */
    public function snapshotPortfolioMarketingForPublish(): array
    {
        return [
            'headline' => $this->portfolio_headline,
            'about' => $this->portfolio_about,
            'skills' => $this->portfolio_skills,
            'intro_video_url' => $this->portfolio_intro_video_url,
            'profile_image' => $this->profile_image,
        ];
    }

    /**
     * حقول التسويق الشخصي الظاهرة في صفحات البورتفوليو العامة (النسخة المعتمدة أثناء المراجعة).
     *
     * @return array{headline: ?string, about: ?string, skills: ?string, intro_video_url: ?string}
     */
    public function publicPortfolioMarketingFields(): array
    {
        if ($this->usesPublishedPortfolioMarketingOnPublicSite()) {
            $pub = $this->portfolio_marketing_published ?? [];

            return [
                'headline' => $pub['headline'] ?? null,
                'about' => $pub['about'] ?? null,
                'skills' => $pub['skills'] ?? null,
                'intro_video_url' => $pub['intro_video_url'] ?? null,
            ];
        }

        return [
            'headline' => $this->portfolio_headline,
            'about' => $this->portfolio_about,
            'skills' => $this->portfolio_skills,
            'intro_video_url' => $this->portfolio_intro_video_url,
        ];
    }

    /**
     * رابط صورة الملف في البورتفوليو العام (يحترم حالة المراجعة).
     */
    public function getPublicPortfolioMarketingPhotoUrlAttribute(): ?string
    {
        if (! $this->usesPublishedPortfolioMarketingOnPublicSite()) {
            return $this->profile_image_url;
        }

        $path = data_get($this->portfolio_marketing_published, 'profile_image');
        if (empty($path)) {
            return null;
        }

        $path = str_replace('\\', '/', ltrim((string) $path, '/'));
        $base = UserProfileImageStorage::publicUrl($path)
            ?? \App\Services\PublicStorageUrl::fromPath($path);
        $ts = $this->portfolio_profile_reviewed_at?->timestamp ?? '0';

        return $base.(str_contains($base, '?') ? '&' : '?').'v='.$ts;
    }

    /**
     * رابط صورة الملف الشخصي.
     * الصور في storage/app/public تُعرض عبر Storage::disk('public')->url() لضمان الرابط الصحيح.
     * تطبيع المسار (backslash على Windows) وضمان URL كامل.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }
        $path = str_replace('\\', '/', trim($this->profile_image));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $base = $path;
        } else {
            $base = UserProfileImageStorage::publicUrl($path)
                ?? \App\Services\PublicStorageUrl::fromPath($path);
        }
        if (! is_string($base) || $base === '') {
            return null;
        }
        $ts = $this->updated_at ? $this->updated_at->timestamp : time();

        return $base.(str_contains($base, '?') ? '&' : '?').'v='.$ts;
    }

    public static function placeholderAvatarUrl(): string
    {
        return asset('img/student-timeline/avatar-placeholder.svg');
    }

    /**
     * صورة الحساب إن وُجدت، وإلا أيقونة عامة — بدون صورة التصميم الافتراضية.
     */
    public function avatarDisplayUrl(): string
    {
        return $this->profile_image_url ?: self::placeholderAvatarUrl();
    }

    /**
     * هل هذا المستخدم مشمول بإلزام المصادقة الثنائية عند تفعيل الخيار من إعدادات النظام (.env أو لوحة التحكم).
     * يقتصر على المدير العام والأدمن فقط — لا يشمل المدربين ولا بقية الأدوار.
     */
    public function requiresTwoFactor(): bool
    {
        if (! \App\Services\PlatformSecuritySettings::isAdminTwoFactorRequired()) {
            return false;
        }

        return in_array((string) $this->role, ['super_admin', 'admin'], true);
    }

    /**
     * هل يستخدم هذا المستخدم 2FA عبر البريد (بدون تطبيق TOTP)
     */
    public function usesEmailTwoFactor(): bool
    {
        return $this->requiresTwoFactor() && ! $this->hasTwoFactorEnabled();
    }

    /**
     * هل المصادقة الثنائية مفعّلة للمستخدم
     */
    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    /**
     * علاقة مع ولي الأمر
     */
    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * علاقة مع الأطفال (للوالدين)
     */
    public function children()
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * علاقة مع السنة الدراسية
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * علاقة مع تسجيلات الكورسات
     */
    public function courseEnrollments()
    {
        return $this->hasMany(StudentCourseEnrollment::class, 'user_id');
    }

    /**
     * اشتراكات الباقات (جدول subscriptions) — مطلوب لـ canHostLiveSession.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * الاشتراك النشط الحالي إن وُجد.
     */
    public function activeSubscription(): ?Subscription
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
            return null;
        }

        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * طلاب يشرف عليهم هذا الموظف كمشرف أكاديمي.
     */
    public function supervisedStudentsAsAcademic()
    {
        return $this->belongsToMany(User::class, 'academic_supervisor_students', 'supervisor_id', 'student_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * المشرفون الأكاديميون المعيّنون لهذا الطالب.
     */
    public function academicSupervisors()
    {
        return $this->belongsToMany(User::class, 'academic_supervisor_students', 'student_id', 'supervisor_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * علاقة مع اتفاقيات المدرب
     */
    public function instructorAgreements()
    {
        return $this->hasMany(InstructorAgreement::class, 'instructor_id');
    }

    public function instructorProfile()
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function tutorApplications()
    {
        return $this->hasMany(TutorApplication::class);
    }

    public function latestTutorApplication(): ?TutorApplication
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('tutor_applications')) {
            return null;
        }

        if ($this->relationLoaded('tutorApplications')) {
            return $this->tutorApplications->sortByDesc('id')->first();
        }

        return $this->tutorApplications()->orderByDesc('id')->first();
    }

    /**
     * دخول لوحة المعلم بعد تفعيل الإدارة فقط.
     * الحسابات التي أنشأتها الإدارة بدون طلب توظيف تبقى مسموحة.
     */
    public function canAccessInstructorPanel(): bool
    {
        if (! $this->isInstructor() && ! $this->isTeacher()) {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        $application = $this->latestTutorApplication();
        if ($application === null) {
            return true;
        }

        if ($application->status === TutorApplication::STATUS_ACTIVATED) {
            return true;
        }

        try {
            if ($this->hasTeachingCourses()) {
                return true;
            }
        } catch (\Throwable) {
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('tutoring_groups')) {
            try {
                if (TutoringGroup::query()->where('instructor_id', $this->id)->exists()) {
                    return true;
                }
            } catch (\Throwable) {
            }
        }

        return false;
    }

    public function instructorHomeUrl(): string
    {
        if ($this->canAccessInstructorPanel()) {
            return route('dashboard');
        }

        return route('public.tutor.apply.profile');
    }

    public function agreementPayments()
    {
        return $this->hasMany(AgreementPayment::class, 'instructor_id');
    }

    public function payoutDetail()
    {
        return $this->hasOne(InstructorPayoutDetail::class);
    }

    /**
     * علاقة مع محاولات الامتحان
     */
    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * علاقة مع التقارير كطالب
     */
    public function studentReports()
    {
        return $this->hasMany(StudentReport::class, 'student_id');
    }

    /**
     * علاقة مع التقارير كولي أمر
     */
    public function parentReports()
    {
        return $this->hasMany(StudentReport::class, 'parent_id');
    }

    /**
     * علاقة مع رسائل الواتساب
     */
    public function whatsappMessages()
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    /**
     * علاقة مع الإشعارات المخصصة (تجاوز Laravel's built-in)
     */
    public function customNotifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    /**
     * تجاوز علاقة notifications الافتراضية
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    /**
     * علاقة مع محفظة المستخدم المالية
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * التحقق من كون المستخدم طالب
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * التحقق من كون المستخدم مدرب
     */
    public function isInstructor(): bool
    {
        return in_array((string) $this->role, ['instructor', 'teacher'], true);
    }

    /**
     * التحقق من كون المستخدم مدير عام
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * التحقق من كون المستخدم إداري (للتوافق مع الكود القديم)
     */
    public function isAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * التحقق من كون المستخدم معلّم/مدرب (للتوافق مع الكود القديم + دور teacher)
     */
    public function isTeacher(): bool
    {
        return in_array((string) $this->role, ['instructor', 'teacher'], true);
    }

    /**
     * ولي أمر: دور parent إن وُجد، أو لديه أبناء عبر parent_id، أو مستخدم كوصي.
     */
    public function isParent(): bool
    {
        if ((string) $this->role === 'parent') {
            return true;
        }

        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        try {
            return $this->children()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * scope للطلاب
     */
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    /**
     * scope للمدربين
     */
    public function scopeInstructors($query)
    {
        return $query->where('role', 'instructor');
    }

    /**
     * scope للمدربين (للتوافق مع الكود القديم)
     */
    public function scopeTeachers($query)
    {
        return $query->where('role', 'instructor');
    }

    /**
     * المستخدمون الذين يمكن تعيينهم كمعلم/مقدم لجلسة بث مباشر:
     * أدمن/مدرب داخلي، أو طالب مشترك لديه اشتراك نشط (المعلم = المشترك عندنا).
     * إن لم يوجد جدول subscriptions (بعض السيرفرات) نكتفي بالمعلمين الداخليين.
     */
    public function scopeCanHostLiveSession($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('role', ['instructor', 'teacher']);

            if (\Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
                $q->orWhere(function ($q2) {
                    $q2->where('role', 'student')
                        ->whereHas('subscriptions', function ($sub) {
                            $sub->where('status', 'active')
                                ->where(function ($d) {
                                    $d->whereNull('end_date')->orWhere('end_date', '>=', now());
                                });
                        });
                });
            }
        });
    }

    /**
     * scope للمستخدمين النشطين
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * الحصول على الكورسات النشطة للطالب
     */
    public function activeCourses()
    {
        return $this->belongsToMany(AdvancedCourse::class, 'student_course_enrollments', 'user_id', 'advanced_course_id')
            ->withPivot(['status', 'progress', 'enrolled_at', 'activated_at', 'expires_at', 'access_type', 'enrollment_type'])
            ->where('student_course_enrollments.status', 'active')
            ->where(function ($q) {
                $q->whereNull('student_course_enrollments.expires_at')
                    ->orWhere('student_course_enrollments.expires_at', '>', now());
            })
            ->orderByDesc('student_course_enrollments.activated_at')
            ->orderByDesc('student_course_enrollments.created_at');
    }

    /**
     * التحقق من التسجيل في كورس أونلاين (يشمل صلاحية الاشتراك الشهري).
     */
    public function isEnrolledIn($courseId): bool
    {
        $enrollment = $this->getCourseEnrollment($courseId);

        return \App\Services\CourseSubscriptionService::enrollmentGrantsAccess($enrollment);
    }

    /**
     * الحصول على تسجيل الكورس
     */
    public function getCourseEnrollment($courseId)
    {
        return $this->courseEnrollments()
            ->where('advanced_course_id', $courseId)
            ->first();
    }

    /**
     * الحصول على آخر تقرير شهري
     */
    public function getLastMonthlyReport()
    {
        return $this->studentReports()
            ->where('report_type', 'monthly')
            ->latest()
            ->first();
    }

    /**
     * الحصول على متوسط الدرجات
     */
    public function getAverageScore()
    {
        return $this->examAttempts()
            ->where('status', 'completed')
            ->avg('percentage') ?? 0;
    }

    /**
     * الحصول على عدد الامتحانات المكتملة
     */
    public function getCompletedExamsCount()
    {
        return $this->examAttempts()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * تحديث آخر دخول بدون تفعيل Observers
     */
    public function updateLastLogin()
    {
        // استخدام DB مباشرة لتجنب أي مشاكل
        \DB::table('users')
            ->where('id', $this->id)
            ->update(['last_login_at' => now(), 'updated_at' => now()]);
    }

    /**
     * العلاقة مع الأدوار (نظام الصلاحيات المخصص)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * الحصول على جميع الصلاحيات للمستخدم (من الأدوار)
     */
    public function permissions()
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id');
    }

    /**
     * كل أسماء الصلاحيات في الجدول التي تُعدّ مطابقة للاسم المطلوب (حديث + قديم).
     *
     * @return list<string>
     */
    public static function permissionNamesToCheck(string $permissionName): array
    {
        $legacy = config('permission_aliases.legacy_names_for_canonical.'.$permissionName, []);

        return array_values(array_unique(array_merge([$permissionName], $legacy)));
    }

    /**
     * هل يمكن للمستخدم الدخول إلى لوحة الإدارة (صلاحية admin.access أو سوبر أدمن بدون أدوار).
     */
    public function userHasAdminAccessCapability(): bool
    {
        if ($this->isAdmin() && ! $this->roles()->exists()) {
            return true;
        }

        // يتماشى مع EnsurePermission لـ permission:admin.access: موظف له دور RBAC يُسمح له بمجموعة admin ثم يُقيَّد لاحقاً
        if ($this->is_employee && $this->roles()->exists()) {
            return true;
        }

        $names = self::permissionNamesToCheck('admin.access');

        if ($this->directPermissions()->whereIn('name', $names)->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', function ($query) use ($names) {
            $query->whereIn('name', $names);
        })->exists();
    }

    /**
     * Gate:: و can('manage.orders') يعتمدان على الصلاحيات المخزّنة للأدوار/المباشرة (مثل hasPermission).
     * بدون هذا، الموظف ذو الدور يمرّ بوسيط RBAC ثم يُرفض داخل المتحكم.
     *
     * @param  string|iterable  $abilities
     * @param  mixed  $arguments
     */
    public function can($abilities, $arguments = [])
    {
        if (is_string($abilities) && $arguments === [] && str_contains($abilities, '.')) {
            if ($this->hasPermission($abilities)) {
                return true;
            }
        }

        return parent::can($abilities, $arguments);
    }

    /**
     * التحقق من وجود صلاحية معينة (من الأدوار أو المباشرة)
     */
    public function hasPermission($permissionName)
    {
        // إذا كان admin (Super Admin) لكن تم ربطه بأدوار RBAC مخصّصة،
        // عندها لا نتجاوز الصلاحيات تلقائياً بل نعتمد على صلاحيات الدور.
        if ($this->isAdmin()) {
            if (! $this->roles()->exists()) {
                return true;
            }
        }

        // لوحة التحكم: من يملك دخول الأدمن يُعتبر لديه view.dashboard تلقائياً (باقي الصلاحيات تُحدَّد لاحقاً)
        if ($permissionName === 'view.dashboard' && $this->userHasAdminAccessCapability()) {
            return true;
        }

        // أسماء حديثة (manage.*) + أسماء قديمة من permission_aliases — مطلوبة لسايدبار الأدمن وRBAC مع أدوار قديمة
        $names = self::permissionNamesToCheck($permissionName);

        // التحقق من الصلاحيات المباشرة
        if ($this->directPermissions()->whereIn('name', $names)->exists()) {
            return true;
        }

        // التحقق من الصلاحيات من الأدوار
        return $this->roles()->whereHas('permissions', function ($query) use ($names) {
            $query->whereIn('name', $names);
        })->exists();
    }

    /**
     * التحقق من وجود أي صلاحية من القائمة (للـ Blade والـ sidebar)
     */
    public function hasAnyPermission(...$permissionNames): bool
    {
        foreach ($permissionNames as $name) {
            if ($this->hasPermission($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من وجود دور معين
     */
    public function hasRole($roleName)
    {
        // التحقق من الدور الأساسي
        if (strtolower($this->role) === strtolower($roleName)) {
            return true;
        }

        // التحقق من الأدوار المخصصة
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * إضافة دور للمستخدم
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && ! $this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * إزالة دور من المستخدم
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * العلاقة المباشرة مع الصلاحيات (بدون أدوار)
     */
    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
    }

    /**
     * الحصول على جميع الصلاحيات (من الأدوار + المباشرة)
     */
    public function getAllPermissions()
    {
        $rolePermissions = $this->roles()->with('permissions')->get()
            ->pluck('permissions')->flatten()->unique('id');

        $directPermissions = $this->directPermissions;

        return $rolePermissions->merge($directPermissions)->unique('id');
    }

    /**
     * علاقة مع وظيفة الموظف
     */
    public function employeeJob()
    {
        return $this->belongsTo(EmployeeJob::class, 'employee_job_id');
    }

    /**
     * علاقة مع مهام الموظف
     */
    public function employeeTasks()
    {
        return $this->hasMany(EmployeeTask::class, 'employee_id');
    }

    /**
     * علاقة مع اتفاقيات الموظف
     */
    public function employeeAgreements()
    {
        return $this->hasMany(EmployeeAgreement::class, 'employee_id');
    }

    /**
     * علاقة مع خصومات الراتب
     */
    public function salaryDeductions()
    {
        return $this->hasMany(EmployeeSalaryDeduction::class, 'employee_id');
    }

    /**
     * علاقة مع مدفوعات الراتب
     */
    public function salaryPayments()
    {
        return $this->hasMany(EmployeeSalaryPayment::class, 'employee_id');
    }

    /**
     * علاقة مع المهام المكلف بها
     */
    public function assignedTasks()
    {
        return $this->hasMany(EmployeeTask::class, 'assigned_by');
    }

    /**
     * علاقة مع طلبات الإجازة
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    /**
     * سجل أحداث الموارد البشرية المرتبطة بهذا الموظف
     */
    public function hrEmployeeEvents()
    {
        return $this->hasMany(HrEmployeeEvent::class, 'employee_id')->latest('event_date')->latest('id');
    }

    /**
     * طلبات الكورسات المسندة للموظف كمندوب مبيعات
     */
    public function salesOwnedOrders()
    {
        return $this->hasMany(Order::class, 'sales_owner_id');
    }

    /**
     * التحقق من كون المستخدم موظف
     */
    public function isEmployee(): bool
    {
        return $this->is_employee === true;
    }

    /**
     * Scope للموظفين
     */
    public function scopeEmployees($query)
    {
        return $query->where('is_employee', true);
    }

    /**
     * رمز وظيفة الموظف (accountant, hr, sales, supervisor, academic_supervisor, general_supervision, …)
     */
    public function employeeJobCode(): ?string
    {
        if (! $this->is_employee || ! $this->relationLoaded('employeeJob')) {
            $this->load('employeeJob');
        }

        return $this->employeeJob?->code;
    }

    public function usesCustomEmployeePermissions(): bool
    {
        return (bool) $this->employee_permissions_custom;
    }

    /**
     * @return list<string>
     */
    public function effectiveEmployeePermissions(): array
    {
        if ($this->usesCustomEmployeePermissions()) {
            return is_array($this->employee_permissions) ? array_values($this->employee_permissions) : [];
        }

        if (! $this->relationLoaded('employeeJob')) {
            $this->load('employeeJob');
        }

        $jobPermissions = $this->employeeJob?->permissions;

        return is_array($jobPermissions) ? $jobPermissions : [];
    }

    public function isSalesDepartmentEmployee(): bool
    {
        return \App\Support\EmployeePermissionCatalog::isSalesDepartmentJob($this->employeeJobCode());
    }

    /**
     * تحويل مفتاح عنصر قائمة الموظف (مثل desk_accountant) إلى اسم صلاحية RBAC في جدول permissions
     * (مثل manage.invoices)، بنفس منطق config/employee_sidebar.php.
     */
    public static function rbacPermissionForEmployeeMenuKey(string $key): string
    {
        $items = config('employee_sidebar.items', []);
        $meta = $items[$key] ?? null;
        if (is_array($meta) && array_key_exists('permission', $meta) && $meta['permission'] !== null) {
            return $meta['permission'];
        }

        return $key;
    }

    /**
     * هل الموظف يملك صلاحية عرض خانة في السايدبار.
     *
     * الأولوية:
     * 1. عناصر أساسية (dashboard, profile, notifications, settings) → دائماً مسموح.
     * 2. إذا كان للمستخدم أدوار RBAC مخصصة → نعتمد على hasPermission() فقط.
     * 3. صلاحيات مخصصة للموظف (قسم المبيعات) إن فُعّلت.
     * 4. إذا كان للمستخدم أدوار RBAC مخصصة → نعتمد على hasPermission() فقط.
     * 5. صلاحيات الوظيفة من EmployeeJob.
     */
    public function employeeCan(string $permission): bool
    {
        if (! $this->is_employee) {
            return false;
        }

        // عناصر أساسية متاحة لكل موظف بغض النظر عن صلاحياته
        $alwaysAllowed = ['dashboard', 'profile', 'notifications', 'settings'];
        if (in_array($permission, $alwaysAllowed, true)) {
            return true;
        }

        if ($this->usesCustomEmployeePermissions()) {
            $perms = $this->effectiveEmployeePermissions();
            if (in_array($permission, $perms, true)) {
                return true;
            }
            // سايدبار المبيعات يستخدم manage.orders بينما الوظيفة/التخصيص تستخدم sales_desk
            if ($permission === 'manage.orders' && (in_array('sales_desk', $perms, true) || in_array('sales_orders', $perms, true))) {
                return true;
            }
            if ($permission === 'sales_orders' && in_array('sales_desk', $perms, true)) {
                return true;
            }
            if ($permission === 'sales_desk' && in_array('sales_orders', $perms, true)) {
                return true;
            }

            return false;
        }

        // إذا كان للمستخدم أدوار RBAC مخصصة → اعتمد عليها فقط
        if ($this->roles()->exists()) {
            if ($permission === 'tasks') {
                return $this->hasPermission('manage.tasks') || $this->hasPermission('view.tasks');
            }
            if ($permission === 'reports') {
                return $this->hasPermission('view.statistics')
                    || $this->hasPermission('view.reports')
                    || $this->hasPermission('view.financial-reports')
                    || $this->hasPermission('view.academic-reports');
            }
            if ($permission === 'sales_desk' || $permission === 'sales_orders') {
                return $this->hasPermission('manage.orders') || $this->hasPermission('sales_desk');
            }

            $rbacName = self::rbacPermissionForEmployeeMenuKey($permission);

            return $this->hasPermission($rbacName);
        }

        // لا يوجد دور RBAC → اعتمد على صلاحيات وظيفة الموظف (النظام القديم)
        if (! $this->relationLoaded('employeeJob')) {
            $this->load('employeeJob');
        }
        $job = $this->employeeJob;
        if (! $job) {
            // بدون وظيفة ولا RBAC: اعرض كل شيء (مدير يدوي)
            return true;
        }
        $jobPermissions = $job->permissions;
        if (! is_array($jobPermissions) || empty($jobPermissions)) {
            // وظيفة بدون صلاحيات محددة → لا تُعرَض الأقسام الإضافية
            return false;
        }

        if (in_array($permission, $jobPermissions, true)) {
            return true;
        }

        // توافق سايدبار المبيعات: manage.orders ≡ sales_desk في وظيفة السيلز
        if ($permission === 'manage.orders'
            && (in_array('sales_desk', $jobPermissions, true) || in_array('sales_orders', $jobPermissions, true))) {
            return true;
        }

        // sales_orders و sales_desk متلازمان وظيفياً في مساحة المبيعات
        if ($permission === 'sales_orders' && in_array('sales_desk', $jobPermissions, true)) {
            return true;
        }
        if ($permission === 'sales_desk' && in_array('sales_orders', $jobPermissions, true)) {
            return true;
        }

        return false;
    }

    /**
     * التحقق من وجود صلاحية معينة (من الأدوار أو المباشرة)
     */
    public function hasPermissionDirect($permissionName)
    {
        // إذا كان admin (Super Admin) لكن تم ربطه بأدوار RBAC مخصّصة،
        // عندها لا نتجاوز الصلاحيات تلقائياً.
        if ($this->isAdmin()) {
            if (! $this->roles()->exists()) {
                return true;
            }
        }

        if ($permissionName === 'view.dashboard' && $this->userHasAdminAccessCapability()) {
            return true;
        }

        $names = self::permissionNamesToCheck($permissionName);

        // التحقق من الصلاحيات المباشرة
        if ($this->directPermissions()->whereIn('name', $names)->exists()) {
            return true;
        }

        // التحقق من الصلاحيات من الأدوار
        return $this->roles()->whereHas('permissions', function ($query) use ($names) {
            $query->whereIn('name', $names);
        })->exists();
    }

    /**
     * علاقة مع الإحالات (كمحيل)
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * علاقة مع الإحالة (كمحال)
     */
    public function referral()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    /**
     * علاقة مع المستخدم الذي أحاله
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * علاقة مع المستخدمين المحالين
     */
    public function referredUsers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * علاقة مع تسجيلات المسارات التعليمية
     */
    public function learningPathEnrollments()
    {
        return $this->hasMany(LearningPathEnrollment::class, 'user_id');
    }

    /**
     * علاقة مع المسارات التعليمية التي يدرب فيها
     */
    public function teachingLearningPaths()
    {
        return $this->belongsToMany(AcademicYear::class, 'academic_year_instructors', 'instructor_id', 'academic_year_id')
            ->withPivot('assigned_courses', 'notes')
            ->withTimestamps();
    }

    /**
     * معرفات الكورسات العادية المعيَّنة للمدرب (مباشرة أو عبر المسار).
     */
    public function teachingAdvancedCourseIds(): \Illuminate\Support\Collection
    {
        $direct = AdvancedCourse::where('instructor_id', $this->id)->pluck('id');

        $fromPaths = $this->teachingLearningPaths()->get()->flatMap(function ($ay) {
            $ids = json_decode($ay->pivot->assigned_courses ?? '[]', true);

            return is_array($ids) ? $ids : [];
        });

        return $direct->merge($fromPaths)->unique()->filter()->values();
    }

    /**
     * هل لدى المدرب كورس عادي مُسند فعلياً (يظهر قسم الكورسات في السايدبار).
     */
    public function hasTeachingCourses(): bool
    {
        return $this->teachingAdvancedCourseIds()->isNotEmpty();
    }

    /**
     * هل لدى الطالب تسجيل نشط في كورس مسجّل (يفتح أدوات الكورس في السايدبار).
     */
    public function hasActiveRecordedCourses(): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('student_course_enrollments')) {
            return false;
        }

        return $this->courseEnrollments()
            ->where('status', 'active')
            ->exists();
    }

    /**
     * معلم معتمد شغّال مع الأكاديمية (وليس مجرد تسجيل طلب توظيف).
     * يظهر له أدوات مثل مكتبة المناهج.
     */
    public function isAcademyWorkingInstructor(): bool
    {
        if (! $this->isInstructor() && ! $this->isTeacher()) {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        // كورس مسند أو فصل/حجز فعلي = يعمل معنا
        if ($this->hasTeachingCourses()) {
            return true;
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('tutoring_groups')) {
            $hasGroup = \App\Models\TutoringGroup::query()
                ->where('instructor_id', $this->id)
                ->exists();
            if ($hasGroup) {
                return true;
            }
        }

        // اكتمال التوظيف: تفعيل الطلب أو اعتماد الملف العام
        if (\Illuminate\Support\Facades\Schema::hasTable('tutor_applications')) {
            $activated = \App\Models\TutorApplication::query()
                ->where('user_id', $this->id)
                ->where('status', \App\Models\TutorApplication::STATUS_ACTIVATED)
                ->exists();
            if ($activated) {
                return true;
            }
        }

        if ($this->instructorProfile && $this->instructorProfile->status === \App\Models\InstructorProfile::STATUS_APPROVED) {
            return true;
        }

        return false;
    }

    /** أقسام مكتبة المناهج «الخاصة» المسموح لهذا المستخدم */
    public function curriculumLibraryRestrictedCategories()
    {
        return $this->belongsToMany(
            CurriculumLibraryCategory::class,
            'curriculum_library_category_user',
            'user_id',
            'category_id'
        )->withTimestamps();
    }

    /**
     * وصول كامل لمكتبة المناهج التفاعلية: باقة مكتبات للطالب، أو معلم معتمد شغّال.
     */
    public function hasCurriculumLibraryAccess(): bool
    {
        if ($this->isAcademyWorkingInstructor()) {
            return true;
        }

        return \App\Services\LibraryFolderAccessService::hasAnyLibraryEntitlement($this);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
