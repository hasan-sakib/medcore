<?php

namespace App\Support;

use App\Models\Tenant;
use RuntimeException;

/**
 * Single source of truth for the current request's tenant context.
 *
 * Bound as a singleton in AppServiceProvider. The IdentifyTenant middleware
 * calls setCurrent(); the TenantScope reads hasCurrent()/current().
 *
 * In queue jobs, re-establish context via:
 *   app(TenantManager::class)->setCurrent(Tenant::withoutTenant()->find($this->tenantId));
 */
class TenantManager
{
    private ?Tenant $current = null;
    private bool $bypassed  = false;

    public function setCurrent(Tenant $tenant): void
    {
        $this->current  = $tenant;
        $this->bypassed = false;

        // Configure spatie/laravel-permission teams mode
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    }

    public function current(): Tenant
    {
        if ($this->current === null) {
            throw new RuntimeException('No tenant has been set for this context.');
        }

        return $this->current;
    }

    public function hasCurrent(): bool
    {
        return $this->current !== null;
    }

    public function bypass(): void
    {
        $this->current  = null;
        $this->bypassed = true;
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function clearCurrent(): void
    {
        $this->current  = null;
        $this->bypassed = false;
    }
}
