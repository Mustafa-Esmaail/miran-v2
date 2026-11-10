<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_requests.viewAny');
    }

    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('service_requests.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_requests.create');
    }

    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('service_requests.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('service_requests.delete');
    }

    public function delete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->can('service_requests.delete');
    }

    public function forceDelete(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('service_requests.delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('service_requests.delete');
    }

    public function restore(User $user, ServiceRequest $serviceRequest): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }
}
