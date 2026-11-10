<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certificates.viewAny');
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.view');
    }

    public function create(User $user): bool
    {
        return $user->can('certificates.create');
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('certificates.delete');
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.delete');
    }
}
