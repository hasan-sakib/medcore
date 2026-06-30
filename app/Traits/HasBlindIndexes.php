<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasBlindIndexes
{
    /**
     * Set a PHI field, encrypting it and computing its blind index simultaneously.
     *
     * Models declare which fields are blind-indexed:
     *   protected array $blindIndexed = ['first_name', 'national_id', 'phone'];
     *
     * Services must call this instead of direct attribute assignment for PHI fields
     * that need blind-index search support, to avoid cast-ordering ambiguity.
     */
    public function setPhiField(string $field, ?string $plaintext): void
    {
        // Setting via magic attribute triggers EncryptedPhi cast
        $this->$field = $plaintext;

        if (in_array($field, $this->blindIndexed ?? [], true)) {
            $this->{$field.'_index'} = $plaintext !== null
                ? static::computeBlindIndex($plaintext)
                : null;
        }
    }

    /**
     * Search by a blind-indexed field. Returns a tenant-scoped query
     * (TenantScope already applied via BelongsToTenant on the model).
     */
    public static function searchByBlindIndex(string $field, string $plaintext): Builder
    {
        $hash = static::computeBlindIndex($plaintext);

        return static::where($field.'_index', $hash);
    }

    private static function computeBlindIndex(string $plaintext): string
    {
        $key = config('phi.blind_index_key');

        if (! $key) {
            throw new \RuntimeException('PHI_BLIND_INDEX_KEY is not set.');
        }

        return hash_hmac('sha256', $plaintext, base64_decode($key));
    }
}
