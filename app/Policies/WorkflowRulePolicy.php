<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkflowRule;

class WorkflowRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('workflow_rules.viewAny');
    }

    public function view(User $user, WorkflowRule $workflowRule): bool
    {
        return $user->can('workflow_rules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('workflow_rules.create');
    }

    public function update(User $user, WorkflowRule $workflowRule): bool
    {
        return $user->can('workflow_rules.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('workflow_rules.delete');
    }

    public function delete(User $user, WorkflowRule $workflowRule): bool
    {
        return $user->can('workflow_rules.delete');
    }
}
