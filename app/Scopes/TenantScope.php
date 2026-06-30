<?php

namespace App\Scopes;

use App\Support\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $manager = app(TenantManager::class);

        if ($manager->hasCurrent()) {
            $builder->where($model->getQualifiedTenantIdColumn(), $manager->current()->id);
            return;
        }

        // No tenant context during CLI/queue without explicit bypass — fail loud in non-production
        // to catch unscoped global queries early.
        if (! app()->runningInConsole() && ! $manager->isBypassed()) {
            abort(500, 'No tenant context established. Refusing unscoped query on '.get_class($model).'.');
        }
    }
}
