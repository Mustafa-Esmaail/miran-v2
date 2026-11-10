<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('companies.viewAny');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('companies.view');
    }

    public function create(User $user): bool
    {
        return $user->can('companies.create');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('companies.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('companies.delete');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('companies.delete');
    }
}
