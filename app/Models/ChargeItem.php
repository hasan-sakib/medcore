<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ChargeItem extends Model
{
    use Auditable, BelongsToTenant;

    protected $fillable = ['name', 'code', 'category', 'unit_price', 'tax_rate', 'is_active'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
