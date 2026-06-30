<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;

class EncryptedPhi implements CastsAttributes
{
    private static ?Encrypter $encrypter = null;

    private static function encrypter(): Encrypter
    {
        if (static::$encrypter === null) {
            $key = config('phi.encryption_key');

            if (! $key) {
                throw new \RuntimeException('PHI_ENCRYPTION_KEY is not set.');
            }

            static::$encrypter = new Encrypter(base64_decode($key), 'AES-256-GCM');
        }

        return static::$encrypter;
    }

    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return static::encrypter()->decryptString($value);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return static::encrypter()->encryptString((string) $value);
    }
}
