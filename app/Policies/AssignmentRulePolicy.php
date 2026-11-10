<?php

namespace App\Policies;

use App\Models\AssignmentRule;
use App\Models\User;

class AssignmentRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assignment_rules.viewAny');
    }

    public function view(User $user, AssignmentRule $assignmentRule): bool
    {
        return $user->can('assignment_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('assignment_rules.create');
    }

    public function update(User $user, AssignmentRule $assignmentRule): bool
    {
        return $user->can('assignment_rules.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('assignment_rules.delete');
    }

    public function delete(User $user, AssignmentRule $assignmentRule): bool
    {
        return $user->can('assignment_rules.delete');
    }
}
