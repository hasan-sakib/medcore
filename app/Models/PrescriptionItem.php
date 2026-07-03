<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionItem extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'prescription_id', 'medicine_id',
        'dosage_instruction', 'frequency', 'duration_days',
        'quantity_prescribed', 'quantity_dispensed', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_prescribed' => 'integer',
            'quantity_dispensed' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function dispenseRecords(): HasMany
    {
        return $this->hasMany(DispenseRecord::class, 'prescription_item_id');
    }
}
