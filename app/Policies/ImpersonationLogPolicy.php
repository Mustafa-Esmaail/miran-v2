<?php

namespace App\Policies;

use App\Models\ImpersonationLog;
use App\Models\User;

class ImpersonationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('impersonation_logs.viewAny');
    }

    public function view(User $user, ImpersonationLog $impersonationLog): bool
    {
        return $user->can('impersonation_logs.view');
    }

    public function create(User $user): bool
    {
        return $user->can('impersonation_logs.create');
    }

    public function update(User $user, ImpersonationLog $impersonationLog): bool
    {
        return $user->can('impersonation_logs.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('impersonation_logs.delete');
    }

    public function delete(User $user, ImpersonationLog $impersonationLog): bool
    {
        return $user->can('impersonation_logs.delete');
    }
}
