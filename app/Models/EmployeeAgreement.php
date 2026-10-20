<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAgreement extends Model
{
    protected $fillable = [
        'employee_id',
        'agreement_number',
        'version',
        'title',
        'description',
        'salary',
        'start_date',
        'end_date',
        'status',
        'contract_terms',
        'agreement_terms',
        'notes',
        'pdf_path',
        'signed_at',
        'signer_name',
        'signature_path',
        'signed_ip',
        'signed_user_agent',
        'acceptance_otp_hash',
        'acceptance_otp_expires_at',
        'acceptance_sent_at',
        'created_by',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'acceptance_otp_expires_at' => 'datetime',
        'acceptance_sent_at' => 'datetime',
        'version' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeSalaryDeduction::class, 'agreement_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EmployeeSalaryPayment::class, 'agreement_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmployeeAgreementVersion::class, 'employee_agreement_id')->orderByDesc('version');
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public static function generateAgreementNumber(): string
    {
        return 'EMP-AGR-'.date('Y').'-'.str_pad((string) (self::count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
