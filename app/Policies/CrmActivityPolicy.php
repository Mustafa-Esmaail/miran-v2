<?php

namespace App\Policies;

use App\Models\CrmActivity;
use App\Models\User;

class CrmActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('crm_activities.viewAny');
    }

    public function view(User $user, CrmActivity $crmActivity): bool
    {
        return $user->can('crm_activities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('crm_activities.create');
    }

    public function update(User $user, CrmActivity $crmActivity): bool
    {
        return $user->can('crm_activities.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('crm_activities.delete');
    }

    public function delete(User $user, CrmActivity $crmActivity): bool
    {
        return $user->can('crm_activities.delete');
    }
}
