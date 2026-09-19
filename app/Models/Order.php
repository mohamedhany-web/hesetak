<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'advanced_course_id',
        'tutoring_group_id',
        'tutoring_group_package_id',
        'tutoring_group_cohort_id',
        'service_package_id',
        'custom_package_data',
        'order_type',
        'academic_year_id',
        'coupon_id',
        'original_amount',
        'discount_amount',
        'wallet_credit_amount',
        'amount',
        'currency',
        'billing_mode',
        'auto_renew',
        'payment_method',
        'wallet_id',
        'payment_proof',
        'invoice_id',
        'payment_id',
        'fawaterak_invoice_id',
        'paypal_order_id',
        'status',
        'notes',
        'approved_at',
        'approved_by',
        'sales_owner_id',
        'sales_contacted_at',
        'sales_lead_id',
    ];

    public const TYPE_COURSE = 'course';

    public const TYPE_TUTORING_PACKAGE = 'tutoring_package';

    public const TYPE_TUTORING_COHORT = 'tutoring_cohort';

    public const TYPE_SERVICE_PACKAGE = 'service_package';

    public const TYPE_CUSTOM_SERVICE_PACKAGE = 'custom_service_package';

    protected $casts = [
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'wallet_credit_amount' => 'decimal:2',
        'auto_renew' => 'boolean',
        'custom_package_data' => 'array',
        'approved_at' => 'datetime',
        'sales_contacted_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(AdvancedCourse::class, 'advanced_course_id');
    }

    public function tutoringGroup()
    {
        return $this->belongsTo(TutoringGroup::class, 'tutoring_group_id');
    }

    public function tutoringPackage()
    {
        return $this->belongsTo(TutoringGroupPackage::class, 'tutoring_group_package_id');
    }

    public function tutoringCohort()
    {
        return $this->belongsTo(TutoringGroupCohort::class, 'tutoring_group_cohort_id');
    }

    public function servicePackage()
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function serviceEntitlements()
    {
        return $this->hasMany(StudentServiceEntitlement::class);
    }

    public function tutoringGroupBookings()
    {
        return $this->hasMany(TutoringGroupBooking::class);
    }

    public function isTutoringOrder(): bool
    {
        return in_array($this->order_type, [
            self::TYPE_TUTORING_PACKAGE,
            self::TYPE_TUTORING_COHORT,
            self::TYPE_SERVICE_PACKAGE,
            self::TYPE_CUSTOM_SERVICE_PACKAGE,
        ], true)
            || $this->tutoring_group_id !== null
            || $this->service_package_id !== null;
    }

    public function currencyCode(): string
    {
        if (filled($this->currency)) {
            return strtoupper((string) $this->currency);
        }

        if ($this->order_type === self::TYPE_CUSTOM_SERVICE_PACKAGE) {
            $fromQuote = strtoupper((string) ($this->custom_package_data['currency'] ?? ''));

            return in_array($fromQuote, ['SAR', 'EGP', 'USD'], true)
                ? $fromQuote
                : platform_currency();
        }

        if ($this->servicePackage) {
            return $this->servicePackage->currencyCode();
        }

        return platform_currency();
    }

    public function learningPath()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function salesOwner()
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\SalesOrderNote, self>
     */
    public function salesNotes()
    {
        return $this->hasMany(SalesOrderNote::class, 'order_id')->latest();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'في الانتظار',
            self::STATUS_APPROVED => 'مقبول',
            self::STATUS_REJECTED => 'مرفوض',
        ];

        return $statuses[$this->status] ?? 'غير محدد';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
