<?php

namespace App\Models;

use App\Casts\EncryptedPhi;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClinicalNote extends Model
{
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'encounter_id', 'author_id', 'note_type',
        'subjective', 'objective', 'assessment', 'plan', 'body',
        'is_signed', 'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'subjective' => EncryptedPhi::class,
            'objective' => EncryptedPhi::class,
            'assessment' => EncryptedPhi::class,
            'plan' => EncryptedPhi::class,
            'body' => EncryptedPhi::class,
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
