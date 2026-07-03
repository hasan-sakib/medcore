<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('prescriptions.view');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('prescriptions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('prescriptions.create');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        return $user->hasPermissionTo('prescriptions.edit')
            && $prescription->status === 'pending';
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->hasRole('tenant-admin');
    }
}
