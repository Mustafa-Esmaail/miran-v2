<?php

namespace App\Policies;

use App\Models\SectionField;
use App\Models\User;

class SectionFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('section_fields.viewAny');
    }

    public function view(User $user, SectionField $sectionField): bool
    {
        return $user->can('section_fields.view');
    }

    public function create(User $user): bool
    {
        return $user->can('section_fields.create');
    }

    public function update(User $user, SectionField $sectionField): bool
    {
        return $user->can('section_fields.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('section_fields.delete');
    }

    public function delete(User $user, SectionField $sectionField): bool
    {
        return $user->can('section_fields.delete');
    }
}
