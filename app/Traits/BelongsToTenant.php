<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model) {
            $manager = app(TenantManager::class);
            if ($manager->hasCurrent()) {
                $model->tenant_id = $manager->current()->id;
            }
        });
    }

    public function getQualifiedTenantIdColumn(): string
    {
        return $this->getTable().'.tenant_id';
    }

    /**
     * Query builder helper — temporarily bypass the tenant scope.
     * Use only in Super Admin contexts and seeders.
     */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
