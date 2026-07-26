<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = ['name', 'code', 'floor', 'ward_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function getAvailableBedsCountAttribute(): int
    {
        return $this->beds()->where('status', 'available')->where('is_active', true)->count();
    }

    public function getTotalBedsCountAttribute(): int
    {
        return $this->beds()->where('is_active', true)->count();
    }
}
