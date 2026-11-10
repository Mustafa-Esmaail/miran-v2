<?php

namespace App\Policies;

use App\Models\ServiceRequestActivity;
use App\Models\User;

class ServiceRequestActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_request_activities.viewAny');
    }

    public function view(User $user, ServiceRequestActivity $serviceRequestActivity): bool
    {
        return $user->can('service_request_activities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_request_activities.create');
    }

    public function update(User $user, ServiceRequestActivity $serviceRequestActivity): bool
    {
        return $user->can('service_request_activities.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('service_request_activities.delete');
    }

    public function delete(User $user, ServiceRequestActivity $serviceRequestActivity): bool
    {
        return $user->can('service_request_activities.delete');
    }
}
