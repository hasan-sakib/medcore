<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = ['ward_id', 'room_id', 'bed_number', 'bed_type', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BedAllocation::class);
    }

    public function currentAllocation(): HasOne
    {
        return $this->hasOne(BedAllocation::class)->whereNull('discharged_at')->latest('admitted_at');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
