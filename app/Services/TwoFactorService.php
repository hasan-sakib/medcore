<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TOTP two-factor helper. The secret is stored encrypted; recovery codes are
 * stored as an encrypted JSON list of SHA-256 hashes (shown to the user once).
 */
class TwoFactorService
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function storeSecret(User $user, string $secret): void
    {
        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function secretFor(User $user): ?string
    {
        if ($user->two_factor_secret === null) {
            return null;
        }

        return Crypt::decryptString($user->two_factor_secret);
    }

    public function otpAuthUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(config('app.name', 'MedCore'), $user->email, $secret);
    }

    /** SVG QR code as a data URI (no imagick required). */
    public function qrDataUri(string $otpAuthUrl): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($otpAuthUrl));
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return (bool) $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Create fresh recovery codes, persist their hashes, return the plain codes.
     *
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $raw = Str::lower(Str::random(10));
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5);
        }

        $this->saveHashes($user, array_map(fn (string $c) => $this->hash($c), $codes));

        return $codes;
    }

    public function recoveryCodesRemaining(User $user): int
    {
        return count($this->hashes($user));
    }

    /** Consume a recovery code; returns true once and only once per code. */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $this->hashes($user);
        $needle = $this->hash($code);

        foreach ($hashes as $i => $hash) {
            if (hash_equals($hash, $needle)) {
                unset($hashes[$i]);
                $this->saveHashes($user, array_values($hashes));

                return true;
            }
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    private function hash(string $code): string
    {
        $normalised = Str::lower(preg_replace('/[\s-]+/', '', $code) ?? '');

        return hash('sha256', $normalised);
    }

    /** @return list<string> */
    private function hashes(User $user): array
    {
        if ($user->two_factor_recovery_codes === null) {
            return [];
        }

        $decoded = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** @param list<string> $hashes */
    private function saveHashes(User $user, array $hashes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($hashes)),
        ])->save();
    }
}
