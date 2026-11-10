<?php

namespace App\Policies;

use App\Models\ServiceRequestType;
use App\Models\User;

class ServiceRequestTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_request_types.viewAny');
    }

    public function view(User $user, ServiceRequestType $serviceRequestType): bool
    {
        return $user->can('service_request_types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_request_types.create');
    }

    public function update(User $user, ServiceRequestType $serviceRequestType): bool
    {
        return $user->can('service_request_types.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('service_request_types.delete');
    }

    public function delete(User $user, ServiceRequestType $serviceRequestType): bool
    {
        return $user->can('service_request_types.delete');
    }
}
