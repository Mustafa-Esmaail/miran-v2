<?php

namespace App\Policies;

use App\Models\ServiceRequestStatus;
use App\Models\User;

class ServiceRequestStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_request_statuses.viewAny');
    }

    public function view(User $user, ServiceRequestStatus $serviceRequestStatus): bool
    {
        return $user->can('service_request_statuses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_request_statuses.create');
    }

    public function update(User $user, ServiceRequestStatus $serviceRequestStatus): bool
    {
        return $user->can('service_request_statuses.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('service_request_statuses.delete');
    }

    public function delete(User $user, ServiceRequestStatus $serviceRequestStatus): bool
    {
        return $user->can('service_request_statuses.delete');
    }
}
