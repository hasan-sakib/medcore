<?php

namespace App\Policies;

use App\Models\Medicine;
use App\Models\User;

class MedicinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('medicines.view');
    }

    public function view(User $user, Medicine $medicine): bool
    {
        return $user->hasPermissionTo('medicines.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('medicines.create');
    }

    public function update(User $user, Medicine $medicine): bool
    {
        return $user->hasPermissionTo('medicines.edit');
    }

    public function delete(User $user, Medicine $medicine): bool
    {
        return $user->hasRole('tenant-admin');
    }
}
