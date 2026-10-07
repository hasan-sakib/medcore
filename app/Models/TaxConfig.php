<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TaxConfig extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = ['name', 'rate', 'applies_to', 'is_active'];

    protected $casts = ['rate' => 'decimal:2', 'is_active' => 'boolean'];
}
