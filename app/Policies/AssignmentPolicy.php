<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assignments.viewAny');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('assignments.create');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('assignments.delete');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('assignments.delete');
    }
}
