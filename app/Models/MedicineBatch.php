<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineBatch extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'medicine_id', 'supplier_id', 'purchase_order_id',
        'batch_number', 'lot_number',
        'quantity_received', 'quantity_on_hand',
        'unit_cost', 'expiry_date', 'manufactured_date',
        'received_at', 'received_by', 'status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufactured_date' => 'date',
            'received_at' => 'datetime',
            'unit_cost' => 'decimal:2',
            'quantity_received' => 'integer',
            'quantity_on_hand' => 'integer',
        ];
    }

    /** Scope for FEFO deduction: active batches ordered by oldest expiry first. */
    public function scopeActiveFEFO(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('expiry_date')
            ->orderBy('id');
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function dispenseRecords(): HasMany
    {
        return $this->hasMany(DispenseRecord::class, 'batch_id');
    }
}
