<?php

namespace App\Policies;

use App\Models\DispenseRecord;
use App\Models\User;

class DispenseRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('dispense-records.view');
    }

    public function view(User $user, DispenseRecord $dispenseRecord): bool
    {
        return $user->hasPermissionTo('dispense-records.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('dispense-records.create');
    }
}
