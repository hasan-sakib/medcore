<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'encounter_id', 'patient_id', 'recorded_by', 'recorded_at',
        'temperature_c', 'pulse_bpm', 'bp_systolic', 'bp_diastolic',
        'spo2_pct', 'respiratory_rate', 'weight_kg', 'height_cm',
        'bmi', 'glucose_mmol', 'pain_scale', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'temperature_c' => 'decimal:1',
            'spo2_pct' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:1',
            'bmi' => 'decimal:1',
            'glucose_mmol' => 'decimal:2',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
