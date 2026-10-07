<?php

namespace App\Policies;

use App\Models\Claim;
use App\Models\User;

class ClaimPolicy
{
    public function viewAny(User $user): bool { return $user->can('claims.view'); }
    public function create(User $user): bool { return $user->can('claims.create'); }
    public function update(User $user, Claim $claim): bool
    {
        return $user->can('claims.create') && $user->tenant_id === $claim->tenant_id;
    }
}
