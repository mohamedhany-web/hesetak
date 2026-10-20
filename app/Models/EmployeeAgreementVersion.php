<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAgreementVersion extends Model
{
    protected $fillable = [
        'employee_agreement_id',
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
        'archived_by',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(EmployeeAgreement::class, 'employee_agreement_id');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
