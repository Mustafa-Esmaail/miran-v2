<?php

namespace App\Policies;

use App\Models\CertificateRequest;
use App\Models\User;

class CertificateRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('certificate_requests.viewAny');
    }

    public function view(User $user, CertificateRequest $certificateRequest): bool
    {
        return $user->can('certificate_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->can('certificate_requests.create');
    }

    public function update(User $user, CertificateRequest $certificateRequest): bool
    {
        return $user->can('certificate_requests.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('certificate_requests.delete');
    }

    public function delete(User $user, CertificateRequest $certificateRequest): bool
    {
        return $user->can('certificate_requests.delete');
    }
}
