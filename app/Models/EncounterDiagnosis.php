<?php

namespace App\Models;

use App\Casts\EncryptedPhi;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncounterDiagnosis extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'encounter_id', 'diagnosis_id', 'type',
        'onset_date', 'resolved_at', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'notes' => EncryptedPhi::class,
            'onset_date' => 'date',
            'resolved_at' => 'date',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
