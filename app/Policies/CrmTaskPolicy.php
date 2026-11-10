<?php

namespace App\Policies;

use App\Models\CrmTask;
use App\Models\User;

class CrmTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm_tasks.viewAny');
    }

    public function view(User $user, CrmTask $crmTask): bool
    {
        return $user->can('crm_tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm_tasks.create');
    }

    public function update(User $user, CrmTask $crmTask): bool
    {
        return $user->can('crm_tasks.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('crm_tasks.delete');
    }

    public function delete(User $user, CrmTask $crmTask): bool
    {
        return $user->can('crm_tasks.delete');
    }
}
