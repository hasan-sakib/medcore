<?php

namespace App\Policies;

use App\Models\ClinicalNote;
use App\Models\User;

class ClinicalNotePolicy
{
    /** Receptionists cannot access clinical notes. */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['doctor', 'nurse', 'tenant-admin']);
    }

    public function view(User $user, ClinicalNote $note): bool
    {
        return $user->hasRole(['doctor', 'nurse', 'tenant-admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('clinical-notes.create');
    }

    /** Authors can edit their own unsigned notes only. Signed notes are immutable. */
    public function update(User $user, ClinicalNote $note): bool
    {
        if ($note->is_signed) {
            return false;
        }

        return $note->author_id === $user->id
            && $user->hasPermissionTo('clinical-notes.edit');
    }

    /** Soft-delete (correction): author only, unsigned only. */
    public function delete(User $user, ClinicalNote $note): bool
    {
        if ($note->is_signed) {
            return false;
        }

        return $note->author_id === $user->id
            && $user->hasRole(['doctor', 'nurse']);
    }
}
