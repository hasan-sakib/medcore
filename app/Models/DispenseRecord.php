<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispenseRecord extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'prescription_id', 'prescription_item_id', 'patient_id',
        'medicine_id', 'batch_id', 'quantity_dispensed',
        'dispensed_by', 'dispensed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'dispensed_at' => 'datetime',
            'quantity_dispensed' => 'integer',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem(): BelongsTo
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineBatch::class, 'batch_id');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }
}
