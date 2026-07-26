<?php

namespace App\Policies;

use App\Models\BedAllocation;
use App\Models\User;

class BedAllocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('beds.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bed-allocations.create');
    }

    public function update(User $user, BedAllocation $allocation): bool
    {
        return $user->can('bed-allocations.edit')
            && $user->tenant_id === $allocation->tenant_id;
    }
}
