<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('patients.view');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('patients.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('patients.create');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermissionTo('patients.edit');
    }

    /** Only tenant-admin can soft-delete patient records. */
    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole('tenant-admin');
    }
}
