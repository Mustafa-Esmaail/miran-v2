<?php

namespace App\Policies;

use App\Models\ServiceRequestStage;
use App\Models\User;

class ServiceRequestStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('service_request_stages.viewAny');
    }

    public function view(User $user, ServiceRequestStage $serviceRequestStage): bool
    {
        return $user->can('service_request_stages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('service_request_stages.create');
    }

    public function update(User $user, ServiceRequestStage $serviceRequestStage): bool
    {
        return $user->can('service_request_stages.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('service_request_stages.delete');
    }

    public function delete(User $user, ServiceRequestStage $serviceRequestStage): bool
    {
        return $user->can('service_request_stages.delete');
    }
}
