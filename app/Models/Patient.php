<?php

namespace App\Models;

use App\Casts\EncryptedPhi;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use App\Traits\HasBlindIndexes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use Auditable, BelongsToTenant, HasBlindIndexes, HasFactory, SoftDeletes;

    protected array $blindIndexed = ['first_name', 'last_name', 'national_id', 'phone'];

    protected $fillable = [
        'mrn', 'first_name', 'last_name', 'date_of_birth', 'gender',
        'national_id', 'phone', 'email', 'address', 'blood_group',
        'emergency_contact', 'status', 'registered_at',
        'registered_by', 'department_id',
    ];

    protected function casts(): array
    {
        return [
            'first_name' => EncryptedPhi::class,
            'last_name' => EncryptedPhi::class,
            'date_of_birth' => EncryptedPhi::class,
            'gender' => EncryptedPhi::class,
            'national_id' => EncryptedPhi::class,
            'phone' => EncryptedPhi::class,
            'email' => EncryptedPhi::class,
            'address' => EncryptedPhi::class,
            'blood_group' => EncryptedPhi::class,
            'emergency_contact' => EncryptedPhi::class,
            'registered_at' => 'datetime',
        ];
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class);
    }
}
