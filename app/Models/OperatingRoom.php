<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatingRoom extends Model
{
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = ['name', 'room_number', 'or_type', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(OrSchedule::class, 'operating_room_id');
    }

    public function todaySchedules(): HasMany
    {
        return $this->hasMany(OrSchedule::class, 'operating_room_id')
            ->whereDate('scheduled_start', today())
            ->orderBy('scheduled_start');
    }
}
