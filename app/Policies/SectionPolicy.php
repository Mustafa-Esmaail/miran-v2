<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sections.viewAny');
    }

    public function view(User $user, Section $section): bool
    {
        return $user->can('sections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sections.create');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->can('sections.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sections.delete');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->can('sections.delete');
    }
}
