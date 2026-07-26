<?php

namespace App\Policies;

use App\Models\OperatingRoom;
use App\Models\OrSchedule;
use App\Models\User;

class OperatingRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('operating-rooms.view');
    }

    public function create(User $user): bool
    {
        return $user->can('or-schedules.manage');
    }

    public function update(User $user, OrSchedule $schedule): bool
    {
        return $user->can('or-schedules.manage') && $user->tenant_id === $schedule->tenant_id;
    }
}
