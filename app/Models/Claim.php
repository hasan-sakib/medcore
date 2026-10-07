<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'invoice_id', 'insurance_policy_id', 'patient_id', 'claim_number',
        'status', 'amount_claimed', 'amount_approved', 'amount_paid',
        'submitted_at', 'reviewed_at', 'notes', 'submitted_by',
    ];

    protected $casts = [
        'amount_claimed'  => 'decimal:2',
        'amount_approved' => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'submitted_at'    => 'datetime',
        'reviewed_at'     => 'datetime',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function insurancePolicy(): BelongsTo { return $this->belongsTo(InsurancePolicy::class); }
    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
}
