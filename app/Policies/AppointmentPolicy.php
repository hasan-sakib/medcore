<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        // Doctors can only view appointments assigned to them
        if ($user->hasRole('doctor')) {
            return $appointment->doctor_id === $user->id;
        }

        return $user->hasPermissionTo('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        // Doctors can update status on their own appointments
        if ($user->hasRole('doctor') && $appointment->doctor_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('appointments.edit');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasPermissionTo('appointments.edit');
    }
}
