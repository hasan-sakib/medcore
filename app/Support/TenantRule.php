<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Validation rules that respect tenancy.
 *
 * Laravel's `exists:` rule queries the table directly and so bypasses the Eloquent tenant
 * scope: a user of hospital A could reference hospital B's records just by guessing IDs.
 */
class TenantRule
{
    /** `exists` constrained to the current tenant (unconstrained when no tenant context). */
    public static function exists(string $table, string $column = 'id'): Exists
    {
        $rule = Rule::exists($table, $column);
        $tenants = app(TenantManager::class);

        if ($tenants->hasCurrent()) {
            $rule->where('tenant_id', $tenants->current()->id);
        }

        return $rule;
    }
}
