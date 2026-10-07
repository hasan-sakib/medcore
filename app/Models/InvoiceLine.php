<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'invoice_id', 'charge_item_id', 'description', 'quantity',
        'unit_price', 'tax_rate', 'tax_amount', 'discount_amount',
        'line_total', 'reference_type', 'reference_id',
    ];

    protected $casts = [
        'quantity'        => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total'      => 'decimal:2',
    ];

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function chargeItem(): BelongsTo { return $this->belongsTo(ChargeItem::class); }
}
