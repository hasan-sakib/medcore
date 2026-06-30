<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('encounters.view');
    }

    public function view(User $user, Encounter $encounter): bool
    {
        // Doctors can only view encounters they are attending
        if ($user->hasRole('doctor')) {
            return $encounter->attending_doctor_id === $user->id;
        }

        return $user->hasPermissionTo('encounters.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('encounters.create');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        if ($user->hasRole('doctor')) {
            return $encounter->attending_doctor_id === $user->id
                && $user->hasPermissionTo('encounters.edit');
        }

        return $user->hasPermissionTo('encounters.edit');
    }
}
