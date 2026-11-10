<?php

namespace App\Policies;

use App\Models\SalesTarget;
use App\Models\User;

class SalesTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales_targets.viewAny');
    }

    public function view(User $user, SalesTarget $salesTarget): bool
    {
        return $user->can('sales_targets.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales_targets.create');
    }

    public function update(User $user, SalesTarget $salesTarget): bool
    {
        return $user->can('sales_targets.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('sales_targets.delete');
    }

    public function delete(User $user, SalesTarget $salesTarget): bool
    {
        return $user->can('sales_targets.delete');
    }
}
