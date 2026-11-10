<?php

namespace App\Policies;

use App\Models\Qualification;
use App\Models\User;

class QualificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('qualifications.viewAny');
    }

    public function view(User $user, Qualification $qualification): bool
    {
        return $user->can('qualifications.view');
    }

    public function create(User $user): bool
    {
        return $user->can('qualifications.create');
    }

    public function update(User $user, Qualification $qualification): bool
    {
        return $user->can('qualifications.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('qualifications.delete');
    }

    public function delete(User $user, Qualification $qualification): bool
    {
        return $user->can('qualifications.delete');
    }
}
