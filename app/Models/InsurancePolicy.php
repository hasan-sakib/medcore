<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePolicy extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'patient_id', 'provider_name', 'policy_number', 'group_number',
        'coverage_type', 'coverage_limit', 'copay_amount', 'deductible_amount',
        'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'coverage_limit'    => 'decimal:2',
        'copay_amount'      => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'valid_from'        => 'date',
        'valid_until'       => 'date',
        'is_active'         => 'boolean',
    ];

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function claims(): HasMany { return $this->hasMany(Claim::class); }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) return false;
        $today = now()->toDateString();
        if ($this->valid_from->gt($today)) return false;
        return $this->valid_until === null || $this->valid_until->gte($today);
    }
}
