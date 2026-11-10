<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\User;

class ApiClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('api_clients.viewAny');
    }

    public function view(User $user, ApiClient $apiClient): bool
    {
        return $user->can('api_clients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('api_clients.create');
    }

    /**
     * Also covers revoking and rotating a client's key.
     */
    public function update(User $user, ApiClient $apiClient): bool
    {
        return $user->can('api_clients.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('api_clients.delete');
    }

    public function delete(User $user, ApiClient $apiClient): bool
    {
        return $user->can('api_clients.delete');
    }
}
