<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Support\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Append-only audit trail for any Eloquent model.
 *
 * Records created/updated/deleted events. PHI field values are redacted to
 * their column names only — the audit log stores structure, not content.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function (self $model) use ($event) {
                static::writeAuditLog($model, $event);
            });
        }
    }

    protected static function writeAuditLog(self $model, string $event): void
    {
        $manager = app(TenantManager::class);

        $changes = match ($event) {
            'created' => ['after' => static::redactPhi(array_keys($model->getAttributes()))],
            'updated' => [
                'before' => static::redactPhi(array_keys($model->getOriginal())),
                'after'  => static::redactPhi(array_keys($model->getDirty())),
            ],
            'deleted' => ['before' => static::redactPhi(array_keys($model->getAttributes()))],
            default   => [],
        };

        AuditLog::withoutGlobalScopes()->create([
            'tenant_id'       => $manager->hasCurrent() ? $manager->current()->id : null,
            'user_id'         => Auth::id(),
            'action'          => $event,
            'auditable_type'  => get_class($model),
            'auditable_id'    => $model->getKey(),
            'changes'         => $changes,
            'ip_address'      => Request::ip(),
        ]);
    }

    /**
     * Redact PHI field values — keep column names only.
     * Subclasses can override $phiColumns to declare sensitive columns.
     */
    protected static function redactPhi(array $columns): array
    {
        return array_fill_keys($columns, '[redacted]');
    }

    /**
     * Log a PHI read access explicitly.
     * Call from controllers/policies: $patient->logPhiRead('patient_profile');
     */
    public function logPhiRead(string $context = 'read'): void
    {
        static::writeAuditLog($this, "phi_read:{$context}");
    }
}
