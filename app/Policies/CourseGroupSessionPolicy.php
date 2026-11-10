<?php

namespace App\Policies;

use App\Models\CourseGroupSession;
use App\Models\User;

class CourseGroupSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sessions.viewAny');
    }

    public function view(User $user, CourseGroupSession $session): bool
    {
        return $user->can('sessions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sessions.create');
    }

    public function update(User $user, CourseGroupSession $session): bool
    {
        return $user->can('sessions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sessions.delete');
    }

    public function delete(User $user, CourseGroupSession $session): bool
    {
        return $user->can('sessions.delete');
    }
}
